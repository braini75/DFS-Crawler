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
    VAR $db_type;
    VAR $sortby;
    VAR $result;  // result handle
    VAR $RowData; // array
 


    function __construct($db_data=NULL) {
        
        if (is_array($db_data)) {
        	try {
        		$this->db_type=$db_data['dbtype'];
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
        
        if ($this->db_type == "mysql"){
        	$this->db_link = new mysqli($db_data["dbhost"], $db_data["dbuser"], $db_data["dbpass"], $db_data["dbname"]); 
        	if (!$this->db_link->set_charset($this->db_link, $db_data["dbencoding"])) throw new Exception('Database Characterset"'.$db_data["dbencoding"].'" not found.' );
        	if ($this->db_link->connect_errno) {
        		throw new RuntimeException("Connect failed: %s\n". $this->db_link->connect_error);
        	}
        }
        
        if ($this->db_type == "sqlite"){
        	$this->db_link = new SQLite3('dfsindex_db');
        	if (!$this->db_link) {
        		throw new RuntimeException("Connect failed: %s\n". $this->db_link->connect_error);
        	}
        
        }         
        
    }
    
    /**
     * 
     * @param string $stat ("BEGIN" or "COMMIT")
     */
    public function Transaction($stat){
    	$this->db_link->query($stat." TRANSACTION");
    }
    
    public function runquery($string)
    {
    	
       $this->result = NULL;
       $this->result = $this->db_link->query($string);
       if (!$this->result) {          
           throw new Exception('SQL Anweisung: '.$string.' konnte nicht durchgef&uuml;hrt werden.');
           
       }
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
                $value_new = '=\''.$this->db_link->escapeString($value).'\'';
             }
             $sqlFull   .= $komma . $key . $value_new;
             $komma = ', ';
        }
        
        $sqlFull   .= ' where ';
        $komma = NULL;
        foreach ($whereArray as $key=>$value)  {
             
             $value_new = '=\''. $this->db_link->escapeString($value).'\'';
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
                $value_new = $key . '=\''. $this->db_link->escapeString($value).'\'';
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
        
        if ($this->db_type == "mysql"){
        	return $this->db_link->insert_id;
        }
        if ($this->db_type == "sqlite"){
        	return $this->db_link->lastInsertRowID();
        }
                        
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
            $valArray[$key]= $this->db_link->escapeString($value);        	
        }
        
        $columns =  implode(', ', array_keys($valArray));        
        $values = implode('\', \'', $valArray);
        
        $sqlFull=$table.' ('.$columns.') VALUES (\''.$values.'\');';                
        
        return $this->insert_row_str($sqlFull);        
    }    
    
    public function truncateTable($table){
    	$sqlFull='';
    	$sqlFull='delete from '.$table.';';
    	$this->runquery($sqlFull);
    }
    
     public function ReadRow() {
        
        $this->RowData = NULL;  
        if (!$this->result) {
            throw new Exception('no result handler' );
        }
        
        if ($this->RowData = $this->result->fetchArray()) {
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
    		$this->RowData = $this->result->fetchArray(SQLITE3_ASSOC);
    	} else {
    		$this->RowData = $this->result->fetchArray(SQLITE3_NUM);
    	}
    
    
    	if ($this->RowData) {
    		return 1;
    	} else {
    		return 0;
    	}
    
    }
    
    public function countLastResults(){
    	if (!$this->result) {
    		throw new Exception('no result handler' );
    	}
    	
    	if (!$this->RowData){
    		$this->RowData = $this->result->fetchArray();
    	}
    	$this->result->
    	$this->ReadRow();
    	echo "TEST:";
    	print_r($this->RowData);
    	die();
    	return count($this->RowData);
    }
    
	public function count_results($sqlsel){		
		$this->selectquery('count(1),'.$sqlsel);
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
