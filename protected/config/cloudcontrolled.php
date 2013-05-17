<?php
// this config file is specific for CloudControl hosting

# read the credentials file
$string = file_get_contents($_ENV['CRED_FILE'], false);
if ($string == false) {
	die('FATAL: Could not read credentials file');
}

# the file contains a JSON string, decode it and return an associative array
$creds = json_decode($string, true);

return array(
	'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
	'name'=>'Twitter influence map',
	'defaultController' => 'people',
	
	'import'=>array(
		'application.models.*',
		'application.components.*',
		'ext.YiiMongoDbSuite.*',
		'ext.Codebird.CCodebird',
        'ext.recaptcha.CRecaptcha',
        'ext.yiimailer.YiiMailer',
	),

	// application components
	'components'=>array(
		'cache'=>array(
				'class'=>'system.caching.CApcCache',
		),

        'clientScript' => array(
            'coreScriptPosition' => CClientScript::POS_END,
            'defaultScriptFilePosition' => CClientScript::POS_END,
            'defaultScriptPosition' => CClientScript::POS_END,
            'packages' => array(
                'd3' => array(
                    'basePath' => 'application.vendors.d3',
                    'js' => array(YII_DEBUG ? 'd3.v3.js' : 'd3.v3.min.js'),
                ),
                'bootstrap' => array(
                    'basePath' => 'application.vendors.bootstrap',
                    'js'=>array(YII_DEBUG ? 'js/bootstrap.js' : 'js/bootstrap.min.js'),
                    'css'=>array(YII_DEBUG ? 'css/bootstrap.css' : 'css/bootstrap.min.css'),
                    'depends' => array('jquery'),
                ),
            ),
        ),

        'urlManager'=>array(
			'urlFormat'=>'path',
			'showScriptName'=>false,
			'rules'=>array(
				'<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
			),
		),
		
		'mongodb' => array(
				'class'            => 'EMongoDB',
				'connectionString' => $creds['MONGOLAB']['MONGOLAB_URI'],
				'dbName'           => substr($creds['MONGOLAB']['MONGOLAB_URI'], strrpos($creds['MONGOLAB']['MONGOLAB_URI'], '/') + 1),
				'useCursor'        => true,
		),
		'errorHandler'=>array(
			'errorAction'=>'people/error',
		),
	),

    'params'=>array(
		'adminEmail'=>'jura.khrapunov@undp.org',
        'recaptcha_public_key' => $creds['CONFIG']['CONFIG_VARS']['recaptcha_public_key'],
        'recaptcha_private_key' => $creds['CONFIG']['CONFIG_VARS']['recaptcha_private_key'],
        'remoteip' => getenv('HTTP_X_FORWARDED_FOR'),
        'codebird' => array(
            'consumerkey' => $creds['CONFIG']['CONFIG_VARS']['codebird_consumerkey'],
            'consumersecret' => $creds['CONFIG']['CONFIG_VARS']['codebird_consumersecret'],
            'bearertoken' => $creds['CONFIG']['CONFIG_VARS']['codebird_bearertoken'],
            'appauth' => true,
        ),
        'YiiMailer'=>array(
            'Host' => $creds['MAILGUN']['MAILGUN_SMTP_SERVER'],
            'Username' => $creds['MAILGUN']['MAILGUN_SMTP_LOGIN'],
            'Password' => $creds['MAILGUN']['MAILGUN_SMTP_PASSWORD'],
            'Mailer' => 'smtp',
            'Port' => $creds['MAILGUN']['MAILGUN_SMTP_PORT'],
            'SMTPAuth' => true,
            'From' => 'noreply@ligo.cloudcontrolled.com',
            'FromName' => 'Ligo',
            'CharSet' => 'UTF-8',
            'AltBody' => Yii::t('YiiMailer','You need an HTML capable viewer to read this message.'),

            'viewPath' => 'application.views.mail',
            'layoutPath' => 'application.views.layouts',
            'baseDirPath' => 'webroot.images.mail',
            'layout' => 'mail',
            'language' => array(
                'authenticate'         => Yii::t('YiiMailer','SMTP Error: Could not authenticate.'),
                'connect_host'         => Yii::t('YiiMailer','SMTP Error: Could not connect to SMTP host.'),
                'data_not_accepted'    => Yii::t('YiiMailer','SMTP Error: Data not accepted.'),
                'empty_message'        => Yii::t('YiiMailer','Message body empty'),
                'encoding'             => Yii::t('YiiMailer','Unknown encoding: '),
                'execute'              => Yii::t('YiiMailer','Could not execute: '),
                'file_access'          => Yii::t('YiiMailer','Could not access file: '),
                'file_open'            => Yii::t('YiiMailer','File Error: Could not open file: '),
                'from_failed'          => Yii::t('YiiMailer','The following From address failed: '),
                'instantiate'          => Yii::t('YiiMailer','Could not instantiate mail function.'),
                'invalid_address'      => Yii::t('YiiMailer','Invalid address'),
                'mailer_not_supported' => Yii::t('YiiMailer',' mailer is not supported.'),
                'provide_address'      => Yii::t('YiiMailer','You must provide at least one recipient email address.'),
                'recipients_failed'    => Yii::t('YiiMailer','SMTP Error: The following recipients failed: '),
                'signing'              => Yii::t('YiiMailer','Signing Error: '),
                'smtp_connect_failed'  => Yii::t('YiiMailer','SMTP Connect() failed.'),
                'smtp_error'           => Yii::t('YiiMailer','SMTP server error: '),
                'variable_set'         => Yii::t('YiiMailer','Cannot set or reset variable: ')
            ),

        ),

    ),
);