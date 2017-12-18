<?php
namespace legtrack;
use \PDO;

require_once 'lib/db_base.php';

class LocalMeasure extends DbBase {
  private $path;

  //STATUS 1) STARTED 2) FAILED 3) COMPLETED
  const CREATE_SCRAPER_JOBS_TABLE_SQL = <<<HERE
    CREATE TABLE IF NOT EXISTS scraperJobs
    (
      id integer PRIMARY KEY AUTOINCREMENT,
      year smallint NOT NULL,
      status tinyint NOT NULL,
      startedAt datetime NOT NULL,
      completedAt datetime,
      totalNumber smallint unsigned NOT NULL,
      updatedNumber smallint unsigned NOT NULL,
      updateNeeded tinyint(1) NOT NULL
    );
HERE;

  const DROP_SCRAPER_JOBS_TABLE_SQL = "DROP TABLE IF EXISTS scraperJobs;";

  const CREATE_SCRAPER_JOBS_INDEX_SQL = <<<HERE
    CREATE INDEX scraperJobsIdx ON scraperJobs(status, startedAt);
HERE;

  const SELECT_UPDATED_AFTER_SQL = <<<HERE
     SELECT id, startedAt
      WHERE status = 3
        AND id > :id
       ORDER BY ASC startedAt
       LIMIT 1
HERE;

  const INSERT_SCRAPER_JOB_SQL = <<<HERE
     INSERT INTO scraperJobs (
        year, status, startedAt, totalNumber, updatedNumber, updateNeeded)
     VALUES (
        :year, 1, :startedAt, 0, 0, 0)
HERE;

  const UPDATE_SCRAPER_JOB_SQL = <<<HERE
     UPDATE scraperJobs
       SET status = :status,
           completededAt = :completededAt,
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
      startedAt datetime NOT NULL,
      completedAt datetime,
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
       scrperJobId, measureType, status, startedAt, totalNumber, updatedNumber)
     VALUES (
       :scrperJobId, :measureType, :status, :startedAt, totalNumber, :updatedNumber)
HERE;

  //STATUS 1) STARTED 2) FAILED 3) COMPLETED
  const CREATE_UPLOADER_MYSQL_JOBS_TABLE_SQL = <<<HERE
    CREATE TABLE IF NOT EXISTS uploaderMysqlJobs
    (
      id integer PRIMARY KEY,
      scraperJobId int unsigned NOT NULL,
      status tinyint NOT NULL,
      updatedAfter datetime NOT NULL,
      startedAt datetime NOT NULL,
      completedAt datetime,
      updatedNumber smallint unsigned NOT NULL
    );
HERE;

  //STATUS 1) STARTED 2) FAILED 3) COMPLETED
  const CREATE_UPLOADER_SQLSVR_JOBS_TABLE_SQL = <<<HERE
    CREATE TABLE IF NOT EXISTS uploaderSqlsvrJobs
    (
      id integer PRIMARY KEY,
      scraperJobId int unsigned NOT NULL,
      status tinyint NOT NULL,
      updatedAfter datetime NOT NULL,
      startedAt datetime NOT NULL,
      completedAt datetime,
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
           completededAt = :completededAt,
           updatedNumber = :updatedNumber
       WHERE id = :id
HERE;

  const UPDATE_UPLOADER_SQLSVR_JOB_SQL = <<<HERE
     UPDATE uploaderSqlsvrJobs
       SET status = :status,
           completededAt = :completededAt,
           updatedNumber = :updatedNumber
       WHERE id = :id
HERE;

  const SELECT_LATEST_MYSQL_UPLOAD_SQL = <<<HERE
     SELECT scraperJobId, updatedAfter
       FROM uploaderMysqlJobs
       WHERE status = 3
       ORDER BY DESC startedAt
       LIMIT 1
HERE;

  const SELECT_LATEST_SQLSVR_UPLOAD_SQL = <<<HERE
     SELECT scraperJobId, updatedAfter
       FROM uploaderSqlsvrJobs
       WHERE status = 3
       ORDER BY DESC startedAt
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

}

?>
