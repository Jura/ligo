<?php 

if (isset($_GET['callback'])) {
	// JSONP request
	header('Content-type: application/javascript');
	echo $_GET['callback'] . '(' . CJSON::encode($content) . ');';
	
} else {
	
	header('Content-type: application/json');
	echo CJSON::encode($content); 
	
}
?>