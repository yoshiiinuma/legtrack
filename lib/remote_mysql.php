<?php
namespace legtrack;
use \PDO;
use \DateTime;

require_once './lib/db_base.php';

class RemoteMysql extends DbBase {
  private $host;
  private $dbname;

  const CREATE_HEARINGS_TABLE_SQL = <<<HERE
    CREATE TABLE IF NOT EXISTS hearings
    (
      year smallint NOT NULL,
      measureType char(4) NOT NULL,
      measureNumber smallint NOT NULL,
      measureRelativeUrl varchar(512),
      code varchar(64),
      committee varchar(256),
      lastUpdated int unsigned,
      timestamp int unsigned,
      datetime varchar(32),
      description varchar(512),
      room varchar(32),
      notice varchar(128),
      noticeUrl varchar(512),
      noticePdfUrl varchar(512),
      UNIQUE (year, measureType, measureNumber, notice)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
HERE;

  const CREATE_MEASURES_TABLE_SQL = <<<HERE
    CREATE TABLE IF NOT EXISTS measures
    (
      id int unsigned NOT NULL AUTO_INCREMENT,
      year smallint NOT NULL,
      measureType char(3) NOT NULL,
      measureNumber smallint NOT NULL,
      lastUpdated int unsigned,
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

  const UPSERT_HEARING_SQL = <<<HERE
     INSERT INTO hearings (
        year, measureType, measureNumber, measureRelativeUrl, code,
        committee, lastUpdated, timestamp, datetime, description,
        room, notice, noticeUrl, noticePdfUrl)
     VALUES (
        :year, :measureType, :measureNumber, :measureRelativeUrl, :code,
        :committee, :lastUpdated, :timestamp, :datetime, :description,
        :room, :notice, :noticeUrl, :noticePdfUrl)
     ON DUPLICATE KEY UPDATE
        year = :year2,
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

  public function getDsn() {
    return "mysql:host=" . $this->host . ';dbname=' . $this->dbname;
  }

  protected function createUpsertMeasureArgs($year, $type, $r) {
    return array_merge(
      parent::createUpsertMeasureArgs($year, $type, $r),
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

  protected function createUpsertHearingArgs($r) {
    return array_merge(
      parent::createUpsertHearingArgs($r),
      array(
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
        ':noticeUrl2' => $r->noticeUrl,
        ':noticePdfUrl2' => $r->noticePdfUrl,
      )
    );
  }
}

?>
