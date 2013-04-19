<?php

Yii::app()->clientScript->registerLinkTag('shortcut icon', 'image/x-icon', 'data:image/x-icon;,')

    ->registerMetaTag('width=device-width, initial-scale=1.0', 'viewport')

    ->registerCssFile(Yii::app()->request->baseUrl.'/css/bootstrap.min.css')
    ->registerCssFile(Yii::app()->request->baseUrl.'/css/main.css')

    ->registerCoreScript('jquery')->registerPackage('d3')

    ->registerScriptFile(Yii::app()->request->baseUrl.'/js/main.js');

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