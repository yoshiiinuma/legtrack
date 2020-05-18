<?php

namespace legtrack;

require_once './lib/functions.php';
require_once './lib/remote_sqlsrv.php';

function usage($argv) {
  echo "\nUASGE: php ex-remove-logging-sqlsrv.php <env>\n\n";
  echo "  env: development|test|production\n";
}

if ($argc < 1 || $argc > 2) {
  usage($argv);
  exit();
}

$env = ($argc == 2) ? $argv[1]: 'development';

loadEnv($env);

$db = new RemoteSqlsrv();
$db->configure($GLOBALS);
$db->connect();

$db->query(RemoteSqlsrv::DROP_TRACKEDMEASURES_LOG_TRIGGER);
$db->query(RemoteSqlsrv::DROP_POSITIONS_LOG_TRIGGER);

$db->query(RemoteSqlsrv::DROP_TRACKEDMEASURES_HISTORY_TABLE_SQL);
$db->query(RemoteSqlsrv::DROP_POSITIONS_HISTORY_TABLE_SQL);

?>


