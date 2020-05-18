<?php

namespace legtrack;

require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/lib/local_sqlite.php';

function usage($argv) {
  echo "\nUASGE: php create-local-db.php <env>\n\n";
  echo "  env: development|test|production\n";
}

if ($argc < 1 || $argc > 2) {
  usage($argv);
  exit();
}

$env = ($argc == 2) ? $argv[1]: 'development';
loadEnv($env);

$db = new LocalSqlite();
$db->configure($GLOBALS);
$db->connect();

$db->query(LocalSqlite::CREATE_HEARINGS_TABLE_SQL);
$db->query(LocalSqlite::CREATE_HEARINGS_INDEX_SQL);

$db->close();
?>
