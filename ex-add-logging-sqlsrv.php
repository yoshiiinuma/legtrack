<?php

namespace legtrack;

require_once __DIR__ . 'lib/functions.php';
require_once __DIR__ . 'lib/remote_sqlsrv.php';

function usage($argv) {
  echo "\nUASGE: php ex-add-logging-sqlsrv.php <env>\n\n";
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

$db->query(RemoteSqlsrv::CREATE_TRACKEDMEASURES_HISTORY_TABLE_SQL);
$db->query(RemoteSqlsrv::CREATE_POSITIONS_HISTORY_TABLE_SQL);

$db->query(RemoteSqlsrv::CREATE_TRACKEDMEASURES_LOG_TRIGGER);
$db->query(RemoteSqlsrv::CREATE_POSITIONS_LOG_TRIGGER);

?>


