<?php

namespace legtrack;

require_once 'lib/functions.php';
require_once 'lib/local_sqlite.php';
require_once 'lib/measure_parser.php';

/**
 *
 * Require PHP >= 5.6
 *
 *   mandatory parameters:
 *
 *      year:        2017
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
  echo "UASGE: php update-local-db.php <year> <measure> <file> [<env>] [debug]\n\n";
  echo "  env:\n";
  echo "    production | development | test\n\n";
  echo "  measure:\n";
  echo "    hb:  House Bills\n";
  echo "    sb:  Senate Bills\n";
  echo "    hr:  House Resos\n";
  echo "    sr:  Senate Resos\n";
  echo "    hcr: House Concurrent Resos\n";
  echo "    scr: Senate Concurrent Resos\n";
  echo "    gm:  Governer's Messages\n\n";
}

if ($argc < 4 || $argc > 6) {
  usage($argv);
  exit();
}

if ($argc == 6) {
  $dbg = TRUE;
}

$year = $argv[1];
$measure = $argv[2];
$file = $argv[3];
$env = ($argc > 4) ? $argv[4] : 'development';

if (!file_exist($file)) {
  die('File Not Found: ' . $file . PHP_EOL);
}

loadEnv($env);

$parser = new MeasureParser();
//$parser->startParsingFile($file);
$parser->start(file_get_contents($file));

$db = new LocalSqlite();
$db->configure($GLOBALS);
$db->connect() || die('Local DB Connection Failed' . PHP_EOL);

while ($parser->hasNext()) {
  $r = $parser->getNext();
  $db->upsertMeasureIfOnlyUpdated($year, $type, $r);
}

$db->close();

?>
