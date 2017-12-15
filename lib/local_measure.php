<?php
namespace legtrack;
use \PDO;

require_once 'lib/db_base.php';

class LocalMeasure extends DbBase {
  private $path;

  public function setPath($path) {
    $this->path = $path;
  }

  public function configure($conf) {
    $this->user = $conf['SQLITE_USER'];
    $this->pass = $conf['SQLITE_PASS'];
    $this->path = $conf['SQLITE_LOCATION'];

    if (!$this->user || !$this->pass || !$this->path) {
      print_r($conf);
      die('SQLite3: Invalid Configuration'.PHP_EOL);
    }
  }

  public function getDns() {
    return "sqlite:".$this->path;
  }

}

?>
