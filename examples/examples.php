#!/usr/bin/php
<?php
mb_internal_encoding('UTF-8');
ini_set('date.timezone', 'Asia/Tokyo');

include_once('../PDO2FMPXMLRESULT.class.php');

$dbh = new PDO('sqlite:sample.sqlite3' , null, null);

$table_info_stmt = null;
$table_info_stmt = $dbh->query('select * from software;');

$xml = new pdo2fmxml();

// Add meta data
for($i=0; $i<$table_info_stmt->columnCount(); $i++)
{
    $column_data = null;
    $column_data = $table_info_stmt->getColumnMeta($i);
    $xml->addMetaData($column_data['name'], $column_data['sqlite:decl_type']); 
}

// Add records
$foundcount_stmt = null;
$foundcount_stmt = $dbh->query('select count(*) from software;');
$foundCount = $foundcount_stmt->fetchColumn();

for($i=0; $i<$foundCount; $i++)
{
    $xml->addRecordObject($table_info_stmt->fetch(PDO::FETCH_ASSOC)); 
}
// or fetchAll
// $xml->addAllRecordsObject($table_info_stmt->fetchAll(PDO::FETCH_ASSOC)); 

$xml->setPDOError($dbh->errorCode(), $dbh->errorInfo()); 

// Output XML
echo($xml->saveXML());

?>
