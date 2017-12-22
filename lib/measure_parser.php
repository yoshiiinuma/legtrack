<?php
namespace legtrack;

require_once 'lib/base_parser.php';

class MeasureParser extends BaseParser {
  //const MTA = array("","HB","SB","HR","SR","HCR","SCR","GM");

  protected function parse() {
    $tds = $this->parseTds($this->raw);
    $this->convertTds($tds);
  }

  protected function extractData($blk) {
   $mturl = substr($blk[0],strpos($blk[0],'"')+1);
   $mturl = htmlspecialchars_decode(substr($mturl,0,strpos($mturl,'"')));
   $mturl = substr($mturl,0,512);
   $mturl2 = substr($blk[1],strpos($blk[1],'"')+1);
   $mturl2 = htmlspecialchars_decode(substr($mturl2,0,strpos($mturl2,'"')));
   $mturl2 = substr($mturl2,0,512);
   if ($this->dbg) { print $mturl."-".$mturl2."\n"; }

   $code = substr($blk[1],strpos($blk[1],'>')+1);
   $code = substr($code,0,strpos($code,'<'));
   $code = substr($code,0,64);
   if ($this->dbg) { print $code."\n"; }

   intval(preg_match('/(\d+)/', $code, $match)) || die("No Number Found: ". $code . PHP_EOL);
   $number = $match[0];
   if ($this->dbg) { print $code.' => '.$number.PHP_EOL; }

   $blk[1] = substr($blk[1],strpos($blk[1],'<span>')); // trim to first span
   $tmp = strpos($blk[1], '</span>');
   $tmp = strpos($blk[1], '</span>', $tmp+1); // rtitle = first two spans
   $rtitle = strip_tags(substr($blk[1],0,$tmp));
   $rtitle = substr($rtitle,0,512);
   $bapp = (strpos($rtitle,"($)") !== false) ? "1" : "0";
   if ($this->dbg) { print $rtitle."\n"; }

   $blk[1] = substr($blk[1],$tmp+7); // trim to next span

   $tmp = strpos($blk[1], '</span>');
   $mtitle = strip_tags(substr($blk[1],0,$tmp));
   $mtitle = substr($mtitle,0,512);
   if ($this->dbg) { print $mtitle."\n"; }

   $blk[1] = substr($blk[1],$tmp+7); // trim to next span

   $tmp = strpos($blk[1], '</span>');
   $mdesc = strip_tags(substr($blk[1],0,$tmp));
   $mdesc = substr($mdesc,0,1024);
   if ($this->dbg) { print $mdesc."\n"; }

   $mstat = strip_tags($blk[2]);
   $mstat = substr($mstat,0,512);
   if ($this->dbg) { print $mstat."\n"; }

   $mintro = strip_tags($blk[3]);
   $mintro = substr($mintro,0,512);
   if ($this->dbg) { print $mintro."\n"; }

   $cref = strip_tags($blk[4]);
   $cref = substr($cref,0,256);
   if ($this->dbg) { print $cref."\n"; }

   $compt = strip_tags($blk[5]);
   $compt = substr($compt,0,256);
   $compt = str_replace('&nbsp;', '', $compt);
   if ($this->dbg) { print $compt."\n"; }

   $compu = strip_tags($blk[5],"<a>");
   $compu = substr($compu,strpos($compu,"'")+1);
   $compu = substr($compu,0,strpos($compu,"'"));
   $compu = substr($compu,0,512);
   if ($this->dbg) { print $compu."\n=====\n"; }

   $r = (object)array();
   #$r->mturl = $mturl;
   #$r->mturl2 = $mturl2;
   #$r->code = $code;
   #$r->number = $number;
   #$r->rtitle = $rtitle;
   #$r->mtitle = $mtitle;
   #$r->bapp = $bapp;
   #$r->mdesc = $mdesc;
   #$r->mstat = $mstat;
   #$r->mintro = $mintro;
   #$r->cref = $cref;
   #$r->compt = $compt;
   #$r->compu = $compu;
   $r->measureNumber = $number;
   $r->code = $code;
   $r->measurePdfUrl = $mturl;
   $r->measureArchiveUrl = $mturl2;
   $r->reportTitle = $rtitle;
   $r->measureTitle = $mtitle;
   $r->bitAppropriation = $bapp;
   $r->description = $mdesc;
   $r->status = $mstat;
   $r->introducer = $mintro;
   $r->currentReferral = $cref;
   $r->companion = $compt;
   $r->companionUrl = $compu;
   return $r;
  }

  private function parseTds($raw) {
    $str = substr($raw,strpos($raw, "<td")); // remove up to first data table row
    $str = substr($str, 0, strpos($str, "</table>")); // remove after end of table
    $str = strip_tags($str, "<td><a><span>"); // use only row and link tags
    $str = preg_replace('/\s+/', ' ', $str); // remove extra spaces
    $str = preg_replace('/> /', '>', $str);
    $str = preg_replace('/ </', '<', $str);
    $str = preg_replace('/ (id|class|target|width|bgcolor)=\"[0-9a-zA-Z_#]+\"/', '', $str);
    return $str;
  }

  private function convertTds($tds) {
    $col=0;
    $row=0;
    $this->size = 0;
    $this->data = array();

    $sl = strpos($tds, "</td>");
    while ($sl !== false) {
       $line = substr($tds, 0, $sl+5); // get line
       $tds = substr($tds, $sl+5); 
       $this->data[$row][$col] = substr($line, 4, strlen($line)-9);
       $col++;

       if ($col == 6) {
          $row++;
          $col=0;
       }
       $sl = strpos($tds, "</td>");
    }
    $this->size = $row;
  }
}

?>
