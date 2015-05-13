<?php 
require_once 'f.pageEntriesOrg.inc';
require_once 'tableViewRaw.inc';

/**
 * php-bean to show a table, created by a SQL-query
 * @package class_tableView.inc
 * @author  Steffen Kube (steffen@clondiag.com)
 * @version $Header: $
 * @example
 * 
 * $reportLib = new tableView_X();
 * $reportLib->init($infoarr);
 * $reportLib->show($sqlo, $sqlo2);
 */
class tableView_abs {
	
	/*
	 * 	@var tableView_info_STRUCT $infoarr : infoarr to visualize a table
	 *   'title'   => table title
	 *   'entries' => number [required]
	 *   'entryPerPage' => number [optional]
	 *   'sqlCmd'  => e.g. "x.CONCRETE_SUBST_ID, x.CCT_ACCESS_ID ". $sqlBase.$orderstr";
	 *   'toolUrl' => e.g. 'p.php?mod='.$this->_mod ; will be extended by '&page=' ...
	 *   'tableHeader' => header array  (
	 *     'html' => array -- the html-header
	 *       'SUC-ID',
			 'substance (conc.)',
			 'status (now)',
			 'status (problem)',
			 )
			'csv' => array (csv columns)
		 "colSortArr" => array of ORDER-options: 'toolUrl' will be extended by '&x_sort='
		   array(colid => $sortval)
		     $sortval:
		       0 => no sort possible
		       1 => sort possible
		       2 => 'ASC' sorted
		       3 => 'DESC' sorted
	 */
	private $_infoarr;
	/**
	 *  ABSTRACT method
	 *  @return array() for output
	 */
	function getOneRow($rowData) {}
	
	/**
	 * 
	 * @param $infoLib must contain method getOneRow()
	 * @param tableView_info_STRUCT $infoarr
	 */
	function init( &$infoarr) {
		$this->_page   = $_REQUEST['page'];
		$this->_parx   = NULL;
		$this->_infoarr = $infoarr;
		
		if ( $infoarr['entryPerPage']>0 ) $this->_parx['entryPerPage'] = $infoarr['entryPerPage'];
	}
	
	/**
	 * show the table
	 * @param object $sqlobject
	 * @param $options
	 *   'readmethod' : ['ID'], 'KEY' : ReadRow method
	 */
	function show(&$sqlobject, $options=NULL) {
		
		$readmethod = 'ID';		
		$parx = $this->_parx;
		$tableHeader = $this->_infoarr['tableHeader']['html'];
		if ($options['readmethod']!=NULL) $readmethod = $options['readmethod'];

		$showinf=array(
			'startShow'    => $this->_page,
			'foundEntries' => $this->_infoarr['entries']
		);
		$pageNavLib = new f_pageEntriesGui();
	    $info = $pageNavLib->setParams( $showinf, $parx );
	    $cnt = 0;
		$moreExists = 0;
		$startShow = $info['start'];
		$endShow   = $info['end'];
		
		$tabobj  = new tableViewRaw();
		if (isset($this->_infoarr['cssclass'])){
			$cssclass=$this->_infoarr['cssclass'];
		} else {
			$cssclass='result_table';
		}
		
		$colOptArr = array();
		if (is_array($this->_infoarr["colSortArr"])) {
			$tmp_url = $this->_infoarr['toolUrl'];
			$sortNewVallDefs = array(
				1 => array('new'=>2),
				2 => array('new'=>3, 'char'=>'*D'),
				3 => array('new'=>2, 'char'=>'*A')
			);
			foreach ($this->_infoarr["colSortArr"] as $colid => $sortval) {
				
				$looptext = NULL;
				if ($sortval>0) {
					$newval = $sortNewVallDefs[$sortval]['new'];
					
					$tableHeader[$colid] = '<a href="'.$tmp_url.'&x_sort['.$colid.']='.$newval.'">'.
						$tableHeader[$colid].'</a>';
				}
				if ($sortval>1) {
					$looptext = $sortNewVallDefs[$sortval]['char'];
				}
				$colOptArr[$colid] = $looptext;
			}
		}

		$headOpt = array( 
			"title" => $this->_infoarr['title'], 
			"cssclass"  => $cssclass, 
			"format"    => $options['format'],
			"colOptArr" => $colOptArr,
			);
		$tabobj->table_head($tableHeader, $headOpt);
		
		$sqlsel = $this->_infoarr['sqlCmd'];
		$sqlobject->selectquery( $sqlsel );
		
		while ( $sqlobject->ReadRowOpt($readmethod) ) {
			$showit = 0;
			
			$rowData = $sqlobject->RowData;
			if ( $cnt >= $startShow ) $showit=1;
			if ( $cnt > $endShow )   {
				$moreExists = 1;
				break;
			}
			
			if ($showit) {
				$outarr = $this->getOneRow($rowData);
				$tabobj->table_row ($outarr);
			}
			$cnt++;
		}
		$tabobj->table_close();
		
		$pageNavLib->setLastShownEntry($cnt-1);
		$desturl= $this->_infoarr['toolUrl'].'&page=';
		$pageNavLib->showEntryNav ( $desturl, $page );			
	}
	
	/**
	 * export the table
	 * @param object $sqlobject
	 * @param $options
	 *   'format' => text (e.g. 'csv'  :export format)
	 *   'extraHeaderInfo' => text (e.g. comments to add at the top of the exportfile) 
	 */
	function export(&$sqlobject, $options=NULL) {
		$export="";
		$extraHeader="";
		
		$tableHeader = $this->_infoarr['tableHeader']['csv'];
		
		if ($options['format']!=NULL) $export = $options['format'];
		if ($options['extraHeaderInfo']!=NULL) $extraHeader = $options['extraHeaderInfo'];

		$sqlsel = $this->_infoarr['sqlCmd'];
		
		try {
			$sqlobject->selectquery( $sqlsel );
		} catch (Exception $e){
         
			throw $e;  //weiterwerfern der Exception
		}
		
		$csv_export = array();
		$csv_export[]=$tableHeader;
		
		
		while ( $sqlobject->ReadRowOpt() ) {
			$rowData = $sqlobject->RowData;				
			$csv_export[] = $this->getOneRow($rowData);
			$cnt++;
		}
		
		//showDebug($csv_export,"CSV-Array: ");
		
		//echo $cnt." Rows exported!";
		
		try {				
				array_to_csv_download($csv_export);
			} catch (Exception $e){
				throw $e;  //weiterwerfern der Exception                    			
        	}

		
		//$pageNavLib->setLastShownEntry($cnt-1);
		//$desturl= $this->_infoarr['toolUrl'].'&page=';
		//$pageNavLib->showEntryNav ( $desturl, $page );
	}
}