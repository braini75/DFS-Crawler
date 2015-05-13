<?php

/**
 * Description of class_mysql_db
 * Globale Klasse für SQL Funktionen
 * @version 1.0
 * @package MIP-core
 * @author Thomas Koch <thomas.koch@clondiag.com>
 * @example
 * $sqlobj = new class_db($db_data);
 * 
 * $sqlselect = "EXP_ID, NAME from EXP where EXP_ID < 100";
   $sqlobj->selectquery($sqlsel);
   while ( $sqlobj->ReadRow() ) {
        $id   = $sqlobj->RowData[0]; // get data by column ID
        $name = $sqlobj->RowData[1]; // get data by column ID
   }
 * 
 * $sqlselect = "EXP_ID, NAME from EXP where EXP_ID < 100";
   $sqlobj->selectquery($sqlsel);
   while ( $sqlobj->ReadRowKey() ) {
        $tmpid = $sqlobj->RowData['EXP_ID']; // get data by column KEY
        $name  = $sqlobj->RowData['NAME']; 
   }
 * 
 * // manipulate data
 * $sqlselect = "update EXP set NAME='hallo' where EXP_ID < 100";
   $sqlobj->runquery($sqlsel);
 */
class class_db {
    VAR $db_link;    
    VAR $sortby;
    VAR $result;  // result handle
    VAR $RowData; // array


    function __construct($db_data=NULL) {
        
        if (is_array($db_data)) {
        	try {
            	$this->connect($db_data);
        	} catch (Exception $e)
        	{
        		echo htmlShowError($e->getMessage());
        	}
        }
    }
    
    /**
     * Initiiert Verbindung mit mysql
     * @param type $db_data = array () or empty, dann wird Session Variable verwendet (default)
     */
    function connect($db_data){       
        if (!$db_data) $db_data = $_SESSION['config'];          
        
        $this->db_link = mysqli_connect($db_data["dbhost"], $db_data["dbuser"], $db_data["dbpass"], $db_data["dbname"]);
        if (mysqli_connect_errno()) { 
            throw new RuntimeException("Connect failed: %s\n". mysqli_connect_error());             
        }
        
        if ($this->db_link)
        {            
            if (!mysqli_select_db($this->db_link, $db_data["dbname"])) throw new Exception('Database "'.$db_data["dbname"].'" not found.' );
            if (!mysqli_set_charset($this->db_link, $db_data["dbencoding"])) throw new Exception('Database Characterset"'.$db_data["dbencoding"].'" not found.' );
                     
        }         
    }
    
    public function runquery($string)
    {
       $this->result = NULL;
       $this->result = mysqli_query($this->db_link,$string);
       
       if (!$this->result) {          
           throw new Exception('SQL Anweisung: '.$string.' konnte nicht durchgef&uuml;hrt werden.');
           
       }
    }    
    
    public function getLastInsertID(){
    	return mysqli_insert_id($this->db_link);
    }
    
    
    public function selectquery($sqlsel) {
        $sqlFull = 'select '.$sqlsel.';';
        $this->runquery($sqlFull);
        showDebug($sqlFull,"SQL String (selectquery)");
    }
    
    /**
     * update one row; supports specila chars like "'"
     * @param string $table
     * @param array $setArray  ('NAME'=>"hal'lo")
     * @param array $whereArray ('EXP_ID'=>234345)
 
     */
    public function updateKeyVal($table, $setArray, $whereArray) {
        $sqlFull='update '.$table. ' set ';
        $komma = NULL;
        foreach ($setArray as $key=>$value)  {
             if ($value==NULL) {
                 $value_new = '= NULL';
             } else {
                $value_new = '=\''.$this->db_link->escape_string($value).'\'';
             }
             $sqlFull   .= $komma . $key . $value_new;
             $komma = ', ';
        }
        
        $sqlFull   .= ' where ';
        $komma = NULL;
        foreach ($whereArray as $key=>$value)  {
             
             $value_new = '=\''. $this->db_link->escape_string($value).'\'';
             $sqlFull   .= $komma . $key . $value_new;
             $komma = ' and ';
        }
        showDebug($sqlFull,"updateKeyVal: ");
        $sqlFull .= ';';
        $this->runquery($sqlFull);
    }
    
    /**
     * 
     * @param string $table
     * @param array $whereArray ('EXP_ID'=>234345, 'NAME'=>'test')
     * @param array $filter (default NULL)
     */
    public function selectKeyVal($table, $whereArray, $filter=NULL) {
    	if (!$filter){
    		$outputKeys='*';    		    		
    	} else {
    		$outputKeys=implode(',',$filter);
    	}
        $sqlFull='select '.$outputKeys. ' from '.$table.' where ';
        $komma = NULL;
        
        foreach ($whereArray as $key => $value)  {
             if ($value==NULL) {
             	//TODO: Testen!! -> Unittest
                 $value_new = "(". $key." is NULL or $key=='' )";
             } else {
                $value_new = $key . '=\''. $this->db_link->escape_string($value).'\'';
             }
             $sqlFull   .= $komma . $value_new ;
             $komma = ' and ';
        }
        $sqlFull .= ';';
        //showDebug($sqlFull,"SQL String");
        $this->runquery($sqlFull);
    }
    
    /**
     * 
     * @param type $sqlstring : 'EXP values (3,45,6)' (string without "INSERT INTO")
     * @return new primary key value
     */
    public function insert_row_str($sqlstring) {
        $sqlFull = 'INSERT INTO '.$sqlstring;
        $this->runquery($sqlFull);
        return mysqli_insert_id($this->db_link);        
    }
    
    /**
     * 
     * @param string $table
     * @param array $valArray ('ua_id'=>234345, 'mitarbeiter'=>'braini')     
     */
    public function insertKeyVal($table, $valArray) {
        $sqlFull='';
        $komma = NULL;
        foreach ($valArray as $key => $value)  {
                 $valArray[$key]= $this->db_link->escape_string ($value);
        }
        
        $columns =  implode(', ', array_keys($valArray));        
        $values = implode('\', \'', $valArray);
        
        $sqlFull=$table.' ('.$columns.') VALUES (\''.$values.'\');';                
        
        return $this->insert_row_str($sqlFull);        
    }    
    
    public function trucateTable($table){
    	$sqlFull='';
    	$sqlFull='truncate '.$table.';';
    	$this->runquery($sqlFull);
    }
    
     public function ReadRow() {
        
        $this->RowData = NULL;  
        if (!$this->result) {
            throw new Exception('no result handler' );
        }
        
        if ($this->RowData = mysqli_fetch_row($this->result)) {
            return 1;
        } else {
            return 0;
        }
        
    }
    
    public function ReadRowKey() {
        
        $this->RowData = NULL;  
        
        if (!$this->result) {
            throw new Exception('no result handler' );
        }
        $this->RowData=mysqli_fetch_assoc($this->result);
        
        if ($this->RowData) {
            return 1;
        } else {
            return 0;
        }
        
    }
    
    /**
     * read row by ID or KEY
     * @param string $method 'ID', 'KEY'
     * @return data exists : 0,1
     * @throws Exception
     */
 	public function ReadRowOpt($method="KEY") {
        
        $this->RowData = NULL;  
        
        if (!$this->result) {
            throw new Exception('no result handler' );
        }
        
        if ($method!='ID' and $method!='KEY' ) {
        	throw new Exception('method invalid.' );
        }
        
        if ($method=='KEY') {
        	$this->RowData = mysqli_fetch_assoc($this->result);
        } else {
        	$this->RowData = mysqli_fetch_row($this->result);
        }
        
        
        if ($this->RowData) {
            return 1;
        } else {
            return 0;
        }
        
    }
    
    public function countLastResults(){
    	return mysqli_num_rows($this->result);
    }
    
	public function count_results($sqlsel){		
		$this->selectquery('count(1) '.$sqlsel);
   		$this->ReadRow();
        $cnt   = $this->RowData[0]; // get data by column ID
        return $cnt;
	}
	
	public function bildeSumme($sqlsel,$col){
		$this->selectquery("CAST(SUM(".$col.") as DECIMAL(10,2)) ".$sqlsel);
   		$this->ReadRow();
   		$sum = $this->RowData[0];
   		if ( $sum =="" )
   		{
        	$sum=0; 
        	        	
   		}
        return $sum;
	}
    

}
