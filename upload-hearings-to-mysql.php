<?php
namespace legtrack;

use \DateTime;

require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/lib/enum.php';
require_once __DIR__ . '/lib/local_sqlite.php';
require_once __DIR__ . '/lib/remote_mysql.php';
require_once __DIR__ . '/lib/logger.php';

function usage($argv) {
  echo "\nUASGE: php upload-hearings-to-mysql.php <env>\n\n";
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

function connectMysql() {
  $db = new RemoteMysql();
  $db->configure($GLOBALS);
  $db->connect() || die('Mysql Conncection Failed'. PHP_EOL);
  return $db;
}

if ($argc < 1 || $argc > 2) {
  usage($argv);
  exit();
}

$env = ($argc == 2) ? $argv[1]: 'development';

$dataTypes = Enum::getDataTypes();
$jobStatus = Enum::getJobStatus();
$pg = 'UPLOAD-HEARING-TO-MYSQL ';

loadEnv($env);

$programStart = new DateTime();

Logger::open($GLOBALS);
Logger::logger()->setLogLevel(Logger::INFO);
Logger::logger()->info($pg . 'STARTED ENV: ' . $env);

$local = connectLocalDb();

$lastProcessedScraperJobId = 0;
$lastUpload = $local->selectLatestUploaderMysqlJob($dataTypes->hearings);
if ($lastUpload) {
  $lastProcessedScraperJobId = $lastUpload->scraperJobId;
}

$unprocessedScraperJob = $local->selectScraperJobUpdatedAfter($lastProcessedScraperJobId, $dataTypes->hearings);

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

$local->insertUploaderMysqlJob($scraperJobId, $dataTypes->hearings);
$jobId = $local->getLastInsertId();

if ($scraperStartedAt > 0) {
  $data = $local->selectUpdatedHearings($scraperStartedAt);
  $total = sizeof($data);

  if ($total > 0) {
    $remote = connectMysql();
    foreach($data as $r) {
      //$remote->insertHearing($r);
      $remote->upsertHearing($r);
    }
    $updated = $remote->getRowAffected();
    $remote->close();
    $status = $jobStatus->completed;
  } else {
    Logger::logger()->info($pg . 'No Unprocessed Data');
  }
}

$local->updateUploaderMysqlJob($jobId, $status, $total, $updated);
closeLocalDb($local);

Logger::logger()->info($pg . $updated . '/' . $total . ' Rows Updated');
Logger::logger()->info($pg . 'COMPLETED! ' . elapsedTime($programStart));
Logger::close();

?>
