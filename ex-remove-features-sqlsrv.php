<?php

namespace legtrack;

require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/lib/remote_sqlsrv.php';

function usage($argv) {
  echo "\nUASGE: php ex-remove-sqlsrv.php <env>\n\n";
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

$db->query(RemoteSqlsrv::DROP_TRACKEDMEASURE_BULKUPSERT_PROC);
$db->query(RemoteSqlsrv::DROP_TRACKEDMEASURE_DATA_PARSE_FUNCTION);

$db->query(RemoteSqlsrv::DROP_TRACKEDMEASURE_PAGE_SQL);
$db->query(RemoteSqlsrv::DROP_MEASURE_PAGE_SQL);
$db->query(RemoteSqlsrv::DROP_MEASURE_SEARCH_PAGE_SQL);
$db->query(RemoteSqlsrv::DROP_POSITION_PAGE_SQL);

$db->query(RemoteSqlsrv::DROP_TRACKEDMEASURE_TOTAL_SQL);
$db->query(RemoteSqlsrv::DROP_MEASURE_TOTAL_SQL);

$db->query(RemoteSqlsrv::DROP_MEASURE_VIEW_SQL);
$db->query(RemoteSqlsrv::DROP_TRACKEDMEASURE_VIEW_SQL);
$db->query(RemoteSqlsrv::DROP_POSITION_VIEW_SQL);
$db->query(RemoteSqlsrv::DROP_GROUP_VIEW_SQL);
$db->query(RemoteSqlsrv::DROP_GROUPMEMBER_VIEW_SQL);
$db->query(RemoteSqlsrv::DROP_INTEGRATED_MEASURE_VIEW_SQL);

//$db->query(RemoteSqlsrv::DROP_TRACKING_DEPTS_TRIGGER);
//$db->query(RemoteSqlsrv::DROP_TRACKING_DEPTS_DELETE_TRIGGER);
//$db->query(RemoteSqlsrv::DROP_TRACKEDMEASURE_INSERT_TRIGGER);
//$db->query(RemoteSqlsrv::DROP_TRACKEDMEASURE_UPDATE_TRIGGER);
//$db->query(RemoteSqlsrv::DROP_POSITION_INSERT_TRIGGER);
//$db->query(RemoteSqlsrv::DROP_POSITION_UPDATE_TRIGGER);

?>


