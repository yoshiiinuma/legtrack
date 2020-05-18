<?php

namespace legtrack;

require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/lib/remote_sqlsrv.php';

function usage($argv) {
  echo "\nUASGE: php ex-populate-sqlsrv.php <env>\n\n";
  echo "  env: development|test|production\n";
}

if ($argc < 1 || $argc > 2) {
  usage($argv);
  exit();
}

$env = ($argc == 2) ? $argv[1]: 'development';

loadEnv($env);

$db = new RemoteSqlsrv();
$db->configure($GLOBALS);
$db->connect();

/***
 * Depts
 *
      1, ADM
      2, AGR
      3, AGS
      4, BED
      5, BUF
      6, DEF
      7, ETS
      8, GOV
      9, LBR
      10, LNR
      11, OIP
      12, PSD
      13, TRN
  *
  ***/

const INSERT_GROUPS_SQL = <<<HERE
  INSERT INTO groups (deptId, groupName) VALUES
    (1,'SUPER ADMIN'),
    (2,'AGR Coordinator'),
    (3,'AGS Coordinator'),
    (4,'BED Coordinator'),
    (5,'BUF Coordinator'),
    (6,'DEF Coordinator'),
    (7,'ETS Coordinator'),
    (8,'GOV Coordinator'),
    (9,'LBR Coordinator'),
    (10,'LNR Coordinator'),
    (11,'OIP Coordinator'),
    (12,'PSD Coordinator'),
    (13,'TRN Coordinator'),
    (2,'ADC'),
    (2,'ADD'),
    (2,'AGL'),
    (2,'AGR Leg Tracking Members'),
    (2,'AGR Leg Tracking Owners'),
    (2,'AGR Leg Tracking Visitors'),
    (2,'AI'),
    (2,'ARM'),
    (2,'ASO'),
    (2,'CHR'),
    (2,'EARL'),
    (2,'IT Site Admin'),
    (2,'LC'),
    (2,'PEST'),
    (2,'PI'),
    (2,'QAD'),
    (3,'AGS ACCOUNTING'),
    (3,'AGS ARCHIVES'),
    (3,'AGS ASO'),
    (3,'AGS ASO RMO'),
    (3,'AGS AUDIT'),
    (3,'AGS AUTO MGMT'),
    (3,'AGS CAMPAIGN SPENDING'),
    (3,'AGS CENTRAL SERVICES'),
    (3,'AGS COMPTROLLER'),
    (3,'AGS E-911'),
    (3,'AGS ELECTIONS'),
    (3,'AGS ETS'),
    (3,'AGS HAWAII'),
    (3,'AGS KAUAI'),
    (3,'AGS KKCC'),
    (3,'AGS LAND SURVEY'),
    (3,'AGS Leg Tracking Members'),
    (3,'AGS Leg Tracking Owners'),
    (3,'AGS MAUI'),
    (3,'AGS OIP'),
    (3,'AGS PERSONNEL'),
    (3,'AGS PUBLIC WORKS'),
    (3,'AGS SFCA'),
    (3,'AGS SPO'),
    (3,'AGS STADIUM AUTHORITY'),
    (3,'IT Site Admin'),
    (4,'Approvers'),
    (4,'DBEDT-ASO'),
    (4,'DBEDT-ASO-L'),
    (4,'DBEDT-BDSD-L'),
    (4,'DBEDT-BOARDS-L'),
    (4,'DBEDT-CID-L'),
    (4,'DBEDT-DIR-L'),
    (4,'DBEDT-FTZ-L'),
    (4,'DBEDT-HBI-L'),
    (4,'DBEDT-HCDA-L'),
    (4,'DBEDT-HGIA-L'),
    (4,'DBEDT-HHFDC-L'),
    (4,'DBEDT-HSDC-L'),
    (4,'DBEDT-HTA-L'),
    (4,'DBEDT-HTDC-HCATT-L'),
    (4,'DBEDT-HTDC-L'),
    (4,'DBEDT-LEG-ALL'),
    (4,'DBEDT-LEG-TEST'),
    (4,'DBEDT-LUC-L'),
    (4,'DBEDT-NELHA-L'),
    (4,'DBEDT-OAD-L'),
    (4,'DBEDT-OP-L'),
    (4,'DBEDT-READ-L'),
    (4,'DBEDT-SBRRB-L'),
    (4,'DBEDT-SEO-L'),
    (4,'Designers'),
    (4,'DIR'),
    (4,'FTZ'),
    (4,'HCDA'),
    (4,'HHFDC'),
    (4,'Hierarchy Managers'),
    (4,'HSDC'),
    (4,'HTA'),
    (4,'HTDC'),
    (4,'HTDC-HCATT'),
    (4,'IT Site Admin'),
    (4,'LegTrack - BED Members'),
    (4,'LegTrack - BED Owners'),
    (4,'LegTrack - BED Visitors'),
    (4,'LUC'),
    (4,'NELHA'),
    (4,'OP'),
    (4,'Quick Deploy Users'),
    (4,'READ'),
    (4,'Restricted Readers'),
    (4,'SBRRB'),
    (4,'SEO'),
    (4,'Style Resource Readers'),
    (5,'BUF Leg Tracking Members'),
    (5,'BUF Leg Tracking Owners'),
    (5,'DBF.ARO.DOAA'),
    (5,'DBF.BPPM.BRANCH.I.DOAA'),
    (5,'DBF.BPPM.BRANCH.II.DOAA'),
    (5,'DBF.BPPM.DOAA'),
    (5,'DBF.DIROFF.DOAA'),
    (5,'DBF.ERS.DOAA'),
    (5,'DBF.EUTF.DOAA'),
    (5,'DBF.FAD.ADMIN.DOAA'),
    (5,'DBF.FAD.BONDS.DOAA'),
    (5,'DBF.FAD.DOAA'),
    (5,'DBF.FAD.FISCAL.DOAA'),
    (5,'DBF.FAD.TREASURY.DOAA'),
    (5,'DBF.FAD.UP.DOAA'),
    (5,'DBF.OFAM.DOAA'),
    (5,'DBF.OPD.DOAA'),
    (5,'IT Site Admin'),
    (7,'ETS-CIO Exec Team'),
    (7,'ETS-Cybersecurity'),
    (7,'ETS-HawaiiPay'),
    (7,'ETS-HHDC'),
    (7,'ETS-LC'),
    (7,'ETS Leg Tracking Members'),
    (7,'ETS Leg Tracking Owners'),
    (7,'IT Site Admin'),
    (8,'Brandon'),
    (8,'David'),
    (8,'Denise'),
    (8,'Donna'),
    (8,'Ford'),
    (8,'GOV Leg Tracking Owners'),
    (8,'IT Site Admin'),
    (8,'Josh'),
    (8,'Lisa'),
    (8,'Lynette'),
    (8,'Scott'),
    (8,'Sharon'),
    (8,'William'),
    (9,'DCD'),
    (9,'ESARO'),
    (9,'HCRC'),
    (9,'HIOSH'),
    (9,'HLRB'),
    (9,'IT Site Admin'),
    (9,'LBR ASO'),
    (9,'LBR DO'),
    (9,'LBR Leg Tracking Members'),
    (9,'LBR Leg Tracking Owners'),
    (9,'LIRAB'),
    (9,'OCS'),
    (9,'R and S'),
    (9,'UID'),
    (9,'WDC'),
    (9,'WDD'),
    (9,'WSD'),
    (10,'AHA MOKU'),
    (10,'AMEL'),
    (10,'BIN'),
    (10,'BOC'),
    (10,'CO'),
    (10,'CWRM'),
    (10,'DAR'),
    (10,'DEAN A'),
    (10,'DOBOR'),
    (10,'DOCARE'),
    (10,'DOFAW'),
    (10,'ENG'),
    (10,'FIS'),
    (10,'IT'),
    (10,'IT Site Admin'),
    (10,'JOSH'),
    (10,'LAND'),
    (10,'LNR Leg Tracking Owners'),
    (10,'LNR Leg Tracking Visitors'),
    (10,'MSY'),
    (10,'OCCL'),
    (10,'PARKS'),
    (10,'PERS'),
    (10,'PIO'),
    (10,'PUA'),
    (10,'SHPD'),
    (10,'TRACY'),
    (11,'IT Site Admin'),
    (11,'OIP Leg Tracking Members'),
    (11,'OIP Leg Tracking Owners'),
    (12,'Admin Bills'),
    (12,'Administrative Services'),
    (12,'Corrections'),
    (12,'IT Site Admin'),
    (12,'Law Enforcement'),
    (12,'Other'),
    (12,'PSD Leg Tracking Members'),
    (12,'PSD Leg Tracking Owners')
HERE;

const INSERT_USERS_SQL = <<<HERE
  INSERT INTO users (deptId, userPrincipalName, displayName) VALUES
    (1, 'yoshiaki.iinuma@hawaii.gov', 'Iinuma, Yoshiaki'),
    (4, 'jill.sugihara@hawaii.gov', 'Sugihara, Jill')
HERE;

const INSERT_GROUPMEMBERS_SQL = <<<HERE
  INSERT INTO groupMembers (userId, groupId, role, permission) VALUES
    (1, 1, 1, 8),
    (2, 4, 2, 4)
HERE;

$db->query(INSERT_GROUPS_SQL);
$db->query(INSERT_USERS_SQL);
$db->query(INSERT_GROUPMEMBERS_SQL);

?>


