<?php

namespace legtrack;

require_once 'lib/functions.php';
require_once 'lib/local_measure.php';

function usage($argv) {
  echo "\nUASGE: php drop-local-db.php <env>\n\n";
  echo "  env: development|test|production\n";
}

if ($argc < 1 || $argc > 2) {
  usage($argv);
  exit();
}

$env = ($argc == 2) ? $argv[1]: 'development';
loadEnv($env);

$db = new LocalMeasure();
$db->configure($GLOBALS);
$db->connect();

$db->query(LocalMeasure::DROP_MEASURES_INDEX_SQL);
$db->query(LocalMeasure::DROP_MEASURES_TABLE_SQL);
$db->query(LocalMeasure::DROP_HEARINGS_INDEX_SQL);
$db->query(LocalMeasure::DROP_HEARINGS_TABLE_SQL);

$db->query(LocalMeasure::DROP_SCRAPER_JOBS_TABLE_SQL);
$db->query(LocalMeasure::DROP_SCRAPER_LOGS_TABLE_SQL);
$db->query(LocalMeasure::DROP_UPLOADER_MYSQL_JOBS_TABLE_SQL);
$db->query(LocalMeasure::DROP_UPLOADER_SQLSRV_JOBS_TABLE_SQL);

$db->close();
?>
