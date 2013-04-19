<?php

if (in_array(getenv('SERVER_ADDR'), array('::1', '127.0.0.1')) ) { // local deployment
	// change the following paths if necessary
	$yii=dirname(__FILE__).'/protected/framework/yii.php';
	$config=dirname(__FILE__).'/protected/config/local.php';
	
	// remove the following lines when in production mode
	defined('YII_DEBUG') or define('YII_DEBUG',true);
	// specify how many levels of call stack should be shown in each log message
	defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);
	
} elseif(isset($_ENV['CRED_FILE'])) { // hosting on cloudcontrol.com
	
	$yii=dirname(__FILE__).'/protected/framework/yiilite.php';
	$config=dirname(__FILE__).'/protected/config/cloudcontrolled.php';
	
}
require_once($yii);
Yii::createWebApplication($config)->run();