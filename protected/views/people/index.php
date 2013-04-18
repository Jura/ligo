<?php
/* @var $this SiteController */

$this->pageTitle=Yii::app()->name;

?>

<div class="row-fluid">
    <div class="span8">
        <h1>Graph</h1>
        <div id="toplist"></div>
    </div>
    <div class="span4">
        <h1>Stats</h1>
    </div>
</div>
<!-- 
<form class="form-inline" id="people-suggest-form" action="<?php echo $this->createUrl('people/suggest'); ?>" method="get">
	<textarea rows="2" placeholder="Twitter handles" name="handle" id="handle" class="input-medium"></textarea>
	<textarea rows="2" placeholder="Groups" name="group" id="group" class="input-medium"></textarea>
	<button type="submit" class="btn">Submit</button>
</form>
 -->
<script>

var force,svg;

function getSize(current, min, max) {
	var minsize = 8, range = 64;
	var size = Math.round(((current - min) * range/(max - min))) + minsize;
	// if the node is out of min/max range - use zero based scale
	if (size < minsize) {
		size = Math.round(current*range/max);
	}
	return size;
}

function renderGraph(group) {
    var $toplist = $('#toplist');
	
	if ($toplist.find('svg').size() > 0) {
        $toplist.find('svg').empty();
    } else {
        $toplist.height($(window).height() - $toplist.offset().top - $toplist.offset().left);
        var width = $toplist.width(), height = $toplist.height();
        svg = d3.select('#toplist').append('svg').attr("width", width).attr("height", height);
        force = d3.layout.force().linkDistance(200).charge(-2000).gravity(1).size([width, height]);
    }

	var data = {'maxnodes': <?php echo $maxnodes; ?>};
     if (group && group != '') {
         data.group = group;
    }
	
	$.getJSON('<?php echo $this->createUrl('people/getNodesAndLinks'); ?>?callback=?', data).done( function(data) {

		force.nodes(data.nodes).links(data.links).start();

		var link = svg.selectAll(".link").data(data.links).enter().append("line").attr("class", "link");
	  	var node = svg.selectAll(".node").data(data.nodes).enter().append("g").attr("class", "node").call(force.drag);

		node.each(function(d, i) {
			var _this = d3.select(this);
			var _groupclass = (data.nodes[i].group) ? 'group' : 'ext';
			var size = getSize(data.nodes[i].size, data.options.min, data.options.max);
			if (data.nodes[i].image) {
                _this.append('svg:defs')
                    .append('svg:pattern').attr('id', 'pattern-' + i).attr('patternUnits','objectBoundingBox').attr('width', 1).attr('height', 1)//userSpaceOnUse
                    .append('svg:image').attr('xlink:href', data.nodes[i].image).attr('x', 0).attr('y', 0).attr('width', size).attr('height', size);
                _this.append("svg:circle").attr("r", size/2).attr('fill', 'url(#pattern-' + i + ')').classed('graph-' + _groupclass + '-handle', true);
				_this.append('title').text(data.nodes[i].handle + ': ' + data.nodes[i].size + ' followers');
			} else {
				_this.append("svg:circle").attr("r", size/2).classed('graph-unknown-handle', true);
				_this.append('title').text(data.nodes[i].size + ' followers');
			}
							
		});

	  	force.on("tick", function() {
		    link.attr("x1", function(d) { return d.source.x; })
		        .attr("y1", function(d) { return d.source.y; })
		        .attr("x2", function(d) { return d.target.x; })
		        .attr("y2", function(d) { return d.target.y; });

		    node.attr("transform", function(d) { return "translate(" + d.x + "," + d.y + ")"; });
	  	});	
	});
	
}

$(function(){

	renderGraph('<?php echo $group; ?>');
	
	$('#people-suggest-form').submit(function(e) {
		e.preventDefault();
		$.getJSON($(this).prop('action'), {'handle': $('#handle').val(), 'group': $('#group').val()}).done(function(data) {
			console.log(data);
			renderGraph('<?php echo $group; ?>');	
		});
		return false;
	});
});
</script>