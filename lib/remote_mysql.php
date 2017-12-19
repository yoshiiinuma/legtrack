<?php
namespace legtrack;
use \PDO;
use \DateTime;

require_once 'lib/db_base.php';

class RemoteMysql extends DbBase {
  private $host;
  private $dbname;

  const CREATE_MEASURES_TABLE_SQL = <<<HERE
    CREATE TABLE IF NOT EXISTS measures
    (
      id int unsigned NOT NULL AUTO_INCREMENT,
      year smallint NOT NULL,
      measureType char(3) NOT NULL,
      measureNumber smallint NOT NULL,
      lastUpdated datetime,
      code varchar(64),
      measurePdfUrl varchar(512),
      measureArchiveUrl varchar(512),
      measureTitle varchar(512),
      reportTitle varchar(512),
      bitAppropriation tinyint(1),
      description varchar(1024),
      status varchar(512),
      introducer varchar(512),
      currentReferral varchar(256),
      companion varchar(256),
      PRIMARY KEY (id),
      UNIQUE (year, measureType, measureNumber)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
HERE;

  const CREATE_MEASURES_INDEX_SQL = <<<HERE
    CREATE INDEX measures_lastupdated_idx ON measures(lastUpdated);
HERE;

  const UPSERT_MEASURE_SQL = <<<HERE
     INSERT INTO measures (
        measureType, year, measureNumber, lastUpdated, code, measurePdfUrl,
        measureArchiveUrl, measureTitle, reportTitle, bitAppropriation,
        description, status, introducer, currentReferral, companion)
     VALUES (
        :measureType, :year, :measureNumber, :lastUpdated, :code, :measurePdfUrl,
        :measureArchiveUrl, :measureTitle, :reportTitle, :bitAppropriation,
        :description, :status, :introducer, :currentReferral, :companion)
     ON DUPLICATE KEY UPDATE
        lastUpdated = :lastUpdated2,
        code = :code2,
        measurePdfUrl = :measurePdfUrl2,
        measureArchiveUrl = :measureArchiveUrl2,
        measureTitle = :measureTitle2,
        reportTitle = :reportTitle2,
        bitAppropriation = :bitAppropriation2,
        description = :description2,
        status = :status2,
        introducer = :introducer2,
        currentReferral = :currentReferral2,
        companion = :companion2
      ;
HERE;

  public function configure($conf) {
    $this->user = $conf['MYSQL_USER'];
    $this->pass = $conf['MYSQL_PASS'];
    $this->host = $conf['MYSQL_HOST'];
    $this->dbname = $conf['MYSQL_DATABASE'];

    if (!$this->user || !$this->pass || !$this->host || !$this->dbname) {
      print_r($conf);
      die('MYSQL: Invalid Configuration'.PHP_EOL);
    }
  }

  public function getDns() {
    return "mysql:host=" . $this->host . ';dbname=' . $this->dbname;
  }

  protected function createUpsertArgs($year, $type, $r) {
    return array_merge(
      parent::createUpsertArgs($year, $type, $r),
      array(
        ':lastUpdated2' => (new DateTime())->getTimestamp(),
        ':code2' => $r->code  ,
        ':measurePdfUrl2' => $r->measurePdfUrl,
        ':measureArchiveUrl2' => $r->measureArchiveUrl,
        ':measureTitle2' => $r->measureTitle,
        ':reportTitle2' => $r->reportTitle,
        ':bitAppropriation2' => $r->bitAppropriation,
        ':description2' => $r->description,
        ':status2' => $r->status,
        ':introducer2' => $r->introducer,
        ':currentReferral2' => $r->currentReferral,
        ':companion2' => $r->companion,
      )
    );
  }
}

?>
