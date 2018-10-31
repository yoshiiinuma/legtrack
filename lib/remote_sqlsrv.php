<?php
namespace legtrack;
use \PDO;
use \DateTime;

require_once 'lib/db_base.php';

class RemoteSqlsrv extends DbBase {
  private $dsn;
  private $dbname;

  const DROP_POSITION_INSERT_TRIGGER = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='positionInsertTrigger' AND xtype='TR')
      DROP TRIGGER positionInsertTrigger
HERE;

  const CREATE_POSITION_INSERT_TRIGGER = <<<HERE
    CREATE TRIGGER positionInsertTrigger ON positions
    INSTEAD OF INSERT
    AS
    BEGIN
      DECLARE @now DATETIME = GETDATE()
      INSERT INTO positions
                  (year, deptId, measureId, groupId,
                  [role], category, position, approvalStatus, status, assignedTo,
                  version, createdBy, createdAt, modifiedBy, modifiedAt)
           SELECT year, deptId, measureId, groupId,
                  [role], category, position, approvalStatus, status, assignedTo,
                  NEWID(), createdBy, @now, modifiedBy, @now
             FROM INSERTED
    END
HERE;

  const DROP_TRACKEDMEASURE_INSERT_TRIGGER = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='trackedMeasureInsertTrigger' AND xtype='TR')
      DROP TRIGGER trackedMeasureInsertTrigger
HERE;

  const CREATE_TRACKEDMEASURE_INSERT_TRIGGER = <<<HERE
    CREATE TRIGGER trackedMeasureInsertTrigger ON trackedMeasures
    INSTEAD OF INSERT
    AS
    BEGIN
      DECLARE @now DATETIME = GETDATE()
      INSERT INTO trackedMeasures
                  (measureId, year, deptId, tracked,
                  billProgress, scrNo, adminBill, dead, confirmed, passed, ccr,
                  appropriation, appropriationAmount, report, directorAttention,
                  govMsgNo, dateToGov, actDate, actNo, reportingRequirement, reportDueDate,
                  sectionsAffected, effectiveDate, veto, vetoDate, vetoOverride, vetoOverrideDate,
                  finalBill, version, createdBy, createdAt, modifiedBy, modifiedAt)
           SELECT measureId, year, deptId, tracked,
                  billProgress, scrNo, adminBill, dead, confirmed, passed, ccr,
                  appropriation, appropriationAmount, report, directorAttention,
                  govMsgNo, dateToGov, actDate, actNo, reportingRequirement, reportDueDate,
                  sectionsAffected, effectiveDate, veto, vetoDate, vetoOverride, vetoOverrideDate,
                  finalBill, NEWID(), createdBy, @now, modifiedBy, @now
             FROM INSERTED
    END
HERE;

  const DROP_TRACKING_DEPTS_DELETE_TRIGGER = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='trackingDeptsTriggerOnDelete' AND xtype='TR')
      DROP TRIGGER trackingDeptsTriggerOnDelete
HERE;

  const CREATE_TRACKING_DEPTS_DELETE_TRIGGER = <<<HERE
    CREATE TRIGGER trackingDeptsTriggerOnDelete ON trackedMeasures
    AFTER DELETE
    AS
    BEGIN
      SET NOCOUNT ON
      UPDATE measures
      SET trackingDepts = y.trackingDepts
      FROM measures m
      INNER JOIN (
        SELECT x.id, (
          SELECT ',' + CAST (t.deptId as nvarchar(12))
          FROM trackedMeasures t
          WHERE t.tracked = 1
            AND t.measureId = x.id
          ORDER By t.deptId
          FOR XML PATH('')
        ) as trackingDepts
        FROM measures x
        WHERE x.id in (SELECT measureId FROM DELETED GROUP BY measureId)
      ) y ON m.id = y.Id
    END
HERE;

  const DROP_TRACKING_DEPTS_TRIGGER = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='trackingDeptsTrigger' AND xtype='TR')
      DROP TRIGGER trackingDeptsTrigger
HERE;

  const CREATE_TRACKING_DEPTS_TRIGGER = <<<HERE
    CREATE TRIGGER trackingDeptsTrigger ON trackedMeasures
    AFTER INSERT, UPDATE
    AS
    BEGIN
      SET NOCOUNT ON
      IF UPDATE (tracked)
      BEGIN
        UPDATE measures
        SET trackingDepts = y.trackingDepts
        FROM measures m
        INNER JOIN (
          SELECT x.id, (
            SELECT ',' + CAST (t.deptId as nvarchar(12))
            FROM trackedMeasures t
            WHERE t.tracked = 1
              AND t.measureId = x.id
            ORDER By t.deptId
            FOR XML PATH('')
          ) as trackingDepts
          FROM measures x
          WHERE x.id in (SELECT measureId FROM INSERTED GROUP BY measureId)
        ) y ON m.id = y.Id
      END
    END
HERE;

  const DROP_GROUPMEMBER_VIEW_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='groupMemberView' AND xtype='V')
      DROP VIEW groupMemberView
HERE;

  const CREATE_GROUPMEMBER_VIEW_SQL = <<<HERE
    CREATE VIEW groupMemberView AS
    SELECT m.userId, m.groupId, g.deptId, d.deptName, g.groupName, u.displayName, u.userPrincipalName, m.roleId, r.title, r.permission
      FROM groupMembers m
      JOIN users u ON u.id = m.userId
      JOIN groups g ON g.id = m.groupId
      JOIN roles r ON r.id = m.roleId
      JOIN depts d ON d.id = g.deptId
HERE;

  const DROP_GROUP_VIEW_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='groupView' AND xtype='V')
      DROP VIEW groupView
HERE;

  const CREATE_GROUP_VIEW_SQL = <<<HERE
    CREATE VIEW groupView AS
    SELECT g.id, g.deptID, d.deptName, g.groupName, g.description
      FROM groups g
      JOIN depts d ON d.id = g.deptId
HERE;

  const DROP_POSITION_VIEW_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='positionView' AND xtype='V')
      DROP VIEW positionView
HERE;

  const CREATE_POSITION_VIEW_SQL = <<<HERE
    CREATE VIEW positionView AS
    SELECT p.id as positionId, t.id as trakedMeasureId, t.measureId, t.year, t.deptId, p.groupId,
           t.tracked, p.role, p.category, p.position, p.approvalStatus, p.status as testimonyStatus, p.assignedTo,
           t.billId, t.measureType, t.measureNumber, t.code, t.measurePdfUrl, t.measureArchiveUrl,
           t.measureTitle, t.reportTitle, t.bitAppropriation, t.description, t.measureStatus,
           t.introducer, t.committee, t.companion,
           t.billProgress, t.scrNo, t.adminBill, t.dead, t.confirmed, t.passed, t.ccr,
           t.appropriation, t.appropriationAmount, t.report, t.directorAttention,
           t.govMsgNo, t.dateToGov, t.actDate, t.actNo, t.reportingRequirement, t.reportDueDate,
           t.sectionsAffected, t.effectiveDate, t.veto, t.vetoDate, t.vetoOverride, t.vetoOverrideDate,
           t.finalBill, t.version as trackedMeasureVersion, p.version as positionVersion
      FROM trackedMeasureView t
      JOIN positions p ON p.year = t.year
                      AND p.deptId = t.deptId
                      AND p.measureId = t.measureId
HERE;

  const DROP_TRACKEDMEASURE_VIEW_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='trackedMeasureView' AND xtype='V')
      DROP VIEW trackedMeasureView
HERE;

  const CREATE_TRACKEDMEASURE_VIEW_SQL = <<<HERE
    CREATE VIEW trackedMeasureView AS
    SELECT t.id, t.measureId, m.year, t.deptId, t.tracked,
           CONCAT(TRIM(m.measureType), RIGHT('00000' + CAST(m.measureNumber as nvarchar(5)), 5)) as billId,
           m.measureType, m.measureNumber, m.code, m.measurePdfUrl, m.measureArchiveUrl,
           m.measureTitle, m.reportTitle, m.bitAppropriation, m.description, m.status as measureStatus,
           m.introducer, m.currentReferral as committee, m.companion,
           t.billProgress, t.scrNo, t.adminBill, t.dead, t.confirmed, t.passed, t.ccr,
           t.appropriation, t.appropriationAmount, t.report, t.directorAttention,
           t.govMsgNo, t.dateToGov, t.actDate, t.actNo, t.reportingRequirement, t.reportDueDate,
           t.sectionsAffected, t.effectiveDate, t.veto, t.vetoDate, t.vetoOverride, t.vetoOverrideDate,
           t.finalBill, t.version
      FROM trackedMeasures t
      JOIN measures m ON m.id = t.measureId
HERE;

  const DROP_MEASURE_VIEW_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='measureView' AND xtype='V')
      DROP VIEW measureView
HERE;

  const CREATE_MEASURE_VIEW_SQL = <<<HERE
    CREATE VIEW measureView AS
    SELECT m.id, m.year,
           CONCAT(TRIM(m.measureType), RIGHT('00000' + CAST(m.measureNumber as nvarchar(5)), 5)) as billId,
           m.measureType, m.measureNumber, m.code, m.measurePdfUrl, m.measureArchiveUrl,
           m.measureTitle, m.reportTitle, m.bitAppropriation, m.description, m.status,
           m.introducer, m.currentReferral as committee, m.companion,
           (SELECT ',' + CAST(t.deptId as nvarchar(12))
              FROM trackedMeasures t
              WHERE t.tracked = 1 AND t.measureId = m.id
              ORDER BY t.deptId
              FOR XML PATH('')
           )  as trackedBy
      FROM measures m
HERE;

  const CREATE_MEASURE_FULLTEXT_INDEX_SQL = <<<HERE
    CREATE FULLTEXT CATALOG CatalogMeasures;
    CREATE FULLTEXT INDEX ON measures (description, reportTitle, measureTitle, status, introducer, currentReferral, companion)
           KEY INDEX PK_measures ON CatalogMeasures;
HERE;

  const DROP_MEASURE_FULLTEXT_INDEX_SQL = <<<HERE
    DROP FULLTEXT INDEX ON measures;
    DROP FULLTEXT CATAGLOG CatalogMeasures;
HERE;

  const DROP_TRACKEDMEASURE_TOTAL_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='trackedMeasureTotal' AND xtype='P')
      DROP PROCEDURE trackedMeasureTotal
HERE;

  const CREATE_TRACKEDMEASURE_TOTAL_SQL = <<<HERE
    CREATE PROCEDURE trackedMeasureTotal
      (
        @size INT,
        @year INT,
        @deptId INT
      )
      AS
      BEGIN
        SELECT count(*) AS records, count(*) / @size + 1 AS pages
          FROM trackedMeasures t
          JOIN measures m ON m.id = t.measureId
                         AND t.deptId = @deptId
                         AND t.tracked = 0
         WHERE t.year = @year
      END
HERE;

  const DROP_MEASURE_TOTAL_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='measureTotal' AND xtype='P')
      DROP PROCEDURE measureTotal
HERE;

  const CREATE_MEASURE_TOTAL_SQL = <<<HERE
    CREATE PROCEDURE measureTotal
      (
        @size INT,
        @year INT,
        @deptId INT,
        @keywords NVARCHAR(256)
      )
      AS
      BEGIN
        DECLARE @sql NVARCHAR(1000);
        DECLARE @params NVARCHAR(500);
        SET @sql = 'SELECT count(*) AS records, count(*) / @size + 1 AS pages' +
                    ' FROM measures m' +
                    ' LEFT JOIN trackedMeasures t ON m.id = t.measureId' +
                                               ' AND t.year = @year' +
                                               ' AND t.deptId = @deptId' +
                   ' WHERE m.year = @year';
        IF (@keywords is NOT NULL AND LEN(@keywords) > 0)
          SET @sql +=    ' AND CONTAINS((m.description, m.measureTitle, m.reportTitle, m.status, m.introducer, m.currentReferral, m.companion), @keywords)';
        SET @params = '@size INT, @year INT, @deptId INT, @keywords NVARCHAR(256)';
        EXECUTE sp_executesql @sql, @params, @size, @year, @deptId, @keywords;
      END
HERE;


  const DROP_POSITION_PAGE_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='positionPage' AND xtype='P')
      DROP PROCEDURE positionPage
HERE;

  const CREATE_POSITION_PAGE_SQL = <<<HERE
    CREATE PROCEDURE positionPage
      (
        @page INT,
        @size INT,
        @year INT,
        @deptId INT
      )
      AS
      BEGIN
        SELECT count(*) AS records, count(*) / @size + 1 AS pages
          FROM trackedMeasures t
          LEFT JOIN positions p ON t.year = @year
                               AND t.deptId = @deptId
                               AND p.year = t.year
                               AND p.deptId = t.deptId
                               AND p.measureId = t.measureId
         WHERE t.year = @year
           AND t.deptId = @deptId;

        SELECT t.id as trakedMeasureId, t.measureId, t.tracked, t.year, t.deptId,
               p.groupId, p.id as positionId,
               CONCAT(TRIM(m.measureType), RIGHT('00000' + CAST(m.measureNumber as nvarchar(5)), 5)) as billId,
               m.measureType, m.measureNumber, m.code, m.measurePdfUrl, m.measureArchiveUrl,
               m.measureTitle, m.reportTitle, m.bitAppropriation, m.description, m.status as measureStatus,
               m.introducer, m.currentReferral as committee, m.companion,
               t.billProgress, t.scrNo, t.adminBill, t.dead, t.confirmed, t.passed, t.ccr,
               t.appropriation, t.appropriationAmount, t.report, t.directorAttention,
               t.govMsgNo, t.dateToGov, t.actDate, t.actNo, t.reportingRequirement, t.reportDueDate,
               t.sectionsAffected, t.effectiveDate, t.veto, t.vetoDate, t.vetoOverride, t.vetoOverrideDate, t.finalBill,
               p.role, p.category, p.position, p.approvalStatus, p.status as testimonyStatus, p.assignedTo,
               t.version as trackedMeasureVersion, p.version as positionVersion
          FROM trackedMeasures t
          LEFT JOIN positions p ON t.year = @year
                               AND t.deptId = @deptId
                               AND p.year = t.year
                               AND p.deptId = t.deptId
                               AND p.measureId = t.measureId
          JOIN measures m ON m.id = t.measureId
         WHERE t.year = @year
           AND t.deptId = @deptId
         ORDER BY t.year, t.deptId, t.measureId
        OFFSET @size * (@page - 1) ROWS
         FETCH NEXT @size ROWS ONLY;
      END
HERE;



  const DROP_TRACKEDMEASURE_PAGE_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='trackedMeasurePage' AND xtype='P')
      DROP PROCEDURE trackedMeasurePage
HERE;

  const CREATE_TRACKEDMEASURE_PAGE_SQL = <<<HERE
    CREATE PROCEDURE trackedMeasurePage
      (
        @page INT,
        @size INT,
        @year INT,
        @deptId INT
      )
      AS
      BEGIN
        SELECT count(*) AS records, count(*) / @size + 1 AS pages
          FROM trackedMeasures t
          JOIN measures m ON m.id = t.measureId
                         AND t.year = @year
                         AND t.deptId = @deptId
         WHERE t.year = @year
           AND t.deptId = @deptId;

        SELECT t.id as trakedMeasureId, t.measureId, t.tracked, t.year, t.deptId,
               CONCAT(TRIM(m.measureType), RIGHT('00000' + CAST(m.measureNumber as nvarchar(5)), 5)) as billId,
               m.measureType, m.measureNumber, m.code, m.measurePdfUrl, m.measureArchiveUrl,
               m.measureTitle, m.reportTitle, m.bitAppropriation, m.description, m.status,
               m.introducer, m.currentReferral as committee, m.companion,
               t.billProgress, t.scrNo, t.adminBill, t.dead, t.confirmed, t.passed, t.ccr,
               t.appropriation, t.appropriationAmount, t.report, t.directorAttention,
               t.govMsgNo, t.dateToGov, t.actDate, t.actNo, t.reportingRequirement, t.reportDueDate,
               t.sectionsAffected, t.effectiveDate, t.veto, t.vetoDate, t.vetoOverride, t.vetoOverrideDate,
               t.finalBill, t.version
          FROM trackedMeasures t
          JOIN measures m ON m.id = t.measureId
                         AND t.year = @year
                         AND t.deptId = @deptId
         WHERE t.year = @year
           AND t.deptId = @deptId
         ORDER BY t.year, t.deptId, t.measureId
        OFFSET @size * (@page - 1) ROWS
         FETCH NEXT @size ROWS ONLY;
      END
HERE;

  const CREATE_TRACKEDMEASURE_PAGE_SQL_OLD = <<<HERE
    CREATE PROCEDURE trackedMeasurePage
      (
        @page INT,
        @size INT,
        @year INT,
        @deptId INT
      )
      AS
      BEGIN
        SELECT t.*,
               CONCAT(TRIM(m.measureType), RIGHT('00000' + CAST(m.measureNumber as nvarchar(5)), 5)) as billId,
               m.measureType, m.measureNumber, m.code, m.measurePdfUrl, m.measureArchiveUrl,
               m.measureTitle, m.reportTitle, m.bitAppropriation, m.description, m.status,
               m.introducer, m.currentReferral as committee, m.companion
          FROM trackedMeasures t
          JOIN measures m ON m.id = t.measureId
                         AND t.deptId = @deptId
                         AND t.tracked = 1
         WHERE t.year = @year
         ORDER BY t.year, t.deptId, t.measureId
        OFFSET @size * (@page - 1) ROWS
         FETCH NEXT @size ROWS ONLY;
      END
HERE;

  const DROP_MEASURE_SEARCH_PAGE_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='measureSearchPage' AND xtype='P')
      DROP PROCEDURE measureSearchPage
HERE;

  const CREATE_MEASURE_SEARCH_PAGE_SQL = <<<HERE
    CREATE PROCEDURE measureSearchPage
      (
        @page INT,
        @size INT,
        @year INT,
        @deptId INT,
        @keywords NVARCHAR(256)
      )
      AS
      BEGIN
        DECLARE @sql NVARCHAR(1000);
        DECLARE @params NVARCHAR(500);

        SET @sql = 'SELECT count(*) AS records, count(*) / @size + 1 AS pages' +
                    ' FROM measures m' +
                    ' LEFT JOIN trackedMeasures t ON m.id = t.measureId' +
                                               ' AND t.year = @year' +
                                               ' AND t.deptId = @deptId' +
                   ' WHERE m.year = @year';
        IF (@keywords is NOT NULL AND LEN(@keywords) > 0)
          SET @sql +=    ' AND CONTAINS((m.description, m.measureTitle, m.reportTitle, m.status, m.introducer, m.currentReferral, m.companion), @keywords)';
        SET @params = '@size INT, @year INT, @deptId INT, @keywords NVARCHAR(256)';
        EXECUTE sp_executesql @sql, @params, @size, @year, @deptId, @keywords;

        SET @sql = ' SELECT m.id,' +
                          ' CONCAT(TRIM(m.measureType), RIGHT(''00000'' + CAST(m.measureNumber as nvarchar(5)), 5)) as billId,' +
                          ' m.measureType, m.measureNumber, m.code, m.measurePdfUrl, m.measureArchiveUrl,' +
                          ' m.measureTitle, m.reportTitle, m.bitAppropriation, m.description, m.status,' +
                          ' m.introducer, m.currentReferral as committee, m.companion, ISNULL(t.tracked, 0) as tracked' +
                     ' FROM measures m' +
                     ' LEFT JOIN trackedMeasures t ON m.id = t.measureId' +
                                                ' AND t.year = @year' +
                                                ' AND t.deptId = @deptId' +
                    ' WHERE m.year = @year';
        IF (@keywords is NOT NULL AND LEN(@keywords) > 0)
          SET @sql += ' AND CONTAINS((m.description, m.measureTitle, m.reportTitle, m.status, m.introducer, m.currentReferral, m.companion), @keywords)';
        SET @sql += ' ORDER BY m.id ' +
                   ' OFFSET @size * (@page - 1) ROWS' +
                    ' FETCH NEXT @size ROWS ONLY;';
        SET @params = '@page INT, @size INT, @year INT, @deptId INT, @keywords NVARCHAR(256)';
        EXECUTE sp_executesql @sql, @params, @page, @size, @year, @deptId, @keywords;
      END
HERE;

  const DROP_MEASURE_PAGE_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='measurePage' AND xtype='P')
      DROP PROCEDURE measurePage
HERE;

  const CREATE_MEASURE_PAGE_SQL = <<<HERE
    CREATE PROCEDURE measurePage
      (
        @page INT,
        @size INT,
        @year INT,
        @deptId INT
      )
      AS
      BEGIN
        SELECT m.id,
               CONCAT(TRIM(measureType), RIGHT('00000' + CAST(measureNumber as nvarchar(5)), 5)) as billId,
               m.measureType, m.measureNumber, m.code, m.measurePdfUrl, m.measureArchiveUrl,
               m.measureTitle, m.reportTitle, m.bitAppropriation, m.description, m.status,
               m.introducer, m.currentReferral as committee, m.companion, ISNULL(t.tracked, 0) as tracked
          FROM measures m
          LEFT JOIN trackedMeasures t ON m.id = t.measureId
                                     AND t.year = @year
                                     AND t.deptId = @deptId
         WHERE m.year = @year
        ORDER BY m.id
        OFFSET @size * (@page - 1) ROWS
         FETCH NEXT @size ROWS ONLY;
      END
HERE;





  const DROP_DEPTS_TABLE_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='depts' AND xtype='U')
      DROP TABLE depts
HERE;

  const CREATE_DEPTS_TABLE_SQL = <<<HERE
    IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='depts' AND xtype='U')
      CREATE TABLE depts
      (
        id smallint,
        deptName nchar(4) NOT NULL,
        CONSTRAINT PK_depts PRIMARY KEY CLUSTERED (id)
      )
      INSERT INTO depts (id, deptName) VALUES (1,'ADM'), (2,'AGR'), (3,'AGS'), (4,'BED'), (5,'BUF'),
         (6,'DEF'), (7,'ETS'), (8,'GOV'), (9,'LBR'), (10,'LNR'), (11,'OIP'), (12,'PSD'), (13,'TRN')
HERE;

  const DROP_ROLES_TABLE_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='roles' AND xtype='U')
      DROP TABLE roles
HERE;

  const CREATE_ROLES_TABLE_SQL = <<<HERE
    IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='roles' AND xtype='U')
      CREATE TABLE roles
      (
        id tinyint,
        title nvarchar(15) NOT NULL,
        permission tinyint NOT NULL,
        CONSTRAINT PK_roles PRIMARY KEY CLUSTERED (id)
      )
      INSERT INTO roles (id, title, permission) VALUES (1,'Admin',8), (2,'Coordinator',4), (3,'Cooperator',2),
        (4,'Approver', 1), (5,'Guest', 0)
HERE;

  const DROP_USERS_TABLE_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='users' AND xtype='U')
      DROP TABLE users
HERE;

  const CREATE_USERS_TABLE_SQL = <<<HERE
    IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='users' AND xtype='U')
      CREATE TABLE users
      (
        id int identity(1,1),
        deptId smallint NOT NULL FOREIGN KEY REFERENCES depts(id),
        objectId varchar(128),
        userPrincipalName nvarchar(256) NOT NULL,
        displayName nvarchar(128) NOT NULL,
        department nvarchar(128),
        CONSTRAINT PK_users PRIMARY KEY CLUSTERED (id)
      )
HERE;

  const DROP_GROUPS_TABLE_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='groups' AND xtype='U')
      DROP TABLE groups
HERE;

  const CREATE_GROUPS_TABLE_SQL = <<<HERE
    IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='groups' AND xtype='U')
      CREATE TABLE groups
      (
        id int identity(1,1),
        deptId smallint NOT NULL FOREIGN KEY REFERENCES depts(id),
        groupName nvarchar(64) NOT NULL,
        description nvarchar(512),
        CONSTRAINT PK_groups PRIMARY KEY CLUSTERED (id),
        INDEX IX_groups_dept NONCLUSTERED (deptId, id)
      )
HERE;

  const DROP_GROUPMEMBERS_TABLE_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='groupMembers' AND xtype='U')
      DROP TABLE groupMembers
HERE;

  const CREATE_GROUPMEMBERS_TABLE_SQL = <<<HERE
    IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='groupMembers' AND xtype='U')
      CREATE TABLE groupMembers
      (
        userId int NOT NULL FOREIGN KEY REFERENCES users(id),
        groupId int NOT NULL FOREIGN KEY REFERENCES groups(id),
        roleId tinyint NOT NULL FOREIGN KEY REFERENCES roles(id),
        permission tinyint NOT NULL
        CONSTRAINT PK_groupmembers PRIMARY KEY CLUSTERED (userId, groupId, roleId, permission),
        INDEX IX_groupmembers_by_group NONCLUSTERED (groupId, userId)
      )
HERE;

  const DROP_TRACKEDMEASUERS_TABLE_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='trackedMeasures' AND xtype='U')
      DROP TABLE trackedMeasures
HERE;

  /**
   * billProgress: [ 1st Lateral, 1st Decking, 2nd Lateral, 2nd Decking, 2nd Crossover, Final Decking ]
   *
   **/
  const CREATE_TRACKEDMEASURES_TABLE_SQL = <<<HERE
    IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='trackedMeasures' AND xtype='U')
      CREATE TABLE trackedMeasures
      (
        id int identity(1,1) NOT NULL UNIQUE,
        year smallint NOT NULL,
        deptId smallint NOT NULL FOREIGN KEY REFERENCES depts(id),
        measureId int NOT NULL FOREIGN KEY REFERENCES measures(id),
        tracked bit DEFAULT 1,
        billProgress nvarchar(18),
        scrNo nvarchar(18),
        adminBill bit DEFAULT 0,
        dead bit DEFAULT 0,
        confirmed bit DEFAULT 0,
        passed bit DEFAULT 0,
        ccr bit DEFAULT 0,
        appropriation bit DEFAULT 0,
        appropriationAmount nvarchar(256),
        report bit DEFAULT 0,
        directorAttention bit DEFAULT 0,
        govMsgNo nvarchar(12),
        dateToGov date,
        actNo nvarchar(12),
        actDate date,
        reportingRequirement nvarchar(256),
        reportDueDate nvarchar(12),
        sectionsAffected nvarchar(128),
        effectiveDate date,
        veto bit DEFAULT 0,
        vetoDate date,
        vetoOverride bit DEFAULT 0,
        vetoOverrideDate date,
        finalBill nvarchar(128),
        version nvarchar(128),
        createdBy int,
        createdAt datetime,
        modifiedBy int,
        modifiedAt datetime,
        CONSTRAINT PK_trackedmeasures PRIMARY KEY CLUSTERED (year, deptId, measureId),
        INDEX IX_trackedmeasures NONCLUSTERED (id)
      )
HERE;

  const DROP_POSITIONS_TABLE_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='positions' AND xtype='U')
      DROP TABLE positions
HERE;

  /**
   * CategoryForTestify:      [ Know OutCome, Monitor, Testify ]
   * TrackedMeasureRole:      [ Primay, Secondary ]
   * Position:                [ Support, Oppose, Comments, No Position ]
   * TestimonyStatus:         [ Draft, Final, None ]
   * TestimonyApprovalStatus: [ Approved, Pending ]
   * StaffComments: Archive of routed info
   *
   *
        committee nvarchar(128),         ==> measures (already have)
        draftNo nvarchar(128),           ==> measures (scraper & uploader need to be updated), or TrackedMeasures temporarily
        staffComments ntext,             ==> New Table
        route nvarchar(1024),            ==> No Need
        routeItem nvarchar(1024),
        routedTo int,
        routingInfo ntext,
   **/
  const CREATE_POSITIONS_TABLE_SQL = <<<HERE
    IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='positions' AND xtype='U')
      CREATE TABLE positions
      (
        id int identity(1,1) NOT NULL UNIQUE,
        year smallint NOT NULL,
        deptId smallint NOT NULL FOREIGN KEY REFERENCES depts(id),
        measureId int NOT NULL FOREIGN KEY REFERENCES measures(id),
        groupId int NOT NULL FOREIGN KEY REFERENCES groups(id),
        role nvarchar(12),
        category nvarchar(12),
        position nvarchar(12),
        approvalStatus nvarchar(12),
        status nvarchar(12),
        assignedTo int,
        version nvarchar(128),
        createdBy int,
        createdAt datetime,
        modifiedBy int,
        modifiedAt datetime,
        CONSTRAINT PK_positions PRIMARY KEY CLUSTERED (year, deptId, measureId, groupId),
        INDEX IX_positions NONCLUSTERED (id),
        INDEX IX_positions_by_group NONCLUSTERED (groupId, year, deptId, measureId)
      )
HERE;

  const DROP_COMMENTS_TABLE_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='comments' AND xtype='U')
      DROP TABLE comments
HERE;

  const CREATE_COMMENTS_TABLE_SQL = <<<HERE
    IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='comments' AND xtype='U')
      CREATE TABLE comments
      (
        year smallint NOT NULL,
        positionId int NOT NULL FOREIGN KEY REFERENCES positions(id),
        createtBy int NOT NULL FOREIGN KEY REFERENCES users(id),
        createdAt datetime,
        comment ntext,
        CONSTRAINT PK_comments PRIMARY KEY CLUSTERED (year, positionId, createdAt)
      )
HERE;

  const DROP_POSITION_BOOKMARKS_TABLE_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='positionBookmarks' AND xtype='U')
      DROP TABLE positionBookmarks
HERE;

  const CREATE_POSITION_BOOKMARKS_TABLE_SQL = <<<HERE
    IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='positionBookmarks' AND xtype='U')
      CREATE TABLE positionBookmarks
      (
        year smallint NOT NULL,
        userId int NOT NULL FOREIGN KEY REFERENCES users(id),
        positionId int NOT NULL FOREIGN KEY REFERENCES positions(id),
        CONSTRAINT PK_positionbookmarks PRIMARY KEY CLUSTERED (year, userId, positionId)
      )
HERE;

  const DROP_MEASURE_BOOKMARKS_TABLE_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='measureBookmarks' AND xtype='U')
      DROP TABLE measureBookmarks
HERE;

  const CREATE_MEASURE_BOOKMARKS_TABLE_SQL = <<<HERE
    IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='measureBookmarks' AND xtype='U')
      CREATE TABLE measureBookmarks
      (
        year smallint NOT NULL,
        userId int NOT NULL FOREIGN KEY REFERENCES users(id),
        trackedMeasureId int NOT NULL FOREIGN KEY REFERENCES trackedMeasures(id),
        CONSTRAINT PK_measurebookmarks PRIMARY KEY CLUSTERED (year, userId, trackedMeasureId)
      )
HERE;

  const DROP_HEARINGS_TABLE_SQL = <<<HERE
    IF EXISTS (SELECT * FROM sysobjects WHERE name='hearings' AND xtype='U')
      DROP TABLE hearings
HERE;

  const CREATE_HEARINGS_TABLE_SQL = <<<HERE
    IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='hearings' AND xtype='U')
      CREATE TABLE hearings
      (
        id int identity(1,1),
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
        CONSTRAINT PK_hearings PRIMARY KEY CLUSTERED (id),
        UNIQUE (year, measureType, measureNumber, notice)
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
        id int identity(1,1),
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
        trackingDepts nvarchar(256),
        companion nvarchar(256),
        CONSTRAINT PK_measures PRIMARY KEY CLUSTERED (id),
        CONSTRAINT UQ_measures UNIQUE (year, measureType, measureNumber)
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
    IF EXISTS (SELECT 1 FROM hearings WHERE year = :year1 AND measureType = :measureType1 AND measureNumber = :measureNumber1 AND notice = :notice1)
      UPDATE hearings
        SET measureRelativeUrl = :measureRelativeUrl2,
            code = :code2,
            committee = :committee2,
            lastUpdated = :lastUpdated2,
            timestamp = :timestamp2,
            datetime = :datetime2,
            description = :description2,
            room = :room2,
            noticeUrl = :noticeUrl2,
            noticePdfUrl = :noticePdfUrl2
        WHERE year = :year2
          AND measureType = :measureType2
          AND measureNumber = :measureNumber2
          AND notice = :notice2
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

  const SELECT_TOP100_MEASURES_SQL = <<<HERE
     SELECT TOP 100 * FROM measures;
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
        ':year1' => $r->year,
        ':measureType1' => $r->measureType,
        ':measureNumber1' => $r->measureNumber,
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
        ':notice2' => $r->notice,
        ':noticeUrl2' => $r->noticeUrl,
        ':noticePdfUrl2' => $r->noticePdfUrl,
      )
    );
  }

}

?>
