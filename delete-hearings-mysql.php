<?php

namespace legtrack;

require_once 'lib/functions.php';
require_once 'lib/remote_mysql.php';

function usage($argv) {
  echo "\nUASGE: php drop-remote-mysql.php <env>\n\n";
  echo "  env: development|test|production\n";
}

if ($argc < 1 || $argc > 2) {
  usage($argv);
  exit();
}

$env = ($argc == 2) ? $argv[1]: 'development';
loadEnv($env);

$db = new RemoteMysql();
$db->configure($GLOBALS);
$db->connect();

$db->query(RemoteMysql::DROP_HEARINGS_INDEX_SQL);
$db->query(RemoteMysql::DROP_HEARINGS_TABLE_SQL);

?>