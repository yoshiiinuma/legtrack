#!/bin/bash

SERVER="bcckof7fgq.database.usgovcloudapi.net"
DB="LegTrack"
USER="lts"
PASS="P@ssword#1"
#DSN="sqlsrv:server = tcp:bcckof7fgq.database.usgovcloudapi.net,1433; Database = LegTrack"
DSN="tcp:bcckof7fgq.database.usgovcloudapi.net,1433"
DEST="BKUP_LEGTRACK_MEASURES_20181003.dat"

#mssql-scripter -S $SERVER -d $DB -U $USER -P $PASS --data-only --target-server-version AzureDB --include-objects measures

#bcp measures out $DEST -S $SERVER -d $DB -U $USER -P $PASS -n 
bcp measures out $DEST -S $SERVER -d $DB -U $USER -P $PASS -n 
