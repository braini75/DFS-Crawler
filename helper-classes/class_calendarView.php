<?php 
require_once('f_calendar.inc');

/**
 * show the calendar (Mallorca:Scenario7)
 * @swreq URS:Scenario10: Bereichsweite Urlaubsplanung
 * @author  Thomas Koch
 * @copyright Alere Technologies GmbH, All rights reserved
 * $HeadURL:  $
 * $Id:  $
 */ 
class calendarView{
	VAR $year;
	VAR $numOfDays;
	VAR $numOfUsers=0;
	VAR $calTableCache=array();
	VAR $ferien=array();
	VAR $holidays=array();
	VAR $maxCalRowsPerHead=20;
	VAR $eventDatesList=array();
	VAR $status_styles=array();
	VAR $show_specialEvents=false;
	
	function __construct(){
		if (isset($_SESSION['formvars']['mallorca.calendar']['year.val'])){
			$this->year=$_SESSION['formvars']['mallorca.calendar']['year.val'];	
		} else {
			$this->year=date('Y');
		}
		
		$this->numOfDays = date('z', mktime(0,0,0,12,31, $this->year));	
		$this->initHolidays();	
		if ($_SESSION['formvars']['mallorca.calendar']['showSpecialEvents'] == 'on') $this->show_specialEvents=true;
			
	}
	
	function initHolidays(){
		$f_cal = new f_calendar();
		$cur_ferien			=$f_cal-> ferien[$this-> year];
		$cur_holidays		=$f_cal-> holidays[$this-> year];
		showDebArr($cur_ferien, "Ferien in".$this-> year.": ");
		
		
		//'2015'=>array('01-01', '04-03', '04-06', '05-01', '05-14', '05-25', '10-03', '10-31', '12-25', '12-26')
 
		$this->holidays=array();  //reset
		foreach ($cur_holidays as $cur_holiday){
			$cur_holiday=$this->year.'-'.$cur_holiday; //convert in format: yyyy-mm-dd
			$this->holidays[]=date('z',strtotime($cur_holiday));	// put day of year to array
		}
		
		$this->ferien=array();  //reset
		foreach ($cur_ferien as $ferien) {
			$start_DoY	=date('z',strtotime($ferien['start']));  //DoY=Day of Year
			$end_DoY	=date('z',strtotime($ferien['end']));
			
			// taking care of end of year-switch, d.h. $end_DoY < $start_DoY
			if ($end_DoY < $start_DoY) $end_DoY=$this->numOfDays; //set to last day of year
			
			for ($day=$start_DoY; $day <= $end_DoY;$day++){
				$this->ferien[$day]=$ferien['name'];
			}
		}		
		
	}	

	
	function get_css_string() {
		$css_string = '
.scroll_container {width:1205px}
table.scroll_name {
  width:300px;
  background:#E0E0E0;
  float:left;
}
table.scroll_data  {
  background:#FFFFFF;
  width:1300px;  
}
table.scroll_name td {
  border:1px solid gray;
}
table.scroll_data td {
  border:1px solid gray;
}

table.scroll_data tr:hover {
	background-color: #ffff77;
}


table.scroll_data td {width:10px;}

tr.scroll_head td { background:#E0E0E0;}

.scroll_wrap{
  width:900px;
  overflow:auto;
  overflow-y:hidden;
  border-right:1px solid gray;
  float:left;
}

.scroll_clear { clear: both; }

div.tooltip
{
  position: relative;
  text-decoration: none;        
}

div.tooltip span
{
  display: none;
  text-align:left;
  font-size:10px;
  color: black;
  width:120px;
}

div.tooltip:hover span
{
  position: absolute;
  display: block;  
  top:auto;
  bottom:100%;
  left: 2em;
  border: 1px solid black;
  background-color:white;
  padding: 0.2em;
}




';
		return $css_string;
	}
	
	/**
	 * get "genutzter ANSPRUCH" in Percent
	 * PERCENT = (VOR + BEA) / ANSPRUCH
	 * @swreq URS:Scenario10: Urlaubsplanung: zeige genutzten Urlaubsanspruch in PROZENT !
	 * @param array $userDaysArr
	 * 	'anspruch' : in days
	 *  'wunsch'   : in days
	 *  'valid'    : in days
	 */
	private function _getAnspruchPercent($userDaysArr) {
		$anspruch_percent = '?';
		if ($userDaysArr['anspruch']==0) $anspruch_percent=0;
		if ($userDaysArr['anspruch']>0){
			$anspruch_percent = ($userDaysArr['wunsch']+$userDaysArr['valid']) / $userDaysArr['anspruch'] * 100.0;
			$anspruch_percent = ceil($anspruch_percent).'%';
		}
		
		return $anspruch_percent;
	}
	
	function scroll_nameHead(){
		$emptyRow="<tr><td colspan=4>&nbsp;</td></tr>"."\n";
		echo $emptyRow;
		echo $emptyRow;
		//Events
		if ($this->show_specialEvents){
			echo '<tr><td colspan=4>'.$this->status_styles['99']['name'].'</td>';
		}
		/*
		 * if we want to have multiple rows, each per user who "owns" the event
		foreach ($this->eventDatesList as $event){
			echo '<tr><td colspan=4>Special Events</td>';
		}
		*/		
				
		echo '<tr><td><span style="font-weight: bold;">Name</span></td>';
		echo '<td style="text-align:center;font-size:75%"><div class="tooltip">ans<span>genutzter Urlaubsanspruch f&uumlrs laufende Jahr in Prozent. (aus SAP; ohne Gew&auml;hr!)</span></div></td>';
		echo '<td style="text-align:center;font-size:75%"><div class="tooltip">vor<span>Zeigt die vorgemerkten Urlaubstage. (Urlaubsplanung)</span></div></td>';
		echo '<td style="text-align:center;font-size:75%"><div class="tooltip">bea<span>Zeigt die bereits beantragten, genehmigten und erfassten Urlaubstage.</span></div></td>
		</tr>'."\n";		
	}
	/**
	 * 
	 * Generiere TabellenKopf
	 * @param int $days //Number of days per year
	 * @param array $nickUsedDays=[$nick => array ( $typ => $days)]
	 */
	function startTable( $users, $nickUsedDays=NULL ){
		//showDebArr($this-> ferien, "Ferien: ");			
			
		$loc_de = setlocale(LC_TIME, 'de_DE@euro', 'de_DE', 'deu_deu');
		//echo "Preferred locale for german on this system is '$loc_de'";
		
		echo '<div class="scroll_container">'."\n";
		
		// start with the user names part-table
		echo '<table class="scroll_name" cellspacing="0" cellpadding="0">';						
		
		$userCount=0;
		foreach($users as $nick => $nice) {			
			if (($userCount % $this->maxCalRowsPerHead) == 0){				
				/* Rownumber modulo $this->maxCalRowsPerHead
				 * 
				 * Show header every this->maxCalRowsPerHead-Row
				 */
				$this->scroll_nameHead();
			}
			$sigmaTD_Style="text-align:right;font-size:60%";
			echo '<tr><td>'.$nice.'</td>';
			if ($this->year == date('Y')){
				
				$anspruch_percent = $this->_getAnspruchPercent($nickUsedDays[$nick]);
				
				echo '<td style='.$sigmaTD_Style.'>'.$anspruch_percent.'</td>';	
			} else echo '<td style='.$sigmaTD_Style.'><div class="tooltip">n.a.<span>Anzeige des Urlaubsanspruch ist nur im laufenden Jahr m&ouml;glich.</span></div></td>';
			echo '<td style=background-color:#81DAF5;'.$sigmaTD_Style.'>'.$nickUsedDays[$nick]['wunsch'].'</td>';
			echo '<td style='.$sigmaTD_Style.'>'.$nickUsedDays[$nick]['valid'].'</td></tr>'."\n";
			$userCount++;
		}
		
		echo '</table>
  			<div class="scroll_wrap"> 
    		<table class="scroll_data" cellspacing="0" cellpadding="0">
    		';
		
		
		//$this->buildCalendarRows();
		 		
	}
	
	function buildCalendarRows(){
		//Erste Zeile: Nick, Monatsnamen...
		echo '<tr class="scroll_head">';
			//NAME-COL: echo '<th> </th>';
			for ($monat=1; $monat <=12; $monat++){
				$timeStamp=mktime(0,0,0,$monat,1,$this->year);
				$daysOfMonth=date ('t',$timeStamp); //Anzahl der Tage des Monats
				$monthName	=date ('F',$timeStamp);
				echo '<td colspan="'.$daysOfMonth.'">'.$monthName.'</td>';								
			}		
		echo '</tr>';
		echo "\n";		
		
		//Zweite Zeile: Tag im Monat
		echo '<tr class="scroll_head">';
		//NAME-COL: echo '<td>Monatstag</td>';	//Platzhalter 1.Spalte
		for ($monat=1; $monat <=12; $monat++){
			$timeStamp=mktime(0,0,0,$monat,1,$this->year);
			$daysOfMonth=date ('t',$timeStamp); //Anzahl der Tage des Monats			
			for ($day=1; $day <=$daysOfMonth; $day++){				
			
				echo '<td>'.$day.'</td>'; // style="font-size:xx-small"
			}
		}		
		echo '</tr>';
		echo "\n"; 				
		
		//Dritte Zeile: Events
		if ($this->show_specialEvents){
			echo '<tr class="scroll_head">';
			$daysOfYear = $this->numOfDays;
			$rowBox=array(); //Array fue Kalenderzeile
			foreach ($this->eventDatesList as $row){	
				foreach ($row as $set){		
					$start =date('z',$set['start']);
					$end=date('z',$set['end']);
					for ($day=$start;$day<=$end;$day++){
						if (isset($rowBox[$day])) {
							//multiple Events the same day
							$rowBox[$day]['comment']=$rowBox[$day]['comment']." | ".$set['comment'];
							$rowBox[$day]['style']="background-color:#FF55FF; color:black";						
						} else {
							$rowBox[$day]['comment']=$set['comment'];
							//$rowBox[$day]['status']=$set['status'];
							$rowBox[$day]['style']=$this->status_styles[$set['status']]['list_mode'];					
						}
				  	}
				}			
			}	
			$day=0;
			while ($day<=$daysOfYear){			
				if (isset($rowBox[$day])){
					$style=$rowBox[$day]['style'];
					echo '<td style="text-align:center;'.$style.'"><div class="tooltip">x<span>'.$rowBox[$day]['comment'].'</span></div></td>';
									
				} else 	{			
					
					echo '<td'.$styleTag.'>&nbsp;</td>';
				}
	
				$day++;			
			}
			echo '</tr>';
			echo "\n"; 					
		}
		
		
		
		echo '<tr class="scroll_head">';
			//NAME-COL: echo '<td>Wochentag</td>';	//Platzhalter 1.Spalte
			$daycnt = 0;
			for ($monat=1; $monat <=12; $monat++){
				$timeStamp=mktime(0,0,0,$monat,1,$this->year);
				$daysOfMonth=date ('t',$timeStamp); //Anzahl der Tage des Monats	
				
							
				for ($day=1; $day <=$daysOfMonth; $day++){
					$timeStamp=mktime(0,0,0,$monat,$day,$this->year);					
					$dayOfWeek=strftime('%a',$timeStamp);
					$this->calTableCache[$daycnt]['dow'] = $dayOfWeek;
					
					
					$loopStyle=NULL;
					$holiday_style=NULL;
					$tooltip=NULL;					
					 
					if ($dayOfWeek=='Sa' or $dayOfWeek=='So') {
						$loopStyle=' style="background:#C0C0FF;"';
					}else {
						
						//ferien
						if (in_array($daycnt, array_keys($this->ferien))){								
							$loopStyle=' style="background-color:#90EE90;"';
							$tooltip=' <span>'.$this->ferien[$daycnt].'</span>';							 
						}
						
						//Feiertag						
						if (in_array($daycnt, $this->holidays)){
							$holiday_style=' style="color:red;"';
							$tooltip=' <span>Feiertag</span>';														
						} 	
					}
					
					$dayOfWeek='<div class="tooltip" '.$holiday_style.'>'.$dayOfWeek.$tooltip.'</div>';
					echo '<td '.$loopStyle.'>'.$dayOfWeek.'</td>'; 
					$daycnt++;
				}
			}
		echo '</tr>';
		echo "\n"; 			
		
	}
	
	/**
	 * 
	 * Add Row to Calendar
	 * @param text $nick_link 
	 * @param array $dateListe = array('start' => $timestamp, 'end' => $timestamp, 'status' => $nummer); //aufsteigend sortiert!!!
	 * @param array $styles
	 */
	function addCalRow($nick, $dateListe){
		
		$daysOfYear = $this->numOfDays;
		$styles=$this->status_styles;
		
		echo '<tr>';
		//NAME-COL: echo '<td>'.$nick.'</td>';
						
		$rowBox=array(); //Array fue Kalenderzeile
		
		foreach ($dateListe as $set){			
			$start =date('z',$set['start']);
			$end=date('z',$set['end']);
			for ($day=$start;$day<=$end;$day++){
				if (isset($rowBox[$day])) $rowBox[$day]='konflikt';
				else $rowBox[$day]=$set['status'];
			}			
		}			

		
		$day=0;
		//showDebArr($rowBox, "RowBox: ");
		while ($day<=$daysOfYear){
			
			//$date = DateTime::createFromFormat('z Y', $day . ' ' . $this->year); 
			//$monthDay=date ('j',$date->getTimestamp()); //Tag im Monat 			
			
			if (isset($rowBox[$day])){
				if ($rowBox[$day]=='konflikt'){
					echo '<td style="text-align:center;color:red;">X</td>';

				}else {
					$style=$styles[$rowBox[$day]]['list_mode'];
					$comment=$styles[$rowBox[$day]]['comment'];
					
					
					//echo '<td style="text-align:center;'.$style.'"><a href="#" class="tooltip">x<span>'.$comment.'</span></a></td>';
					echo '<td style="text-align:center;'.$style.'"><div class="tooltip">x<span>'.$comment.'</span></div></td>';
				}
			} else 	{
				$styleTag = NULL;
				if ($this->calTableCache[$day]['dow']=='Sa' or $this->calTableCache[$day]['dow']=='So') {
					$styleTag=' style="background:#C0C0FF;"';
				}				
				
				echo '<td'.$styleTag.'>&nbsp;</td>';
			}

			$day++;
		}
		echo '</tr>';
		echo "\n"; 				
	}
	
	/**
	 * 
	 * Display information about different colors
	 * @param array $legendArray. e.g. $legendArray[2]['name'] => Name des StatusNr.2 oder $legendArray[2]['list_mode']=> css data
	 */
	function dsplayLegend($legendArray){
		echo '<p><br><b>Legende</b> (Status des Urlaubsantrags):<br>';
		foreach ($legendArray as $status){
			echo '<span style='.$status['list_mode'].'>'.$status['comment'].'</span><br>';
		}		
		echo '<br>Konflikt (&Uuml;berschneidung):<span style=color:red>X</span>';
		echo '</p>';
	}
	
	function endTable(){
		//if ($this->numOfUsers>40) $this->buildCalendarRows();
		
		echo '</table>'."\n";	
		echo '</div> '."\n"; // class="scroll_wrap"
		echo '</div>'."\n";	 //  class="scroll_container" 
		echo '<p class="scroll_clear">'."\n"; // clear css-FLOAT
		//showDebArr($this->calTableCache, "CacheTable: ");
	}
	
	
}