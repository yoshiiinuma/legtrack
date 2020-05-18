<?php
namespace legtrack;

require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/lib/local_sqlite.php';
require_once __DIR__ . '/lib/remote_mysql.php';

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
$time = strtotime($time);


$env = ($argc == 4) ? $argv[3] : 'development';
loadEnv($env);

$local = new LocalSqlite();
$local->configure($GLOBALS);
$local->connect();

$updated = $local->selectUpdatedMeasures($time);

$mysql = new RemoteMysql();
$mysql->configure($GLOBALS);
$mysql->connect();

$cnt = 0;
foreach($updated as $r) {
  print_r($r);
  $cnt++;
  $mysql->upsertMeasure($r);
}

print "\n " . $cnt . " rows selected => " . $mysql->getRowAffected() / 2 . " rows updated\n";
?>
