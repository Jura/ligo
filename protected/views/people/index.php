<?php

$this->pageTitle=Yii::app()->name;

?>

<div class="row-fluid">
    <div class="span8">
        <div id="graph"></div>
    </div>
    <div class="span4">
        <div id="toplist"></div>
    </div>
</div>
<!-- 
<form class="form-inline" id="people-suggest-form" action="<?php echo $this->createUrl('people/suggest'); ?>" method="get">
	<textarea rows="2" placeholder="Twitter handles" name="handle" id="handle" class="input-medium"></textarea>
	<textarea rows="2" placeholder="Groups" name="group" id="group" class="input-medium"></textarea>
	<button type="submit" class="btn">Submit</button>
</form>
 -->
<?php

$api_endpoint = Yii::app()->request->baseUrl;

// avoid using $ for jQuery to prevent parsing them as PHP variables
$script = <<<EOT

    var stats = new Stats();
    stats.domElement.style.position = 'absolute';
    stats.domElement.style.right = stats.domElement.style.bottom = '20px';
    $('body').append(stats.domElement);
    stats.begin();
    setInterval(function(){ stats.update(); }, 1000 / 60);

    var _graph = jQuery('#graph');
    // set container's height
    _graph.height(jQuery(window).height() - _graph.offset().top - _graph.offset().left);

    var options = {
        'api_endpoint': '$api_endpoint',
        'group': '$group',
        'maxnodes': '$maxnodes'
    };

    ligo.init(options).renderStats('#toplist').renderGraph('#graph');

EOT;

Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl.'/js/stats.min.js');
Yii::app()->clientScript->registerScript('ligoinit', $script, CClientScript::POS_READY);

?>