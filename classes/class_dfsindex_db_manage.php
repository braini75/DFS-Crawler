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
    	if (!is_array($searchValues)) return false;
    	reset($searchValues);
    	print_r($searchValues);
    	
    	$sql=$headerCols." FROM ".$this->table_name; //." WHERE dir_name LIKE '%".$pathStr."%' and file_name LIKE '".$fileStr."'";
    	
    	$sql   .= ' WHERE ';
    	$komma = NULL;
    	foreach ($searchValues as $key=>$value)  {
    		$sign= " = "; 			//other fields should exact
    		
    		if (strstr($key, "name")){
    			$sign=" LIKE ";		//names will searched with LIKE
    			$value=$this->replaceWildcard($value); 	
    		}
    		$value_new = $sign.'\''. SQLite3::escapeString($value).'\'';
    		$sql   .= $komma . $key . $value_new;
    		$komma = ' AND ';
    	}
    	
    	
    	return $sql;    	
    }
    

    function findDuplicateFiles($dup){    	
    	//$sql="file_name,file_size, count(1) AS count from ".$this->table_name." GROUP BY file_name,file_size HAVING count>".$dup." ORDER BY count desc";
    	$sql="file_id,file_name,file_size, count(1) AS count from ".$this->table_name." GROUP BY file_name,file_size ORDER BY count desc";
    	return $sql;    	
    }
    
    
    function checkPathExist($searchValues){
    	$pathStr=mysql_escape_string($this->replaceWildcard($searchValues['path_filter'])); //not yet required -> always do an "%path_filter%" - search    	
    	$sql=" dir_name from ".$this->table_name." WHERE dir_name LIKE '%".$pathStr."%'";
    	return $sql;
    }
    
}