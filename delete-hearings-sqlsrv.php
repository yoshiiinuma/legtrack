<?php

namespace legtrack;

require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/lib/remote_sqlsrv.php';

function usage($argv) {
  echo "\nUASGE: php drop-remote-sqlsrv.php <env>\n\n";
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

$db->query(RemoteSqlsrv::DROP_HEARINGS_INDEX_SQL);
$db->query(RemoteSqlsrv::DROP_HEARINGS_TABLE_SQL);

?>
