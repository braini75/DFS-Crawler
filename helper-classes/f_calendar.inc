<?php

/**
 * calendar methods
 * @package f_calendar.inc
 * @swreq 
 * @author  Steffen Kube (steffen@clondiag.com)
 * $HeadURL:  $
 * $Id:  $
 */
class f_calendar {
	/**
	 * array of FREE days in THURINGIA  per year
	 * format: year = array( days )
	 *    day-format: MM-DD
	 */
	var $holidays = array(
			'2013'=>array('01-01', '03-29', '04-01','05-01','05-09','05-20','10-03','10-31', '12-25', '12-26'),
			'2014'=>array('01-01', '04-18', '04-21', '05-01', '05-29', '06-09', '10-03', '10-31', '12-25', '12-26'),
			'2015'=>array('01-01', '04-03', '04-06', '05-01', '05-14', '05-25', '10-03', '10-31', '12-25', '12-26')
			);

	/**
	 * 
	 * Array of school breaks in THURINGIA 
	 */		
	VAR $ferien	= array(
		'2015' => array (	
				//02.02. - 07.02. 	30.03. - 11.04. 	15.05. 	13.07. - 21.08. 	05.10. - 17.10. 	23.12. - 02.01. 		
				array('start'=>'2015-02-02','end'=>'2015-02-07','name' => 'Winterferien 2015'),
				array('start'=>'2015-03-30','end'=>'2015-04-11','name' => 'Osterferien 2015'),		
				array('start'=>'2015-05-15','end'=>'2015-05-15','name' => 'Pfingstferien 2015'),
				array('start'=>'2015-07-13','end'=>'2015-08-21','name' => 'Sommerferien 2015'),
				array('start'=>'2015-10-05','end'=>'2015-10-17','name' => 'Herbstferien 2015'),
				array('start'=>'2015-12-23','end'=>'2016-01-02','name' => 'Weihnachtsferien 2015')
				)
	); 
	
	function __construct() {
		$this->dayNames = array(0=>'So', 1=>'Mo', 2=>'Di', 3=>'Mi', 4=>'Do', 5=>'Fr', 6=>'Sa', 7=>'So');
	}
	/**
	 * 
	 * @param string $year e.g. 2013
	 * @return array $
	 */
	function getHolidays($year) {
		return $this->holidays[$year];
	}
	
	/**
	 *
	 * @param string $date 'TT.MM.YYYY'
	 */
	private function _date2weekday($date) {
		$unxdate = strtotime($date);
		$weekday = date('w',$unxdate);
		return $weekday;
	}
	
	/**
	 * 
	 * @param string $date 'DD.MM.YYYY'
	 * @return array( 'daycode'=>number, 'dayname'=>string, 'daytype'=>'NORMAL','HOLIDAY' )
	 */
	function getDayInfo($date) {
		
		$dayType='NORMAL';
		$wochentag = $this->_date2weekday($date);
		if ($wochentag==0 or $wochentag==6 or $wochentag==7) {
			$dayType='HOLIDAY';
		}
		
		$dayname = $this->dayNames[$wochentag];
		
		$DD = substr($date,0,2);
		$MM = substr($date,3,2);
		$YY = substr($date,6,4);
		$format2 = $MM.'-'.$DD;
			
		// maybe  array $this->holidays[$YY] is empty, because not updated
		if ( is_array($this->holidays[$YY]) and in_array($format2, $this->holidays[$YY]) ) {
			$dayType='HOLIDAY';
		}
		
		return array('daycode'=>$wochentag, 'dayname'=>$dayname, 'daytype'=>$dayType);
	}
}