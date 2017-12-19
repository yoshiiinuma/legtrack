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

  protected $insertUploaderSqlsvrJobSql;
  protected $updateUploaderSqlsvrJobSql;
  protected $selectUploaderSqlsvrJobSql;

  //STATUS 1) STARTED 2) FAILED 3) COMPLETED
  const CREATE_SCRAPER_JOBS_TABLE_SQL = <<<HERE
    CREATE TABLE IF NOT EXISTS scraperJobs
    (
      id integer PRIMARY KEY AUTOINCREMENT,
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
    CREATE INDEX scraperJobsIdx ON scraperJobs(status, startedAt);
HERE;

  const SELECT_SCRAPER_JOB_UPDATED_AFTER_SQL = <<<HERE
     SELECT id, startedAt
       FROM scraperJobs
      WHERE status = 3
        AND id > :id
      ORDER BY startedAt ASC
      LIMIT 1
HERE;

  const INSERT_SCRAPER_JOB_SQL = <<<HERE
     INSERT INTO scraperJobs (
        status, startedAt, totalNumber, updatedNumber, updateNeeded)
     VALUES (
        1, :startedAt, 0, 0, 0)
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
      scraperJobId int unsigned NOT NULL,
      status tinyint NOT NULL,
      updatedAfter int NOT NULL,
      startedAt int NOT NULL,
      completedAt int,
      updatedNumber smallint unsigned NOT NULL
    );
HERE;

  //STATUS 1) STARTED 2) SKIPPED 3) FAILED 4) COMPLETED
  const CREATE_UPLOADER_SQLSVR_JOBS_TABLE_SQL = <<<HERE
    CREATE TABLE IF NOT EXISTS uploaderSqlsvrJobs
    (
      id integer PRIMARY KEY,
      scraperJobId int unsigned NOT NULL,
      status tinyint NOT NULL,
      updatedAfter int NOT NULL,
      startedAt int NOT NULL,
      completedAt int,
      updatedNumber smallint unsigned NOT NULL
    );
HERE;

  const DROP_UPLOADER_MYSQL_JOBS_TABLE_SQL = "DROP TABLE IF EXISTS uploaderMysqlJobs;";

  const DROP_UPLOADER_SQLSVR_JOBS_TABLE_SQL = "DROP TABLE IF EXISTS uploaderSqlsvrJobs;";

  const CREATE_UPLOADER_MYSQL_JOBS_INDEX_SQL = <<<HERE
    CREATE INDEX uploaderMysqlJobsIdx ON uploaderMysqlJobs(status, startedAt);
HERE;

  const CREATE_UPLOADER_SQLSVR_JOBS_INDEX_SQL = <<<HERE
    CREATE INDEX uploaderSqlsvrJobsIdx ON uploaderSqlsvrJobs(status, startedAt);
HERE;

  const INSERT_UPLOADER_MYSQL_JOB_SQL = <<<HERE
     INSERT INTO uploaderMysqlJobs (
        scraperJobId, status, updatedAfter, startedAt, updatedNumber)
     VALUES (
        :scraperJobId, 1, :updatedAfter, :startedAt, 0)
HERE;

  const INSERT_UPLOADER_SQLSVR_JOB_SQL = <<<HERE
     INSERT INTO uploaderSqlsvrJobs (
        scraperJobId, status, updatedAfter, startedAt, updatedNumber)
     VALUES (
        :scraperJobId, 1, :updatedAfter, :startedAt, 0)
HERE;

  const UPDATE_UPLOADER_MYSQL_JOB_SQL = <<<HERE
     UPDATE uploaderMysqlJobs
        SET status = :status,
            completedAt = :completedAt,
            updatedNumber = :updatedNumber
      WHERE id = :id
HERE;

  const UPDATE_UPLOADER_SQLSVR_JOB_SQL = <<<HERE
     UPDATE uploaderSqlsvrJobs
        SET status = :status,
            completedAt = :completedAt,
            updatedNumber = :updatedNumber
      WHERE id = :id
HERE;

  const SELECT_LATEST_MYSQL_UPLOAD_SQL = <<<HERE
     SELECT scraperJobId, updatedAfter
       FROM uploaderMysqlJobs
      WHERE status = 4
      ORDER BY startedAt DESC
      LIMIT 1
HERE;

  const SELECT_LATEST_SQLSVR_UPLOAD_SQL = <<<HERE
     SELECT scraperJobId, updatedAfter
       FROM uploaderSqlsvrJobs
      WHERE status = 4
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

  public function getDns() {
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

      $this->insertUploaderSqlsvrJobSql = $this->prepare(static::INSERT_UPLOADER_SQLSVR_JOB_SQL);
      if (!$this->insertUploaderSqlsvrJobSql) { die('INSERT UploaderSqlsvrJob SQL Preparation Failed' . PHP_EOL); }
      $this->updateUploaderSqlsvrJobSql = $this->prepare(static::UPDATE_UPLOADER_SQLSVR_JOB_SQL);
      if (!$this->updateUploaderSqlsvrJobSql) { die('UPDATE UploaderSqlsvrJob SQL Preparation Failed' . PHP_EOL); }
      $this->selectUploaderSqlsvrJobSql = $this->prepare(static::SELECT_LATEST_SQLSVR_UPLOAD_SQL);
      if (!$this->selectUploaderSqlsvrJobSql) { die('SELECT UploaderSqlsvrJob SQL Preparation Failed' . PHP_EOL); }
    }
    parent::setupStatements();
  }

  public function insertScraperJob() {
    $this->setupStatements();
    if (!$this->insertScraperJobSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(
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

  public function selectScraperJobUpdatedAfter($scraperJobId) {
    $this->setupStatements();
    if (!$this->selectScraperJobSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(':id' => $scraperJobId);
    if ($this->exec($this->selectScraperJobSql, $args)) {
      return $this->selectMeasureSql->fetchObject();
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

  public function insertUploaderMysqlJob($scraperJobId, $updatedAfter) {
    $this->setupStatements();
    if (!$this->insertUploaderMysqlJobSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(
        ':scraperJobId' => $scraperJobId,
        ':updatedAfter' => $updatedAfter,
        ':startedAt' => (new DateTime())->getTimestamp(),
    );
    if ($this->exec($this->insertUploaderMysqlJobSql, $args)) {
      return TRUE;
    }
    return NULL;
  }

  public function updateUploaderMysqlJob($id, $status, $updatedNumber) {
    $this->setupStatements();
    if (!$this->updateUploaderMysqlJobSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(
        ':id' => $id,
        ':status' => $status,
        ':completedAt' => (new DateTime())->getTimestamp(),
        ':updatedNumber' => $updatedNumber,
    );
    if ($this->exec($this->updateUploaderMysqlJobSql, $args)) {
      return TRUE;
    }
    $this->error = $this->updateUploaderMysqlJobSql->errorInfo();
    return NULL;
  }

  public function selectLatestUploaderMysqlJob() {
    $this->setupStatements();
    if (!$this->selectUploaderMysqlJobSql) die('No SQL Prepared' . PHP_EOL);
    $args = array();
    if ($this->exec($this->selectUploaderMysqlJobSql, $args)) {
      return $this->selectUploaderMysqlJobSql->fetchObject();
    }
    $this->error = $this->selectUploaderMysqlJobSql->errorInfo();
    return NULL;
  }

  public function insertUploaderSqlsvrJob($scraperJobId, $updatedAfter) {
    $this->setupStatements();
    if (!$this->insertUploaderSqlsvrJobSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(
        ':scraperJobId' => $scraperJobId,
        ':updatedAfter' => $updatedAfter,
        ':startedAt' => (new DateTime())->getTimestamp(),
    );
    if ($this->exec($this->insertUploaderSqlsvrJobSql, $args)) {
      return TRUE;
    }
    return NULL;
  }

  public function updateUploaderSqlsvrJob($id, $status, $updatedNumber) {
    $this->setupStatements();
    if (!$this->updateUploaderSqlsvrJobSql) die('No SQL Prepared' . PHP_EOL);
    $args = array(
        ':id' => $id,
        ':status' => $status,
        ':completedAt' => (new DateTime())->getTimestamp(),
        ':updatedNumber' => $updatedNumber,
    );
    if ($this->exec($this->updateUploaderSqlsvrJobSql, $args)) {
      return TRUE;
    }
    $this->error = $this->updateUploaderSqlsvrJobSql->errorInfo();
    return NULL;
  }

  public function selectLatestUploaderSqlsvrJob() {
    $this->setupStatements();
    if (!$this->selectUploaderSqlsvrJobSql) die('No SQL Prepared' . PHP_EOL);
    $args = array();
    if ($this->exec($this->selectUploaderSqlsvrJobSql, $args)) {
      return $this->selectUploaderSqlsvrJobSql->fetchObject();
    }
    $this->error = $this->selectUploaderSqlsvrJobSql->errorInfo();
    return NULL;
  }

}

?>
