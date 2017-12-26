<?php
namespace legtrack;
use \PDO;
use \DateTime;

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
  protected $selectUpdatedMeasuresSql;
  protected $selectUpdatedHearingsSql;
  protected $insertHearingSql;

  const DROP_HEARINGS_TABLE_SQL = "DROP TABLE IF EXISTS hearings;";

  const CREATE_HEARINGS_TABLE_SQL = <<<HERE
    CREATE TABLE IF NOT EXISTS hearings
    (
      year smallint NOT NULL,
      measureType nchar(4) NOT NULL,
      measureNumber smallint NOT NULL,
      measureRelativeUrl nvarchar(512),
      code nvarchar(64),
      committee nvarchar(256),
      lastUpdated int,
      timestamp int,
      datetime nvarchar(32),
      description nvarchar(512),
      room nvarchar(32),
      notice nvarchar(256),
      noticeUrl nvarchar(512),
      noticePdfUrl nvarchar(512)
    );
HERE;

  const CREATE_HEARINGS_INDEX_SQL = <<<HERE
    CREATE INDEX hearings_lastupdated_idx ON hearings(lastUpdated);
HERE;

  const DROP_HEARINGS_INDEX_SQL = <<<HERE
    DROP INDEX hearings_lastupdated_idx ON hearings;
HERE;

  const DROP_MEASURES_TABLE_SQL = "DROP TABLE IF EXISTS measures;";

  const CREATE_MEASURES_TABLE_SQL = <<<HERE
    CREATE TABLE IF NOT EXISTS measures
    (
      year smallint NOT NULL,
      measureType nchar(3) NOT NULL,
      measureNumber smallint NOT NULL,
      lastUpdated int,
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

  const DROP_MEASURES_INDEX_SQL = <<<HERE
    DROP INDEX measures_lastupdated_idx ON measures;
HERE;

  const SELECT_MEASURE_SQL = <<<HERE
     SELECT * FROM measures
      WHERE year = :year
        AND measureType = :measureType
        AND measureNumber = :measureNumber;
HERE;

  const SELECT_UPDATED_MEASURES_SQL = <<<HERE
     SELECT * FROM measures
      WHERE lastUpdated >= :lastUpdated;
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

  const SELECT_UPDATED_HEARINGS_SQL = <<<HERE
     SELECT * FROM hearings
      WHERE lastUpdated >= :lastUpdated;
HERE;

  const INSERT_HEARING_SQL = <<<HERE
     INSERT INTO hearings (
        year, measureType, measureNumber, measureRelativeUrl, code,
        committee, lastUpdated, timestamp, datetime, description,
        room, notice, noticeUrl, noticePdfUrl)
     VALUES (
        :year, :measureType, :measureNumber, :measureRelativeUrl, :code,
        :committee, :lastUpdated, :timestamp, :datetime, :description,
        :room, :notice, :noticeUrl, :noticePdfUrl)
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
  public function getDsn() {
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
        ':lastUpdated' => (new DateTime())->getTimestamp(),
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

  protected function createInsertHearingArgs($r) {
    return array(
        ':year' => $r->year,
        ':measureType' => $r->measureType,
        ':measureNumber' => $r->measureNumber,
        ':measureRelativeUrl' => $r->measureRelativeUrl,
        ':code' => $r->code,
        ':committee' => $r->committee,
        ':lastUpdated' => (new DateTime())->getTimestamp(),
        ':timestamp' => $r->timestamp,
        ':datetime' => $r->datetime,
        ':description' => $r->description,
        ':room' => $r->room,
        ':description' => $r->description,
        ':notice' => $r->notice,
        ':noticeUrl' => $r->noticeUrl,
        ':noticePdfUrl' => $r->noticePdfUrl,
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
    $r = $this->connectManually($this->getDsn(), $this->user, $this->pass);
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

  public function connectManually($dsn, $user, $pass) {
    try {
      $opts = $this->getConnectionOptions();
      $this->error = NULL;
      $this->conn = new PDO($dsn, $user, $pass, $opts);
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

  public function setupStatements() {
    if (!$this->ready) {
      $this->upsertMeasureSql = $this->prepare(static::UPSERT_MEASURE_SQL);
      if (!$this->upsertMeasureSql) { die('UPSERT Measure SQL Preparation Failed' . PHP_EOL); }
      $this->insertMeasureSql = $this->prepare(static::INSERT_MEASURE_SQL);
      if (!$this->insertMeasureSql) { die('INSERT Measure SQL Preparation Failed' . PHP_EOL); }
      $this->updateMeasureSql = $this->prepare(static::UPDATE_MEASURE_SQL);
      if (!$this->updateMeasureSql) { die('UPDATE Measure SQL Preparation Failed' . PHP_EOL); }
      $this->selectMeasureSql = $this->prepare(static::SELECT_MEASURE_SQL);
      if (!$this->selectMeasureSql) { die('SELECT Measure SQL Preparation Failed' . PHP_EOL); }
      $this->selectUpdatedMeasuresSql = $this->prepare(static::SELECT_UPDATED_MEASURES_SQL);
      if (!$this->selectUpdatedMeasuresSql) { die('SELECT Updated SQL Preparation Failed' . PHP_EOL); }
      $this->selectUpdatedHearingsSql = $this->prepare(static::SELECT_UPDATED_HEARINGS_SQL);
      if (!$this->selectUpdatedHearingsSql) { die('SELECT Updated SQL Preparation Failed' . PHP_EOL); }
      $this->insertHearingSql = $this->prepare(static::INSERT_HEARING_SQL);
      if (!$this->insertHearingSql) { die('INSERT Hearing SQL Preparation Failed' . PHP_EOL); }
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
    $this->setupStatements();
    if ($this->query('SELECT * FROM measures;')) {
      return $this->sql->fetchAll(PDO::FETCH_OBJ);
    }
    return NULL;
  }

  public function selectUpdatedMeasures($time) {
    $this->setupStatements();
    if (!$this->selectUpdatedMeasuresSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(
        ':lastUpdated' => $time,
    );
    if ($this->exec($this->selectUpdatedMeasuresSql, $args)) {
      return $this->selectUpdatedMeasuresSql->fetchAll(PDO::FETCH_OBJ);
    }
    $this->error = $this->selectUpdatedMeasuresSql->errorInfo();
    Logger::logger()->error('SELECT UPDATED: ', $this->error);
    return NULL;
  }

  public function selectMeasure($year, $type, $r) {
    $this->setupStatements();
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
    Logger::logger()->error('SELECT MEASURE: ', $this->error);
    return NULL;
  }

  public function upsertMeasureIfOnlyUpdated($year, $type, $r) {
    $this->setupStatements();
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

  public function upsertMeasure($r) {
    $this->setupStatements();
    if (!$this->upsertMeasureSql) die('No SQL Prepared' . PHP_EOL);
    $args = $this->createUpsertArgs($r->year, $r->measureType, $r);
    if ($this->exec($this->upsertMeasureSql, $args)) {
      $this->rowAffected += $this->upsertMeasureSql->rowCount();
      return TRUE;
    }
    $this->error = $this->upsertMeasureSql->errorInfo();
    Logger::logger()->error('UPSERT MEASURE: ', $this->error);
    return NULL;
  }

  public function updateMeasure($year, $type, $r) {
    $this->setupStatements();
    if (!$this->updateMeasureSql) die('No SQL Prepared' . PHP_EOL);
    $args = $this->createUpdateArgs($year, $type, $r);
    if ($this->exec($this->updateMeasureSql, $args)) {
      $this->rowAffected += $this->updateMeasureSql->rowCount();
      return TRUE;
    }
    $this->error = $this->updateMeasureSql->errorInfo();
    Logger::logger()->error('UPDATE MEASURE: ', $this->error);
    return NULL;
  }

  public function insertMeasure($year, $type, $r) {
    $this->setupStatements();
    if (!$this->insertMeasureSql) die('No SQL Prepared' . PHP_EOL);
    $args = $this->createInsertArgs($year, $type, $r);
    if ($this->exec($this->insertMeasureSql, $args)) {
      $this->rowAffected += $this->insertMeasureSql->rowCount();
      return TRUE;
    }
    $this->error = $this->insertMeasureSql->errorInfo();
    Logger::logger()->error('INSERT MEASURE: ', $this->error);
    return NULL;
  }

  public function selectUpdatedHearings($time) {
    $this->setupStatements();
    if (!$this->selectUpdatedHearingsSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(
        ':lastUpdated' => $time,
    );
    if ($this->exec($this->selectUpdatedHearingsSql, $args)) {
      return $this->selectUpdatedHearingsSql->fetchAll(PDO::FETCH_OBJ);
    }
    $this->error = $this->selectUpdatedHearingsSql->errorInfo();
    Logger::logger()->error('SELECT UPDATED: ', $this->error);
    return NULL;
  }

  public function insertHearing($r) {
    $this->setupStatements();
    if (!$this->insertHearingSql) die('No SQL Prepared' . PHP_EOL);
    $args = $this->createInsertHearingArgs($r);
    if ($this->exec($this->insertHearingSql, $args)) {
      $this->rowAffected += $this->insertHearingSql->rowCount();
      return TRUE;
    }
    $this->error = $this->insertHearingSql->errorInfo();
    Logger::logger()->error('INSERT HEARING: ', $this->error);
    return NULL;
  }

  public function getRowAffected() {
    $cnt = $this->rowAffected;
    $this->rowAffected = 0;
    return $cnt;
  }

  public function getLastInsertId() {
    if (!$this->conn) die('No Connection Established' . PHP_EOL);
    return $this->conn->lastInsertId();
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
      Logger::logger()->error('PREPARE STATEMENT: '. $e->getMessage, $e);
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
      Logger::logger()->error('EXEC STATEMENT: '. $e->getMessage, $e);
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
      Logger::logger()->error('QUERY: '. $e->getMessage, $e);
      print($e->getMessage() . PHP_EOL);
      print_r($e);
      $this->error = $e;
    }
    return false;
  }
}

?>
