<?php
if ($db = new SQLite3('dfsindex_db')) { 
   $db->query('CREATE TABLE atj_dfs_index ( file_id  INTEGER PRIMARY KEY NOT NULL,
file_name TEXT not NULL, dir_name TEXT,
file_size int, file_mtime TEXT,
file_owner TEXT,
last_update TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');

} else {
    die($sqliteerror);
}
?>