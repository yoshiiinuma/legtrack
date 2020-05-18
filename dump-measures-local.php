<?php
namespace legtrack;

use \DateTime;

require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/lib/enum.php';
require_once __DIR__ . '/lib/local_sqlite.php';

function usage($argv) {
  echo "\nUASGE: php select-measures-from-local.php <env>\n\n";
  echo "  env: development|test|production\n";
}

function connectLocalDb() {
  $db = new LocalSqlite();
  $db->configure($GLOBALS);
  $db->connect() || die('Local DB Conncection Failed'. PHP_EOL);
  return $db;
}

if ($argc < 1 || $argc > 2) {
  usage($argv);
  exit();
}

$env = ($argc > 1) ? $argv[1]: 'development';

$dataTypes = Enum::getDataTypes();
$measureTypes = Enum::getMeasureTypes();
$jobStatus = Enum::getJobStatus();

loadEnv($env);

$total = 0;
$selected = 0;

$sql = LocalSqlite::SELECT_ALL_MEASURES_SQL; 

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
