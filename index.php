<?php
session_start();
require_once('classes/class_dfsindex_db_manage.php');
require_once('function_lib.php');
require_once('helper-classes/class_form.inc');
require_once('helper-classes/tableViewRaw.inc');
require_once('helper-classes/class_tableView.inc');
require_once('plug_search.inc');
require_once 'classes/wrapper.php';

//$_SESSION['wrapper']['active_menu']=0;

$thispage = wrapper::getInstance();
$thispage->menu = array(
		'0' => array('index.php','Home'),
		'1' => array('index.php?action=duplicate','Find Duplicate'),
//		'2' => array('menu2.php','Menu2'),
//		'3' => array('#','Menu 3')
);

$action=$_REQUEST['action'];
switch ($action){
	case 'analyze':
		$_POST['parx']=$_SESSION['parx'];
		$thispage->buttons[]= '<button type="button" class="btn btn-primary" onclick="javascript:window.location.href=\'index.php?action=sha1&go=2\'">Analyze SHA1</button>';

		break;
	case 'duplicate':
		
		$_SESSION['wrapper']['active_menu']=1;
		break;
	default:
		$_SESSION['wrapper']['active_menu']=0;
		break;
}


$thispage->htmlHeader();



$_SESSION['sessvars']['debug.val']=1;

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


$_SESSION['config']=$config;

$new_search = new search_MIP();
$new_search->register();

$new_search->run();

$thispage->htmlFooter();

