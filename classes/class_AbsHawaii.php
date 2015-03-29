<?php


/**
 * Abstrakte Klasse zur DRA-Session
 * @author Thomas Koch <thomas.koch@clondiag.com
 * @version 1.0
 * @package hawaii-core
 * 
 */
class AbsHawaiiCls {
    
    private static $instance;
    private $suppressHTML=0; // supress HTML? e.g. on CSV-output
    private $headconfig; // option array from htmlHeader()
    VAR $config=array();
    
    public static function getInstance()    {
        if (!isset(self::$instance)) {
            self::$instance = new AbsHawaiiCls();
            
        }
        
        return self::$instance;
    }
    
    
    private function __construct() {
    	$this->_divEndWrapShow = 1; // show the "</div>" for <div id="wrap"> - tag
        $this->config=$_SESSION['config'];     
                  
    }
    
    /**
     * if a new session start, get config vars
     */
    public function checkConfigVars() {
    	if (is_array($_SESSION['config'])) return;
    	
    	require_once('config.inc');
    }
    
    /**
     * init start of page WITHOUT any html-output
     * - do some checks
     * @param array $options
     */
    public function pureHeader($options) {
    	$this->suppressHTML=1;
    	if (isset($options['noLoginCheck']) and $options['noLoginCheck']>0 ) {
    		// no login check ...
    	} else {
	    	if ( !$this->isLoggedin() ) {
	    		throw new Exception('You are not logged in.' );
	    	}
    	}
    }
	
	/**
	 * 
	 * @param array $options
	 * 	'noLoginCheck' = [0],1
	 *  'title'
	 *  'css' - extra css-text
	 *  'js_scripts' - array of Javascript-files: relativ to MIP-root; e.g. "plugin/hawaii/hallo.js"
	 *  'locrow' : array[] = array(URL, text)
	 *  'accessOnlyAdmin' : [0],1 deny page for normal users
	 *  'centerStyle': where is the main content located?
	 *  	['center'], 'left'
         * 
	 */
    public function htmlHeader($options=NULL) {   
    	$title = $options['title'];
    	$this->headconfig = $options;
    	
    	if ($this->headconfig['centerStyle']==NULL) {
    		$this->headconfig['centerStyle']='center';
    	}
    	
        ?>
<!DOCTYPE html>
<html>
 
            <head>
             <title>MIP - <?php echo $this->headconfig['title']; ?></title>
             <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
             
             <meta name="robots" content="noindex" />
             <meta name="author" content="Thomas Koch" />
             <meta name="application-name" content="MIP - ATJ Mitarbeiter-Info-Portal">
             <meta http-equiv="expires" content="0" />
			 <meta http-equiv="X-UA-Compatible" content="IE=edge" />
			 <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" /> 
			 <link rel="stylesheet" type="text/css" href="hawaii.css" />
             <link rel="stylesheet" href="jquery_lib/css/ui-lightness/jquery-ui-1.10.3.custom.css">
			 <script src="jquery_lib/js/jquery-1.9.1.js"></script>
			 <script src="jquery_lib/js/jquery.ui.core.js"></script>
			 <script src="jquery_lib/js/jquery.ui.datepicker.js"></script>
			 <script src="jquery_lib/js/jquery.ui.datepicker-de.min.js"></script>   
                         <script type="text/javascript" src="jquery_lib/js/jquery-ui-1.10.3.custom.js"></script>
                         
             
			<?php 
			if (is_array($options['js_scripts'])) {
				reset ($options['js_scripts']);
				while ( list(,$val) = each($options['js_scripts']) ) {
					echo '<script src="'.$val.'"></script>'."\n";
				}
				 
			}
			if ($options["css"]!="") {
				echo "<style type=\"text/css\">\n";
				echo $options["css"];
				echo "\n</style>\n";
			}
			?>           
            </head>
            
             <body>
    <br />	
    <div id="wrap">
        <div id="header">
    <table width="820" border="0" cellspacing="0">	 
            <!-- title //-->
            <tr class="headerbox">
             <td id="logo" width="200"><img src="logo.gif" alt="logo"/></td>
             <td><div class="slimparagraph"><p style="float: left; color:#A0A0A0">
             <a href="http://www" class="title_link" >ATJ-Intranet</a> &gt; 
             <a href="p.php?mod=general/home" class="title_link"><?php echo $this->config['title'];?></a> &nbsp;
             <a href="http://userwiki/infra/mip/start" class="title_link" title="Hilfe">(?)</a>
             </p>
             <p style="float: right;"><?php
             if ($this->isLoggedin()) {
				echo '<img src="ic.user.png" title="eingeloggt als"><a href="p.php?mod=general/user_prefs" title="Profil bearbeiten">'.$_SESSION['user']['user'].'</a>';
				echo ' [<a href="p.php?mod=general/logout">logout</a>]';
			 }
             ?></p><div style="clear: both;"></div>
             
            
             <span style="font-size: xx-large; "><?php echo $title;?></span>
             </div>
             </td>
            </tr>
            
            
    </table>
            
            </div><!-- End div#header -->            
           
	<?php
        

        /*
         * Prüfe Login und zeige Menu
         */  
		if (isset($options['noLoginCheck']) and $options['noLoginCheck']>0 ) {
			// no login check
		} else {
			if ( !$this->isLoggedin() ) {
	            // go to login page
	            
				$forward_pure = $_SERVER['PHP_SELF'];
				if ($_SERVER['QUERY_STRING']!=NULL) {
					$forward_pure .='?'.$_SERVER['QUERY_STRING'];
				}
				$forward = urlencode($forward_pure);

	            $this->htmlGo('login.php?forward='.$forward);
	            $this->htmlFooter();
	        }
		}
		
		if ($options['accessOnlyAdmin']>0) {
			if ($_SESSION['user']['is_admin']<=0) {
				$this->htmlError('Diese Seite ist nur erlaubt f&uuml; den Admin.');
			}
		}
		
		$this->locrow($options, $options['locrow']);
		echo "<br />\n";
		if ($this->headconfig['centerStyle']=='left') {
			echo '<!-- end #wrap --></div>'."\n";
		}

	}
	
	/**
	 * show plugin location
	 * @param  $locRowData array[] = array(URL, text)
	 */
	private function locrow( $infox, $locRowIn ) {
		global $PHP_SELF;
	
		if (!is_array($locRowIn)) $locRowIn=array();
		
		if(sizeof($locRowIn)) {
			// go back to last URL in array
			$lastElement = end($locRowIn);
			reset($locRowIn);
			$url = $lastElement[0];
			echo '[<a href="'.$url.'" style="font-weight:bold;">&lt;&lt; Zur&uuml;ck</a>] &nbsp;&nbsp;';
			
		}
		echo '  <span style="color:gray;">';
		echo "<B>Location:</B> ";
		
		
		
		$locRowData[]= array('p.php?mod=general/home','MIP');
		$locRowData = array_merge($locRowData,$locRowIn);
		$tmpsep = "";
		if ( sizeof($locRowData) ) {
			while ( list(, $valarr) = each($locRowData) ) {
				echo $tmpsep;
				if ($valarr[0]!="") echo "<a href=\"".$valarr[0]."\" >".$valarr[1]."</a>";
				else echo "<span >".$valarr[1]."</span>";
				$tmpsep = " &gt; ";
			}
		}
		echo $tmpsep;
		echo "<span >" . $infox['title'] . "</span>";
		echo "</span>\n";
	}
	
	/*
	public function htmlStopCenterStyle() {
		echo '<!-- end #wrap --></div>'."\n";
		$this->_divEndWrapShow = 0;
	}
	*/
	
	
	public function htmlFooter() {
		if (!$this->suppressHTML) {
			?>
            	<!-- end #content -->
            
            <div id="footer">
            
            <?PHP 
            echo '<hr style="color: #808080; background-color: #808080; border: none;  height:1px;">';
			echo 'MIP - Version: '.$_SESSION['config']['version'];
			echo "\n".'  <!-- end #footer --></div>'."\n";
			if ($this->headconfig['centerStyle']=='center') echo '<!-- end #wrap --></div>'."\n";
				
	        echo '</body></html>'."\n";
	  	}
     	exit;
      
    }
	
	/**
	 * show error and exit
	 */
	public function htmlError($text) {
		echo '<p style="text-align:center;"><br />'."\n";
		echo '<table class="failure">'."\n";
		echo '<tr><td>Fehler: '.$text.'</td></tr>'."\n";
		echo '</table></p>'."\n";
                $this->htmlFooter();
		die;
	}
	
	/**
	 * show error and do NOT exit
	 */
	public function htmlErrorShow($text) {
		echo '<p style="text-align:center;"><br />'."\n";
		// echo '<table class="failure">'."\n";
		$this->messageBox('error',  $text );
		
	}
	
	/**
	 * show slim message box
	 * @param string $type : "ok", "error", "warning"
	 * @param string $text : the message text
	 * @param string $moretext : optional more text in extra line
	 * @param string $open : '':default; 'open', 'close'
	 */
	public function messageBox($type,  $text	 =NULL, $moretext=NULL, $open=NULL ) {

		$optx["framecol"] = "#BFF588";
		$optx["icon"]     = "ic.ok40.gif";
		 
		if ($type == "error") {
			$optx["framecol"] = "#FF3333";
			$optx["icon"]     = "ic.err40.gif";
		}
		if ($type == "warning") {
			$optx["framecol"] = "#F2E346";
			$optx["icon"]     = "ic.warn40.gif";
		}
		 
		$closeTxt = "</table></td></tr></table>\n";
		 
		if ($open!="close") {
		?>
	    <table cellpadding="1" cellspacing="0" border="0" width="100%" bgcolor="<?echo $optx["framecol"]?>">
		<tr><td>
		<table cellpadding="5" cellspacing="0" border="0" width="100%" bgcolor="#ffffff">
		 <tr><td width="40" valign="top" align="center">
			<img src="images/<?echo $optx["icon"]?>" width="37" height="40"/>
		  </td>
		  <td ><?echo $text?></td>
		 </tr>
		 <?
	   }
	   
	   if ($moretext!="") {
		 	echo "<tr><td>".$moretext."</td></tr>\n";
	   }
	   
	   if ($open!="open") echo $closeTxt;
	}
	
	/**
	 * show ok box
	 */
	public function htmlOk($text=NULL) {

		$oktext = 'OK';
		
		if ($text!=NULL) {
			$oktext=$text;
		}

		echo '<span style="text-align:center;"><br />'."\n";
		echo '<table style="outline:1px solid green; background-color:#DDFFDD;">'."\n";
		echo '<tr><td style="padding-top: 5px; padding-bottom: 5px; 
			padding-left: 50px; padding-right: 50px;">'.$oktext.'</td></tr>'."\n";
		echo '</table></span>'."\n";
		
	}
	
	
	public function addUserInput($field, $data) {
		$this->userInput[$field]=$data;
	}
	
	public function htmlGoStart() {   		
            $url='login.php';		
            ?>
            <script language="JavaScript">
		
            function FuncForward() {
				location.replace("<?php echo $url ?>");
            }
            setTimeout("FuncForward()", 200);
		</script>
	<?PHP 			
	}
	
	/**
	 * go to other URL and exit this page
	 * - forward can be stopped by $_SESSION['sessvars']['debug.val']>=1
	 * @param array $options
	 * 	'delay' : in seconds : [0]
	 */
	public function htmlGo($url, $options=NULL) {	

		echo '[<a href="'.$url.'">FORWARD ...</a>]<br />';
		if ($_SESSION['sessvars']['debug.val']>=1) {
			echo "Go to: <".$url."> abgebrochen, da Debug!<br>";
			exit;
		}
		
		$delay_ms = 0;
		if ( $options['delay']>0 ) {
			$delay_ms = $options['delay'] * 1000;
		}
		
		$jsCmd = 'location.replace("'.$url.'");';
		echo '<script type="text/javascript"><!--',"\n";
		
		if ( $delay_ms>0 ) {
			echo 'function FuncForward() {'."\n";
			echo '   ' .$jsCmd. "\n";
			echo '} '."\n";
			echo 'setTimeout("FuncForward()", '.$delay_ms.');'."\n";
		} else {
			echo $jsCmd."\n";
		}
		
		echo '//--></script>',"\n";
		exit;			
	}

	function htmlLine($text) {
		echo $text."<br />\n";
	}
	
	// just put the text plain to output
	function htmlRaw($text) {
		echo $text;
	}
    
        
    function isLoggedin() {
			
            if (isset($_SESSION['user']['isloggedin']) and $_SESSION['user']['isloggedin']>0){
                    return 1;
            } else return 0;
    }         

            
        
}


// SOME GLOBAL FUNCTIONS

/**
 *
 * @param type $input
 * @param type $text
 * @param type $minDebLev
 * @return type
 */
function showDebug($input, $debugHint, $minDebLev=1){
	if ($_SESSION['sessvars']['debug.val']<$minDebLev) return;

	echo 'DEBUG: <b>'.$debugHint.': </b>';
	if (is_array($input)) {

		echo '<pre align="left">';
		print_r($input);
		echo '</pre>';
	} else {
		echo $input. "<br />\n";
	}
}


function showDebArr($input, $text, $minDebLev=1){
	if ($_SESSION['sessvars']['debug.val']<$minDebLev) return;
	echo 'DEBUG: <b>'.$text.': </b>';
	echo '<pre align="left">';
	print_r($input);
	echo '</pre><hr>';

}


/**
 * show error; do not exit
 */
function htmlShowError($text) {
	echo '<table class="failure">'."\n";
	echo '<tr><td>Fehler: '.$text.'</td></tr>'."\n";
	echo '</table>'."<br />\n";

}

/**
 * show info; do not exit
 */
function htmlShowInfo($text) {
	echo '<table class="infomsg">'."\n";
	echo '<tr><td>'.$text.'</td></tr>'."\n";
	echo '</table>'."<br />\n";

}

/**
 * Create csv-file from array
 * @param $in_array => array( array(line1), array(line2),... ) 	: Inputarray to be exportet
 * @param $filename => text 	: Filename, default "export.csv"
 * @param $delimiter => text	: delimiter in csv-file, default ";" for excel
 */
function array_to_csv_download($in_array, $filename = "export.csv", $delimiter=";") {
		
	
		//showDebug($in_array,"CSV-Input Array: ");
		// open raw memory as file so no temp files needed, you might run out of memory though
		$f = fopen('php://temp', 'w');
		if (! $f) {
        	throw new Exception("Could not open the file!");
    	}
    	
		fputs( $f, "\xEF\xBB\xBF" ); //UTF-8 BOM
	
		// loop over the input array
		foreach ($in_array as $line) {
			// replace linebreaks in data
			foreach ($line as $col => $field){
				$line[$col] = preg_replace('/[\n\r]+/', ' - ', trim($field));
			} 
			// generate csv lines from the inner arrays			
			if (!empty($line))  fputcsv($f, $line, $delimiter);
		
		}
		
		// rewrind the "file" with the csv lines
		rewind($f);
		
		// 	tell the browser it's going to be a csv file
		header('Content-Type: text/csv;');
		// tell the browser we want to save it instead of displaying it
		header('Content-Disposition: attachement; filename="'.$filename.'"');			
		
		// make php send the generated csv lines to the browser
		fpassthru($f);
		fclose($f);
}
