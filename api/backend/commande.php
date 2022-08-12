<?php namespace CMDE;

use PDO;
use Exception;
use DateTime;

class CommandeBackend extends SQLBackend implements Backend {
    function setProgress ($uid, $data) {
        $stmt = $this->pdo->prepare('SELECT * FROM ptype WHERE ptype_code = :type');
        $stmt->bindValue(':type', $data['code'], PDO::PARAM_STR);
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            throw new Exception('Missing progression type');
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare('INSERT INTO progression (progression_commande, progression_type, progression_timestamp) VALUES (:cmde, :type, :ts)');
        $stmt->bindValue(':cmde', $uid, PDO::PARAM_INT);
        $stmt->bindValue(':type', $result['ptype_uid'], PDO::PARAM_INT);
        $stmt->bindValue(':ts', (new DateTime())->getTimestamp(), PDO::PARAM_INT);
        $stmt->execute();

        return  $this->pdo->lastInsertId();
    }

    function opened() {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM commande LEFT JOIN progression ON progression_commande = commande_uid AND progression_timestamp = (SELECT MAX(progression_timestamp) FROM progression WHERE commande_uid =
progression_commande) LEFT JOIN ptype ON progression_type = ptype_uid WHERE ptype_code NOT IN (\'delete\', \'close\') ORDER BY progression_timestamp');
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                yield null;
            } else {
                while (($entry = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
                    yield $this->out_process($entry, 'commande');
                }
            }

        } catch (Exception $e) {
            throw new Exception(sprintf('Database error : "%s"', $e->getMessage()));
        }
    }

    function closed() {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM commande LEFT JOIN progression ON progression_commande = commande_uid AND progression_timestamp = (SELECT MAX(progression_timestamp) FROM progression WHERE commande_uid =
progression_commande) LEFT JOIN ptype ON progression_type = ptype_uid WHERE ptype_code = \'close\' ORDER BY progression_timestamp');
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                yield null;
            } else {
                while (($entry = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
                    yield $this->out_process($entry, 'commande');
                }
            }

        } catch (Exception $e) {
            throw new Exception(sprintf('Database error : "%s"', $e->getMessage()));
        }
    }

    function deleted() {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM commande LEFT JOIN progression ON progression_commande = commande_uid AND progression_timestamp = (SELECT MAX(progression_timestamp) FROM progression WHERE commande_uid =
progression_commande) LEFT JOIN ptype ON progression_type = ptype_uid WHERE ptype_code = \'delete\' ORDER BY progression_timestamp');
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                yield null;
            } else {
                while (($entry = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
                    yield $this->out_process($entry, 'commande');
                }
            }

        } catch (Exception $e) {
            throw new Exception(sprintf('Database error : "%s"', $e->getMessage()));
        }
    }

    function delete ($uid) {
        try {
            $this->pdo->beginTransaction();
            $this->setProgress($uid, ['code' => 'delete']);
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw new Exception(sprintf('Database error : "%s"', $e->getMessage()));
        }
    }

    function create ($data) {
        try {
            $this->pdo->beginTransaction();

            $result = parent::create($data);
            $uid = $this->pdo->lastInsertId();
            $this->setProgress($result['uid'], ['code' => 'create', 'message' => 'Création']);
            $this->pdo->commit();
            return ['uid' => $uid];
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw new Exception(sprintf('Database error : "%s"', $e->getMessage()));
        }
    }

    /* progress is an update that is recorded as a change of state for the order */
    function progress ($uid, $data) {
        try {
            $this->pdo->beginTransaction();
            
            $progressuid = $this->setProgress($uid, $data['progress']);
            
            $entry = parent::get($uid);
            foreach ($data as $k => $v) {
                /* might not be perfect to check what has changed but good enough */
                if (crc32(strval($v)) !== crc32(strval($entry[$k]))) {
                    $stmt = $this->pdo->prepare('INSERT INTO pattrchange (pattrchange_progression, pattrchange_attribute, pattrchange_value) VALUES (:progress, :change, :value)');
                    $stmt->bindValue(':progress', $progressuid, PDO::PARAM_INT);
                    $stmt->bindValue(':change', $k, PDO::PARAM_STR);
                    $stmt->bindValue(':value', mb_strcut(strval($v), 0, 160, 'UTF-8'), PDO::PARAM_STR);
                    $stmt->execute();
                }
            }

            $out = parent::update($uid, $data['commande']);

            $this->pdo->commit();
            return $out;
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw new Exception(sprintf('Database error : "%s"', $e->getMessage()));
        }
    }
}