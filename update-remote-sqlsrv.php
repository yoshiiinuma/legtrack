<?php
namespace legtrack;

require_once __DIR__ . 'lib/functions.php';
require_once __DIR__ . 'lib/local_sqlite.php';
require_once __DIR__ . 'lib/remote_sqlsrv.php';

function usage($argv) {
  echo "Wrong parameters were given:\n\n";
  print_r($argv);
  echo "\n";
  echo "UASGE: php update-remote-sqlsrv.php <LAST-UPDATED-TIME> [<env>]\n\n";
  echo "  time: YYYY-MM-DD HH:mm:ss\n\n";
  echo "  env:\n";
  echo "    production | development | test\n\n";
}

if ($argc != 4) {
  usage($argv);
  exit();
}

//$lastUpdatedAt = '2017-12-14 15:01:00';
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

$sqlsrv = new RemoteSqlsrv();
$sqlsrv->configure($GLOBALS);
$sqlsrv->connect();

$cnt = 0;
foreach($updated as $r) {
  print_r($r);
  $cnt++;
  $sqlsrv->upsertMeasure($r);
}

print "\n " . $cnt . " rows selected => " . $sqlsrv->getRowAffected() . " rows updated\n";
?>
