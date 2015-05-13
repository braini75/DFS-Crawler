<?php
/**
 * DFS-Index search form

 * @package plug_search
 * @author  Thomas Koch
 * @param array $_REQUEST['parx']
 *   'file' :       file name pattern
     'dir_name' : directory pattern
 * @param $_REQUEST['action']
 *   ['search']
 *   'duplicate'
 * @param $_REQUEST['x_sort'][colid] => 2,3
 * @param $_REQUEST['go']   : 0,1,2,   
 * 
 * @globals $_SESSION['formvars']['search_MIP']
 *   ['parx'] save vals
 *   ['x_sort'][colid] => 2,3
 */




require_once('classes/class_dfsindex_db_manage.php');
require_once('function_lib.php');
require_once('helper-classes/class_form.inc');
require_once('helper-classes/tableViewRaw.inc');
require_once('helper-classes/class_tableView.inc');
require_once('classes/class_db.php');




class tableView_dfs extends tableView_abs {
	VAR $headerDict;

	/**
	 *  get rowdata; output HTML-data
	 *  @param array $rowData (value by index)
	 *  @return array() for output
	 */
	function getOneRow($rowData) {

		$outdata  = array();
		//csv Export gew�hlt?
		if (isset($_REQUEST['export'])){
			$export_type=$_REQUEST['export'];
		} 
		//showDebArr($rowData, "RowData in object: ");
		foreach ($this->headerDict as $key){
			$style="";
			switch ($key){
				case 'dir_name':
					$val=pathToWindowsStyle($rowData[$key]);
					//$val="<a href=file://///".$val.">".$val."</a>";
				break;	
				case 'file_size':
					$val='<span style="text-align: right;">'.humanFileSize($rowData[$key]).'</span>';							
					break;
				case 'count':					
					$val='<a href=index.php?parx[file_name]='.$rowData['file_name'].'&parx[file_size]='.$rowData['file_size'].'&go=1&action=analyze>'.$rowData[$key].'</a>';								
					break;
				default:
					$val = $rowData[$key];
					break;					
			}
			//$outdata[]=html_entity_decode($val);
			$outdata[]=$val;
		}

		return $outdata;		
	}
	
	
}

class dfs_forms{
	/**
     * Erstellt ein Formular zur Bearbeitung fuer einen Urlaubsantrag
     * 
     */
	VAR $form_attr=array(
        'file_name'	=>    array('name'=> 'Dateiname',  				'object'=>'text', 'view'=>0, 'notes'=>'Suchstring des Dateinamens, nutze "*" als Wildcard','allow_sort' =>1, 'required'=>1 ),                        
        'dir_name'	=>    array('name'=> 'Pfadeinschr&auml;nkung',  'object'=>'text', 'view'=>0, 'notes'=>'Startpfad im DFS, wie Z:\Projekte','allow_sort' =>1, 'required'=>1 )
        );
        
    VAR $resultTable_col_names = array (
    	'file_id'		=> array ('name'=>'Datei ID'),
	    'file_name'		=> array ('name'=>'Dateiname'),
	    'dir_name'		=> array ('name'=>'Verzeichnis'),
	    'file_size'		=> array ('name'=>'Gr&ouml;sse'),
	    'file_mtime'=> array ('name'=>'letzte &Auml;nderung')
       );
       
    VAR $url="";
    VAR $indexStatus=array();
    VAR $WILDCARD = '*'; // search wildcard

   	function __construct($url) {

		$this->url=$url;
   	}
   	
   	function generate_sha1($dbhandle,$query){
   		
   		$dbhandle->selectquery($query);
   		$result = $dbhandle->result;
   		$sha1_array = array();
  
   		while ($row = $result->fetchArray()) {
   			$file_id=$row["file_id"];
   			$file=$row["dir_name"]."/".$row["file_name"];
   			$sha1_array[$file_id]=sha1_file($file);
   			echo $file_id." - ".$file." => ".$sha1_array[$file_id];   				
   			echo "<br>";
   		}

   	}
   
	function showShortHelp($extraText=""){
		$indexStatusText="?";
		if (isset($this->indexStatus)){
			$indexStatus=$this->indexStatus;
			$indexDate=$indexStatus['time.start'];
			$indexNumFiles=number_format($indexStatus['count.files'],0,',','.');
			$indexNumDirs=number_format($indexStatus['count.dirs'],0,',','.');
			$indexAllSize=humanFileSize($indexStatus['count.bytes']);
			$indexStatusText="vom: ".$indexDate." <b>Anzahl Dateien: </b>".$indexNumFiles." <b>Anzahl Verzeichnisse: </b>".$indexNumDirs." <b>Gesamtgr&ouml;&szlig;e aller Dateien: </b>".$indexAllSize;		
		}
		
		$text='
		<table>
		<tr><td colspan="2">Derzeit sind nur Z:\Group und Z:\Projekte im Index.</td></tr>
		<tr><td><b>Dateiname: </b></td>				<td>Die Suche kann mittels "*" als Platzhalter eingegrenzt werden. <br>Zum Beispiel: "*<b>hiv</b>*<b>protokoll</b>*" liefert "QM-DOT-0327_<b>HIV</b>_QC_Chip_Anl&ouml;se<b>protokoll</b>_Reporter.docx"<br></td></tr>
		<tr><td><b>Pfadeinschr&auml;nkung: </b></td><td>Ergebnisse nur aus angebenen Verzeichnissen zeigen. Auch hier sind Platzhalter "*" m&ouml;glich!</td></tr>
		<tr><td><b>IndexInfo: </b></td><td>'.$indexStatusText.'</td></tr>
		</table>';
		
		$shortHelp='						
			<br><table class="helpbox">
			<tr><th>Kurzhilfe (<a href="http://userwiki/infra/mip/dfsindex">mehr</a>)</th></tr>
			
			<tr><td>'.$text.'</td></tr>
			
			';
		
		if ($extraText!=="") $shortHelp .='<tr><td>'.$extraText.'</td></tr>';
		
		$shortHelp .='</table><br />'."\n";
		
		echo $shortHelp;		
	}
	
    function search_form( $parx ){
        $initarr   = NULL;
        $initarr["action"] = $this->url;        
        $initarr["submittitle"] = 'Suche'; 
        $initarr["class"]      = "result_ua";

        
        $formobj = new formc( $initarr, $hiddenarr, $go );
        $formobj->start();
        
        foreach (array_keys($this->form_attr) as $col){
        	
        	// get old inputs from/in $_SESSION['formvars']['search_MIP']        	 
			// if (isset ($_REQUEST[parx][$col])) $_SESSION['formvars']['search_MIP'][$col] = $_REQUEST[parx][$col];
            // if (isset ($_SESSION['formvars']['search_MIP'][$col])) $val=$_SESSION['formvars']['search_MIP'][$col];
            
        	$val = $parx[$col];
			
            /* reload 'inits' */            
            
            $fieldx = array (
                "title"     => $this->form_attr[$col]['name'], 
                "name"      => $col, 
                "val"       => $val, 
                "notes"     => $this->form_attr[$col]['notes'],
                "object"    => $this->form_attr[$col]['object'],
                "inits"     => $this->form_attr[$col]['inits'],
                "view"      => $this->form_attr[$col]['view'],
            	"req"		=> $req,
                "fsize"     => '60'                    
                    );
            


            $formobj->fieldOut( $fieldx ); 
        }


	        //show "Abrechen"-Button:
	        
	        $onCloseOption['addObjects']='<input type=button Name="cancel" value="Abbrechen" onclick="javascript:window.location.href=\''.$this-> fromUrl.'\'">';	        
	        
        $formobj->close( TRUE, $onCloseOption );    

        //Show short help
        $this->showShortHelp();
    }
    
    /**
     * Show results
     *  
     * @param array $searchValues ('file' => Part of the filename (Searchstring), 'dir_name' => 'Z:\Projekte')
     * @param object $dbhandle ('link to db')
     * @param str $baseUrl = URL 
     * @return 1 on success
     */
    function showResultTable($searchValues, &$dbhandle,$baseUrl){
    	
    	$dfsdb = new dfsindex_db_manage();
    	
    	/**
        * 
        * "colSortArr" => array of ORDER-options: 'toolUrl' will be extended by '&x_sort='
		    0 => no sort possible
		    1 => sort possible
		    2 => 'ASC' sorted
		    3 => 'DESC' sorted
        */
    	$colSortArr = array();
    	    	
    	$headerDict = array_keys($this-> resultTable_col_names);
    	$headerNice = array();
    	
    	if (is_array( $_REQUEST['x_sort']) ) {
    		list($colid,$val) = each($_REQUEST['x_sort']);
    		$_SESSION['formvars']['search_MIP']['x_sort'] = array(); // reset array
    		$_SESSION['formvars']['search_MIP']['x_sort'][$colid] = $val;
    	}
    	
    	$colno =0;
    	$sql_order_string = NULL; 
    	foreach ($headerDict as $key){
			$niceColName=$this-> resultTable_col_names[$key]['name'];
			$sort_in_param = $_SESSION['formvars']['search_MIP']['x_sort'][$colno]; 
			$current_sort_flag = 1; // default
			if ($sort_in_param>0) {
				$order_direction = 'ASC';
				if ($sort_in_param==3) $order_direction = 'DESC';
				$sql_order_string = ' order by '.$key. ' '.$order_direction;
				$current_sort_flag = $sort_in_param;
			}
			$headerNice['html'][]='<span style="white-space:nowrap">'.$niceColName.'</span>';
			$colSortArr[$colno] = $current_sort_flag;
			$colno++;
		}
    	    	
    	$headerCols=implode(',',$headerDict);
    	
    	// Convert search string to Linux style
    	if (isset($searchValues['dir_name'])) $searchValues['dir_name']=pathToLinuxStyle($searchValues['dir_name']);
    	
    	$query=$dfsdb-> findFiles($searchValues,$headerCols);    	
    	$cnt= $dbhandle->count_results($query);
    	
    	if (!$cnt) {
    		// check, if user forgot "*"
    		if (strstr($searchValues['file_name'], $this->WILDCARD)==NULL) {
    			// user did not add an wildcard, try with wildcards
    			$searchValues['file_name'] = $this->WILDCARD . $searchValues['file_name'] . $this->WILDCARD;
    			echo "Info: automatically added WILDCARDS: ".htmlspecialchars($searchValues['file_name'])." ...<br>";
    			$query= $dfsdb-> findFiles($searchValues,$headerCols);
    			$cnt  = $dbhandle->count_results($query); // query again
    			echo "Count: ".$cnt;
    		}
    	}
    	

    	
    	//htmlShowInfo("Anzahl: ".$cnt);    	
    	
    	if ($cnt > 0) {
    		$resultTable = new tableView_dfs();
    		$infoarr=array(
				//'title'		=> 'Ergebnisse',
				'entries'	=>	$cnt,
			 	'sqlCmd'	=>	$query . $sql_order_string,
				'toolUrl'	=>	$baseUrl.'&go=2',
				'tableHeader' => $headerNice,
    			'cssclass'	=> 'result',
    			'colSortArr'=> $colSortArr
				
			);
			
    		$resultTable-> headerDict=$headerDict;
    		$resultTable-> init($infoarr);

    		$showOptions=array();
			
    		$showOptions['readmethod']='KEY';				
			$resultTable->show($dbhandle, $showOptions);		        				
			
    		
    		/*while ($dbhandle->ReadRowKey()){
				$row=$dbhandle-> RowData;											
				$resultTable-> table_row($row);
			}*/
			//$this-> auditTab-> table_row($dataArr);
			//$resultTable-> table_close();
			
			if ($_REQUEST['action']=='sha1'){
				$this->generate_sha1($dbhandle,$query);
			}
			 
			return 1;    		
    	} else {
    		$cnt=$dbhandle->count_results($dfsdb-> checkPathExist($searchValues));
    		if ($cnt == 0) return "path_not_found"; else return "file_not_found";
    		
    	} 
    	
    }
/**
     * Show results
     *  
     * @param string $action (duplicate,...)
     * @param object $dbhandle ('link to db')
     * @param string $baseUrl = URL 
     * @return 1 on success
     */
    function showSpecialResults($action, &$dbhandle,$baseUrl){
    	
    	$dfsdb = new dfsindex_db_manage();
    	
    	$this->headerDict= array (
		    'file_name'	=> array ('name'=>'Dateiname'),
		    'file_size'		=> array ('name'=>'Gr&ouml;sse'),
		    'count'=> array ('name'=>'Anzahl')
       );
    	    	
      
    	$headerDict = array_keys($this->headerDict);
    	$headerNice = array();
    	
    	foreach ($headerDict as $key){
			$niceColName=$this->headerDict[$key]['name'];
			$headerNice['html'][]='<span style="white-space:nowrap">'.$niceColName.'</span>';
		}
    	    	
    	$headerCols=implode($headerDict);

    	switch ($action){
    		case 'analyze':
    		case 'duplicate':
    			$query=$dfsdb-> findDuplicateFiles(20);
    			break;
    		default:
    			die("No option given!");
    			break;
    	}
    	
    	    	
    	$cnt= $dbhandle-> count_results($query);
    	
    	//htmlShowInfo("Anzahl: ".$cnt);    	
    	
    	if ($cnt > 0) {
    		$resultTable = new tableView_dfs();
    		$infoarr=array(
				//'title'		=> 'Ergebnisse',
				'entries'	=>	$cnt,
			 	//'sqlCmd'	=>	$columnString.' '.$sqlfrom,
			 	'sqlCmd'	=>	$query,
				'toolUrl'	=>	$baseUrl.'&go=2',
				'tableHeader' => $headerNice,
    			'cssclass'	=> 'result'
				
			);
			
    		$resultTable-> headerDict=$headerDict;
    		$resultTable-> init($infoarr);

    		$showOptions=array();
			
    		$showOptions['readmethod']='KEY';				
			$resultTable->show($dbhandle, $showOptions);		        				
			
    		
			return 1;    		
    	} else {
    		$cnt=$dbhandle->count_results($dfsdb-> checkPathExist($searchValues));
    		if ($cnt == 0) return "path_not_found"; else return "file_not_found";
    		
    	} 
    	
    }
    
}

class search_MIP {
	VAR $ecmdb_handle;
	VAR $url;
	VAR $indexStatus=array();
	VAR $config;
	
	function register() {
		$this->_infox['noLoginCheck'] = 1;
		$this->_infox['title']='DFS Suchmaschine';
		//$this->_infox['locrow'][] = array('p.php?mod=dfsindex/home', 'DFS Indexer Search Home');
		//$this->_infox['ecmwork']  = 1;
		
		//$this->_ecmdb_handle = new class_db($_SESSION['config']['ecm-work']);
		//$this->url=$this->_getPluginUrl();
		$this->url="index.php?debug=1";
		$this->config=	$_SESSION['config'];
		$this->ecmdb_handle = new class_db($this->config['dfsindex']);
	}
	
	
	function getIndexStatus(){
		$status=array();
		
		$table_name=$this->config["dfsindex"]["global_table"];
		
		$global_keyArr = array(
			'plugin'	=>	"dfsindex",
			'global_key'	=>	"lastIndexCreateLog"
		);
		try{
			$this->ecmdb_handle-> selectKeyVal($table_name,$global_keyArr);
			$this->ecmdb_handle->ReadRow();			
			$status=json_decode($this->ecmdb_handle->RowData['data'],true);
			
		}catch (Exception $e){
            	throw $e;  //weiterwerfern der Exception       			
		} 
		return $status;			
	}
	
	/**
	 * log the searches in $_SESSION['config']["logdir"] .'/dfsindex.log'
	 * @param array $parx
	 * @return number
	 */
	private function logsearch( $parx ) {
		
		// is logdir configured ?
		if ( !isset($_SESSION['config']["logdir"]) ) return -2;
			
		$logfile= $_SESSION['config']["logdir"] .'/dfsindex.log';
		$log_format="Y-m-d H:i:s";
		
		if (file_exists($logfile) and is_writable($logfile)){
			
			$searchstring = $parx['file_name']."\t".$parx['dir_name'];
			$logrow = date($log_format)."\t".$_SERVER['REMOTE_ADDR']."\t".$_SESSION['user']['user']."\t".$searchstring."\n";
			error_log($logrow, 3, $logfile);
			
		} else {
			return -1;
		}
			
		
	}
	
	/**
	 * trim parameters ...
	 * @param array $parx
	 * @return array
	 */
	private function _prepParameters($parx) {
		
		// this is important !!!
		if (isset($parx['file_name'])) $parx['file_name'] = trim($parx['file_name']);
		if (isset($parx['dir_name'])) $parx['dir_name'] = trim($parx['dir_name']);
		
		return $parx;
	}
	
	
	function run() {
		$indexStatus=array();

		
		$dfsdb = new dfsindex_db_manage();
		
		$cnt=$this->ecmdb_handle->count_results(" file_name from atj_dfs_index");

		showDebug( $cnt, "Anzahl: ");

		//get and show Indexstatus:
		$indexStatus=$this->getIndexStatus();

		$go=$_REQUEST['go'];
		
		$action=$_REQUEST['action'];
		
		$dfs = new dfs_forms($this->url);
		$dfs-> indexStatus=$indexStatus;
		
		showDebug($this->url, "URL: ");
		
		if ($go==2) {
			$parx = $_SESSION['formvars']['search_MIP']['parx'];
			showDebug($parx, "parx");
		}
		if ($go==1) {
			//	$val='<a href=index.php?parx[file]="'.$rowData['file_name'].'"&go=1>'.$rowData[$key].'</a>';
			$parx = $_REQUEST['parx'];
		}
		if ($go>0){
			$_SESSION['formvars']['search_MIP']['parx'] = $parx; // save $parx
		}
		
		$parx = $this->_prepParameters($parx);
		
		switch ($action){
			case 'analyze':
			
				//$this->generate_sha1();
			
				break;			
			case 'sha1':
				print_r($parx);
				
				
				break;
			case 'duplicate':
				
				$dfs-> showSpecialResults($action, $this->ecmdb_handle,$this->url);
				break;
			default:
				
				$dfs->search_form($parx);				
				break;				
		}

		
		if ($go){
			
			
			$_SESSION['formvars']['search_MIP']['parx'] = $parx;
			
			$this->logsearch($parx);
			
			//$dfs-> showResultTable($parx, $this->ecmdb_handle);
			$err=$dfs-> showResultTable($parx, $this->ecmdb_handle,$this->url);
			
			switch ($err){
				case 'path_not_found':
					htmlShowInfo("<b>Pfadeinschr&auml;nkung ung&uuml;ltig!</b> <br>Verzeichnis konnte nicht im Index gefunden werden.");
					break;
				case 'file_not_found':
					htmlShowInfo("Keine passenden Dateien im Index gefunden!");
					break;
				
			}
			
		}	
					
		
	}
	
}