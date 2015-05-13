<?php 
session_start();
require_once('classes/class_db.php');
require_once('classes/class_AbsHawaii.php');
require_once('classes/class_dfsindex_db_manage.php');
/**
 * Create an Index over Filesystems following symbolic links, as in DFS
 * usable in browser or at commandline
 * 
 * @author braini
 *
 */
class dfsIndex {
	VAR $config = array();
	VAR $ecmDBhandle;
	VAR $dfs_db;				// db manager object
	VAR $test_mode=0;
	
    function __construct() {
    	$this->init();
    }
    	
	private function init(){
		$_SESSION['sessvars']['debug.val'] = 0;
		/* 
		 * Load config-file
		 * */
		$configfile       = '/etc/mip-config.php'; // /etc-path
		$configfile_local = 'conf/settings.conf';
		
		
		if (file_exists($configfile_local)) {
		
			$configfile = $configfile_local;
		} 
		
		if (!file_exists($configfile)) {
		        die('MIP: Config-File missing "'.$configfile.'"');
		}
		
		require_once($configfile);
		$config["configfile"] = $configfile;
		
		$this->config = $config;
		
		// DFS Index Parameter
		$this->config["indexDirs"]	= array ('/media/sf_E_DRIVE/*');
		//$this->config["indexDirs"]	= array ('/share/HDA_DATA/Multimedia/Bilder/privat/2005/*');
		$this->config["blacklist"]	= 'dfsindex_blacklist.linux.txt';
		
	}
	
	
	/**
	 * Generate an array with excluded dirs ...
	 * @return array
	 */
	function readBlacklist(){
		$dirs=array();
		// Blacklist laden und abarbeiten
		$blacklist_file	=	$this->config["blacklist"];
		
		$blacklist_fh	=	fopen($blacklist_file,  "r");
		if (! $blacklist_fh) {
	        	throw new Exception("Could not open the file!".$blacklist_file);
	    }
		
		while ($zeile = fgets($blacklist_fh, 4096))
			{
				$dirs[]=trim($zeile);
			}
		fclose ($blacklist_fh);
		return $dirs;
	}
	
	function createCmdLine($dfs_pfad=".",$blackArray=NULL){
		$exclude="";
		$firstRun=1;
		if (isset($blackArray)) {
			foreach ($blackArray as $to_exclude){
				/*if ($firstRun==1){
					$oder="";				
					$firstRun=0;
				}else {
					$oder=" -o";
				}*/
				//$exclude .=$oder." -path '".$to_exclude."' -prune";
				$exclude .=" -path '".$to_exclude."' -prune -o";
			}
		}
		
		/*
		 * Results: 
		 * 	\t -> TAB
		 *  %f -> filename
		 *  %h -> pathname
		 *  %s -> file size
		 *  %TY-%Tm-%Td %TT -> file modification time
		 *  %u -> username
		 */
		$find_cmd=$this->config["dfsindex"]["find_cmd"];
		if (!file_exists($find_cmd)) {
			die('dfsindex: find_cmd missing "'.$find_cmd.'"');
		}
		
		$cmd_line=$find_cmd." -L ".$dfs_pfad.$exclude." -type f -printf '%f\t%h\t%s\t%TY-%Tm-%Td %TT\t%u\t\n'";
		
		return $cmd_line;
	}
	
	function parseFindResults($str){	
		$resultArr=array();
		$resultArr=explode("\t", $str);
		$cols = array(
			'file_name' 	=> $resultArr[0],
			'dir_name' 		=> $resultArr[1],
			'file_size' 	=> $resultArr[2],
		    'file_mtime' 	=> substr($resultArr[3],0,19),
			'file_owner' 	=> $resultArr[4]
		); 
		return $cols;
	}
	
	function execFind($dfs_pfad="."){
		// init progres view
		// ToDo: get the "du" of the $dfs_pfad and messure progress
		$table_name=$this->dfs_db->table_name;
		$ecmdb_handle=$this->ecmDBhandle;
		
		$cmd=$this->createCmdLine($dfs_pfad,$this-> readBlacklist());	
		//echo "DEBUG CMD: ".$cmd;		
	
		$cmd_handle = popen($cmd, 'r');

		$cnt=0;
		$lineArray=array();
		if ($this-> test_mode) {	
    		//give first 5 result and break
    		//TBD: not working yet -> find: `standard output': Broken pipe    		
    		while($cnt<6){
    			$line = fgets($cmd_handle);
    			echo $cnt." - ".$line;
    			$cnt++;    			    			    			
    		}
    		
    		pclose($cmd_handle);
	    } else {
	    	// give all results and put in DB
	    	$this->ecmDBhandle->Transaction("BEGIN");
			while ( ! feof($cmd_handle)){
				$line = fgets($cmd_handle);
		    	//ToDo: ERROR aus stdout abfangen
				if (!empty($line)){
		    		$lineArray=$this->parseFindResults($line);
	    			$ecmdb_handle->insertKeyVal($table_name, $lineArray); 	
	    			$cnt++;	
		    	}
			}
			pclose($cmd_handle);	    	
	    }
		
	    $this->ecmDBhandle->Transaction("COMMIT");
	    echo $cnt." files found in ".$dfs_pfad.".";
		  		  
	}
	
	function logThisWalk($startTime){		
		
		//ToDo: sqlite Handling
		
		//return 1;
		

		
		$table_name=$this-> config["dfsindex"]["global_table"];
		$dfs_db=$this-> dfs_db;
		$dbhandle = $this->ecmDBhandle;
		
		$dbhandle-> selectquery( $dfs_db-> count_files());
		$dbhandle-> ReadRow();   		
		$countFiles= $dbhandle->RowData[0];
		
		$dbhandle-> selectquery( $dfs_db-> count_dirs());
		$dbhandle-> ReadRow();
		$countDirs = $dbhandle->RowData[0];
		
		$dbhandle-> selectquery( $dfs_db-> bytes_total());
		$dbhandle-> ReadRow();
		$bytesTotal = $dbhandle->RowData[0];
		
		$StatsArray=array();
		
		$StatsArray['time.start'] =$startTime;
		$StatsArray['time.end'] = date('Y-m-d H:i:s');
		$StatsArray['count.files'] = $countFiles; 
		$StatsArray['count.dirs'] =  $countDirs;
		$StatsArray['count.bytes'] = $bytesTotal;

				
		$global_keyArr = array(
			'plugin'	=>	"dfsindex",
			'global_key'	=>	"lastIndexCreateLog"
		);
		
		$StatsData=array(
			'plugin' 	    => $global_keyArr['plugin'],
			'global_key'	=> $global_keyArr['global_key'],
			'data'		    => json_encode($StatsArray),
			'last_set'	  => date('Y-m-d H:i:s')
		);				
		
		
		
		// Check if value exist and needs to be updated
		$dbhandle-> selectKeyVal($table_name,$global_keyArr);
		if (!$dbhandle->ReadRow()){
			
			$this->ecmDBhandle->insertKeyVal($table_name, $StatsData);
		} else {

			$this->ecmDBhandle->updateKeyVal($table_name, $StatsData, $global_keyArr);
		}		
	}
	
	/**
	 * The whole table will be truncated (caution: All data will be lost!)
	 */
	function emptyIndexTable(){
		$this->ecmDBhandle->truncateTable($this->dfs_db-> table_name);
	}
		
}

/* Run Indexer: */
$indexer =new dfsIndex();
$indexer-> ecmDBhandle 	= new class_db($indexer-> config['dfsindex']);  // db handle
$indexer-> dfs_db		= new dfsindex_db_manage();	 					// db manager object

/* Comandline help */
if ($argc < 2 || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
	?>

DFS Suchmaschine Indexerstellung	
(Commandozeilen-Programm zur Indexerstellung)

	 Benutzung:
	 <?php echo $argv[0]; ?> <option>
	
		 <option> 
		 Starte Indexierung mit "do=1".
		 Check mit "check=1".
<?php
	die; 	
}

/* Testmode ?check=1 */
if (isset($argv)) parse_str(implode('&', array_slice($argv, 1)), $_REQUEST);


if (isset($_REQUEST['check']) and $_REQUEST['check']==1){
	$indexer->test_mode=1;
	?>

DFS Suchmaschine Indexerstellung
	Test Blacklist read : <?php	print_r($indexer->readBlacklist());?>	
	Index following dirs: <?php print_r($indexer->config["indexDirs"]); ?>
	<?php
	if (isset($_REQUEST['update_log'])){
		echo "Setting new Log-Entry";
		$indexer-> logThisWalk(date('Y-m-d H:i:s'));
	} 
} 

if (isset($_REQUEST['do']) and $_REQUEST['do']==1){
	$indexer-> emptyIndexTable();		// empty the whole table!!! Be carefull!
	$startTime=date('Y-m-d H:i:s');
	
	foreach ($indexer->config["indexDirs"] as $dir){	
		$indexer-> execFind($dir);	
	}
	
	/* Log Status of index walk */
	$indexer-> logThisWalk($startTime);	
}



