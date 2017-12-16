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

$programStart = new DateTime();

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
  $curl = new Curl();
  $curl->debug = $dbg;

  $dst = getResultPath($year, $type);
  $curl->getMeasures($year, $type);

  $curMd5 = (file_exists($dst)) ? md5_file($dst) : 'xxx';
  $newMd5 = $curl->getMd5();

  if ($curMd5 == $newMd5) {
    return NULL;
  } 
  $curl->saveResult($dst);
  return $curl->getResult();
}

function updateLocalDb($year, $type, $data) {
  $parser = new MeasureParser();
  //$parser->start(file_get_contents($data));
  $parser->start($data);

  $db = new LocalMeasure();
  $db->configure($GLOBALS);
  $db->connect() || die('Local DB Connection Failed' . PHP_EOL);

  $cnt = 0;
  $db->beginTransaction();
  while ($parser->hasNext()) {
    $cnt++;
    $r = $parser->getNext();
    $db->upsertMeasureIfOnlyUpdated($year, $type, $r);
    //$db->insertOrIgnoreAndUpdateMeasure($year, $type, $r);
    //$db->insertMeasure($year, $type, $r);
    //$db->updateMeasure($year, $type, $r);
  }
  $db->commit();
  $db->close();
  print $db->getRowAffected() . '/' . $cnt . " Rows Updated";
}

foreach ($measureTypes as $type) {
  $downloadStart = new DateTime();
  $dst = getResultPath($year, $type);
  $status = 'MATCHED';
  $data = checkCapitolSiteUpdate($year, $type, $dbg);
  $status = ($data) ? 'UPDATED' : 'MATCHED';
  $elapsed = $downloadStart->diff(new DateTime());
  $title = $year . ' ' . $type;
  if (strlen($title) == 7) $title .= ' ';
  print $title . " : " . $status . " => " . $dst;
  print "  " . $elapsed->format("%i mins %s secs");
  print " => DB UPDATE ";
  if ($data) {
    $dbUpdateStart = new DateTime();
    updateLocalDb($year, $type, $data);
    //updateLocalDb($year, $type, $dst);
    $elapsed = $dbUpdateStart->diff(new DateTime());
    print "  " . $elapsed->format("%i mins %s secs") . PHP_EOL;
  } else {
    print "SKIPPED\n";
  }
}
$elapsed = $programStart->diff(new DateTime());
print "\nCompleted! " . $elapsed->format("%i mins %s secs elapsed\n");

?>
