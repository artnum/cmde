<?php namespace CMDE;

use Exception;
use PDO;

/* handle nested transaction, allows to call any backend, which would beginTransaction and commit without worry */
class NestedTransactionPDO extends PDO {
  protected $transactionCount;
  function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null) {
    parent::__construct($dsn, $username, $password, $options);
    $this->transactionCount = 0;
  }

  function beginTransaction(): bool {
    if ($this->transactionCount === 0) {
      $this->transactionCount++;
      return parent::beginTransaction();
    }
    $this->transactionCount++;
    return parent::inTransaction();
  }

  function commit(): bool {
    if (!parent::inTransaction())  { return parent::commit(); }
    $this->transactionCount--;
    if ($this->transactionCount === 0) {
      return parent::commit();
    }
    return true;
  }

  function rollBack(): bool {
    if (!parent::inTransaction()) { return parent::rollBack(); }
    $this->transactionCount--;
    if ($this->transactionCount === 0) {
      return parent::rollBack();
    }
    return true;
  }
}

function init_pdo($dsn, $user, $password) {
    try {
      $options = array(PDO::ATTR_PERSISTENT => true);
      /* odbc should not use persistent connection */
      if (substr(strtolower($dsn), 0, 4) === 'odbc') {
        $options = array(PDO::ATTR_PERSISTENT => false);
      }
      $pdo = new NestedTransactionPDO($dsn, $user, $password, $options);
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      /* driver specific setup */
      switch ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)) {
        case 'mysql':
          $pdo->exec('SET sql_mode=\'ANSI\'');
          break;
      }
    } catch (Exception $e) {
      throw new Exception(sprintf('Database error "%s"', $e->getMessage()));
    }
    return $pdo;
  }
  