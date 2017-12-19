<?php
/**
 *
 * Require PHP >= 7.0
 *
 * Capitol Deadline Tracking Page < Reports and Lists Page
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
 **/

namespace legtrack;

use \DateTime;

require_once 'lib/functions.php';
require_once 'lib/enum.php';
require_once 'lib/curl.php';
require_once 'lib/measure_parser.php';
require_once 'lib/local_measure.php';

function usage($argv) {
  echo "Wrong parameters were given:\n\n";
  print_r($argv);
  echo "\n";
  echo "UASGE: php scrape-and-store.php [env] [year] [debug]\n\n";
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

$measureTypes = Enum::getMeasureTypes();
$jobStatus = Enum::getJobStatus();

loadEnv($env);

function checkCapitolSiteUpdate($year, $type, $dbg) {
  $start = new DateTime();

  $data = NULL;
  $status = 'MATCHED';
  $title = $year . ' ' . $type;
  if (strlen($title) == 7) $title .= ' ';

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

  print $title . " : " . $status . " => " . $dst . ' ' . elapsedTime($start);
  return (object)array('status' => $status, 'data' => $data,
    'oldMd5' => $curMd5, 'newMd5' => $newMd5);
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
  $updated = ($updatedNumber > 0) ? TRUE : FALSE; 
  print $updatedNumber . '/' . $cnt . " Rows " . elapsedTime($start);
  return (object)array(
    'totalNumber' => $cnt,
    'updatedNumber' => $updatedNumber,
    'updated' => $updated);
}

function connectDb() {
  $db = new LocalMeasure();
  $db->configure($GLOBALS);
  $db->connect() || die('Local DB Connection Failed' . PHP_EOL);
  return $db;
}

function closeDb($db) {
  $db->close();
}

$programStart = new DateTime();

$db = connectDb();

$db->insertScraperJob();
$jobId = $db->getLastInsertId();

$totalNumber = 0;
$updatedNumber = 0;
$updated = FALSE;

foreach ($measureTypes as $type => $val) {
  $startedAt = new DateTime();

  $scrapeRslt = checkCapitolSiteUpdate($year, $type, $dbg);

  if ($scrapeRslt->status == 'UPDATED') {
    print " => DB UPDATE ";
    $dbRslt = updateLocalDb($db, $year, $type, $scrapeRslt);
    print "\n";
    $totalNumber += $dbRslt->totalNumber;
    $updatedNumber += $dbRslt->updatedNumber;
    if ($dbRslt->updated) $updated = TRUE;
    $db->insertScraperLog($jobId, $type, $jobStatus->completed, $startedAt->getTimestamp(),
      $dbRslt->totalNumber, $dbRslt->updatedNumber);
  } else {
    $db->insertScraperLog($jobId, $type, $jobStatus->skipped, $startedAt->getTimestamp(), 0, 0);
    print " => DB UPDATE SKIPPED\n";
  }
}

$db->updateScraperJob($jobId, $jobStatus->completed, $totalNumber, $updatedNumber, $updated);
closeDb($db);

print "\nCompleted! " . elapsedTime($programStart) . PHP_EOL;

?>
