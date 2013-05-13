<?php

header('Content-Type: text/html; charset=utf-8');

Yii::app()->clientScript->registerLinkTag('shortcut icon', 'image/x-icon', Yii::app()->request->baseUrl.'/favicon.ico')
//->registerLinkTag('shortcut icon', 'image/x-icon', 'data:image/x-icon;,')

    ->registerMetaTag('width=device-width, initial-scale=1.0', 'viewport')

    ->registerCoreScript('jquery')->registerPackage('d3')->registerPackage('bootstrap')

    ->registerScriptFile(Yii::app()->request->baseUrl.'/js/ligo.js')

    ->registerCssFile(Yii::app()->request->baseUrl.'/css/main.css')

    ->registerScriptFile(Yii::app()->request->baseUrl.'/js/main.js');

if (!YII_DEBUG) {
$ga = <<<'EOT'

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-36042003-3']);
  _gaq.push(['_setDomainName', 'cloudcontrolled.com']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

EOT;

Yii::app()->clientScript->registerScript('ga', $ga);
}


?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo CHtml::encode($this->pageTitle); ?></title>
</head>
<body>
<?php echo $content; ?>
</body>
</html>