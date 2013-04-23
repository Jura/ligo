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

$script = <<<EOT

    /*var _stats = new Stats();
    _stats.domElement.style.position = 'absolute';
    _stats.domElement.style.right = _stats.domElement.style.bottom = '20px';
    $('body').append(_stats.domElement);*/


    var _graph = $('#graph');
    // set container's height
    _graph.height($(window).height() - _graph.offset().top - _graph.offset().left);

    var options = {
        'api_endpoint': '$api_endpoint',
        'group': '$group',
        'maxnodes': '$maxnodes'
    };

    ligo.init(options).renderStats('#toplist').renderGraph('#graph');

    // run performance measurement
    setInterval(function(){
        ligo.setFps();
        if (ligo.fps < 10 && ligo.fps > 0) {
            if (ligo.fps_counter < ligo.fps_max_counter) {
                ligo.fps_counter++;
            } else {
                if (!ligo.fps_flag_raised){

                    console.log('poor performance: ' + ligo.fps);
                    ligo.fps_flag_raised = true;
                }
                ligo.fps_counter = 0;
            }
        }
    }, 1000 / 60);

EOT;

Yii::app()->clientScript->registerScript('ligoinit', $script, CClientScript::POS_READY);

?>