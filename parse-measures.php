<?php
namespace legtrack;

require_once __DIR__ . 'lib/measure_parser.php';

if ($argc != 2) {
  print_r($argv);
  print("\n\nUSAGE: php parse-measures.php <FILE>\n\n");
  die("Invalid Arguments");
}

$file = $argv[1];

$parser = new MeasureParser();
//$parser->startParsingFile('measure_hb.html');
$parser->start(file_get_contents($file));

while ($parser->hasNext()) {
  print_r($parser->getCurrentSrc());
  $r = $parser->getNext();
  print_r($r);
}
?>
