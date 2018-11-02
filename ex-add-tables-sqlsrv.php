<?php

namespace legtrack;

require_once 'lib/functions.php';
require_once 'lib/remote_sqlsrv.php';

function usage($argv) {
  echo "\nUASGE: php ex-add-features-sqlsrv.php <env>\n\n";
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

$db->query(RemoteSqlsrv::CREATE_DEPTS_TABLE_SQL);
$db->query(RemoteSqlsrv::CREATE_ROLES_TABLE_SQL);
$db->query(RemoteSqlsrv::CREATE_USERS_TABLE_SQL);
$db->query(RemoteSqlsrv::CREATE_GROUPS_TABLE_SQL);
$db->query(RemoteSqlsrv::CREATE_GROUPMEMBERS_TABLE_SQL);

$db->query(RemoteSqlsrv::CREATE_TRACKEDMEASURES_TABLE_SQL);
$db->query(RemoteSqlsrv::CREATE_POSITIONS_TABLE_SQL);
$db->query(RemoteSqlsrv::CREATE_COMMENTS_TABLE_SQL);

#$db->query(RemoteSqlsrv::CREATE_POSITION_BOOKMARKS_TABLE_SQL);
#$db->query(RemoteSqlsrv::CREATE_MEASURE_BOOKMARKS_TABLE_SQL);

$db->query(RemoteSqlsrv::CREATE_TAGS_TABLE_SQL);
$db->query(RemoteSqlsrv::CREATE_TAGGED_MEASURES_TABLE_SQL);

?>


