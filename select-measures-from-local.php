<?php
namespace legtrack;

use \DateTime;

require_once './lib/functions.php';
require_once './lib/enum.php';
require_once './lib/local_sqlite.php';
require_once './lib/logger.php';

function usage($argv) {
  echo "\nUASGE: php select-measures-from-local.php <env> [ALL]\n\n";
  echo "  env: development|test|production\n";
}

function connectLocalDb() {
  $db = new LocalSqlite();
  $db->configure($GLOBALS);
  $db->connect() || die('Local DB Conncection Failed'. PHP_EOL);
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
$pg = 'SELECT-MEASURES-FROM-LOCAL ';

loadEnv($env);

$programStart = new DateTime();

Logger::open($GLOBALS);
Logger::logger()->setLogLevel(Logger::INFO);
Logger::logger()->info($pg . 'STARTED ENV: ' . $env);


$total = 0;
$selected = 0;

$sql = ($all) ? LocalSqlite::SELECT_ALL_MEASURES_SQL : LocalSqlite::SELECT_TOP100_MEASURES_SQL; 

$db = connectLocalDb();
$stmt = $db->prepare($sql, []);

if (!$stmt->execute()) {
  die("Cannot execute the SQL");
}

$data = $stmt->fetchAll();
$total = sizeof($data);
//if (!$all) {
  foreach($data as $r) { print_r($r); }
//}
$db->close();

echo($pg . ' ' . $total . ' Rows Selected' . PHP_EOL);
echo($pg . 'COMPLETED! ' . elapsedTime($programStart) . PHP_EOL);

Logger::logger()->info($pg . ' ' . $total . ' Rows Selected');
Logger::logger()->info($pg . 'COMPLETED! ' . elapsedTime($programStart));
Logger::close();

?>
