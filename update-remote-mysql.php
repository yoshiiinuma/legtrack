<?php
namespace legtrack;

require_once 'lib/functions.php';
require_once 'lib/local_measure.php';
require_once 'lib/remote_mysql.php';

function usage($argv) {
  echo "Wrong parameters were given:\n\n";
  print_r($argv);
  echo "\n";
  echo "UASGE: php update-remote-mysql.php <LAST-UPDATED-TIME> [<env>]\n\n";
  echo "  time: YYYY-MM-DD HH:mm:ss\n\n";
  echo "  env:\n";
  echo "    production | development | test\n\n";
}

if ($argc != 4) {
  usage($argv);
  exit();
}

//$time = '2017-12-14 15:01:00';
$time = $argv[1] . ' ' . $argv[2];
if (!date_parse($time)) {
  print "Wrong Time Format: " . $time . PHP_EOL;
  usage($argv);
  exit();
}

$env = ($argc == 4) ? $argv[3] : 'development';
loadEnv($env);

$local = new LocalMeasure();
$local->configure($GLOBALS);
$local->connect();

$updated = $local->selectUpdated($time);

$mysql = new RemoteMysql();
$mysql->configure($GLOBALS);
$mysql->connect();

$cnt = 0;
foreach($updated as $r) {
  print_r($r);
  $cnt++;
  $mysql->upsertMeasure($r->year, $r->measureType, $r);
}

print "\n " . $cnt . " rows selected => " . $mysql->getRowAffected() / 2 . " rows updated\n";
?>
