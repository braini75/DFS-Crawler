<?php
/**
 * helper methods for HTML visualization
 * - tables
 * - graphic buttons
 * - links
 * @namespace core::gui
 * @package tableViewRaw.inc
 * @author  Steffen Kube (steffen@clondiag.com) 
 */

class tableViewRaw { 
	var $options;




/**
 * start table head
 * @param  array $header  // can be NULL
 * @param  array $opt
 *  "title"   = title-text
 *  "colopt"  = <DEPRICATED: use "cssclass"> array[COLID] = text
 *  "headNoShow" = [0], 1 -- do not show header
 *  "noCellBorder" <DEPRICATED: use "cssclass"> = 0|1
 *  "cssclass"     e.g. "datatab"
 *  "colThCss" = array of styles for header
 *  "format"   = ["html"] | "csv"
 *  "colOptArr" = array of extra COLUMN text (after the main column name)
 *  			  has the same INDEX like $header
 */
function table_head( 
	&$header, 
     $opt=NULL 
    ) {

	$borderColor="#B0B0B0";
	
	$tmpcellspace = 1;
	$thisclass = "";
	if ($opt["cssclass"]!="") {
		$thisclass = "class=\"".$opt["cssclass"]."\" ";
	}
	$this->cellColor = "#F3F3F3";
	
	$this->options = $opt;
	
	if ($this->options["format"]=="csv") {
	
		if ($opt["title"]!="") {
			echo "# ".$opt["title"]."\n";
		}
		if (is_array($header) AND !$opt["headNoShow"]) {
			$tmptab = "";
			while (list (,$tmptxt)= each($header) ) {
				echo $tmptab. $tmptxt;
				$tmptab="\t";
			}
			echo "\n"; 
			reset ( $header );
		}
		
	} else {
	
		echo "<table cellpadding=1 cellspacing=$tmpcellspace border=0 ".$thisclass."bgcolor=".$borderColor.">\n";
		
			
		if ($opt["title"]!="") {
			$colspan=2;
			if (sizeof($header)) $colspan=sizeof($header);
			echo "<tr><td colspan=$colspan><font color=white><b>".$opt["title"]."</b></font></td></tr>";
		}
		
		if ($opt["noCellBorder"]) echo "<tr><td><table cellpadding=1 cellspacing=0 border=0 width=100% bgcolor=".$borderColor.">\n";
		
		if (is_array($header) AND !$opt["headNoShow"]) {
			
			echo "<tr bgcolor=\"#D0D0D0\" style=\"color:#707070; font-weight:bold;\">";
			$i=0;
			while (list ($colid,$tmptxt)= each($header) ) {
				$tdopt = $opt["headopt"][$i];
				if ($opt["colThCss"][$i]!="" ) {
					$tdopt .=" class=\"".$opt["colThCss"][$i]."\"";
				}
				echo "<td $tdopt>".$tmptxt;
				if (is_array($opt["colOptArr"] )) { // show extra text, like sort criteria
					echo ' '. $opt["colOptArr"][$colid];
				}
				echo "</td>";
				$i++;
			}  
			reset ( $header );
		}
		$this->colopt  = $opt["colopt"];
		echo "</tr>\n";
	} 
}

/**
 * @param $dataArr
 * @param $opt=NULL	<pre>
 * 	"bgcolor" = "#EEEEEE"
 *  "colspan" = NUMBER
 *  "other"   = variable text per column
 *  "trOpt"   = optional text in TR-tag
 *  </pre>
 */
function table_row( $dataArr, $opt=NULL) {
	$format="";	
	if (isset($this->options["format"])){
		$format=$this->options["format"];
	} 
	if ($format=="csv") {
	
		$i=0;
		$tmptab = "";
		while (list (,$tmptxt)= each($dataArr) ) {
			echo $tmptab.$tmptxt;
			$tmptab="\t";
			$i++;
		}
		echo "\n";
		
	} else {
	
		$thisbgcolor = $this->cellColor;
		if ($opt["bgcolor"] !="") $thisbgcolor = $opt["bgcolor"];
		$tmptd = "";
		if ($opt["colspan"] !="") $tmptd  = " colspan=\"".$opt["colspan"]."\"";
		if ($opt["other"] !="")   $tmptd .= " ".$opt["other"];
		
		echo "<tr bgcolor=".$thisbgcolor.' '.$opt["trOpt"].'>'; 
		$i=0;
		while (list (,$tmptxt)= each($dataArr) ) {
			echo "<td ".$this->colopt[$i].$tmptd.">".$tmptxt."</td>";
			$i++;
		}
		echo "</tr>\n";
	}
}

/**
 * set HTML-option per column <td>
 * @param array $colopt = array(POS=>text)
 */
function setColOpt($colopt) {
	$this->colopt = $colopt;
}




	
function table_close() {
	if ($this->options["format"]=="csv") {
		// nothing ....
	} else {
		if ($this->options["noCellBorder"]) echo "</table></td></tr>\n";
		echo "</table>\n";
	}
}   







}