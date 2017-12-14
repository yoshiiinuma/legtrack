<?php

namespace legtrack;

require_once './lib/functions.php';
require_once './lib/local_measure.php';

$env = 'development';

loadEnv($env);

$db = new LocalMeasure();
$db->configure($GLOBALS);
$db->connect();

$db->query(LocalMeasure::CREATE_MEASURES_TABLE_SQL);

?>
