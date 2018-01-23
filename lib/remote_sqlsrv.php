<?php
namespace legtrack;
use \PDO;
use \DateTime;

require_once 'lib/db_base.php';

class RemoteSqlsrv extends DbBase {
  private $dsn;
  private $dbname;

  const DROP_HEARINGS_TABLE_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='hearings' AND xtype='U')
      DROP TABLE hearings
HERE;


  const CREATE_HEARINGS_TABLE_SQL = <<<HERE
    IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='hearings' AND xtype='U')
      CREATE TABLE hearings
      (
        year smallint NOT NULL,
        measureType nchar(4) NOT NULL,
        measureNumber smallint NOT NULL,
        measureRelativeUrl nvarchar(512),
        code nvarchar(64),
        committee nvarchar(256),
        lastUpdated int,
        timestamp int,
        datetime nvarchar(32),
        description nvarchar(512),
        room nvarchar(32),
        notice nvarchar(128),
        noticeUrl nvarchar(512),
        noticePdfUrl nvarchar(512),
        UNIQUE (measureType, measureNumber, notice)
      )
HERE;

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
        lastUpdated int,
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

  const UPSERT_HEARING_SQL = <<<HERE
    IF EXISTS (SELECT 1 FROM hearings WHERE notice = :notice1)
      UPDATE hearings
        SET year = :year2,
            measureType = :measureType2,
            measureNumber = :measureNumber2,
            measureRelativeUrl = :measureRelativeUrl2,
            code = :code2,
            committee = :committee2,
            lastUpdated = :lastUpdated2,
            timestamp = :timestamp2,
            datetime = :datetime2,
            description = :description2,
            room = :room2,
            noticeUrl = :noticeUrl2,
            noticePdfUrl = :noticePdfUrl2
        WHERE notice = :notice2
    ELSE
     INSERT INTO hearings (
        year, measureType, measureNumber, measureRelativeUrl, code,
        committee, lastUpdated, timestamp, datetime, description,
        room, notice, noticeUrl, noticePdfUrl)
     VALUES (
        :year, :measureType, :measureNumber, :measureRelativeUrl, :code,
        :committee, :lastUpdated, :timestamp, :datetime, :description,
        :room, :notice, :noticeUrl, :noticePdfUrl)
HERE;

  public function configure($conf) {
    $this->user = $conf['SQLSRV_USER'];
    $this->pass = $conf['SQLSRV_PASS'];
    $this->dsn = $conf['SQLSRV_DSN'];
    $this->dbname = $conf['SQLSRV_DATABASE'];

    if (!$this->user || !$this->pass || !$this->dsn || !$this->dbname) {
      print_r($conf);
      die('SQLSRV: Invalid Configuration'.PHP_EOL);
    }
  }

  public function getDsn() {
    return $this->dsn;
  }

  protected function createUpsertMeasureArgs($year, $type, $r) {
    return array_merge(
      parent::createUpsertMeasureArgs($year, $type, $r),
      array(
        ':measureType1' => $type,
        ':year1' => $year,
        ':measureNumber1' => $r->measureNumber,
        ':measureType2' => $type,
        ':year2' => $year,
        ':measureNumber2' => $r->measureNumber,
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

  protected function createUpsertHearingArgs($r) {
    return array_merge(
      parent::createUpsertHearingArgs($r),
      array(
        ':notice1' => $r->notice,
        ':year2' => $r->year,
        ':measureType2' => $r->measureType,
        ':measureNumber2' => $r->measureNumber,
        ':measureRelativeUrl2' => $r->measureRelativeUrl,
        ':code2' => $r->code,
        ':committee2' => $r->committee,
        ':lastUpdated2' => (new DateTime())->getTimestamp(),
        ':timestamp2' => $r->timestamp,
        ':datetime2' => $r->datetime,
        ':description2' => $r->description,
        ':room2' => $r->room,
        ':description2' => $r->description,
        ':notice2' => $r->notice,
        ':noticeUrl2' => $r->noticeUrl,
        ':noticePdfUrl2' => $r->noticePdfUrl,
      )
    );
  }

}

?>
