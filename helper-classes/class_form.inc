<?php
/**
 * manage a HTML-form
 * @package func_form
 * @swreq common requirement
 * @author  Steffen Kube (steffen@clondiag.com)
 * @link  file://CCT_QM_doc/89_1002_SDS_code.pdf#pck:func_form
 * @swreq: 0002114: p.098.HIV-NAT > Themepark > filter for views
 */  
class formc {


var $go;  // 0,1,2, ...  
var $colspan;
var $hiddenarr;
var $htmltab;  // info array
            /*  "col0width"
                "col1width"
                "col2width"
            */

/**
 * - init the HTML-form
 * - set <form> tag
 * - start form table 
 * - manage hidden-fields
 * - variable-array: parx[]
 * 
 * @param $initarr <pre>
    "action"  = URL 
    "ENCTYPE" = "", "multipart/form-data"
    "title"
    "submittitle"
    ... OPTIONAL ... 
    "FORMXTRA" : extra text in <form>-tag
    "goNext"   : 1,2,3, ...
    "goBack"   : 0,1,2, ...
    "colwidth" : array of three columns
    "tabwidth" : width e.g. "80%" or "AUTO"
    "tabnowrap": 0|1
	"tabBgColor": #C0FFC0 backgolor of header
	"tabBgHdImg": image of header image
    "fsize"    : global field size (e.g. 40)
	"cellBgColor": cell back color
	"cellspace": 1,2,3,..
	"dblink"   : 1 => include special javascript scripts ...
  </pre>
 * @param &$hiddenarr
 *  - hidden fields  ( "name" => "value" ) 
 *  - do not give $go here !!!, will be given automatically at the end
 * @param $go, the current go-value 0,1,2, ...
 */

function formc( 
    &$initarr,  
    &$hiddenarr,
    $go
    ) {
    global $s_sessVars;
	
    $this->initarr   = $initarr;
    $this->hiddenarr = $hiddenarr; 
    $this->go        = $go;   
    
    $this->colspan   = 4;
    $this->htmltab["col0width"] = "20%";
    $this->htmltab["col1width"] = "30%";
    $this->htmltab["col2width"] = "50%";
    $this->htmltab["cellspace"] = 0;
	
	if ($initarr["cellspace"]!="") $this->htmltab["cellspace"] = $initarr["cellspace"];
	
    if ($this->initarr["tabnowrap"]) $this->htmltab["NOWRAP"]=" NOWRAP";

    if (is_array($initarr["colwidth"])) {
        $this->htmltab["col0width"] = $initarr["colwidth"][0];
        $this->htmltab["col1width"] = $initarr["colwidth"][1];
        $this->htmltab["col2width"] = $initarr["colwidth"][2];
    }
    
	$tmpcolarr = $this->getColors();
    $this->htmltab["bgcolor"]   = $tmpcolarr["bgcolor"];
	if ($initarr["tabBgColor"]!="" ) $this->htmltab["bgcolor"]   =  $initarr["tabBgColor"];
	
	$this->htmltab["bgcolor2"] = "#FFFFFF";       // #EBF2FA | #EFEFEF;
	if ($initarr["tab2BgColor"]!="" ) $this->htmltab["bgcolor2"]   =  $initarr["tab2BgColor"];
	
    $this->htmltab["keycolor"]  = $tmpcolarr["keycolor"];   
    $this->htmltab["spaceKey"]  = "<td width=1%><img width=10 height=1></td>";  


    $this->htmltab["cellBgColor"] = $initarr["cellBgColor"];
    
    // call it editform, because it is needed for backcalls !!! 
    $tmpform = $initarr["FORMXTRA"];
    if ( $initarr["ENCTYPE"] != "" ) $tmpform .= " ENCTYPE=\"".$initarr["ENCTYPE"]."\"";
	
	
	$this->spaceGif = $s_sessVars["loginURL"]."/0.gif";
    
    echo "<form method=\"post\" name=\"editform\" action=\"". $initarr["action"] ."\" ".$tmpform.">\n"; 
    echo '<style type="text/css">'."\n";
	echo ' table.ttabf {  } '."\n"; // TBD: only MOZILLA supported: -moz-border-radius: 5px;
									// needs also class=ttabf border=0 cellspacing=3 <======
	echo '</style>'."\n";
    
    
}

/**
 * start of a normal form-table
 * - use close() to close it ...
 */
function start() {
	$initarr = &$this->initarr;
	if ($this->initarr["class"] != "") $class = $this->initarr["class"];
    else $class = "ttabf";
    $color1   = $this->htmltab["bgcolor"];
    $color2   = $this->htmltab["bgcolor2"];
    $headtext = $initarr["title"];
    $twidth   = "width=100%";    
    if ( $initarr["tabwidth"]!=""     ) $twidth   = "width=\"".$initarr["tabwidth"]."\""; 
    if ( $initarr["tabwidth"]=="AUTO" ) $twidth   = "";
    
	print "<table class=\"".$class."\" border=0 cellspacing=1 cellpadding=0 bgcolor=".$color1." ".$twidth."><tr><td>";
    print "  <table id=\"form_head_row\" border=0 cellspacing=0 cellpadding=2 bgcolor=".$color1." width=100% valign=top><tr><td>";
    print "    <font color=".$this->htmltab["keycolor"]."><B>".$headtext."</B></font></td>";
    print "    </tr></table>\n";
    print "  <table id=\"form_body\" border=0 cellspacing=".$this->htmltab["cellspace"]." cellpadding=1 bgcolor=".$color2." width=100%>\n\n";
}

/**
 * open formular without standard HTML-Table
 * can be used  instead of start()
 */
public function startSlim() {
	
}

public function closeSlim() {
	echo "\n";
    echo "</form>\n";
}

function getColors() {
	return array(
			"bgcolor"=>"#CAD5E3", 
			"keycolor"  => "#46648C" ); 
				// changing bgcolor
				// 
				/* hell:      #CAD5E3 
					dark-blau: #46648C 
					gelb:      #F8FB13
					dark-gelb: #FDC311  
								*/
}

/**
 * get a HTML-<select> field-string
 * @param  $varname  : FORM-variable-name
 * @param  array $feld key=> "nice name"
 * @param  $preselected : can be a singel STRING or when $option["multiple"] => array ("keyword1" => 1, "keyword2" => 1, ...) 
 * @param  array $option
 *  "selecttext"
	"selbody"   [0] | 1
	"id"       HTML-tag id
	"req" => [0],1 input required ?
 */
function selectFget( $varname, $feld, $preselected, $option=NULL, $attStr=NULL) {
	$tmpmultiple = "";
	$tmpissarr   = 0;
	$found = 0;
	if (is_array($preselected)) $tmpissarr   = 1;
    $selecttext = " --- select ---";
    if ($option["selecttext"] != "") $selecttext = $option["selecttext"];
    
    $tmpID = NULL;
    if ($option['id']!=NULL) $tmpID = ' id="'.$option['id'].'" ';
    if ($option["req"]>0) $requiredTag=' required';
    else $requiredTag=NULL;
    
	$tmptxt  = "<select name=\"".$varname."\" " . $tmpID . $option["selbody"] ." ".$attStr.$requiredTag.">\n";
	$tmptxt .= "<option value=\"\">".$selecttext."</option>\n";	
	if (sizeof($feld)) {
		while (list($id, $text)= each($feld)) {
			$tmpsel = "";
			if ( $preselected == $id ) {
				$tmpsel = "selected";
				$found=1;
			}
			if ( $tmpissarr AND $preselected[$id] )   $tmpsel = "selected";
			$tmptxt .= "<option value=\"". $id ."\" ".$tmpsel."> ".$text."</option>\n";
		}
	}
	if (!$found AND $preselected!="")  
		$tmptxt .= "<option value=\"". $id ."\" selected> ".$preselected."</option>\n";
	$tmptxt .= "</select>\n"; 
	return ($tmptxt);
} 

function radioArrOut($varname, $initval, $radioVals, $radioSeperator) {
	// print radiobuttons
	while ( list($id, $text)= each($radioVals) ) {
			$tmpsel = "";
			if ( $initval == $id ) $tmpsel = "checked";
			echo "<input name=\"".$varname."\" type=radio value=\"". $id ."\" ".$tmpsel."> ".$text."\n".$radioSeperator;	
	}
}

/**
 * 
 * @param string $fieldID; e.g. html-field-ID: "verteter_nice"
 * @param string $ajax_url; e.g. search_ldap_json.php
 * @param string $codename; e.g. parx[genehmiger]
 * @param string $codeval; e.g. preloaded value, like '4193' (employeenumber)
 * @param string $niceVal: e.g. displayed Value, like 'Thomas Koch' (displayname)
 * 
 */
private function _field_ldap($fieldID, $ajax_url, $codename, $codeval, $niceVal, $fsize, $requiredTag) {
       ?>

	<script type="text/javascript">
	$(function() {
	 
	    $("#<?php echo $fieldID;?>").autocomplete({
	        source: "<?php echo $ajax_url;?>",
	        minLength: 2,
	        select: function(event, ui) {
	    
	            /*var url = ui.item.pn;*/       /* liefert Personalnumber (employeeNumber) */
	            var url = ui.item.id;   /* liefertUser-NIC (samAccountName) */
	            if( url !== '' ) {
	                /*location.href = '/blog/' + url;*/                
			document.editform<?php echo '["'.$codename.'"]';?>.value = url;		
			document.getElementById('<?php echo $fieldID;?>').className="auto-found";
	            }
	        },
			
		response: function(event, ui) {
	            /* remove underline and hidden var, if text has changed ... */
	            document.editform<?php echo '["'.$codename.'"]';?>.value = '';
	            document.getElementById('<?php echo $fieldID;?>').className="auto-norm";
				
	        },
			
	 
	        html: true, // optional (jquery.ui.autocomplete.html.js required)
	 
	      // optional (if other layers overlap autocomplete list)
	        open: function(event, ui) {
	            $(".ui-autocomplete").css("z-index", 1000);
	        }
	    });
		
	   
	
	 
	});
	</script>
	<?
    echo "<input class=\"norm\" id=\"".$fieldID."\" type=\"text\" name=\"".$fieldID."\" value=\"".$niceVal."\" ".
	$fsize.$requiredTag." ".$fieldx["attStr"].">";
    echo '<input type="hidden" name="'.$codename.'" value="'.$codeval.'">';
}

/**
 * show one value field
 * @param  field_STRUCT $fieldx
 */
function _valueField(&$fieldx) {
	$fsize    = "";
    if ( $this->initarr["fsize"] !="" ) $fsize = " size=".$this->initarr["fsize"];
    if ( $fieldx ["fsize"] !="" ) $fsize = " size=".$fieldx["fsize"];
    
    $tmpname = "parx[". $fieldx["name"] ."]";
    if ( $fieldx["namex"] == TRUE )  $tmpname = $fieldx["name"] ;
    if ($fieldx["req"]=="yes") $requiredTag=' required';
    else $requiredTag=NULL;
	
	if ($fieldx["view"]!=1) {
    
		switch ( $fieldx["object"] ) {  
			
			case "button":    
				echo "<input type=button name='dummy' value='".$fieldx["val"]."' ";
				if ( $fieldx["inits"]["varset"] !="" ) {
					if ( $fieldx["namex"] == TRUE ) $tmpelemname = $fieldx["inits"]["varset"];
					else    $tmpelemname = "parx[".$fieldx["inits"]["varset"]."]";
					
					echo "onclick=\"document.editform.elements['".$tmpelemname."'].value='".$fieldx["inits"]["value"]."';".
						" document.editform.submit();\"";
				} elseif ( $fieldx["inits"]["func"] !="" ) {
					echo $fieldx["inits"]["func"]; // can be onClick="" ...
				} 
				echo ">";
				break; 
			
			case "checkbox":
				$tmpinit = 1; // default value
				if ($fieldx["inits"]!="") $tmpinit = $fieldx["inits"];
				
				$tmpopt = "";
				if ( $fieldx["val"] == $tmpinit ) $tmpopt = " checked";
				echo "<input type=checkbox name=\"".$tmpname."\" value=\"".$tmpinit."\" $tmpopt>";
				if ( $fieldx["optx"]!="") echo "&nbsp;".$fieldx["optx"]; 
				break;
			
			
			
			case "file":
				echo "<input type=\"file\" name=\"".$tmpname."\" value=\"".$fieldx["val"]."\" ".$fsize.$requiredTag.">";
				break; 
			
			case "info2":
				// NO input field, only the value
				$tmpOut = $fieldx["val"];
				if ($fieldx["inits"]!="") $tmpOut = $fieldx["inits"];
				echo $tmpOut;
				if ($fieldx["name"]!="" ) echo "<input type=\"hidden\" name=\"".$tmpname."\" value=\"".$fieldx["val"]."\">";
				break;      
			
			case "radio":
				$tmpinit = 1;
				if ( sizeof($fieldx["inits"])==1 ) {
					$tmpinit = $fieldx["inits"];
					$tmpopt = "";
					if ( $fieldx["val"] == $tmpinit ) $tmpopt = " checked";
					echo "<input type=radio name=\"".$tmpname."\" value=\"".$tmpinit."\" $tmpopt>";
				} else {
					$tmpSep = "&nbsp;";
					if (  $fieldx["optx"]["rowbr"]==1 ) $tmpSep = "<br>\n";
					$this->radioArrOut($tmpname, $fieldx["val"], $fieldx["inits"], $tmpSep);
				}
				break;    
			
			case "select":
				$selopt=array();
				if ($fieldx["req"]=="yes") $selopt['req']=1;
				$rettxt = $this->selectFget( $tmpname, $fieldx["inits"], $fieldx["val"], $selopt, $fieldx["attStr"]);
				echo $rettxt;
				break;
			
			case "password":
				echo "<input type=\"password\" name=\"".$tmpname."\" value=\"".$fieldx["val"]."\" ".$fsize.$requiredTag.">";
				break;	
			
			case "textarea":
			
				$tmpsize = "";
				$tmprows  = 5; 
				$tmpcols = $fieldx["inits"]["cols"];
				if ( $fieldx["inits"]["rows"]>0 ) $tmprows = $fieldx["inits"]["rows"];
				if ( $tmpcols   ) $tmpsize .= " cols=\"$tmpcols\"";
				
				$tmpsize .= " rows=\"$tmprows\"";
				
				echo "<textarea name=\"".$tmpname."\" ".$tmpsize.$requiredTag.">".$fieldx["val"]."</textarea>\n";
				break;
				
			case "date": //jquery datepicker
				echo "<input class=\"datepicker\" type=\"text\" name=\"".$tmpname."\" value=\"".$fieldx["val"]."\" ".
					$fsize.$requiredTag." ".$fieldx["attStr"].">";
				break;
                            
            case "ajaxselect":
                $niceVal = $fieldx['inits']['niceval'];
                        //showDebug($niceVal, "NiceVAL");
                $ajaxurl = $fieldx['inits']['ajaxurl'];
                $this->_field_ldap( $fieldx["name"].'_ID', $ajaxurl, $tmpname, $fieldx["val"], $niceVal, $fsize,$requiredTag);
				break;
                            
                            
			default:   // "text" !!!
				echo "<input type=\"text\" name=\"".$tmpname."\" value=\"".$fieldx["val"]."\" ".$fsize.$requiredTag." ".$fieldx["attStr"].">";
				break;
		}
		
	} else {
		switch ( $fieldx["object"] ) {  
			case "select":
				$valx = $fieldx["val"];
				if (isset($fieldx["inits"])) {
					echo $fieldx["inits"][$valx];
				} else echo $valx;
				break;
			case "textarea":
				echo "<pre>".$fieldx["val"]."</pre>";
				break;
				
			
			default:   // "text" !!!
				echo $fieldx["val"];
				break;
		}
		if ($fieldx["hidden"]>0) {
			echo '<input type="hidden" name="'.$tmpname.'" value="'.$fieldx["val"].'">'."\n"; 
		}
	}
}

/**
 * only title and value
 */
function slimField( &$fieldx ) {
	global $s_sessVars;
	
	$keycolor = "#46648C";
	$titleStyle = " font-weight:bold; color:".$keycolor.";";
	echo "<td NOWRAP align=right  valign=top>";
    echo "<font style='".$titleStyle."'>".$fieldx["title"]."</font>"; 
    if ($fieldx["req"]=="yes") echo " <img src=\"".$s_sessVars["loginURL"]."/images/redstar.gif\" border=0>";
    
    echo "</td>";
    echo '<td>';
    $this->_valueField($fieldx);
    echo '</td>';
	
}

/**
 * manage only value
 */
function slimField2( &$fieldx ) {

    $this->_valueField($fieldx);

}

function freeRow_open() {
	echo "<tr valign=top>";
    echo "<td colspan=".$this->colspan.">";
}
function freeRow_close() {
	echo "</td></tr>\n"; 
}

/**
 * check input
 * @param $vararr array of input-values e.g. array("prod_date"=>"2010-04-05")
 * @param $fieldName name of field e.g. "prod_date"
 * @param string $fieldType
 * 	 INT
 *   FLOAT
 *   DATE1 "YYYY-MM-DD"
 *   STRING
 * @param $optional 0,1
 * @param $options
 *   'maxlen' : max length of string
 * @return array($ok, $info)
 * 		$ok>0 : ok
 */
function checkField($vararr, $fieldName, $fieldType, $optional=0, $options=NULL) {
	$ok  = 0;
	$info= NULL;
	$val = trim($vararr[$fieldName]);
	
	if (!$optional) {
		if ($val==NULL) {
			$ok   =-1;
			$info ='no value given';
			return array($ok, $info);
		}
	}
	
	
	switch ($fieldType) {
		case "DATE1":
			if ($val==NULL) return array(1, NULL);
			if ( !preg_match( '/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $val ) ) {
				$ok   =-2;
				$info ='bad format; need: "YYYY-MM-DD"';
			} else $ok   = 1;
			break;
		case "STRING":
			 $ok   = 1;
			if ($options['maxlen']>0 and strlen($val)>$options['maxlen']) {
				$ok   =-3;
				$info ='string too long. Max-length:'.$options['maxlen'];
			}
			break;
		default:
			$ok  = 1;
			break;
	}
	return array($ok, $info);
}

/**
 * manage one row (one field) of the form
 * 
 * @param field_STRUCT $fieldx <pre>
	  title => "Name"	// title of field
	, name  => "nachname" // varname 
	, namex => TRUE       // other namespace than parx[name], take pure name
	, val   => "345"      // initial value of the field
	, inits => $optarr    // optional data-array e.g. for select-fields
	, req   => "yes"      // required? yes, [no]
	, errtxt=> ""         // text of an error message, column appears RED
	, type  => "text"     // OPT: text, number
	, notes => "yajaja"   // OPT: notes
	, fsize => "40"       // field size [40] 
	, vmin  => "3"        // OPT: minimum characters allowed
	, vmax  => "50"       // OPT: maximal characters allowed
	, colspan=>"2"        // OPT: span field over next column; default:1 
					         use this for long fileds, e.g. textarea 
	, backcolor=>"#efedfe"// backcolor
	, optional            // 0|1 silent backcolor
	, addobj => "text"	  // add more html-text behind the INPUT-object (e.g. more input fields)
	, view   => [0]|1     // show field in VIEW-mode (no edit possible)
	, hidden => [0]|1     // save field as hidden field (on "view"=1)
	, attStr => disabled="disabled"		  // additional attribute string
	, rowid  => "xf34"    // HTML-tag: id: for this row
	, rowStyle=> "DISPLAY: none" // HTML-css-sytle of the row
	, optx   => ...		  // other options ...
	, object => the input-field type ...
		text    : text-field 
		checkbox: needs "inits"
					 "optx" => optional text behind checkbox 
		radio   : needs ["inits"] = SIZE
				=1 => contains VALUE
				>1 => array of VALUE=>TEXT for an radio field
				give $fieldx["optx"] => array ("rowbr"=>1) line break for radio elements
		select  : needs "inits"= array of key=>value pairs
		textarea: rows=$fieldx["init"]["rows"],
					cols=$fieldx["init"]["cols"]  
		file   : recommended: "name"=>"userfile", "namex"=>TRUE (deny parx-name)
			on upload, the file-info is stored in $_FILES['userfile']
			you have to add  initarr["ENCTYPE"]="multipart/form-data" in 
			the constructor formc() !!!
		hr     : horizontal line (like "space")
		space  : a white space row (like "hr")
		info   : takes only "title" for infotext, full span
		info2  : implement as HIDDEN, shows title, value as HTML-formated text and notes
				   if "inits" !="" => show as HTML-formated text
		password : password style
                ajaxselect: AJAX selection; need 'inits'=>array('ajaxurl'=>string, 'niceval'=>string of nice value; e.g. Name des Stellvertreters)
		button : interactive button 
			take "inits" for javascript text:
			"varset" => "action"  -> the name of a parx[] variable
			"value" => "actlink"
		

      example: $fieldx = array ("title" => "", "object" => "" );
   </pre>
 */
function fieldOut( $fieldx ) { 
    global $error, $s_sessVars;
    $firstspace = "&nbsp;&nbsp;&nbsp; ";
	$bgcolor  = ""; 
    if ($this->htmltab["cellBgColor"]!="") $bgcolor  = " bgcolor=\"".$this->htmltab["cellBgColor"]."\"";
    $keycolor = "#46648C";
    
                           
    if ( $fieldx["object"] == "info") {
        echo "<tr valign=top bgcolor=".$this->htmltab["bgcolor"].">"; // #C0C0C0,<font color=".$keycolor.">
        echo "<td colspan=".$this->colspan." valign=top>".$firstspace."<font color=".$keycolor."><B>".$fieldx["title"]."</B></font>";
        echo "</td></tr>\n";  
        return;
    } 
    if ( $fieldx["object"] == "hr") {     
        echo "<tr bgcolor=".$this->htmltab["bgcolor"].">";
        echo "<td colspan=".$this->colspan."><img src=\"".$this->spaceGif."\" height=1 width=1>";
        echo "</td></tr>\n";  
        return;
    }
	if ( $fieldx["object"] == "space") {     
        echo "<tr".$bgcolor.">";
        echo "<td colspan=".$this->colspan."><img src=\"".$this->spaceGif."\" height=10 width=1>";
        echo "</td></tr>\n";  
        return;
    }
	
    
    $titleStyle = "";
    
    if ($fieldx["optional"]) {
        $bgcolor  = " bgcolor=\"#EFEFEF\"";
        $keycolor = "#66849C";
    } else {
		$titleStyle .= " font-weight:bold;";
	}
	if ($fieldx["backcolor"]!="") {
        $bgcolor  = " bgcolor=\"".$fieldx["backcolor"]."\"";
    }
    if ($fieldx["errtxt"] !="" ) {
    	$keycolor='#FF0000';
    	// $bgcolor=" bgcolor=\"#FFDFDF\"";
    } 
	$titleStyle .= " color:".$keycolor.";"; 
	
	$rowidStr=NULL;
	if ($fieldx["rowid"]!=NULL) {
		$rowidStr = ' id="'.$fieldx['rowid'].'" ';
	}
	
	$rowStyleStr=NULL;
	if ($fieldx["rowStyle"]!=NULL) {
		$rowStyleStr = ' style="'.$fieldx['rowStyle'].'" ';
	}
	
    echo "<tr " . $bgcolor . $rowidStr . $rowStyleStr. " valign=top>";
    echo "<td NOWRAP align=right  valign=top width=".$this->htmltab["col0width"].">";
    echo "<font style='".$titleStyle."'>".$fieldx["title"]."</font>"; // TBD: font-size
    //if ($fieldx["req"]=="yes") echo " <img src=\"".$s_sessVars["loginURL"]."/images/redstar.gif\" border=0>";
    if ($fieldx["req"]=="yes") echo "<span style=\"color:red\"> *</span>";
    
    echo "</td>";
    echo $this->htmltab["spaceKey"];
    $colspanHtml="";
    if ( $fieldx["colspan"]==2 ) $colspanHtml=" colspan=2";
    echo "<td".$colspanHtml.$this->htmltab["NOWRAP"].">";
    
    $this->_valueField($fieldx);
    
    if ( $fieldx["colspan"]!=2 ) {
		if ( $fieldx["addobj"] != "") echo $fieldx["addobj"];
        echo "</td>";
        echo "<td width=".$this->htmltab["col2width"].$this->htmltab["NOWRAP"].">";
    }
    
    // notes-field
    if ($fieldx["errtxt"]!="") {
        echo "[<font color=red>".$fieldx["errtxt"]."</font>] ";
    }
    echo "<font color=gray>".$fieldx["notes"]."</font></td>";
    echo "  </tr>\n"; 

} 

function editViewBut(
	$modenow,		// ["view"], "edit"
	$edit_possible, // 0|1
	$seturl,  		// go to ... e.g. "waferfunc.php?id=92939"
	$formvar		// e.g. "viewmode"
	) {
	// FUNCTION: show edit/view buttons
	global $s_sessVars;
	
	$pioPath     = $s_sessVars["loginURL"]."/";
	// $urlenc = urlencode($backurl);
	// $sessvarname = 'o.'.$tablename.'.editmode';
	// $s_sessVars[$sessvarname]
	// $seturl = $pioPath."sessVars_set.php?variable=".$sessvarname."&backurl=".$urlenc."&val=";
	
	if ( $modenow == 'edit') {
		$but_view = $pioPath."images/but.view.gif";
		$but_edit = $pioPath."images/but.edit.ac.gif";	
	} else {
		$but_view = $pioPath."images/but.view.ac.gif";
		$but_edit = $pioPath."images/but.edit.gif";
	}
	
	if ( !$edit_possible )  $but_edit = $pioPath."images/but.edit.no.gif";
	$outstr  = "<a href=\"".$seturl."&".$formvar."=view\" >";

	$outstr .= '<img src="'.$but_view.'" TITLE="view" border=0></a>';
	if ( $edit_possible ) {
			$outstr .= "<a href=\"".$seturl."&".$formvar."=edit\" >".'<img src="'.$but_edit.'" TITLE="edit" border=0></a>';
	} else  $outstr .= '<img src="'.$but_edit.'" TITLE="edit">';
		
	return ($outstr); 
}

function addHiddenParx( $parx ) {
	while ( list($key, $val) = each($parx) ) {
		$this->hiddenarr["parx[".$key."]"] = $val;
	}
	reset ($parx); 
}
  
// show whole array
function fieldArrOut( $fieldArr ) {

	while ( list(, $valarr) = each( $fieldArr) ) {
		$this->fieldOut(  $valarr );
	}
	reset ( $fieldArr); 
} 

/**
 * - set submit buttons 
 * - set hidden fields
 * - close form
 * - close table 
 * 
 * @param $allowFlag,     
 *  - [TRUE] - submit button ACTIVE
 *  - FALSE  - submit button incative
 * @param $option = NULL  
 *  - ["noSubmitButton"]  0|1 do not show any submit button
 *  - ["noRow"]      0|1 do not show any BOTTOM row (including the buttons) !
 *  - ["addObjects"] = "TEXT" TEXT contains HTML text like extra buttons 
 *  - ["noBackButton"] = 0|1
 */
function close( $allowFlag,
                $option = NULL
     ) {
    
     $tabBotMore = "";
	 if ($this->initarr["tabBgHdImg"]!="" ) $tabBotMore .= " background=\"".$this->initarr["tabBgHdImg"]."\"";	 
     
     if ( !$option["noRow"] ) {  // show BOTTOM submit-row ?
       
        $newbgcol= $this->htmltab["bgcolor"] ; //  "#FDC311"  
        echo "<tr bgcolor=\"".$newbgcol."\" height=5>";
        echo "<td width=".$this->htmltab["col0width"]."><img width=1 height=1></td>"; // 1. col
        echo $this->htmltab["spaceKey"];
        echo "<td width=".$this->htmltab["col1width"]."><img width=1 height=1></td>"; // 2. col
        echo "<td><img width=1 height=1></td>";                                       // 3. col
        echo "</tr>\n";

        echo "<tr id=\"form_submit_row\" bgcolor=\"".$newbgcol."\"".$tabBotMore.">";  
        echo "<td nowrap>";     // 1. col

        if ( ($this->go>0) AND (!$option["noBackButton"])) {
            $goBack = $this->go - 1; 
            if ( $this->initarr["goBack"]!="" ) $goBack = $this->initarr["goBack"];
            echo "<input type=button name='dummy' value='&lt;&lt; Back' onclick=\"document.editform.go.value=".$goBack."; document.editform.submit();\">\n";
        }
        echo "&nbsp;</td>";
        echo $this->htmltab["spaceKey"];
        echo "<td nowrap colspan=2>";    // 2. col

        if ($option["noSubmitButton"] != "1") { // show submit-button info ???

            if ( $allowFlag != FALSE ) {
                $submittitle = "Submit";
                if ( $this->initarr["submittitle"]!="" ) $submittitle = $this->initarr["submittitle"];
                echo "<input class=\"buttonSubmit\" type=submit value=\"".$submittitle."\">\n"; 
            } else {
                echo "<font color=red>Submit is not allowed</font>";
            }

        } else echo "&nbsp;";     // no submit button, even no error
		
		if ($option["addObjects"]!="" ) {
			echo "&nbsp;".$option["addObjects"];
		}
        echo "</td>"; 

        // echo "<td>&nbsp;</td>";   // 3. col
        echo "</tr>\n";
     }
     
     echo "  </table>\n";
     echo "</td></tr></table>\n";   
     echo "\n";
               
        
     $gotmp = $this->go + 1;
     if ( $this->initarr["goNext"]!="" ) $gotmp = $this->initarr["goNext"];
     echo "<input type=\"hidden\" name=\"go\" value=\"".$gotmp."\">\n"; 
        
     if (sizeof($this->hiddenarr)) { 
        while (list($idx, $valx) = each($this->hiddenarr)) {  
               echo "<input type=\"hidden\" name=\"". $idx ."\" value=\"".$valx."\">\n";
        }
        reset($this->hiddenarr);
        echo "\n";
     
     }
     
     echo "\n";
     echo "</form>\n";
}

}