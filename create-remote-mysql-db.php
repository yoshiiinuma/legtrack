<?php

namespace legtrack;

require_once 'lib/functions.php';
require_once 'lib/remote_mysql.php';

$env = 'development';

loadEnv($env);

$db = new RemoteMysql();
$db->configure($GLOBALS);
$db->connect();

$db->query(RemoteMysql::CREATE_MEASURES_TABLE_SQL);
$db->query(RemoteMysql::CREATE_MEASURES_INDEX_SQL);

?>
