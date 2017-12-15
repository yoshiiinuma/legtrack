<?php

namespace legtrack;

require_once 'lib/functions.php';
require_once 'lib/remote_mysql.php';

$env = 'development';

loadEnv($env);

$db = new RemoteMysql();
$db->configure($GLOBALS);
$db->connect();

$db->query(RemoteMysql::DROP_MEASURES_TABLE_SQL);

?>
