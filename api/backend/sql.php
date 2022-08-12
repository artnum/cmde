<?php namespace CMDE;

use Exception;
use PDO;

interface Backend {
    function __construct(PDO $pdo, string $table);
    function get(int $uid);
    function delete (int $uid);
    function create ($data);
    function update (int $uid, $data);
    function query ($body);
}

class SQLBackend implements Backend {
    function __construct(PDO $pdo, string $table) {
        /* @var $this->pdo PDO */
        $this->pdo = $pdo;
        $this->table = $table;
    }

    /* remove table name from field name */
    function out_process($entry, $maintable = null) {
        $processed = [];
        foreach ($entry as $k => $v) {
            list ($table, $field) = explode('_', $k, 2);
            if ($maintable === null || $table === $maintable) {
                $processed[$field] = $v;
                continue;
            }

            if ($table !== $maintable) {
                if (!isset($processed['_relation'])) { $processed['_relation'] = []; }
                if (!isset($processed['_relation'][$table])) { $processed['_relation'][$table] = []; }
                $processed['_relation'][$table][$field] = $v;
            }

        }
        return $processed;
    }

    /* remove keys, ... */
    function in_process($entry) {
        return $entry;
    }

    function get (int $uid) {
        try {
            $stmt = $this->pdo->prepare(sprintf('SELECT * FROM %s WHERE %s_uid = :uid', $this->table, $this->table));
            $stmt->bindParam(':uid', $uid, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() !== 1) {
                throw new Exception('Get should return one result');
            }
            $entry = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$entry) {
                throw new Exception('Error getting data');
            }
            return $this->out_process($entry);
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    function delete (int $uid) {
        try {
            $stmt = $this->pdo->prepare(sprintf('DELETE FROM %s WHERE %s_uid = :uid', $this->table, $this->table));
            $stmt->bindParam(':uid', $uid, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() !== 1) {
                throw new Exception('Get should return one result');
            }
            return ['uid' => $uid];
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    function create ($data) {
        try {
            $this->pdo->beginTransaction();
            $keys = array_keys($data);
            $query = sprintf('INSERT INTO %s (%s) VALUES (%s)',
                $this->table,
                join(',', array_map(function ($i) { return sprintf('%s_%s', $this->table, $i); }, $keys)),
                join(',', array_map(function ($i) { return sprintf(':%s', $i); }, $keys))
            );

            $stmt = $this->pdo->prepare($query);
            foreach ($data as $k => $v) {      
                if (is_numeric($v) && !is_float($v)) {
                    $stmt->bindValue(':' . $k, $v, PDO::PARAM_INT);
                } else if (is_null($v)) {
                    $stmt->bindValue(':' . $k, null, PDO::PARAM_NULL);
                } else if (is_bool($v)) {
                    $stmt->bindValue(':' . $k, $v, PDO::PARAM_BOOL);
                } else {
                    $stmt->bindValue(':' . $k, $v, PDO::PARAM_STR);
                }
            }

            $stmt->execute();
            $uid = $this->pdo->lastInsertId();
            $this->pdo->commit();
            return ['uid' => $uid];
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw new Exception(sprintf('Database error : "%s"', $e->getMessage()));
        }
    }

    function update (int $uid, $data) {
        try {
            $this->pdo->beginTransaction();

            foreach ($data as $k => $v) {
                $stmt = $this->pdo->prepare(sprintf('UPDATE %s SET %s = :value WHERE %s_uid = :uid', $this->table, sprintf('%s_%s', $this->table, $k), $this->table));
                if (is_numeric($v) && !is_float($v)) {
                    $stmt->bindValue(':value', $v, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(':value', $v, PDO::PARAM_STR);
                }
                $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
                $stmt->execute();
            }
            
            $this->pdo->commit();
            return ['uid' => $uid];
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw new Exception(sprintf('Database error : "%s"', $e->getMessage()));
        }
    }

    function prepare_query($body, &$params, &$count, $up_relation = null) {
        function isUnary($op)
        {
            if (!is_string($op)) {
                return false;
            }
            switch ($op) {
                case '--':
                case '-':
                case '**':
                case '*':
                    return true;
                default:
                    return false;
            }
            return false;
        }

        if ($params === null) {
            $params = [];
        }
        if ($count === null) {
            $count = 0;
        }

        $predicats = [];
        $relation = ' AND ';
        foreach ($body as $key => $value) {
            if (substr($key, 0, 1) === '#') {
                $effectiveKey = explode(':', $key)[0];
                switch (strtolower($effectiveKey)) {
                    case '#or':
                        $relation = ' OR ';
                        break;
                    case '#and':
                        $relation = ' AND ';
                        break;
                }

                $predicats[] = '( ' . $this->prepare_query($value, $params, $count, $relation) . ' )';
            } else {
                if (!is_array($value)) {
                    if (isUnary($value)) {
                        $value = [$value];
                    } else {
                        $value = ['=', $value, gettype($value)];
                    }
                }
                if (!isUnary($value[0])) {
                    if (count($value) === 1) {
                        $value = ['=', $value[0], gettype($value[0])];
                    } else if (count($value) === 2) {
                        $value = [$value[0], $value[1], gettype($value[1])];
                    }
                }
                $type = 'str';
                if (isset($value[2])) {
                    switch (strtolower($value[2])) {
                        case 'int':
                        case 'integer':
                            $type = 'int';
                            break;
                        case 'boolean':
                        case 'bool':
                            $type = 'bool';
                            break;
                        case 'null':
                            $type = 'null';
                            break;
                        default:
                            $type = 'str';
                            break;
                    }
                }
                $novalue = false;
                $nullify = false;
                $operator = '';
                $predicat = '';

                $effectiveKey = explode(':', $key)[0];
                switch ($value[0]) {
                    case '<=':
                    case '>=':
                    case '>':
                    case '<':
                    case '=':
                        $operator = $value[0];
                        break;
                    case '~':
                        $operator = 'LIKE';
                        break;
                    case '!=':
                        $operator = '<>';
                        break;
                    case '--':
                        $nullify = true;
                        // fall through
                    case '-':
                        $operator = 'IS NULL';
                        $novalue = true;
                        break;
                    case '**':
                        $nullify = true;
                        // fall through
                    case '*':
                        $operator = 'IS NOT NULL';
                        $novalue = true;
                        break;
                }

                if ($type === 'str' && isset($value[1]) && strpos($value[1], '*') !== false) {
                    $operator = ' LIKE ';
                }

                $table = $this->table;
                $attr = $effectiveKey;
                if (strpos($effectiveKey, '_')) {
                    [$table, $attr] = explode('_', $key, 2);
                }
                if ($nullify) {
                    $predicat = 'NULLIF("' . $table . '"."' . $table . '_' . $attr . '", \'\') ' . $operator;
                } else {
                    $predicat = '"' . $table . '"."' . $table . '_' . $attr . '" ' . $operator;
                }
                if (!$novalue) {
                    $predicat .= ' :params' . $count;
                    $v = strval($value[1]);
                    switch ($type) {
                        case 'bool':
                            $v = boolval($value[1]);
                            break;
                        case 'int':
                            $v = intval($value[1]);
                            break;
                        case 'null':
                            $v = null;
                            break;
                        default:
                            $v = str_replace('*', '%', $v);
                            break;
                    }
                    $params[':params' . $count] = [$v, $type];
                    $count++;
                }
                $predicats[] = $predicat;
            }
        }
        if ($up_relation) {
            $relation = $up_relation;
        }
        return join($relation, $predicats);
    }

    function query($body = []) {
        $params = [];
        $count = 0;
        try {
            $query = sprintf('SELECT * FROM %s', $this->table); 
            $where = $this->prepare_query($body, $params, $count);
            if ($count > 0) {
                $query .= ' WHERE ' . $where;
            }
            $stmt = $this->pdo->prepare($query);
            foreach ($params as $key => $value) {
                switch($value[1]) {
                    default:
                    case 'str': $stmt->bindParam($key, $value[0], PDO::PARAM_STR); break;
                    case 'int': $stmt->bindParam($key, $value[0], PDO::PARAM_INT); break;
                    case 'bool': $stmt->bindParam($key, $value[0], PDO::PARAM_BOOL); break;
                    case 'null': $stmt->bindParam($key, $value[0], PDO::PARAM_NULL); break;
                }
            }
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                yield null;
            } else {
                while (($entry = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
                    yield $this->out_process($entry);
                }
            }
        } catch (Exception $e) {
            throw new Exception(sprintf('Error in query : "%s"', $e->getMessage()));
        }
    }
}