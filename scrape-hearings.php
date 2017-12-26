<?php
/**
 *
 * Require PHP >= 7.0
 *
 * Scrape and Store upcoming hearings from:
 *
 *   Current Hearing Notices < Hearing Notices < Reports and Lists
 *
 *   URL:
 *
 *     https://www.capitol.hawaii.gov/upcominghearings.aspx
 *
 **/

namespace legtrack;

use \DateTime;

require_once 'lib/functions.php';
require_once 'lib/curl.php';
require_once 'lib/hearing_parser.php';
require_once 'lib/local_sqlite.php';
require_once 'lib/logger.php';

function usage($argv) {
  echo "Wrong parameters were given:\n\n";
  print_r($argv);
  echo "\n";
  echo "UASGE: php scrape-hearings.php [env] [debug]\n\n";
}

$dbg = FALSE;

if ($argc < 1 || $argc > 3) {
  usage($argv);
  exit();
}

if ($argc == 3) {
  $dbg = TRUE;
}

$env = ($argc > 1) ?  $argv[1] : 'development';

$pg = 'SCRAPE-HEARINGS ';

loadEnv($env);

Logger::open($GLOBALS);
Logger::logger()->setLogLevel(Logger::INFO);
Logger::logger()->info($pg . 'STARTED ENV: ' . $env);

function connectDb() {
  $db = new LocalSqlite();
  $db->configure($GLOBALS);
  $db->connect() || die('Local DB Connection Failed' . PHP_EOL);
  return $db;
}

function closeDb($db) {
  $db->close();
}

function checkUpcomingHearingsUpdate($dbg) {
  $start = new DateTime();

  $data = NULL;
  $status = 'MATCHED';

  $curl = new Curl();
  $curl->debug = $dbg;

  $dst = getCurrentHearingsPath();
  $curl->getCurrentHearings();

  $curMd5 = (file_exists($dst)) ? md5_file($dst) : 'xxx';
  $newMd5 = $curl->getMd5();

  if ($curMd5 != $newMd5) {
    $status = 'UPDATED';
    $curl->saveResult($dst);
    $data = $curl->getResult();
  } 

  return (object)array('status' => $status, 'data' => $data,
    'oldMd5' => $curMd5, 'newMd5' => $newMd5, 'dst' => $dst,
    'elapsed' => elapsedTime($start));
}

function updateLocalDb($db, $args) {
  $start = new DateTime();

  $parser = new HearingParser();
  $parser->start($args->data);

  $cnt = 0;

  $db->beginTransaction();
  while ($parser->hasNext()) {
    $cnt++;

    print_r($parser->getCurrentSrc());
    print PHP_EOL;

    $cur = $parser->getNext();

    print_r($cur);
    print PHP_EOL;

    $db->insertHearing($cur);
  }
  $db->commit();

  $updatedNumber = $db->getRowAffected();
  $updated = ($updatedNumber > 0) ? TRUE : FALSE; 

  return (object)array(
    'totalNumber' => $cnt,
    'updatedNumber' => $updatedNumber,
    'updated' => $updated,
    'elapsed' => elapsedTime($start)
  );
}

$programStart = new DateTime();

$db = connectDb();

$scrapeRslt = checkUpcomingHearingsUpdate($dbg);

Logger::logger()->info($pg . "HTML : " . $scrapeRslt->status . " => " . $scrapeRslt->dst . ' ' . $scrapeRslt->elapsed);

if ($scrapeRslt->status == 'UPDATED') {
  //$db->insertScraperJob();
  //$jobId = $db->getLastInsertId();

  $dbRslt = updateLocalDb($db, $scrapeRslt);
  Logger::logger()->info($pg . 'UPDATED ' . $dbRslt->updatedNumber . '/' . $dbRslt->totalNumber . " Rows " . $dbRslt->elapsed);


}

//$db->updateScraperJob($jobId, $jobStatus->completed, $totalNumber, $updatedNumber, $updated);
closeDb($db);

Logger::logger()->info($pg . 'COMPLETED! '. elapsedTime($programStart));
Logger::close();

?>
