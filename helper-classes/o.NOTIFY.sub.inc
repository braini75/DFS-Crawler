<?php

/**
 * sub functions for table NOTIFY
 * @version 1.0
 * @package o.NOTIFY.sub.inc
 * @author Thomas Koch <thomas.koch@clondiag.com>
 * @example
 */

class oNOTIFY_sub {
	
	/**
	 * if an entry exists for this object, get last date stamp 
	 * @param object $_dbhandle
	 * @param string $table
	 * @param int $objid
	 * @return $lastnotify or NULL
	 */
	public function getLastNotifyDate(&$_dbhandle, $table, $objid) {
		$sqlstring = "lastnotify from notify where objtable='".$table."' and objid='".$objid."'";
		$_dbhandle->selectquery($sqlstring);
		if ( $_dbhandle->ReadRow() ) {
			$lastnotify         = $_dbhandle->RowData[0];
			//echo ":DEBUGG: hat schon.\n";
			return $lastnotify;
		} else return NULL;
	}
	
}
