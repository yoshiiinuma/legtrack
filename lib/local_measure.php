<?php
namespace legtrack;
use \PDO;

require_once 'lib/db_base.php';

class LocalMeasure extends DbBase {
  private $dns;
  private $path;

//  const DROP_MEASURES_TABLE_SQL = "DROP TABLE IF EXISTS measures;";
//
//  const CREATE_MEASURES_TABLE_SQL = <<<HERE
//    CREATE TABLE IF NOT EXISTS measures
//    (
//      year smallint NOT NULL,
//      measureType nchar(3) NOT NULL,
//      measureNumber smallint NOT NULL,
//      lastUpdated datetime,
//      code nvarchar(64),
//      measurePdfUrl nvarchar(512),
//      measureArchiveUrl nvarchar(512),
//      measureTitle nvarchar(512),
//      reportTitle nvarchar(512),
//      bitAppropriation tinyint,
//      description nvarchar(1024),
//      status nvarchar(512),
//      introducer nvarchar(512),
//      currentReferral nvarchar(256),
//      companion nvarchar(256),
//      PRIMARY KEY (year, measureType, measureNumber)
//    );
//HERE;
//
//  const CREATE_MEASURES_INDEX_SQL = <<<HERE
//    CREATE INDEX measures_lastupdated_idx ON measures(lastUpdated);
//HERE;
//
//  const SELECT_MEASURE_SQL = <<<HERE
//     SELECT * FROM measures
//      WHERE year = :year
//        AND measureType = :measureType
//        AND measureNumber = :measureNumber;
//HERE;
//
//  const SELECT_UPDATED_SQL = <<<HERE
//     SELECT * FROM measures
//      WHERE lastUpdated > :lastUpdated;
//HERE;
//
//  const UPDATE_MEASURE_SQL = <<<HERE
//     UPDATE measures
//       SET lastUpdated = :lastUpdated,
//           code = :code,
//           measurePdfUrl = :measurePdfUrl,
//           measureArchiveUrl = :measureArchiveUrl,
//           measureTitle = :measureTitle,
//           reportTitle = :reportTitle,
//           bitAppropriation = :bitAppropriation,
//           description = :description,
//           status = :status,
//           introducer = :introducer,
//           currentReferral = :currentReferral,
//           companion = :companion
//       WHERE year = :year AND measureType = :measureType AND measureNumber = :measureNumber;
//HERE;
//
//  const INSERT_MEASURE_SQL = <<<HERE
//     INSERT INTO measures (
//        measureType, year, measureNumber, lastUpdated, code, measurePdfUrl,
//        measureArchiveUrl, measureTitle, reportTitle, bitAppropriation,
//        description, status, introducer, currentReferral, companion)
//     VALUES (
//        :measureType, :year, :measureNumber, :lastUpdated, :code, :measurePdfUrl,
//        :measureArchiveUrl, :measureTitle, :reportTitle, :bitAppropriation,
//        :description, :status, :introducer, :currentReferral, :companion)
//HERE;

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
