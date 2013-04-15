<?php

if (in_array(getenv('SERVER_ADDR'), array('::1', '127.0.0.1')) ) {
	// change the following paths if necessary
	$yii=dirname(__FILE__).'/../../../programmes/yii/framework/yii.php';
	$config=dirname(__FILE__).'/protected/config/local.php';
	
	// remove the following lines when in production mode
	defined('YII_DEBUG') or define('YII_DEBUG',true);
	// specify how many levels of call stack should be shown in each log message
	defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);
	
	require_once($yii);
	Yii::createWebApplication($config)->run();
	
} elseif(isset($_ENV['CRED_FILE'])) {
	
	// change the following paths if necessary
	$yii=dirname(__FILE__).'/protected/framework/yiilite.php';
	$config=dirname(__FILE__).'/protected/config/cloudcontrolled.php';
	
	// remove the following lines when in production mode
	// defined('YII_DEBUG') or define('YII_DEBUG',true);
	// specify how many levels of call stack should be shown in each log message
	//defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);
	
	require_once($yii);
	Yii::createWebApplication($config)->run();
	
}
