<?php
if ($db = new SQLite3('dfsindex_db')) { 
   $db->query('CREATE TABLE atj_dfs_index ( file_id  INTEGER PRIMARY KEY,
file_name TEXT not NULL, dir_name TEXT,
file_size int, file_mtime TEXT,
file_owner TEXT,
key TEXT,   		
last_update TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
   
   //KEY `atj_dfs_dirname_idx` (`dir_name`(1000)),
   //KEY `atj_dfs_filename_idx` (`file_name`)
   
   $db->query('CREATE INDEX atj_dfs_dirname_idx ON atj_dfs_index (dir_name)');
   $db->query('CREATE INDEX atj_dfs_filename_idx ON atj_dfs_index (file_name)');


   $db->query('CREATE TABLE IF NOT EXISTS global_table (
		plugin varchar(40) NOT NULL,
		global_key varchar(40) NOT NULL,
		data varchar(4000) NOT NULL,
		last_set TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (plugin,global_key))');   
   
} else {
    die($sqliteerror);
}
   		
?>