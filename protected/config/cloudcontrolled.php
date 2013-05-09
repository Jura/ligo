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
	'name'=>'Ligo',
	'defaultController' => 'people',
	
	'import'=>array(
		'application.models.*',
		'application.components.*',
		'ext.YiiMongoDbSuite.*',
		'ext.Codebird.*',
        'ext.recaptcha.*',
        'ext.phpmailer.PHPMailer',
	),

	'modules'=>array(
	),

	// application components
	'components'=>array(
		'cache'=>array(
				'class'=>'system.caching.CApcCache',
		),

        'phpmailer'=>array(
            'class' => 'PHPMailer',
            'Host' => $creds['MAILGUN']['MAILGUN_SMTP_SERVER'],
            'Username' => $creds['MAILGUN']['MAILGUN_SMTP_LOGIN'],
            'Password' => $creds['MAILGUN']['MAILGUN_SMTP_PASSWORD'],
            'Mailer' => 'smtp',
            'Port' => $creds['MAILGUN']['MAILGUN_SMTP_PORT'],
            'SMTPAuth' => true,
            //'SMTPSecure' => 'tls',
            'CharSet' => 'utf-8',
            'From' => 'noreply@cloud32.mailgun.org',
            'FromName' => 'Ligo via MailGun',
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
        'recaptcha_public_key' => '6LdZH8ESAAAAAO6O9yaiaNqy9h05OKQ_VnnX7qOB',
        'remoteip' => getenv('HTTP_X_FORWARDED_FOR'),
	),
);