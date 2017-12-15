<?php
namespace legtrack;

require_once 'lib/functions.php';
require_once 'lib/local_measure.php';
require_once 'lib/remote_sqlsvr.php';

$env = 'test';
loadEnv($env);

$local = new LocalMeasure();
$local->configure($GLOBALS);
$local->connect();

$time = '2017-12-14 15:01:00';
$updated = $local->selectUpdated($time);

$year = 2017;
$type = 'hr';

$sqlsvr = new RemoteSqlsvr();
$sqlsvr->configure($GLOBALS);
$sqlsvr->connect();

$cnt = 0;
foreach($updated as $r) {
  print_r($r);
  $cnt++;
  $sqlsvr->upsertMeasure($year, $type, $r);
}

print "\n" . $cnt . " rows selected => " . $sqlsvr->getRowAffected() . " rows updated\n";
?>
