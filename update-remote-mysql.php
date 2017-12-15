<?php
namespace legtrack;

require_once 'lib/functions.php';
require_once 'lib/local_measure.php';
require_once 'lib/remote_mysql.php';

$env = 'test';
loadEnv($env);

$local = new LocalMeasure();
$local->configure($GLOBALS);
$local->connect();

$time = '2017-12-14 15:01:00';
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

print "\n" . $cnt . " rows selected => " . $mysql->getRowAffected() . " rows updated\n";
?>
