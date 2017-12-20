<?php
namespace legtrack;

use \DateTime;

require_once 'lib/functions.php';
require_once 'lib/enum.php';
require_once 'lib/local_measure.php';
require_once 'lib/remote_sqlsvr.php';
require_once 'lib/logger.php';

function usage($argv) {
  echo "\nUASGE: php create-remote-sqlsvr.php <env>\n\n";
  echo "  env: development|test|production\n";
}

function connectLocalDb() {
  $db = new LocalMeasure();
  $db->configure($GLOBALS);
  $db->connect() || die('Local DB Connection Failed' . PHP_EOL);
  return $db;
}

function closeLocalDb($db) {
  $db->close();
}

function connectSqlsvr() {
  $db = new RemoteSqlsvr();
  $db->configure($GLOBALS);
  $db->connect() || die('Sqlsvr Conncection Failed'. PHP_EOL);
  return $db;
}

if ($argc < 1 || $argc > 2) {
  usage($argv);
  exit();
}

$env = ($argc == 2) ? $argv[1]: 'development';

$measureTypes = Enum::getMeasureTypes();
$jobStatus = Enum::getJobStatus();

loadEnv($env);

$programStart = new DateTime();

Logger::open($GLOBALS);
Logger::logger()->setLogLevel(Logger::INFO);
Logger::logger()->info('UPLOAD-TO-SQLSVR STARTED ENV: ' . $env);

$local = connectLocalDb();

$lastProcessedScraperJobId = 0;
$lastUpload = $local->selectLatestUploaderSqlsvrJob();
if ($lastUpload) {
  $lastProcessedScraperJobId = $lastUpload->scraperJobId;
}

$unprocessedScraperJob = $local->selectScraperJobUpdatedAfter($lastProcessedScraperJobId);

$scraperJobId = 0;
$scraperStartedAt = 0;

if ($unprocessedScraperJob) {
  $scraperJobId = $unprocessedScraperJob->id;
  $scraperStartedAt = $unprocessedScraperJob->startedAt;
  Logger::logger()->info('Found Unprocessed Scraper Job: ' . $scraperJobId);
} else {
  Logger::logger()->info('No Unprocessed Scraper Job');
}

$status = $jobStatus->skipped;
$total = 0;
$updated = 0;

$local->insertUploaderSqlsvrJob($scraperJobId);
$jobId = $local->getLastInsertId();

if ($scraperStartedAt > 0) {
  $data = $local->selectUpdated($scraperStartedAt);
  $total = sizeof($data);

  if ($total > 0) {
    $remote = connectSqlsvr();
    foreach($data as $r) {
      $remote->upsertMeasure($r);
    }
    $updated = $remote->getRowAffected();
    $remote->close();
    $status = $jobStatus->completed;
  } else {
    Logger::logger()->info('No Unprocessed Data');
  }
}

$local->updateUploaderSqlSvrJob($jobId, $status, $total, $updated);
closeLocalDb($local);

Logger::logger()->info($updated . '/' . $total . ' Rows Updated');
Logger::logger()->info('UPLOAD-TO-SQLSVR COMPLETED! ' . elapsedTime($programStart));
Logger::close();

?>
