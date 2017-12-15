<?php
namespace legtrack;

require_once 'lib/functions.php';
require_once 'lib/curl.php';

/**
 *
 * Require PHP >= 5.5
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
  echo "UASGE: php download-measures.php <year> <measure> [debug]\n\n";
  echo "  measure:\n";
  echo "    hb:  House Bills\n";
  echo "    sb:  Senate Bills\n";
  echo "    hr:  House Resos\n";
  echo "    sr:  Senate Resos\n";
  echo "    hcr: House Concurrent Resos\n";
  echo "    scr: Senate Concurrent Resos\n";
  echo "    gm:  Governer's Messages\n\n";
}

$dbg = FALSE;

if ($argc < 3 || $argc > 4) {
  usage($argv);
  exit();
}

if ($argc == 4) {
  $dbg = TRUE;
}

$year = $argv[1];
$measure = $argv[2];

$dst = getResultPath($year, $measure);

$curl = new Curl();
$curl->debug = $dbg;
$curl->getMeasures($year, $measure);

$curl->saveResult($dst);
print "Saved => "$dst . ": " . $curl->getMd5() . PHP_EOL;
//saveContents();

?>
