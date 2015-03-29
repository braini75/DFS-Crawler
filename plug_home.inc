<?php

/**
 * HOME of DFS-Indexer

 * @package plug_dfsindex
 * @author  Thomas Koch

 */
class home_MIP extends MIP_PLUGIN {

	function register() {
		$this->_infox['title']='Alere Jena DFS Index Suche';
		//$this->_ecmdb_handle = new class_db($_SESSION['config']['ecm']);
		$this->_infox['noLoginCheck'] = 1;
	}
		
	
	function run() {
		
		?>
		<br />
		<br />
		Willkommen bei <?php echo $this->_infox['title'];?><br><br>
		<?php				
		
		$links=array(); // 0:link, 1: text, 2: other text/image
		
		$links[]=array('p.php?mod=dfsindex/search','Suche in DFS-Indexer');
		 
		$links[]=array('','');
		$links[]=array('http://userwiki/infra/mip/mallorca','Hilfe');


		echo '<ul>';
		reset ($links);
		while ( list(, $valarr) = each($links) ) {
			if ($valarr[1]=="") echo "<br>\n";
			else {
				echo '<li><a href="'.$valarr[0].'">'.$valarr[1].'</a>';
				if (isset($valarr[2])) echo  ' '.$valarr[2];
				echo '</li>'."\n";
			}
		}
		echo '</ul>'."<br>\n";
				
		
		
	}
	
}