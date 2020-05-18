<?php
namespace legtrack;

require_once './lib/functions.php';
require_once './lib/local_sqlite.php';
require_once './lib/remote_sqlsrv.php';

function usage($argv) {
  echo "\n\n";
  echo "This program selects hearings by lastUpdated from Local SQLite3\n";
  echo "with START-TIME and SPAN-IN-SECS, and upserts them into SQL Server";
  echo "\n\n";
  echo "Wrong parameters were given:\n\n";
  print_r($argv);
  echo "\n";
  echo "UASGE: php update-selected-hearings-sqlsrv.php <START-TIME> <SPAN-IN-SECS> [<ENV>]\n\n";
  echo "  START-TIME:   specifies lastUpdated to select hearings in Unix timestamp\n";
  echo "  SPAN-IN-SECS: specifies span from START-TIME in seconds\n";
  echo "  ENV:          production | development | test\n\n";
}

if ($argc > 4 || $argc < 3) {
  usage($argv);
  exit();
}

$startTime = (int)$argv[1];
$endTime = $startTime + (int)$argv[2];

$env = ($argc == 4) ? $argv[3] : 'development';
loadEnv($env);

$local = new LocalSqlite();
$local->configure($GLOBALS);
$local->connect();

$updated = $local->selectHearingsByTime($startTime, $endTime);

$sqlsrv = new RemoteSqlsrv();
$sqlsrv->configure($GLOBALS);
$sqlsrv->connect();

$cnt = 0;
foreach($updated as $r) {
  print_r($r);
  $cnt++;
  $sqlsrv->upsertHearing($r);
}

print "\n " . $cnt . " rows selected => " . $sqlsrv->getRowAffected() . " rows updated\n";
?>
