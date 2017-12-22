<?php
namespace legtrack;

class BaseParser {
  public $dbg;
  protected $raw;
  protected $data;
  protected $size;
  protected $cur;

  public function __construct($dbg = FALSE) {
    $this->dbg = $dbg;
    $this->raw = NULL;
    $this->data = NULL;
    $this->size = 0;
    $this->cur = 0;
  }

  public function start($raw) {
    $this->setRawData($raw);
    $this->parse();
  }

  public function startParsingFile($file) {
    $this->readData($file);
    $this->parse();
  }

  public function setRawData($raw) {
    $this->raw = $raw;
  } 

  public function readData($file) {
    if (!file_exists($file)) {
      die("File Not Exists: ".$file.PHP_EOL);
    }
    $this->raw = file_get_contents($file);
  } 

  public function hasNext() {
    return ($this->cur < $this->size);
  }

  public function getNext() {
    $r = $this->extractData($this->data[$this->cur]);
    $this->cur++;
    return $r;
  }

  //Not forward the pointer
  public function getCurrent() {
    return $this->extractData($this->data[$this->cur]);
  }

  public function getCurrentSrc() {
    return $this->data[$this->cur];
  }

  //Override
  //Populates data and size using raw
  protected function parse() {
    die("Must Implement in subclass!");
  }

  //Override
  //Returns the current data and  forward the pointer
  protected function extractData($blk) {
    die("Must Implement in subclass!");
  }

}

?>
