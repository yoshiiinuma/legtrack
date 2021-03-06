<?php

namespace legtrack;

require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/lib/remote_sqlsrv.php';

function usage($argv) {
  echo "\nUASGE: php ex-add-specialsession-tables-sqlsrv.php <env>\n\n";
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

$db->query(RemoteSqlsrv::CREATE_SP_MEASURES_TABLE_SQL);
$db->query(RemoteSqlsrv::CREATE_SP_TRACKEDMEASURES_TABLE_SQL);
$db->query(RemoteSqlsrv::CREATE_SP_POSITIONS_TABLE_SQL);
$db->query(RemoteSqlsrv::CREATE_SP_COMMENTS_TABLE_SQL);

$db->query(RemoteSqlsrv::CREATE_SP_MEASURES_INDEX_SQL);

$db->query(RemoteSqlsrv::CREATE_SP_MEASURE_VIEW_SQL);
$db->query(RemoteSqlsrv::CREATE_SP_TRACKEDMEASURE_VIEW_SQL);
$db->query(RemoteSqlsrv::CREATE_SP_POSITION_VIEW_SQL);

?>


