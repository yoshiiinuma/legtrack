<?php
namespace legtrack;

use \DateTime;

require_once __DIR__ . 'lib/functions.php';
require_once __DIR__ . 'lib/enum.php';
require_once __DIR__ . 'lib/remote_sqlsrv.php';
require_once __DIR__ . 'lib/logger.php';

function usage($argv) {
  echo "\nUASGE: php select-measures-from-sqlsrv.php <env> [ALL]\n\n";
  echo "  env: development|test|production\n";
}

function connectSqlsrv() {
  $db = new RemoteSqlsrv();
  $db->configure($GLOBALS);
  $db->connect() || die('Sqlsrv Conncection Failed'. PHP_EOL);
  return $db;
}

if ($argc < 1 || $argc > 3) {
  usage($argv);
  exit();
}

$env = ($argc > 1) ? $argv[1]: 'development';
$all = false;
if ($argc > 2) {
  if ($argv[2] != 'ALL') {
    usage($argv);
    exit();
  }
  $all = true;
}

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

$sql = ($all) ? RemoteSqlsrv::SELECT_ALL_MEASURES_SQL : RemoteSqlsrv::SELECT_TOP100_MEASURES_SQL; 

$remote = connectSqlsrv();
$stmt = $remote->prepare($sql, []);

if (!$stmt->execute()) {
  die("Cannot execute the SQL");
}

$data = $stmt->fetchAll();
$total = sizeof($data);
if (!$all) {
  foreach($data as $r) { print_r($r); }
}
$remote->close();

echo($pg . ' ' . $total . ' Rows Selected' . PHP_EOL);
echo($pg . 'COMPLETED! ' . elapsedTime($programStart) . PHP_EOL);

Logger::logger()->info($pg . ' ' . $total . ' Rows Selected');
Logger::logger()->info($pg . 'COMPLETED! ' . elapsedTime($programStart));
Logger::close();

?>
