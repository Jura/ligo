<?php

$this->pageTitle=Yii::app()->name;

?>

<div class="row-fluid">
    <div class="span8">
        <div id="graph"></div>
    </div>
    <div class="span4">
        <div id="toplist"></ul>
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

Yii::app()->clientScript->registerScript('ligoinit', $script, CClientScript::POS_READY);


/*var $top = $('#toplist');
 var ordered = Object.keys(data.options.top).sort(function(a,b){return parseInt(b)-parseInt(a)});
 for (var i = 0; i < ordered.length; i++) {
 var $div = $top.append('<h2>' + ordered[i] + ' followers</h2>').after('<div></div>'), source;
 for (var j = 0; j < data.options.top[ordered[i]].length; j++) {
 source = data.nodes[data.options.top[ordered[i]][j]];
 $node = (source.image) ? $('<a href="http://twitter.com/' + source.handle + '"><img src="' + source.image + '" title="@' + source.handle + '" class="img-rounded"></a>') : source.handle ;
 $div.append($node);
 }
 if ($top.find('li').size() > 9) {
 break;
 }
 }*/
/*$.each(data.options.top, function(key) {
 var $div = $top.append('<h2>' + key + ' followers</h2>').after('<div></div>'), source;
 for (var i = 0; i < data.options.top[key].length; i++) {
 source = data.nodes[data.options.top[key][i]];
 $node = (source.image) ? $('<a href="http://twitter.com/' + source.handle + '"><img src="' + source.image + '" title="@' + source.handle + '" class="img-rounded"></a>') : source.handle ;
 $div.append($node);
 }
 if ($top.find('li').size() > 9) {
 return false;
 }
 });*/

?>