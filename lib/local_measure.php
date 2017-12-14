<?php
namespace legtrack;
use \PDO;

//FIXME Should extend PDO?
class LocalMeasure {
  private $conn;
  private $user;
  private $pass;
  private $path;
  private $dns;
  private $error;
  private $ready;
  private $insertMeasureSql;
  private $updateMeasureSql;
  private $selectMeasureSql;

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

  const SELECT_MEASURE_SQL = <<<HERE
     SELECT * FROM measures
      WHERE year = :year
        AND measureType = :measureType
        AND measureNumber = :measureNumber;
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

  public function __construct() {
    $this->ready = FALSE;
  }

  public function connect() {
    return $this->connectManually($this->getDns(), $this->user, $this->pass);
  }

  public function close() {
    $this->conn = NULL;
  }

  public function getError() {
    return $this->error;
  }

  public function connectManually($dns, $user, $pass) {
    try {
      $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
      ];
      $this->error = NULL;
      $this->conn = new PDO($dns, $user, $pass, $opts);
      return ($this->conn) ? TRUE : FALSE;
    } catch (PDOException $e) {
      print($e->getMessage() . PHP_EOL);
      print_r($e);
      $this->error = $e;
    }

    return false;
  }

  public function setPath($path) {
    $this->path = $path;
  }

  public function configure($conf) {
    $this->user = $conf['SQLITE_USER'];
    $this->pass = $conf['SQLITE_PASS'];
    $this->path = $conf['SQLITE_LOCATION'];

    if (!$this->user || !$this->pass || !$this->path) {
      print_r($conf);
      die('SQLite3: Invalid Configuration'.PHP_EOL);
    }
  }

  public function setup() {
    if (!$this->ready) {
      $this->insertMeasureSql = $this->prepare(LocalMeasure::INSERT_MEASURE_SQL);
      if (!$this->insertMeasureSql) { die('INSERT SQL Preparation Failed' . PHP_EOL); }
      $this->updateMeasureSql = $this->prepare(LocalMeasure::UPDATE_MEASURE_SQL);
      if (!$this->updateMeasureSql) { die('UPDATE SQL Preparation Failed' . PHP_EOL); }
      $this->selectMeasureSql = $this->prepare(LocalMeasure::SELECT_MEASURE_SQL);
      if (!$this->selectMeasureSql) { die('SELECT SQL Preparation Failed' . PHP_EOL); }
      $this->ready = TRUE;
    }
  }

  public function upsertMeasure($year, $type, $r) {
    $this->setup();
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
    $this->setup();
    if ($this->query('SELECT * FROM measures;')) {
      return $this->sql->fetchAll(PDO::FETCH_OBJ);
    }
    return NULL;
  }

  public function selectMeasure($year, $type, $r) {
    $this->setup();
    if (!$this->selectMeasureSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(
        ':measureType' => $type,
        ':year' => $year,
        ':measureNumber' => $r->measureNumber,
    );
    if ($this->exec($this->selectMeasureSql, $args)) {
      return $this->selectMeasureSql->fetchObject();
    }
    $this->error = $this->updateMeasureSql->errorInfo();
    return NULL;
  }

  public function updateMeasure($year, $type, $r) {
    $this->setup();
    if (!$this->updateMeasureSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(
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
    if ($this->exec($this->updateMeasureSql, $args)) {
      return TRUE;
    }
    $this->error = $this->updateMeasureSql->errorInfo();
    return NULL;
  }

  public function insertMeasure($year, $type, $r) {
    $this->setup();
    if (!$this->insertMeasureSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(
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
    if ($this->exec($this->insertMeasureSql, $args)) {
      return TRUE;
    }
    $this->error = $this->insertMeasureSql->errorInfo();
    return NULL;
  }

  public function getDns() {
    return "sqlite:".$this->path;
  }

  public function getMeasureCount() {
    $sql = $this->prepare('SELECT count(*) FROM measures;');
    $args = array();
    if ($this->exec($sql, $args)) {
      $r = $sql->fetch();
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
