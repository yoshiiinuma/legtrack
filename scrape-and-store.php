<?php
namespace legtrack;

use \DateTime;

require_once 'lib/functions.php';
require_once 'lib/curl.php';
require_once 'lib/measure_parser.php';
require_once 'lib/local_measure.php';

/**
 *
 * Require PHP >= 5.6
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

$measureTypes = array('hb', 'sb', 'hr', 'sr', 'hcr', 'scr', 'gm');

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
  return $data;
}

function updateLocalDb($year, $type, $data) {
  $start = new DateTime();

  $parser = new MeasureParser();
  $parser->start($data);

  $db = new LocalMeasure();
  $db->configure($GLOBALS);
  $db->connect() || die('Local DB Connection Failed' . PHP_EOL);

  $cnt = 0;

  $db->beginTransaction();
  while ($parser->hasNext()) {
    $cnt++;
    $cur = $parser->getNext();
    $db->upsertMeasureIfOnlyUpdated($year, $type, $cur);
    //$db->insertOrIgnoreAndUpdateMeasure($year, $type, $cur);
    //$db->insertMeasure($year, $type, $cur);
    //$db->updateMeasure($year, $type, $cur);
  }
  $db->commit();
  $db->close();

  print $db->getRowAffected() . '/' . $cnt . " Rows " . elapsedTime($start);
}

function elapsedTime($startTime) {
  $elapsed = $startTime->diff(new DateTime());
  return $elapsed->format("%i mins %s secs");
}

$programStart = new DateTime();

foreach ($measureTypes as $type) {
  $data = checkCapitolSiteUpdate($year, $type, $dbg);


  if ($data) {
    print " => DB UPDATE ";
    updateLocalDb($year, $type, $data);
    print "\n";
  } else {
    print " => DB UPDATE SKIPPED\n";
  }
}

print "\nCompleted! " . elapsedTime($programStart);

?>
