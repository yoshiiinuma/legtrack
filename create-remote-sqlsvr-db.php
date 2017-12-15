<?php

namespace legtrack;

require_once 'lib/functions.php';
require_once 'lib/remote_sqlsvr.php';

function usage($argv) {
  echo "\nUASGE: php create-remote-sqlsvr.php <env>\n\n";
  echo "  env: development|test|production\n";
}

if ($argc < 1 || $argc > 2) {
  usage($argv);
  exit();
}

$env = ($argc == 2) ? $argv[1]: 'development';

loadEnv($env);

$db = new RemoteSqlsvr();
$db->configure($GLOBALS);
$db->connect();

$db->query(RemoteSqlsvr::CREATE_MEASURES_TABLE_SQL);
$db->query(RemoteSqlsvr::CREATE_MEASURES_INDEX_SQL);

?>
