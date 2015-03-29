<?php
/*
 * Needs more work
 * 
 * For future use of form box ajaxselect as 'Pfadeinschraenkung'
 */
require_once('../../ad_connector.inc');
require_once('../../classes/class_db.php');
require_once('classes/class_dfsindex_db_manage.php');
session_start();




if(!$_REQUEST) return;
$q = trim($_REQUEST['term']);
if ($q==NULL) return;

try {
	$db = new dfsindex_db_manage();
	$sql=$db-> show_distinct_path($q);
	
	$ecmdb_handle = new class_db($_SESSION['config']['mip.work']);
	$ecmdb_handle-> selectquery($sql);
	$result=$ecmdb_handle-> result;
} catch (Exception $e) {
		throw $e;  //weiterwerfen
}

$a_json=array();

while($row = mysqli_fetch_array($result))
{
    //print_r($row);
    echo $row['path_new']."<br>";
    $a_json_row=array();
	$a_json_row["id"]    = $row[0];
	array_push($a_json, $a_json_row);
}

	$json = json_encode($a_json);
	print $json;