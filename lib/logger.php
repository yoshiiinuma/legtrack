<?php
namespace legtrack;

use \Date;

class Logger {
  private static $instance;
  private $level;
  private $out;

  public const ERROR = 4;
  public const WARN  = 3;
  public const INFO  = 2;
  public const DEBUG = 1;

  static function logger() {
    if (!self::$instance) {
      die('Call Logger::open first');
    }
    return self::$instance;
  }

  //Expects 'LOG_PATH'
  static function open($conf) {
    if (!self::$instance) {
      self::$instance = new Logger($conf);
    }
  }

  static function close() {
    if (self::$instance) {
      self::$instance->closeLogFile();
      self::$instance = NULL;
    }
  }

  private function __construct($conf) {
    $this->openLogFile($conf);
    $level = self::INFO; 
  }

  private function openLogFile($conf) {
    $this->out = fopen($conf['LOG_PATH'], 'a');
  }

  private function closeLogFile() {
    if ($this->out) {
      fclose($this->out);
      $this->out = NULL;
    }
  }

  public function setlogLevel($level) {
    $this->level = $level;
  }

  public function error($msg, $obj = NULL) {
    if ($this->level >= self::ERROR) $this->puts('ERROR', $msg);
  }

  public function warn($msg, $obj = NULL) {
    if ($this->level >= self::WARN) $this->puts('WARN ', $msg);
  }

  public function info($msg, $obj = NULL) {
    if ($this->level >= self::INFO) $this->puts('INFO ', $msg);
  }

  public function debug($msg, $obj = NULL) {
    if ($this->level >= self::DEBUG) $this->puts('DEBUG', $msg);
  }

  private function puts($level, $msg, $obj = NULL) {
    fwrite($this->out, Date('Y-m-d H:i:s') . ' [' . $level . '] ' . $msg . PHP_EOL); 
    if ($obj) {
      fwrite(PHP_EOL . print_r($obj, TRUE) . PHP_EOL);
    }
  }
}

?>
