<?php
namespace legtrack;

require_once './lib/base_parser.php';

class HearingParser extends BaseParser {

  protected function parse() {
    $tds = $this->parseTds($this->raw);
    $this->convertTds($tds);
  }

  protected function extractData($blk) {
    $r = (object)array();

    $r->committee = $blk[0];

    $measureUrl = substr($blk[1], 9, strpos($blk[1], '">') - 9);
    $measureType = substr($measureUrl, strpos($measureUrl, 'billtype=') + 9);
    $measureNumber = substr($measureType, strpos($measureType, 'billnumber=') + 11);
    $measureType = substr($measureType, 0, strpos($measureType, '&amp;'));

    $r->measureRelativeUrl = $measureUrl;
    $r->measureType = $measureType;
    $r->measureNumber = intval($measureNumber);

    $r->code = strip_tags(substr($blk[1], 0, strpos($blk[1], '</a>') + 4));
    $r->description = substr($blk[1], strpos($blk[1], '</a>') + 4);
    $r->datetime = $blk[2];
    $r->timestamp = strtotime($blk[2]);
    $r->year = date_parse($blk[2])['year'];
    $r->room = $blk[3];

    //td5 contains two anchors for notice and pdf
    $a1 = substr($blk[4], 0, strpos($blk[4], '</a>') + 4);
    $a2 = substr($blk[4], strlen($a1));

    $r->noticeUrl = substr($a1, 9, strpos($a1, '">') - 9);
    $r->notice = strip_tags($a1);
    $r->noticePdfUrl = substr($a2, strpos($a2, '<a href="') + 9, strpos($a2, '"><img') - 9);

    return $r;
  }

  private function parseTds($raw) {
    $tbl = $this->extractTable($raw);
    return $this->stripTds($tbl);
  }

  // grab 2nd table
  private function extractTable($raw) {
    $tbl = substr($raw, strpos($raw, '<table')+1); // skip 1st table
    $tbl = substr($tbl, strpos($tbl, '<table')); // start of 2nd table
    $tbl = substr($tbl, 0, strpos($tbl, '</table>')+8); // end of 2nd table
    return $tbl;
  }

  private function stripTds($tbl) {
    $tds = strip_tags($tbl, '<td><a><img>');
    $tds = substr($tds, strpos($tds, '<td')); // start at first td
    $tds = preg_replace('/\s+/', ' ', $tds); // remove extra spaces
    $tds = preg_replace('/> /', '>', $tds);
    $tds = preg_replace('/ </', '<', $tds);
    $tds = preg_replace('/ (id|align|class|target|width|bgcolor)=\"[0-9a-zA-Z_#]+\"/', '', $tds);
    return $tds;
  }

  private function convertTds($tds) {
    $col=0;
    $row=0;
    $this->size = 0;
    $this->data = array();
 
    $sl = strpos($tds, '</td>');
    while ($sl !== false) {
      $line = substr($tds, 0, $sl+5); // get line
      if ($this->dbg) print $line . PHP_EOL;
      $tds = substr($tds, $sl+5);
      $this->data[$row][$col] = substr($line, 4, strlen($line)-9);
      $col++;

      if ($col == 5) {
        if ($this->dbg) {
          print PHP_EOL;
          print_r($this->data[$row]);
          print PHP_EOL;
        }
         $row++;
         $col=0;
      }
      $sl = strpos($tds, '</td>');
    }
    $this->size = $row; 
  }
}

?>
