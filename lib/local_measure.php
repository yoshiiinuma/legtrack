<?php
namespace legtrack;
use \PDO;

require_once 'lib/db_base.php';

class LocalMeasure extends DbBase {
  private $path;

  //STATUS 1) STARTED 2) COMPLETED 3) FAILED
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

  const CREATE_SCRAPER_LOGS_TABLE_SQL = <<<HERE
    CREATE TABLE IF NOT EXISTS scraperLogs
    (
      scraperJobId int unsigned,
      measureType tinyint NOT NULL,
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

  //STATUS 1) STARTED 2) COMPLETED 3) FAILED
  const CREATE_UPLOADER_JOBS_TABLE_SQL = <<<HERE
    CREATE TABLE IF NOT EXISTS uploaderJobs
    (
      scraperJobId int unsigned NOT NULL,
      status tinyint NOT NULL,
      updatedAfter datetime NOT NULL,
      startedAt datetime NOT NULL,
      completedAt datetime,
      updatedNumber smallint unsigned NOT NULL
    );
HERE;

  const DROP_UPLOADER_JOBS_TABLE_SQL = "DROP TABLE IF EXISTS uploaderJobs;";

  const CREATE_UPLOADER_JOBS_INDEX_SQL = <<<HERE
    CREATE INDEX uploaderJobsIdx ON uploaderJobs(status, startedAt);
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
