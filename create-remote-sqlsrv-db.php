<?php

namespace legtrack;

require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/lib/remote_sqlsrv.php';

function usage($argv) {
  echo "\nUASGE: php create-remote-sqlsrv.php <env>\n\n";
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

$db->query(RemoteSqlsrv::CREATE_MEASURES_TABLE_SQL);
$db->query(RemoteSqlsrv::CREATE_MEASURES_INDEX_SQL);

$db->query(RemoteSqlsrv::CREATE_HEARINGS_TABLE_SQL);
$db->query(RemoteSqlsrv::CREATE_HEARINGS_INDEX_SQL);

?>
