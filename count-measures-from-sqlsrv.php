<?php
namespace legtrack;

use \DateTime;

require_once __DIR__ . 'lib/functions.php';
require_once __DIR__ . 'lib/enum.php';
require_once __DIR__ . 'lib/remote_sqlsrv.php';
require_once __DIR__ . 'lib/logger.php';

function usage($argv) {
  echo "\nUASGE: php count-measures-from-sqlsrv.php <env>\n\n";
  echo "  env: development|test|production\n";
}

function connectSqlsrv() {
  $db = new RemoteSqlsrv();
  $db->configure($GLOBALS);
  $db->connect() || die('Sqlsrv Conncection Failed'. PHP_EOL);
  return $db;
}

if ($argc < 1 || $argc > 2) {
  usage($argv);
  exit();
}

$env = ($argc == 2) ? $argv[1]: 'development';

$dataTypes = Enum::getDataTypes();
$measureTypes = Enum::getMeasureTypes();
$jobStatus = Enum::getJobStatus();
$pg = 'SELECT-MEASURES-FROM-SQLSRV ';

loadEnv($env);

$programStart = new DateTime();

Logger::open($GLOBALS);
Logger::logger()->setLogLevel(Logger::INFO);
Logger::logger()->info($pg . 'STARTED ENV: ' . $env);


$total = 0;
$selected = 0;

$remote = connectSqlsrv();
$total = $remote->getMeasureCount();
$remote->close();

echo($pg . ' ' . $total . ' Rows Selected' . PHP_EOL);
echo($pg . 'COMPLETED! ' . elapsedTime($programStart) . PHP_EOL);

Logger::logger()->info($pg . ' ' . $total . ' Rows Selected');
Logger::logger()->info($pg . 'COMPLETED! ' . elapsedTime($programStart));
Logger::close();

?>
