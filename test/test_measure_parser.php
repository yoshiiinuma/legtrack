<?php
namespace legtrack;

//require_once "lib/functions.php";
require_once "lib/measure_parser.php";

//loadConfig("config/test.php");

function testRow($r, $strict) {
  $err = false;
  if (gettype($r->measureNumber) != "string") {
    $err = true;
    print "MeasureNumber is not a string\n";
  }
  if (preg_match('/^\d+$/', $r->measureNumber, $matches) != 1) {
    $err = true;
    print "MeasureNumber is not a positive number";
  }
  if (gettype($r->bitAppropriation) != "string") {
    $err = true;
    print "bitAppropriation is not a string\n";
  }
  if ($r->bitAppropriation != 0 && $r->bitAppropriation != 1) {
    $err = true;
    print "bitAppropriation must be 0 or 1";
  }
  if (gettype($r->code) != "string") {
    $err = true;
    print "code is not a string\n";
  }
  if (preg_match("/^(GM|HB|SB|HR|SR|HCR|SCR)\d+( *(GM|HB|SB|HR|SR|HCR|SCR)\d+)*/", $r->code, $matches) != 1) {
    $err = true;
    print "code must match /^(GM|HB|SB|HR|SR|HCR|SCR)\d+$/";
  }
  if (gettype($r->measurePdfUrl) != "string") {
    $err = true;
    print "measurePdfUrl is not a string\n";
  }
  if (preg_match("/^https:\/\/www.capitol.hawaii.gov\/.+\.pdf$/", $r->measurePdfUrl, $matches) != 1) {
    $err = true;
    print "code must match /^https:\/\/www.capitol.hawaii.gov\/.+\.pdf$/";
  }
  if (gettype($r->measureArchiveUrl) != "string") {
    $err = true;
    print "measureArchiveUrl is not a string\n";
  }
  if (preg_match("/^https:\/\/www.capitol.hawaii.gov\/measure_indiv.aspx\?/", $r->measureArchiveUrl, $matches) != 1) {
    $err = true;
    print "code must match /^https:\/\/www.capitol.hawaii.gov\/.+\.pdf$/";
  }
  if (gettype($r->reportTitle) != "string") {
    $err = true;
    print "reportTitle is not a string\n";
  }
  if (gettype($r->measureTitle) != "string") {
    $err = true;
    print "measureTitle is not a string\n";
  }
  if (strlen($r->measureTitle) == 0) {
    $err = true;
    print "measureTitle must not be empty\n";
  }
  if (gettype($r->description) != "string") {
    $err = true;
    print "description is not a string\n";
  }
  if ($strict && strlen($r->description) == 0) {
    $err = true;
    print "description must not be empty\n";
  }
  if (gettype($r->status) != "string") {
    $err = true;
    print "status is not a string\n";
  }
  if (strlen($r->status) == 0) {
    $err = true;
    print "status must not be empty\n";
  }
  if (gettype($r->introducer) != "string") {
    $err = true;
    print "introducer is not a string\n";
  }
  if ($strict && strlen($r->introducer) == 0) {
    $err = true;
    print "introducer must not be empty\n";
  }
  if (gettype($r->currentReferral) != "string") {
    $err = true;
    print "currentReferral is not a string\n";
  }
  if (gettype($r->companion) != "string") {
    $err = true;
    print "companion is not a string\n";
  }
  if (gettype($r->companionUrl) != "string") {
    $err = true;
    print "companionUrl is not a string\n";
  }

  if ($err) {
    print_r($r);
    die("\n\n!!! Something Wrong !!!\n\n");
  }
}

/**
 * $strict: check whether description and introducer are not empty if true.
 **/
function testFile($file, $strict) {
  print 'Start Testing ' . $file . PHP_EOL;
  $parser = new MeasureParser();
  $parser->startParsingFile($file);

  while ($parser->hasNext()) {
    $cur = $parser->getNext();
    //print_r($cur);
    testRow($cur, $strict);
  }
  print 'End Testing ' . $file . PHP_EOL;
}

$file1 = "test/data/2018_gm.html";
$file2 = "test/data/2018_hb.html";
$file3 = "test/data/2018_hcr.html";
$file4 = "test/data/2018_hr.html";
$file5 = "test/data/2018_sb.html";
$file6 = "test/data/2018_scr.html";
$file7 = "test/data/2018_sr.html";

testFile($file1, false);
testFile($file2, true);
testFile($file3, false);
testFile($file4, false);
testFile($file5, true);
testFile($file6, false);
testFile($file7, false);

print "SUCCESS\n";
?>
