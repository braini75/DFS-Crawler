<?php
if ($db = new SQLite3('dfsindex_db')) { 
    /*sqlite_query($db, 'CREATE TABLE foo (bar varchar(10))');
    sqlite_query($db, "INSERT INTO foo VALUES ('fnord')");
    $result = sqlite_query($db, 'select bar from foo');*/
    
	/*$db->query('CREATE TABLE atj_dfs_index ( file_id  INTEGER PRIMARY KEY NOT NULL,
file_name TEXT not NULL, dir_name TEXT,
file_size int, file_mtime TEXT,
file_owner TEXT,
last_update TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
	
	$db->query("INSERT INTO atj_dfs_index (file_name, dir_name, file_size, file_mtime, file_owner) VALUES ('class_dfsindex_db_manage.php', '/share/HDA_DATA/Web/find/dfsindex/classes', '2250', '2014-12-09 13:34:20', 'braini')");
*/
	$result = $db->query('select * from atj_dfs_index');
	while ($row = $result->fetchArray()) {
		var_dump($row);
		echo "<br>";
	} 
} else {
    die($sqliteerror);
}
?>