<?php
namespace legtrack;
use \PDO;
use \DateTime;

require_once 'lib/db_base.php';

class LocalMeasure extends DbBase {
  private $path;

  protected $insertScraperJobSql;
  protected $updateScraperJobSql;
  protected $selectScraperJobSql;

  protected $insertScraperLogSql;

  protected $insertUploaderMysqlJobSql;
  protected $updateUploaderMysqlJobSql;
  protected $selectUploaderMysqlJobSql;

  protected $insertUploaderSqlsrvJobSql;
  protected $updateUploaderSqlsrvJobSql;
  protected $selectUploaderSqlsrvJobSql;

  //STATUS 1) STARTED 2) SKIPPED 3) FAILED 4) COMPLETED
  const CREATE_SCRAPER_JOBS_TABLE_SQL = <<<HERE
    CREATE TABLE IF NOT EXISTS scraperJobs
    (
      id integer PRIMARY KEY AUTOINCREMENT,
      dataType tinyint NOT NULL,
      status tinyint NOT NULL,
      startedAt int NOT NULL,
      completedAt int,
      totalNumber smallint unsigned NOT NULL,
      updatedNumber smallint unsigned NOT NULL,
      updateNeeded tinyint(1) NOT NULL
    );
HERE;

  const DROP_SCRAPER_JOBS_TABLE_SQL = "DROP TABLE IF EXISTS scraperJobs;";

  const CREATE_SCRAPER_JOBS_INDEX_SQL = <<<HERE
    CREATE INDEX scraperJobsIdx ON scraperJobs(dataType, status, startedAt);
HERE;

  const SELECT_SCRAPER_JOB_UPDATED_AFTER_SQL = <<<HERE
     SELECT id, startedAt
       FROM scraperJobs
      WHERE status = 4
        AND dataType = :dataType
        AND id > :id
        AND updatedNumber > 0
      ORDER BY startedAt ASC
      LIMIT 1
HERE;

  const INSERT_SCRAPER_JOB_SQL = <<<HERE
     INSERT INTO scraperJobs (
        dataType, status, startedAt, totalNumber, updatedNumber, updateNeeded)
     VALUES (
        :dataType, 1, :startedAt, 0, 0, 0)
HERE;

  const UPDATE_SCRAPER_JOB_SQL = <<<HERE
     UPDATE scraperJobs
        SET status = :status,
            completedAt = :completedAt,
            totalNumber = :totalNumber,
            updatedNumber = :updatedNumber,
            updateNeeded = :updateNeeded
      WHERE id = :id
HERE;

  const CREATE_SCRAPER_LOGS_TABLE_SQL = <<<HERE
    CREATE TABLE IF NOT EXISTS scraperLogs
    (
      scraperJobId int unsigned,
      measureType tinyint NOT NULL,
      status tinyint NOT NULL,
      startedAt int NOT NULL,
      completedAt int,
      totalNumber smallint unsigned NOT NULL,
      updatedNumber smallint unsigned NOT NULL
    );
HERE;

  const DROP_SCRAPER_LOGS_TABLE_SQL = "DROP TABLE IF EXISTS scraperLogs;";

  const CREATE_SCRAPER_LOGS_INDEX_SQL = <<<HERE
    CREATE INDEX scraperLogsIdx ON scraperLogs(scraperJobId, measureType, startedAt);
HERE;

  const INSERT_SCRAPER_LOG_SQL = <<<HERE
     INSERT INTO scraperLogs (
       scraperJobId, measureType, status, startedAt, completedAt, totalNumber, updatedNumber)
     VALUES (
       :scraperJobId, :measureType, :status, :startedAt, :completedAt, :totalNumber, :updatedNumber)
HERE;

  //STATUS 1) STARTED 2) SKIPPED 3) FAILED 4) COMPLETED
  const CREATE_UPLOADER_MYSQL_JOBS_TABLE_SQL = <<<HERE
    CREATE TABLE IF NOT EXISTS uploaderMysqlJobs
    (
      id integer PRIMARY KEY,
      dataType tinyint NOT NULL,
      scraperJobId int unsigned NOT NULL,
      status tinyint NOT NULL,
      startedAt int NOT NULL,
      completedAt int,
      totalNumber smallint unsigned NOT NULL,
      updatedNumber smallint unsigned NOT NULL
    );
HERE;

  //STATUS 1) STARTED 2) SKIPPED 3) FAILED 4) COMPLETED
  const CREATE_UPLOADER_SQLSRV_JOBS_TABLE_SQL = <<<HERE
    CREATE TABLE IF NOT EXISTS uploaderSqlsrvJobs
    (
      id integer PRIMARY KEY,
      dataType tinyint NOT NULL,
      scraperJobId int unsigned NOT NULL,
      status tinyint NOT NULL,
      startedAt int NOT NULL,
      completedAt int,
      totalNumber smallint unsigned NOT NULL,
      updatedNumber smallint unsigned NOT NULL
    );
HERE;

  const DROP_HEARINGS_INDEX_SQL = <<<HERE
    DROP INDEX hearings_lastupdated_idx;
HERE;

  const DROP_MEASURES_INDEX_SQL = <<<HERE
    DROP INDEX measures_lastupdated_idx;
HERE;

  const DROP_UPLOADER_MYSQL_JOBS_TABLE_SQL = "DROP TABLE IF EXISTS uploaderMysqlJobs;";

  const DROP_UPLOADER_SQLSRV_JOBS_TABLE_SQL = "DROP TABLE IF EXISTS uploaderSqlsrvJobs;";

  const CREATE_UPLOADER_MYSQL_JOBS_INDEX_SQL = <<<HERE
    CREATE INDEX uploaderMysqlJobsIdx ON uploaderMysqlJobs(dataType, status, startedAt);
HERE;

  const CREATE_UPLOADER_SQLSRV_JOBS_INDEX_SQL = <<<HERE
    CREATE INDEX uploaderSqlsrvJobsIdx ON uploaderSqlsrvJobs(dataType, status, startedAt);
HERE;

  const INSERT_UPLOADER_MYSQL_JOB_SQL = <<<HERE
     INSERT INTO uploaderMysqlJobs (
        dataType, scraperJobId, status, startedAt, totalNumber, updatedNumber)
     VALUES (
        :dataType, :scraperJobId, 1, :startedAt, 0, 0)
HERE;

  const INSERT_UPLOADER_SQLSRV_JOB_SQL = <<<HERE
     INSERT INTO uploaderSqlsrvJobs (
        dataType, scraperJobId, status, startedAt, totalNumber, updatedNumber)
     VALUES (
        :dataType, :scraperJobId, 1, :startedAt, 0, 0)
HERE;

  const UPDATE_UPLOADER_MYSQL_JOB_SQL = <<<HERE
     UPDATE uploaderMysqlJobs
        SET status = :status,
            completedAt = :completedAt,
            totalNumber = :totalNumber,
            updatedNumber = :updatedNumber
      WHERE id = :id
HERE;

  const UPDATE_UPLOADER_SQLSRV_JOB_SQL = <<<HERE
     UPDATE uploaderSqlsrvJobs
        SET status = :status,
            completedAt = :completedAt,
            totalNumber = :totalNumber,
            updatedNumber = :updatedNumber
      WHERE id = :id
HERE;

  const SELECT_LATEST_MYSQL_UPLOAD_SQL = <<<HERE
     SELECT scraperJobId
       FROM uploaderMysqlJobs
      WHERE status = 4
        AND dataType = :dataType
      ORDER BY startedAt DESC
      LIMIT 1
HERE;

  const SELECT_LATEST_SQLSRV_UPLOAD_SQL = <<<HERE
     SELECT scraperJobId
       FROM uploaderSqlsrvJobs
      WHERE status = 4
        AND dataType = :dataType
      ORDER BY startedAt DESC
      LIMIT 1
HERE;

  const INSERT_MEASURE_SQL = <<<HERE
     INSERT OR IGNORE INTO measures (
        measureType, year, measureNumber, lastUpdated, code, measurePdfUrl,
        measureArchiveUrl, measureTitle, reportTitle, bitAppropriation,
        description, status, introducer, currentReferral, companion)
     VALUES (
        :measureType, :year, :measureNumber, :lastUpdated, :code, :measurePdfUrl,
        :measureArchiveUrl, :measureTitle, :reportTitle, :bitAppropriation,
        :description, :status, :introducer, :currentReferral, :companion)
HERE;

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

  public function getDsn() {
    return "sqlite:".$this->path;
  }

  public function setupStatements() {
    if (!$this->ready) {
      $this->insertScraperJobSql = $this->prepare(static::INSERT_SCRAPER_JOB_SQL);
      if (!$this->insertScraperJobSql) { die('INSERT ScraperJob SQL Preparation Failed' . PHP_EOL); }
      $this->updateScraperJobSql = $this->prepare(static::UPDATE_SCRAPER_JOB_SQL);
      if (!$this->updateScraperJobSql) { die('UPDATE ScraperJob SQL Preparation Failed' . PHP_EOL); }
      $this->selectScraperJobSql = $this->prepare(static::SELECT_SCRAPER_JOB_UPDATED_AFTER_SQL);
      if (!$this->selectScraperJobSql) { die('SELECT ScraperJob SQL Preparation Failed' . PHP_EOL); }

      $this->insertScraperLogSql = $this->prepare(static::INSERT_SCRAPER_LOG_SQL);
      if (!$this->insertScraperLogSql) { die('INSERT ScraperLog SQL Preparation Failed' . PHP_EOL); }

      $this->insertUploaderMysqlJobSql = $this->prepare(static::INSERT_UPLOADER_MYSQL_JOB_SQL);
      if (!$this->insertUploaderMysqlJobSql) { die('INSERT UploaderMysqlJob SQL Preparation Failed' . PHP_EOL); }
      $this->updateUploaderMysqlJobSql = $this->prepare(static::UPDATE_UPLOADER_MYSQL_JOB_SQL);
      if (!$this->updateUploaderMysqlJobSql) { die('UPDATE UploaderMysqlJob SQL Preparation Failed' . PHP_EOL); }
      $this->selectUploaderMysqlJobSql = $this->prepare(static::SELECT_LATEST_MYSQL_UPLOAD_SQL);
      if (!$this->selectUploaderMysqlJobSql) { die('SELECT UploaderMysqlJob SQL Preparation Failed' . PHP_EOL); }

      $this->insertUploaderSqlsrvJobSql = $this->prepare(static::INSERT_UPLOADER_SQLSRV_JOB_SQL);
      if (!$this->insertUploaderSqlsrvJobSql) { die('INSERT UploaderSqlsrvJob SQL Preparation Failed' . PHP_EOL); }
      $this->updateUploaderSqlsrvJobSql = $this->prepare(static::UPDATE_UPLOADER_SQLSRV_JOB_SQL);
      if (!$this->updateUploaderSqlsrvJobSql) { die('UPDATE UploaderSqlsrvJob SQL Preparation Failed' . PHP_EOL); }
      $this->selectUploaderSqlsrvJobSql = $this->prepare(static::SELECT_LATEST_SQLSRV_UPLOAD_SQL);
      if (!$this->selectUploaderSqlsrvJobSql) { die('SELECT UploaderSqlsrvJob SQL Preparation Failed' . PHP_EOL); }
    }
    parent::setupStatements();
  }

  public function insertScraperJob($dataType) {
    $this->setupStatements();
    if (!$this->insertScraperJobSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(
      ':dataType' => $dataType, 
      ':startedAt' => (new DateTime())->getTimestamp(),
    );
    if ($this->exec($this->insertScraperJobSql, $args)) {
      return TRUE;
    }
    return NULL;
  }

  public function updateScraperJob($id, $status, $totalNumber, $updatedNumber, $updateNeeded) {
    $this->setupStatements();
    if (!$this->updateScraperJobSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(
        ':id' => $id,
        ':status' => $status,
        ':completedAt' => (new DateTime())->getTimestamp(),
        ':totalNumber' => $totalNumber,
        ':updatedNumber' => $updatedNumber,
        ':updateNeeded' => $updateNeeded,
    );

    if ($this->exec($this->updateScraperJobSql, $args)) {
      return TRUE;
    }
    $this->error = $this->updateScraperJobSql->errorInfo();
    return NULL;
  }

  public function selectScraperJobUpdatedAfter($scraperJobId, $dataType) {
    $this->setupStatements();
    if (!$this->selectScraperJobSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(
      ':id' => $scraperJobId,
      ':dataType' => $dataType,
    );
    if ($this->exec($this->selectScraperJobSql, $args)) {
      return $this->selectScraperJobSql->fetchObject();
    }
    $this->error = $this->selectScraperJobSql->errorInfo();
    return NULL;
  }

  public function insertScraperLog($scraperJobId, $type, $status, $startedAt, $totalNumber, $updatedNumber) {
    $this->setupStatements();
    if (!$this->insertScraperLogSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(
        ':scraperJobId' => $scraperJobId,
        ':measureType' => $type,
        ':status' => $status,
        ':startedAt' => $startedAt,
        ':completedAt' => (new DateTime())->getTimestamp(),
        ':totalNumber' => $totalNumber,
        ':updatedNumber' => $updatedNumber,
    );
    if ($this->exec($this->insertScraperLogSql, $args)) {
      return TRUE;
    }
    return NULL;
  }

  public function insertUploaderMysqlJob($scraperJobId, $dataType) {
    $this->setupStatements();
    if (!$this->insertUploaderMysqlJobSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(
      ':dataType' => $dataType, 
      ':scraperJobId' => $scraperJobId,
      ':startedAt' => (new DateTime())->getTimestamp(),
    );
    if ($this->exec($this->insertUploaderMysqlJobSql, $args)) {
      return TRUE;
    }
    return NULL;
  }

  public function updateUploaderMysqlJob($id, $status, $totalNumber, $updatedNumber) {
    $this->setupStatements();
    if (!$this->updateUploaderMysqlJobSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(
        ':id' => $id,
        ':status' => $status,
        ':completedAt' => (new DateTime())->getTimestamp(),
        ':totalNumber' => $totalNumber,
        ':updatedNumber' => $updatedNumber,
    );
    if ($this->exec($this->updateUploaderMysqlJobSql, $args)) {
      return TRUE;
    }
    $this->error = $this->updateUploaderMysqlJobSql->errorInfo();
    return NULL;
  }

  public function selectLatestUploaderMysqlJob($dataType) {
    $this->setupStatements();
    if (!$this->selectUploaderMysqlJobSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(':dataType' => $dataType);
    if ($this->exec($this->selectUploaderMysqlJobSql, $args)) {
      return $this->selectUploaderMysqlJobSql->fetchObject();
    }
    $this->error = $this->selectUploaderMysqlJobSql->errorInfo();
    return NULL;
  }

  public function insertUploaderSqlsrvJob($scraperJobId, $dataType) {
    $this->setupStatements();
    if (!$this->insertUploaderSqlsrvJobSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(
      ':dataType' => $dataType, 
      ':scraperJobId' => $scraperJobId,
      ':startedAt' => (new DateTime())->getTimestamp(),
    );
    if ($this->exec($this->insertUploaderSqlsrvJobSql, $args)) {
      return TRUE;
    }
    return NULL;
  }

  public function updateUploaderSqlsrvJob($id, $status, $totalNumber, $updatedNumber) {
    $this->setupStatements();
    if (!$this->updateUploaderSqlsrvJobSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(
        ':id' => $id,
        ':status' => $status,
        ':completedAt' => (new DateTime())->getTimestamp(),
        ':totalNumber' => $totalNumber,
        ':updatedNumber' => $updatedNumber,
    );
    if ($this->exec($this->updateUploaderSqlsrvJobSql, $args)) {
      return TRUE;
    }
    $this->error = $this->updateUploaderSqlsrvJobSql->errorInfo();
    return NULL;
  }

  public function selectLatestUploaderSqlsrvJob($dataType) {
    $this->setupStatements();
    if (!$this->selectUploaderSqlsrvJobSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(':dataType' => $dataType);
    if ($this->exec($this->selectUploaderSqlsrvJobSql, $args)) {
      return $this->selectUploaderSqlsrvJobSql->fetchObject();
    }
    $this->error = $this->selectUploaderSqlsrvJobSql->errorInfo();
    return NULL;
  }

}

?>
