<?php
namespace legtrack;

use \DateTime;

require_once 'lib/functions.php';
require_once 'lib/enum.php';
require_once 'lib/local_sqlite.php';

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

loadEnv($env);

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

print "measureNumber,code,measureTitle,reportTitle,bitAppropriation,status,introducer,currentReferral,companion,measurePdfUrl,measureArchiveUrl,description" . PHP_EOL; 
foreach($data as $r) { 
  #print '"' . join('","', (array)$r) . '"' . PHP_EOL; 
  print "{$r->measureNumber},'{$r->code}','{$r->measureTitle}','{$r->reportTitle}',{$r->bitAppropriation},'{$r->status}','{$r->introducer}','{$r->currentReferral}','{$r->companion}','{$r->measurePdfUrl}','{$r->measureArchiveUrl}','{$r->description}'" . PHP_EOL; 

}

$db->close();

?>
