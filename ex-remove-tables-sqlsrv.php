<?php

namespace legtrack;

require_once __DIR__ . 'lib/functions.php';
require_once __DIR__ . 'lib/remote_sqlsrv.php';

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

#$db->query(RemoteSqlsrv::DROP_POSITION_BOOKMARKS_TABLE_SQL);
#$db->query(RemoteSqlsrv::DROP_MEASURE_BOOKMARKS_TABLE_SQL);

$db->query(RemoteSqlsrv::DROP_KEYWORDS_TABLE_SQL);

$db->query(RemoteSqlsrv::DROP_TAGGED_MEASURES_TABLE_SQL);
$db->query(RemoteSqlsrv::DROP_TAGS_TABLE_SQL);

$db->query(RemoteSqlsrv::DROP_COMMENTS_TABLE_SQL);

$db->query(RemoteSqlsrv::DROP_POSITIONS_TABLE_SQL);
$db->query(RemoteSqlsrv::DROP_TRACKEDMEASUERS_TABLE_SQL);

$db->query(RemoteSqlsrv::DROP_GROUPMEMBERS_TABLE_SQL);
$db->query(RemoteSqlsrv::DROP_USERS_TABLE_SQL);

$db->query(RemoteSqlsrv::DROP_GROUPS_TABLE_SQL);
$db->query(RemoteSqlsrv::DROP_ROLES_TABLE_SQL);
$db->query(RemoteSqlsrv::DROP_DEPTS_TABLE_SQL);
?>


