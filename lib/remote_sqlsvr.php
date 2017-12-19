<?php
namespace legtrack;
use \PDO;
use \DateTime;

require_once 'lib/db_base.php';

class RemoteSqlsvr extends DbBase {
  private $dsn;
  private $dbname;

//  const DROP_MEASURES_TABLE_SQL = "DROP TABLE IF EXISTS measures;";

  const DROP_MEASURES_TABLE_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='measures' AND xtype='U')
      DROP TABLE measures
HERE;

  const CREATE_MEASURES_TABLE_SQL = <<<HERE
    IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='measures' AND xtype='U')
      CREATE TABLE measures
      (
        id int identity(1,1) NOT NULL PRIMARY KEY,
        year smallint NOT NULL,
        measureType nchar(3) NOT NULL,
        measureNumber smallint NOT NULL,
        lastUpdated datetime,
        code nvarchar(64),
        measurePdfUrl nvarchar(512),
        measureArchiveUrl nvarchar(512),
        measureTitle nvarchar(512),
        reportTitle nvarchar(512),
        bitAppropriation tinyint,
        description nvarchar(1024),
        status nvarchar(512),
        introducer nvarchar(512),
        currentReferral nvarchar(256),
        companion nvarchar(256),
        UNIQUE (year, measureType, measureNumber)
      )
HERE;

  const UPSERT_MEASURE_SQL = <<<HERE
    IF EXISTS (SELECT 1 FROM measures WHERE year = :year1 AND measureType = :measureType1 AND measureNumber = :measureNumber1)
      UPDATE measures
        SET lastUpdated = :lastUpdated2,
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
        WHERE year = :year2
          AND measureType = :measureType2
          AND measureNumber = :measureNumber2
    ELSE
      INSERT INTO measures (
        measureType, year, measureNumber, lastUpdated, code, measurePdfUrl,
        measureArchiveUrl, measureTitle, reportTitle, bitAppropriation,
        description, status, introducer, currentReferral, companion)
      VALUES (
        :measureType, :year, :measureNumber, :lastUpdated, :code, :measurePdfUrl,
        :measureArchiveUrl, :measureTitle, :reportTitle, :bitAppropriation,
        :description, :status, :introducer, :currentReferral, :companion)
HERE;

  public function configure($conf) {
    $this->user = $conf['SQLSVR_USER'];
    $this->pass = $conf['SQLSVR_PASS'];
    $this->dsn = $conf['SQLSVR_DSN'];
    $this->dbname = $conf['SQLSVR_DATABASE'];

    if (!$this->user || !$this->pass || !$this->dsn || !$this->dbname) {
      print_r($conf);
      die('SQLSVR: Invalid Configuration'.PHP_EOL);
    }
  }

  public function getDns() {
    return $this->dsn;
  }

  protected function createUpsertArgs($year, $type, $r) {
    return array_merge(
      parent::createUpsertArgs($year, $type, $r),
      array(
        ':measureType1' => $type,
        ':year1' => $year,
        ':measureNumber1' => $r->measureNumber,
        ':measureType2' => $type,
        ':year2' => $year,
        ':measureNumber2' => $r->measureNumber,
        //':lastUpdated2' => Date("Y-m-d H:i:s"),
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
