<?php
/**
 *
 * Require PHP >= 7.0
 *
 * Scrape and Store measures from:
 *
 *   Capitol Deadline Tracking Page < Reports and Lists Page
 *
 *   e.g.
 *     http://capitol.hawaii.gov/advreports/advreport.aspx?year=2017&report=deadline&active=true&rpt_type=&measuretype=hb
 *
 *   URL:
 *
 *     http://capitol.hawaii.gov/advreports/advreport.aspx
 *
 *   mandatory parameters:
 *
 *      year:        2017
 *      report:      deadline
 *      active:      true (necessary only if measuretype is hb or sb)
 *      rpt_type:
 *      measuretype: [hb|sb|hr|sr|hcr|scr|gm]
 *
 *   Measure Type:
 *      hb:  House Bills
 *      sb:  Senate Bills
 *      hr:  House Resos
 *      sr:  Senate Resos
 *      hcr: House Concurrent Resos
 *      scr: Senate Concurrent Resos
 *      gm:  Governer's Messages
 *
 * UASGE:
 *
 *   php scrape-measures.php [env] [year] [debug]
 *
 *      env: production | development | test (default development)
 *           need configuration under config for the selected env
 *
 **/

namespace legtrack;

use \DateTime;

require_once __DIR__ . 'lib/functions.php';
require_once __DIR__ . 'lib/enum.php';
require_once __DIR__ . 'lib/curl.php';
require_once __DIR__ . 'lib/measure_parser.php';
require_once __DIR__ . 'lib/local_sqlite.php';
require_once __DIR__ . 'lib/logger.php';

function usage($argv) {
  echo "Wrong parameters were given:\n\n";
  print_r($argv);
  echo "\n";
  echo "UASGE: php scrape-measures.php [env] [year] [debug]\n\n";
}

$dbg = FALSE;

if ($argc < 1 || $argc > 4) {
  usage($argv);
  exit();
}

if ($argc == 4) {
  $dbg = TRUE;
}

$env = ($argc > 1) ?  $argv[1] : 'development';
$year = ($argc > 2) ?  $argv[2] : date('Y');

$dataTypes = Enum::getDataTypes();
$measureTypes = Enum::getMeasureTypes();
$jobStatus = Enum::getJobStatus();
$pg = 'SCRAPE-MEASURES ';

loadEnv($env);

Logger::open($GLOBALS);
Logger::logger()->setLogLevel(Logger::INFO);
Logger::logger()->info($pg . 'STARTED ENV: ' . $env . ', YEAR: ' . $year);

function checkCapitolSiteUpdate($year, $type, $dbg) {
  $start = new DateTime();

  $data = NULL;
  $status = 'MATCHED';

  $curl = new Curl();
  $curl->debug = $dbg;

  $dst = getResultPath($year, $type);
  $curl->getMeasures($year, $type);

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

function updateLocalDb($db, $year, $type, $args) {
  $start = new DateTime();

  $parser = new MeasureParser();
  $parser->start($args->data);

  $cnt = 0;

  $db->beginTransaction();
  while ($parser->hasNext()) {
    $cnt++;
    $cur = $parser->getNext();
    $db->upsertMeasureIfOnlyUpdated($year, $type, $cur);
  }
  $db->commit();

  $updatedNumber = $db->getRowAffected();

  return (object)array(
    'totalNumber' => $cnt,
    'updatedNumber' => $updatedNumber,
    'updated' => ($updatedNumber > 0) ? TRUE : FALSE,
    'elapsed' => elapsedTime($start)
  );
}

function connectDb() {
  $db = new LocalSqlite();
  $db->configure($GLOBALS);
  $db->connect() || die('Local DB Connection Failed' . PHP_EOL);
  return $db;
}

function closeDb($db) {
  $db->close();
}

$programStart = new DateTime();

$db = connectDb();

$db->insertScraperJob($dataTypes->measures);
$jobId = $db->getLastInsertId();

$totalNumber = 0;
$updatedNumber = 0;
$updated = FALSE;

foreach ($measureTypes as $type => $val) {
  try {
    $startedAt = new DateTime();

    $scrapeRslt = checkCapitolSiteUpdate($year, $type, $dbg);
    Logger::logger()->info($pg . $year . ' ' . $type . " : " . $scrapeRslt->status . " => " . $scrapeRslt->dst . ' ' . $scrapeRslt->elapsed);

    if ($scrapeRslt->status == 'UPDATED') {
      $dbRslt = updateLocalDb($db, $year, $type, $scrapeRslt);
      Logger::logger()->info($pg . $year . ' ' . $type . ' UPDATED ' . $dbRslt->updatedNumber . '/' . $dbRslt->totalNumber . " Rows " . $dbRslt->elapsed);
      $totalNumber += $dbRslt->totalNumber;
      $updatedNumber += $dbRslt->updatedNumber;
      if ($dbRslt->updated) $updated = TRUE;
      $db->insertScraperLog($jobId, $type, $jobStatus->completed, $startedAt->getTimestamp(),
        $dbRslt->totalNumber, $dbRslt->updatedNumber);
    } else {
      $db->insertScraperLog($jobId, $type, $jobStatus->skipped, $startedAt->getTimestamp(), 0, 0);
      Logger::logger()->info($pg . $year . ' ' . $type . ' SKIPPED');
    }
  } catch (Exception $e) {
    Logger::logger()->error($pg . $year . ' ' . $type);
    Logger::logger()->error($e->getMessage());
  }
}

$db->updateScraperJob($jobId, $jobStatus->completed, $totalNumber, $updatedNumber, $updated);
closeDb($db);

Logger::logger()->info($pg . $updatedNumber . '/' . $totalNumber . ' Rows Updated');
Logger::logger()->info($pg . 'COMPLETED! '. elapsedTime($programStart));
Logger::close();

?>
