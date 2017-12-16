<?php
namespace legtrack;
use \PDO;

//FIXME Should extend PDO?
class DbBase {
  protected $conn;
  protected $user;
  protected $error;
  protected $ready;
  protected $rowAffected;
  protected $insertMeasureSql;
  protected $updateMeasureSql;
  protected $upsertMeasureSql;
  protected $selectMeasureSql;
  protected $selectUpdatedSql;

  const DROP_MEASURES_TABLE_SQL = "DROP TABLE IF EXISTS measures;";

  const CREATE_MEASURES_TABLE_SQL = <<<HERE
    CREATE TABLE IF NOT EXISTS measures
    (
      year smallint NOT NULL,
      measureType nchar(3) NOT NULL,
      measureNumber smallint NOT NULL,
      lastUpdated datetime,
      code nvarchar(64),
      measurePdfUrl nvarchar(512),
      measureArchiveUrl nvarchar(512),
      measureTitle nvarchar(512),
      reportTitle nvarchar(512),
      bitAppropriation tinyint,
      description nvarchar(1024),
      status nvarchar(512),
      introducer nvarchar(512),
      currentReferral nvarchar(256),
      companion nvarchar(256),
      PRIMARY KEY (year, measureType, measureNumber)
    );
HERE;

  const CREATE_MEASURES_INDEX_SQL = <<<HERE
    CREATE INDEX measures_lastupdated_idx ON measures(lastUpdated);
HERE;

  const SELECT_MEASURE_SQL = <<<HERE
     SELECT * FROM measures
      WHERE year = :year
        AND measureType = :measureType
        AND measureNumber = :measureNumber;
HERE;

  const SELECT_UPDATED_SQL = <<<HERE
     SELECT * FROM measures
      WHERE lastUpdated > :lastUpdated;
HERE;

  const UPDATE_MEASURE_SQL = <<<HERE
     UPDATE measures
       SET lastUpdated = :lastUpdated,
           code = :code,
           measurePdfUrl = :measurePdfUrl,
           measureArchiveUrl = :measureArchiveUrl,
           measureTitle = :measureTitle,
           reportTitle = :reportTitle,
           bitAppropriation = :bitAppropriation,
           description = :description,
           status = :status,
           introducer = :introducer,
           currentReferral = :currentReferral,
           companion = :companion
       WHERE year = :year AND measureType = :measureType AND measureNumber = :measureNumber;
HERE;

  const INSERT_MEASURE_SQL = <<<HERE
     INSERT INTO measures (
        measureType, year, measureNumber, lastUpdated, code, measurePdfUrl,
        measureArchiveUrl, measureTitle, reportTitle, bitAppropriation,
        description, status, introducer, currentReferral, companion)
     VALUES (
        :measureType, :year, :measureNumber, :lastUpdated, :code, :measurePdfUrl,
        :measureArchiveUrl, :measureTitle, :reportTitle, :bitAppropriation,
        :description, :status, :introducer, :currentReferral, :companion)
HERE;

  const UPSERT_MEASURE_SQL = <<<HERE
     INSERT OR REPLACE INTO measures (
        measureType, year, measureNumber, lastUpdated, code, measurePdfUrl,
        measureArchiveUrl, measureTitle, reportTitle, bitAppropriation,
        description, status, introducer, currentReferral, companion)
     VALUES (
        :measureType, :year, :measureNumber, :lastUpdated, :code, :measurePdfUrl,
        :measureArchiveUrl, :measureTitle, :reportTitle, :bitAppropriation,
        :description, :status, :introducer, :currentReferral, :companion)
HERE;

  public function __construct() {
    $this->ready = FALSE;
    $this->rowAffected = 0;
  }

  //Override
  public function configure($conf) {
    die("Must Implement in subclass!");
  }

  //Override
  public function getDns() {
    die("Must Implement in subclass!");
  }

  //Override if necessary
  protected function getConnectionOptions() {
      return [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
      ];
  }

  protected function createSqlArgs($year, $type, $r) {
    return array(
        ':measureType' => $type,
        ':year' => $year,
        ':measureNumber' => $r->measureNumber,
        ':lastUpdated' => Date("Y-m-d H:i:s"),
        ':code' => $r->code  ,
        ':measurePdfUrl' => $r->measurePdfUrl,
        ':measureArchiveUrl' => $r->measureArchiveUrl,
        ':measureTitle' => $r->measureTitle,
        ':reportTitle' => $r->reportTitle,
        ':bitAppropriation' => $r->bitAppropriation,
        ':description' => $r->description,
        ':status' => $r->status,
        ':introducer' => $r->introducer,
        ':currentReferral' => $r->currentReferral,
        ':companion' => $r->companion,
    );
  }

  //Override if necessary
  protected function createUpsertArgs($year, $type, $r) {
    return $this->createSqlArgs($year, $type, $r);
  }

  //Override if necessary
  protected function createUpdateArgs($year, $type, $r) {
    return $this->createSqlArgs($year, $type, $r);
  }

  //Override if necessary
  protected function createInsertArgs($year, $type, $r) {
    return $this->createSqlArgs($year, $type, $r);
  }

  public function connect() {
    $r = $this->connectManually($this->getDns(), $this->user, $this->pass);
    return $r;
  }

  public function beginTransaction() {
    $this->conn->beginTransaction();
  }

  public function commit() {
    $this->conn->commit();
  }

  public function close() {
    $this->conn = NULL;
  }

  public function getError() {
    return $this->error;
  }

  public function connectManually($dns, $user, $pass) {
    try {
      $opts = $this->getConnectionOptions();
      $this->error = NULL;
      $this->conn = new PDO($dns, $user, $pass, $opts);
      if ($this->conn) {
        return TRUE;
      }
    } catch (PDOException $e) {
      print($e->getMessage() . PHP_EOL);
      print_r($e);
      $this->error = $e;
    }
    return false;
  }

  public function setup_statements() {
    if (!$this->ready) {
      $this->upsertMeasureSql = $this->prepare(static::UPSERT_MEASURE_SQL);
      if (!$this->upsertMeasureSql) { die('UPSERT Measure SQL Preparation Failed' . PHP_EOL); }
      $this->insertMeasureSql = $this->prepare(static::INSERT_MEASURE_SQL);
      if (!$this->insertMeasureSql) { die('INSERT Measure SQL Preparation Failed' . PHP_EOL); }
      $this->updateMeasureSql = $this->prepare(static::UPDATE_MEASURE_SQL);
      if (!$this->updateMeasureSql) { die('UPDATE Measure SQL Preparation Failed' . PHP_EOL); }
      $this->selectMeasureSql = $this->prepare(static::SELECT_MEASURE_SQL);
      if (!$this->selectMeasureSql) { die('SELECT Measure SQL Preparation Failed' . PHP_EOL); }
      $this->selectUpdatedSql = $this->prepare(static::SELECT_UPDATED_SQL);
      if (!$this->selectUpdatedSql) { die('SELECT Updated SQL Preparation Failed' . PHP_EOL); }
      $this->ready = TRUE;
    }
  }

  public function compare($a, $b) {
    if ($a->measureNumber != $b->measureNumber) return FALSE;
    if ($a->code != $b->code  ) return FALSE;
    if ($a->measurePdfUrl != $b->measurePdfUrl) return FALSE;
    if ($a->measureArchiveUrl != $b->measureArchiveUrl) return FALSE;
    if ($a->measureTitle != $b->measureTitle) return FALSE;
    if ($a->reportTitle != $b->reportTitle) return FALSE;
    if ($a->bitAppropriation != $b->bitAppropriation) return FALSE;
    if ($a->description != $b->description ) return FALSE;
    if ($a->status != $b->status) return FALSE;
    if ($a->introducer != $b->introducer) return FALSE;
    if ($a->currentReferral != $b->currentReferral ) return FALSE;
    if ($a->companion != $b->companion) return FALSE;
    return TRUE;
  }

  public function diff($a, $b) {
    $r = (object)array();
    $sp = "\n     ";
    if ($a->measureNumber != $b->measureNumber) {
      $r->measureNumber = $sp . $a->measureNumber . $sp . $b->measureNumber;
    }
    if ($a->code != $b->code) {
      $r->code = $sp . $a->code . $sp . $b->code;
    }
    if ($a->measurePdfUrl != $b->measurePdfUrl) {
      $r->measurePdfUrl = $sp . $a->measurePdfUrl . $sp . $b->measurePdfUrl;
    }
    if ($a->measureArchiveUrl != $b->measureArchiveUrl) {
      $r->measureArchiveUrl = $sp . $a->measureArchiveUrl . $sp . $b->measureArchiveUrl;
    }
    if ($a->measureTitle != $b->measureTitle) {
      $r->measureTitle = $sp . $a->measureTitle . $sp . $b->measureTitle;
    }
    if ($a->reportTitle != $b->reportTitle) {
      $r->reportTitle = $sp . $a->reportTitle . $sp . $b->reportTitle;
    }
    if ($a->bitAppropriation != $b->bitAppropriation) {
      $r->bitAppropriation = $sp . $a->bitAppropriation . $sp . $b->bitAppropriation;
    }
    if ($a->description != $b->description) {
      $r->description = $sp . $a->description . $sp . $b->description;
    }
    if ($a->status != $b->status) {
      $r->status = $sp . $a->status . $sp . $b->status;
    }
    if ($a->introducer != $b->introducer) {
      $r->introducer = $sp . $a->introducer . $sp . $b->introducer;
    }
    if ($a->currentReferral != $b->currentReferral ) {
      $r->currentReferral = $sp . $a->currentReferral . $sp . $b->currentReferral;
    }
    if ($a->companion != $b->companion) {
      $r->companion = $sp . $a->companion . $sp . $b->companion;
    }
    return $r;
  }

  public function selectMeasures($year, $type) {
    $this->setup_statements();
    if ($this->query('SELECT * FROM measures;')) {
      return $this->sql->fetchAll(PDO::FETCH_OBJ);
    }
    return NULL;
  }

  public function selectUpdated($time) {
    $this->setup_statements();
    if (!$this->selectUpdatedSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(
        ':lastUpdated' => $time,
    );
    if ($this->exec($this->selectUpdatedSql, $args)) {
      return $this->selectUpdatedSql->fetchAll(PDO::FETCH_OBJ);
    }
    $this->error = $this->selectUpdatedSql->errorInfo();
    return NULL;
  }

  public function selectMeasure($year, $type, $r) {
    $this->setup_statements();
    if (!$this->selectMeasureSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(
        ':measureType' => $type,
        ':year' => $year,
        ':measureNumber' => $r->measureNumber,
    );
    if ($this->exec($this->selectMeasureSql, $args)) {
      return $this->selectMeasureSql->fetchObject();
    }
    $this->error = $this->selectMeasureSql->errorInfo();
    return NULL;
  }

  public function insertOrIgnoreAndUpdateMeasure($year, $type, $r) {
    $this->setup_statements();
    if (!$this->insertMeasureSql) die('No SQL Prepared' . PHP_EOL);
    if (!$this->updateMeasureSql) die('No SQL Prepared' . PHP_EOL);
    $args = $this->createInsertArgs($year, $type, $r);
    if ($this->exec($this->insertMeasureSql, $args)) {
      $this->rowAffected += $this->insertMeasureSql->rowCount();
      if ($this->exec($this->updateMeasureSql, $args)) {
        $this->rowAffected += $this->updateMeasureSql->rowCount();
        return TRUE;
      } else {
        $this->error = $this->updateMeasureSql->errorInfo();
      }
    } else {
      $this->error = $this->insertMeasureSql->errorInfo();
    }
    return NULL;
  }

  public function upsertMeasureIfOnlyUpdated($year, $type, $r) {
    $this->setup_statements();
    $cur = $this->selectMeasure($year, $type, $r);
    if ($cur) {
      if (!$this->compare($cur, $r)) {
        return $this->updateMeasure($year, $type, $r);
      }
    } else {
      return $this->insertMeasure($year, $type, $r);
    }
    return FALSE;
  }

  public function upsertMeasure($year, $type, $r) {
    $this->setup_statements();
    if (!$this->upsertMeasureSql) die('No SQL Prepared' . PHP_EOL);
    $args = $this->createUpsertArgs($year, $type, $r);
    if ($this->exec($this->upsertMeasureSql, $args)) {
      $this->rowAffected += $this->upsertMeasureSql->rowCount();
      return TRUE;
    }
    $this->error = $this->upsertMeasureSql->errorInfo();
    return NULL;
  }

  public function updateMeasure($year, $type, $r) {
    $this->setup_statements();
    if (!$this->updateMeasureSql) die('No SQL Prepared' . PHP_EOL);
    $args = $this->createUpdateArgs($year, $type, $r);
    if ($this->exec($this->updateMeasureSql, $args)) {
      $this->rowAffected += $this->updateMeasureSql->rowCount();
      return TRUE;
    }
    $this->error = $this->updateMeasureSql->errorInfo();
    return NULL;
  }

  public function insertMeasure($year, $type, $r) {
    $this->setup_statements();
    if (!$this->insertMeasureSql) die('No SQL Prepared' . PHP_EOL);
    $args = $this->createInsertArgs($year, $type, $r);
    if ($this->exec($this->insertMeasureSql, $args)) {
      $this->rowAffected += $this->insertMeasureSql->rowCount();
      return TRUE;
    }
    $this->error = $this->insertMeasureSql->errorInfo();
    return NULL;
  }

  public function getRowAffected() {
    return $this->rowAffected;
  }

  public function getMeasureCount() {
    $sql = $this->prepare('SELECT count(*) FROM measures;');
    $args = array();
    if ($this->exec($sql, $args)) {
      $r = $sql->fetch(PDO::FETCH_ASSOC);
      return $r['count(*)'];
    }
    $this->error = $this->updateMeasureSql->errorInfo();
    return NULL;
  }

  public function prepare($stmt) {
    if (!$this->conn) die('No Connection Established' . PHP_EOL);
    $this->error = NULL;

    try {
      $sql = $this->conn->prepare($stmt);
      return $sql; 
    } catch (PDOException $e) {
      print($e->getMessage() . PHP_EOL);
      print_r($e);
      $this->error = $e;
    }
    return NULL;
  }

  public function exec($sql, $args) {
    if (!$this->conn) die('No Connection Established' . PHP_EOL);
    if (!$sql) die('No SQL Prepared' . PHP_EOL);
    $this->error = NULL;

    try {
      $sql->execute($args);
      return true;
    } catch (PDOException $e) {
      print($e->getMessage() . PHP_EOL);
      print_r($e);
      $this->error = $e;
    }
    return false;
  }

  public function query($stmt) {
    if (!$this->conn) die('No Connection Established' . PHP_EOL);
    $this->error = NULL;

    try {
      $sql = $this->conn->prepare($stmt);
      $sql->execute();
      return true;
    } catch (PDOException $e) {
      print($e->getMessage() . PHP_EOL);
      print_r($e);
      $this->error = $e;
    }
    return false;
  }
}

?>
