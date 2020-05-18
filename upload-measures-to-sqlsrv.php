<?php
namespace legtrack;

use \DateTime;

require_once './lib/functions.php';
require_once './lib/enum.php';
require_once './lib/local_sqlite.php';
require_once './lib/remote_sqlsrv.php';
require_once './lib/logger.php';

function usage($argv) {
  echo "\nUASGE: php upload-measures-to-sqlsrv.php <env>\n\n";
  echo "  env: development|test|production\n";
}

function connectLocalDb() {
  $db = new LocalSqlite();
  $db->configure($GLOBALS);
  $db->connect() || die('Local DB Connection Failed' . PHP_EOL);
  return $db;
}

function closeLocalDb($db) {
  $db->close();
}

function connectSqlsrv() {
  $db = new RemoteSqlsrv();
  $db->configure($GLOBALS);
  $db->connect() || die('Sqlsrv Conncection Failed'. PHP_EOL);
  return $db;
}

if ($argc < 1 || $argc > 2) {
  usage($argv);
  exit();
}

$env = ($argc == 2) ? $argv[1]: 'development';

$dataTypes = Enum::getDataTypes();
$measureTypes = Enum::getMeasureTypes();
$jobStatus = Enum::getJobStatus();
$pg = 'UPLOAD-MEASURES-TO-SQLSRV ';

loadEnv($env);

$programStart = new DateTime();

Logger::open($GLOBALS);
Logger::logger()->setLogLevel(Logger::INFO);
Logger::logger()->info($pg . 'STARTED ENV: ' . $env);

$local = connectLocalDb();

$lastProcessedScraperJobId = 0;
$lastUpload = $local->selectLatestUploaderSqlsrvJob($dataTypes->measures);
if ($lastUpload) {
  $lastProcessedScraperJobId = $lastUpload->scraperJobId;
}

$unprocessedScraperJob = $local->selectScraperJobUpdatedAfter($lastProcessedScraperJobId, $dataTypes->measures);

$scraperJobId = 0;
$scraperStartedAt = 0;

if ($unprocessedScraperJob) {
  $scraperJobId = $unprocessedScraperJob->id;
  $scraperStartedAt = $unprocessedScraperJob->startedAt;
  Logger::logger()->info($pg . 'Found Unprocessed Scraper Job: ' . $scraperJobId);
} else {
  Logger::logger()->info($pg . 'No Unprocessed Scraper Job');
}

$status = $jobStatus->skipped;
$total = 0;
$updated = 0;

$local->insertUploaderSqlsrvJob($scraperJobId, $dataTypes->measures);
$jobId = $local->getLastInsertId();

if ($scraperStartedAt > 0) {
  $data = $local->selectUpdatedMeasures($scraperStartedAt);
  $total = sizeof($data);

  if ($total > 0) {
    $remote = connectSqlsrv();
    foreach($data as $r) {
      $remote->upsertMeasure($r);
    }
    $updated = $remote->getRowAffected();
    $remote->close();
    $status = $jobStatus->completed;
  } else {
    Logger::logger()->info($pg . 'No Unprocessed Data');
  }
}

$local->updateUploaderSqlsrvJob($jobId, $status, $total, $updated);
closeLocalDb($local);

Logger::logger()->info($pg . $updated . '/' . $total . ' Rows Updated');
Logger::logger()->info($pg . 'COMPLETED! ' . elapsedTime($programStart));
Logger::close();

?>
