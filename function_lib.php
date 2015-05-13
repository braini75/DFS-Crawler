<?php 
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
/**
 * 
 * Convert linux-Path to Windows, e.g. /dfs/Group -> Z:\Group
 * @param string $path
 */
function pathToWindowsStyle($path){
	$path=str_replace("/", "\\", $path);
	$path=str_replace("\\dfs\\", "Z:\\", $path);
	return $path;	
}

/**
 * 
 * Convert Windowspath to linux-Path, e.g. Z:\Group -> /dfs/Group
 * @param string $path
 */
function pathToLinuxStyle($path){
	$path=str_replace("Z:\\", "\\dfs\\", $path);
	$path=str_replace("z:\\", "\\dfs\\", $path);
	$path=str_replace("\\", "/", $path);	
	return $path;	
}


function humanFileSize($size)
{
    if ($size >= 1073741824) {
      $fileSize = round($size / 1024 / 1024 / 1024,1) . ' GB';
    } elseif ($size >= 1048576) {
        $fileSize = round($size / 1024 / 1024,1) . ' MB';
    } elseif($size >= 1024) {
        $fileSize = round($size / 1024,1) . ' KB';
    } else {
        $fileSize = $size . ' bytes';
    }
    return $fileSize;
}