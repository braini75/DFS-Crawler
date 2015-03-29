<?php

/**
 * manage DB-objects for DFS-Index
 * 
 * @author Thomas Koch <thomas.koch@clondiag.com
 * @version 1.0
 * @package dfsindex
 */

class dfsindex_db_manage{
    VAR $table_name='atj_dfs_index';
            
    function __construct() {
    }            
    
    function list_all(){
    	$sql="* from ".$this->table_name;
    	return $sql;    	
    	
    }
    
    function show_distinct_path($str="*"){
    	$sql="DISTINCT dir_name from ".$this->table_name." WHERE dir_name LIKE '%".$str."%'";
    	return $sql;
    }
    
    function count_dirs(){
    	$sql="count(DISTINCT dir_name) from ".$this->table_name;
    	return $sql;
    }
    
    function count_files(){
    	$sql="count(1) from ".$this->table_name;
    	return $sql;
    }
    
	function bytes_total(){
		$sql="SUM(file_size) from ".$this->table_name;
    	return $sql;
    }
    
    function get_lastIndexingTime(){
    	$sql="MAX(last_update) from ".$this->table_name;
    	return $sql;
    }
    
	function replaceWildcard($str){
		$str=str_replace("*", "%", $str);
		return $str;	
	}
    
    /**
     * returns select-part of sql-Query for searching all file containing $searchValues['file']
     * 
     * @param array $searchValues
     * @param list $headerCols
     */
    function findFiles($searchValues,$headerCols){
    	$fileStr=mysql_escape_string($this->replaceWildcard($searchValues['file']));
    	
    	$pathStr=mysql_escape_string($this->replaceWildcard($searchValues['path_filter'])); //not yet required -> always do an "%path_filter%" - search
    	
    	$sql=$headerCols." from ".$this->table_name." WHERE dir_name LIKE '%".$pathStr."%' and file_name LIKE '".$fileStr."'";
    	return $sql;    	
    }
    

    function findDuplicateFiles($dup){    	
    	$sql="file_name,file_size, count(1) AS count from ".$this->table_name." GROUP BY file_name,file_size HAVING count>".$dup." ORDER BY count desc";
    	return $sql;    	
    }
    
    
    function checkPathExist($searchValues){
    	$pathStr=mysql_escape_string($this->replaceWildcard($searchValues['path_filter'])); //not yet required -> always do an "%path_filter%" - search    	
    	$sql=" from ".$this->table_name." WHERE dir_name LIKE '%".$pathStr."%'";
    	return $sql;
    }
    
}