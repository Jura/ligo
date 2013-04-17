<?php /* @var $this Controller */ ?>
<!DOCTYPE html>
<html>
  <head>
    <title><?php echo CHtml::encode($this->pageTitle); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="data:image/x-icon;," type="image/x-icon">
    <?php echo CHtml::cssFile(Yii::app()->request->baseUrl.'/css/bootstrap.min.css'); ?>
    
    <?php //echo CHtml::cssFile(Yii::app()->request->baseUrl.'/css/bootstrap-responsive.min.css'); ?>
    
    <?php echo CHtml::cssFile(Yii::app()->request->baseUrl.'/css/main.css'); ?>
    
    <?php echo CHtml::scriptFile(Yii::app()->request->baseUrl.'/js/jquery-1.9.1.min.js'); ?>
	  
	<?php //echo CHtml::scriptFile(Yii::app()->request->baseUrl.'/js/bootstrap.min.js'); ?>
	  
	<?php echo CHtml::scriptFile(Yii::app()->request->baseUrl.'/js/d3.v3.min.js'); ?>
	
	</head>
  <body>
	  <?php echo $content; ?>
  </body>
</html>