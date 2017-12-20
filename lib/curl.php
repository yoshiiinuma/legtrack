<?php
namespace legtrack;

require_once 'lib/logger.php';

class Curl {
  public $debug;
  private $ch;
  private $result;
  private $errno;
  private $error;
  private $info;

  public function __construct() {
    $this->debug = FALSE;
    $this->ch = curl_init();
    $this->errno = NULL;
    $this->error = NULL;
    $this->info = NULL;
    $this->result = NULL;
  }

  public function getMeasuresUrl($year, $measure) {
    $url = "http://capitol.hawaii.gov/advreports/advreport.aspx?year=".$year."&report=deadline";
    if ($measure == 'hb' || $measure == 'sb') $url .= "&active=true";
    $url .= "&rpt_type=&measuretype=".$measure;
    return $url;
  }

  public function getMeasures($year, $measure) {
    if ($this->debug) echo "Trying to connect to captiol\n";
    if (!$this->ch) {
      die("No curl handler\n");
    }
    $url = $this->getMeasuresUrl($year, $measure);
    $r = $this->download($url);
    if ($this->debug) {
      if ($r) {
        echo "Downloading measures successfully completed\n";
      } else {
        echo "Downloading measures failed\n";
      }
    }
    return $r;
  }
  
  private function download($url) {
    try {
      $this->setDefaultOptions();
      if ($this->debug) setDebugOptions();
      $this->setopt(CURLOPT_URL, $url);
      $this->result = curl_exec($this->ch);
      $this->saveCurlStatus();
    } catch (Exception $e) {
      if ($this->debug) echo "Exception got raised during curl_exec";
      $this->error = $e;
      Logger::logger()->info('CURL_EXEC Exception');
      Logger::logger()->info('CURL_ERRORNO: ' . curl_errno($this->ch));
      Logger::logger()->info('CURL_ERROR: ' . curl_error($this->ch));
      Logger::logger()->info('CURL_INFO: ' . curl_getinfo($this->ch));
    }
    $this->close();
    if ($this->error) {
      if ($this->debug) {
        print_r('---< CURL_ERROR >------------------------------------');
        print PHP_EOL;
        print(" ERRNO: ".$this->errno);
        print(" ERROR: ".$this->error);
        print PHP_EOL;
        if ($this->result) print_r($this->result);
        print PHP_EOL;
      }
      return false;
    } else if (!$this->result) {
      die("No Result");
    }
    return true;
  }

  private function setDefaultOptions() {
    $this->setopt(CURLOPT_RETURNTRANSFER, 1);
    //$this->setopt(CURLOPT_CONNECTTIMEOUT, 0);
    $this->setopt(CURLOPT_CONNECTTIMEOUT, 20);
    $this->setopt(CURLOPT_TIMEOUT, 120);
    $this->setopt(CURLOPT_HTTPGET, TRUE);
    //$this->setopt(CURLOPT_POST, FALSE);
    $this->setopt(CURLOPT_FOLLOWLOCATION, 1);
    $this->setopt(CURLOPT_FRESH_CONNECT, TRUE);
  }

  private function setDebugOptions() {
    $this->setopt(CURLOPT_VERBOSE, TRUE);
  }

  public function setopt($key, $val) {
    curl_setopt($this->ch, $key, $val);
  }

  private function close() {
    curl_close($this->ch);
    $this->ch = NULL;
  }

  private function saveCurlStatus() {
    if (!$this->ch) return;
    $this->errno = curl_errno($this->ch);
    $this->error = curl_error($this->ch);
    $this->info = curl_getinfo($this->ch);
  }

  public function saveResult($file) {
    if (!$this->result) {
      die("Not Download Yet");
    }
    file_put_contents($file, $this->result);
  }

  public function getResult() {
    return $this->result;
  }

  public function getMd5() {
    if (!$this->result) {
      die("Not Download Yet");
    }
    return md5($this->result);
  }

  public function hasError() {
    return ($this->error) ? TRUE : FALSE;
  }

  public function getErrorNo() {
    return $this->errno;
  }

  public function getError() {
    return $this->error;
  }

  public function getInfo() {
    return $this->info;
  }

  public function showError() {
    print(" ERR# : ".$this->errno.PHP_EOL);
    print(" ERROR:".PHP_EOL);
    print_r($this->error);
    print(" INFO:".PHP_EOL);
    print_r($this->info);
    print PHP_EOL;
  }

  public function showResult() {
    print_r($this->result);
    print PHP_EOL;
  }
}

?>
