<?php

$this->pageTitle=Yii::app()->name;

?>

<div class="row-fluid">
    <div class="span8">
        <div id="graph"></div>
    </div>
    <div class="span4">
        <ul class="nav nav-pills">
            <li class="dropdown active">
                <a class="dropdown-toggle" data-toggle="dropdown" href="#">Available groups  <span class="caret"></span></a>
                <ul class="dropdown-menu">
                    <?php
                    foreach($groups as $key => $value) {
                        if ($value > 3) {
                            echo '<li><a href="' . Yii::app()->request->baseUrl . '/#' . $key . '"><span class="badge badge-info">' . $value . '</span> <b>' . $key . '</b></a></li>';
                        }
                    }
                    ?>
                </ul>
            </li>

        </ul>
        <div id="toplist"></div>
        <div id="fps"></div>
    </div>
</div>

<?php
/*<!--
<form class="form-inline" id="people-suggest-form" action="<?php echo $this->createUrl('people/suggest'); ?>" method="get">
	<textarea rows="2" placeholder="Twitter handles" name="handle" id="handle" class="input-medium"></textarea>
	<textarea rows="2" placeholder="Groups" name="group" id="group" class="input-medium"></textarea>
	<button type="submit" class="btn">Submit</button>
</form>
    -->
*/

$api_endpoint = Yii::app()->request->baseUrl;

$script = <<<EOT

    // set graph container's height
    var _graph = $('#graph');
    _graph.height($(window).height() - _graph.offset().top - _graph.offset().left);

    var options = {
        'api_endpoint': '$api_endpoint',
        //'group': '$group',
        'group': window.location.hash.substring(1),
        'maxnodes': '$maxnodes'
    };

    ligo.init(options).renderStats('#toplist').renderGraph('#graph').monitorFps('#fps');

    $(window).bind('hashchange', function(){
        var _group = window.location.hash.substring(1);
        if (ligo.group.toLowerCase() != _group.toLowerCase()) {
            ligo.data = null;
            ligo.group = _group;
            ligo.renderStats().renderGraph();
        }
    });

EOT;

Yii::app()->clientScript->registerScript('ligoinit', $script, CClientScript::POS_READY);

?>