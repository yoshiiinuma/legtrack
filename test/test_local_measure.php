<?php
namespace legtrack;

require_once __DIR__ . 'lib/functions.php';
require_once __DIR__ . 'lib/local_sqlite.php';
require_once __DIR__ . 'lib/measure_parser.php';

loadConfig('config/test.php');

function setup($r) {
  $r->db = new LocalSqlite();
  $r->db->configure($GLOBALS);
  $r->db->connect();
  $r->db->query(LocalSqlite::DROP_MEASURES_TABLE_SQL);
  $r->db->query(LocalSqlite::CREATE_MEASURES_TABLE_SQL);
}

function shutdown($r) {
  //$r->db->query(LocalSqlite::DROP_MEASURES_TABLE_SQL);
  $r->db->close();
}

function stop($r, $msg) {
  shutdown($r);
  die($msg);
}

function insertData($cxt) {
  $parser = new MeasureParser();
  $parser->start(file_get_contents('./data/measures_before.html'));
  while ($parser->hasNext()) {
    $r = $parser->getNext();
    $r->year = $cxt->year;
    $r->measureType = $cxt->measureType;
    if (!$cxt->db->upsertMeasure($r)) {
      print "Insert Failed\n";
      print_r($r);
      print_r($cxt->db->getError());
      return FALSE;
    }
  }
  return TRUE;
}

function updateData($cxt) {
  $cnt = 0;
  $parser = new MeasureParser();
  $parser->start(file_get_contents('./data/measures_after.html'));
  while ($parser->hasNext()) {
    $r = $parser->getNext();
    $r->year = $cxt->year;
    $r->measureType = $cxt->measureType;
    if ($cxt->db->upsertMeasure($r)) {
      $cnt++;
    }
  }
  if ($cnt != 5) {
    print "Must update 5 data\n";
    return FALSE;
  }
  return TRUE;
}

function testInsertData($cxt) {
  if ($cxt->db->getMeasureCount() != 10) {
    print "Count must be 10\n";
    return FALSE;
  };
  
  $parser = new MeasureParser();
  $parser->start(file_get_contents('./data/measures_before.html'));
  while ($parser->hasNext()) {
    $r = $parser->getNext();
    $cur = $cxt->db->selectMeasure($cxt->year, $cxt->measureType, $r);
    if (!$cur) {
      print "No DB Contents\n";
      print_r($r);
      return FALSE;
    }
    if (!$cxt->db->compare($cur, $r)) {
      print "Wrong DB Contents\n";
      print_r($r);
      print_r($cur);
      return FALSE;
    }
  }
  return TRUE;
}

function testUpdateData($cxt) {
  if ($cxt->db->getMeasureCount() != 10) {
    print "Count must be 10\n";
    return FALSE;
  };
  
  $parser = new MeasureParser();
  $parser->start(file_get_contents('./data/measures_after.html'));
  while ($parser->hasNext()) {
    $r = $parser->getNext();
    $cur = $cxt->db->selectMeasure($cxt->year, $cxt->measureType, $r);
    if (!$cur) {
      print "No DB Contents\n";
      print_r($r);
      return FALSE;
    }
    if (!$cxt->db->compare($cur, $r)) {
      print "Wrong DB Contents\n";
      print_r($cxt->db->diff($cur, $r));
      return FALSE;
    }
  }
  return TRUE;
}

$cxt = (object)array();

$cxt->year = 2017;
$cxt->measureType = 'hr';

setup($cxt);

if (!insertData($cxt)) stop($cxt, "insertData Failed\n");
if (!testInsertData($cxt)) stop($cxt, "testInsertData Failed\n");

sleep(11);

if (!updateData($cxt)) stop($cxt, "updateData Failed\n");
if (!testUpdateData($cxt)) stop($cxt, "testUpdateData Failed\n");

shutdown($cxt);

print "SUCCESS\n";
?>
