<?php
class wrapper{
	private static $instance;
	VAR $title = "Plugin Wrapper";
	VAR $description ="Auf Bootstrap basierender PHP Wrapper.";
	VAR $buttons = array();
	
	/**
	 * Metainformation for the menu, like link, title(name), extra (an image)
	 * @var array
	 */
	VAR $menu = array(
			'0' => array('index.php','Home1'),
			'1' => array('menu1.php','Menu1'),
			'2' => array('menu2.php','Menu2'),
			'3' => array('#','Menu 3')
			);
	
	public static function getInstance()    {
		if (!isset(self::$instance)) {
			self::$instance = new wrapper();

	
		}
	
		return self::$instance;
	}
	
	function htmlHeader(){
		?>
		<!DOCTYPE html>
<html lang="en">
<head>
  <title>Bootstrap Example</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="http://192.168.56.2/wrapper/bootstrap-3.3.4-dist/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
  <script src="http://192.168.56.2/wrapper/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>
</head>
<body>

<div class="container">
  <h1><?php echo $this->title?></h1>
  <p><?php echo $this->description?></p> 
<ul class="nav nav-tabs">
<?php 
	reset ($this->menu);
	foreach ($this->menu as $key => $valarr )
	{
		$set_activ="";
		if ($_SESSION['wrapper']['active_menu']==$key) $set_activ=' class="active"';
		
		echo '<li'.$set_activ.'><a href="'.$valarr[0].'">'.$valarr[1].'</a></li>';
	}
?>

</ul>
<?php 
	foreach ($this->buttons as $button){
		echo $button;
	}
	}
	
	function htmlFooter(){
		?>
			<div class="row">
    	  		<div class="col-sm-4">.col-sm-4</div>
    			<div class="col-sm-8">.col-sm-8</div>
  			</div>
		</div>
	</body>
</html>
<?php 	
	}
	


}