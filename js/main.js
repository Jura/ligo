'use strict';
/**
 * User: jura.khrapunov
 * Date: 18.4.2013
 * Time: 16:11
 *
 * monitorFps() is inspired by http://github.com/mrdoob/stats.js
 *
 * dependency: jQuery, D3.js
 */

(function( ligo, $, undefined ) {

    //Private Properties
    var inprogress = false,

        stats_container = 'body',

        graph_container = 'body',
        graph_force = null,
        graph_svg = null,
        graph_width = 100,
        graph_height = 100,

        fps_container = 'body',
        fps_prevTime = Date.now(),
        fps = 0,
        fps_frames = 0,
        fps_counter = 0,
        fps_ticker = null;

    //Public Properties
    ligo.data = null;
    ligo.api_endpoint = null;
    ligo.group = null;
    ligo.maxnodes = null;
    ligo.default_image = 'https://si0.twimg.com/sticky/default_profile_images/default_profile_0_normal.png';

    // default setting for graph vis
    ligo.graph_node_min_size = 8;
    ligo.graph_node_range = 64;
    ligo.d3_linkDistance = 200;
    ligo.d3_charge = -2000;
    ligo.d3_gravity = 1;

    // performance data
    ligo.fps = 0;


    /**
     * Public methods
     */

    ligo.init = function(options){
        for (var _option in options) {
            if (typeof ligo[_option] != 'undefined') {
                ligo[_option] = options[_option];
            }
        }
        return this;
    };

    ligo.loadData = function () {
        var callback = arguments[0] || null,
            reload = arguments[1] || false;
        if (inprogress) {
            var i = arguments[2] || 0;
            if (i < 100) {
                setTimeout(function(){ligo.loadData(callback, reload, i++)}, 100);
            } else {
                throw 'data loading takes too long';
            }
        } else if (ligo.data === null || reload) {
            inprogress = true;
            $.getJSON(ligo.api_endpoint + '/people/getNodesAndLinks?callback=?', {'maxnodes': ligo.maxnodes, 'group': ligo.group}).done( function(data) {
                // get an independent clone using a deep copy
                ligo.data = $.extend(true, {}, data);
                inprogress = false;
                if ($.isFunction(callback)) {
                    callback.call();
                }
            });
        } else if ($.isFunction(callback)) {
            callback.call();
        }
        return this;
    };

    ligo.renderGraph = function() {
        var container = arguments[0] || graph_container,
            reload = arguments[1] || false;

        // setup container's params if the argument provided
        if (container != graph_container || reload) {
            graph_svg = null;
            graph_container = container;
            graph_width = $(graph_container).width();
            graph_height = $(graph_container).height();
        }

        if (ligo.data === null || reload) {
            ligo.loadData(ligo.renderGraph, reload);
        } else {
            var force = ligo.getForce(), svg = ligo.getSvg(), data = ligo.data;

            force.nodes(data.nodes).links(data.links).start();

            var link = svg.selectAll(".link").data(data.links).enter().append("line").attr("class", "link");
            var node = svg.selectAll(".node").data(data.nodes).enter().append("g").attr("class", "node").call(force.drag);

            // add legend
            svg.append('svg:rect').attr('rx', 16).attr('ry', 16).attr('y', 16).attr('width', 200).attr('height', 60).classed('graph-legend', true);
            svg.append('svg:circle').attr('r', 8).attr('cx', 16).attr('cy', 32).classed('graph-group-handle', true).style('cursor', 'default');
            svg.append('svg:text').attr('x', 30).attr('y', 36).text(' - members of the group');
            svg.append('svg:circle').attr('r', 8).attr('cx', 16).attr('cy', 56).classed('graph-unknown-handle', true);
            svg.append('svg:text').attr('x', 30).attr('y', 62).text('- external handles');

            node.each(function(d, i) {
                var _this = d3.select(this);
                var _groupclass = (data.nodes[i].group) ? 'group' : 'ext';
                var size = getSize(data.nodes[i].size, data.options.min, data.options.max);

                // render nodes
                if (data.nodes[i].image) {
                    _this.append('svg:defs')
                        .append('svg:pattern').attr('id', 'pattern-' + i).attr('patternUnits','objectBoundingBox').attr('width', 1).attr('height', 1)//userSpaceOnUse
                        .append('svg:image').attr('xlink:href', data.nodes[i].image).attr('x', 0).attr('y', 0).attr('width', size).attr('height', size);
                    _this.append('svg:circle').attr("r", size/2).attr('fill', 'url(#pattern-' + i + ')')
                        .classed('graph-' + _groupclass + '-handle', true)
                        .on('click', function(){window.location.href='https://twitter.com/' + data.nodes[i].handle});
                    _this.append('title').text('@' + data.nodes[i].handle + ': ' + data.nodes[i].size + ' followers (' + Math.round(data.nodes[i].size*100/data.options.members) + '%)');
                } else {
                    _this.append('svg:circle').attr("r", size/2).classed('graph-unknown-handle', true);
                    _this.append('title').text(data.nodes[i].size + ' followers (' + Math.round(data.nodes[i].size*100/data.options.members) + '%)');
                }

            });

            force.on("tick", function() {
                link.attr("x1", function(d) { return d.source.x; })
                    .attr("y1", function(d) { return d.source.y; })
                    .attr("x2", function(d) { return d.target.x; })
                    .attr("y2", function(d) { return d.target.y; });

                node.attr("transform", function(d) { return "translate(" + d.x + "," + d.y + ")"; });
            });
        }
        return this;
    };

    ligo.renderStats = function() {
        var container = arguments[0] || stats_container,
            reload = arguments[1] || false;

        // setup container
        if (container != stats_container) {
            stats_container = container;
        }

        if (ligo.data === null || reload) {
            ligo.loadData(ligo.renderStats, reload);
        } else {
            $(stats_container).empty()
                .append('<h1>' + ligo.data.options.group + '</h1>')
                 .append('<p>Group members: <span class="badge badge-info">' + ligo.data.options.members + '</span></p>')
                .append('<p>Connections analyzed: <span class="badge badge-inverse">' + ligo.data.options.friends + '</span></p>');

            var $gallery = $('<div class="stats-handles"></div>').appendTo(stats_container);
            $gallery.css('height', $(window).height() - $gallery.offset().top - 20);

            for (var i = 0; i < ligo.data.nodes.length; i++) {
                var $div = $('#stats-size-' + ligo.data.nodes[i].size);

                if ($div.length < 1) {
                    var $divs = $('.stats-handles-container', $gallery);
                    $div = $('<div id="stats-size-' + ligo.data.nodes[i].size + '" class="stats-handles-container row-fluid"><div class="span3"><h1 title="Followed by ' + ligo.data.nodes[i].size + ' ' + ligo.data.options.group + ' group members" class="muted">' + Math.round(ligo.data.nodes[i].size*100/ligo.data.options.members) + '%: </h1></div><div class="span9"><ul class="thumbnails"></ul></div></div>').data('size', ligo.data.nodes[i].size);
                    if ($divs.length > 0 && $divs.last().data('size') < ligo.data.nodes[i].size) {
                        $divs.each(function(j){
                            if ($(this).data('size') < ligo.data.nodes[i].size) {
                                $(this).before($div);
                                return false;
                            }
                        });
                    } else {
                        $($gallery).append($div);
                    }
                }

                $div.find('ul').append('<li><a href="http://twitter.com/' + ligo.data.nodes[i].handle + '" title="@' + ligo.data.nodes[i].handle + '" class="thumbnail"><img src="' + (ligo.data.nodes[i].image || ligo.default_image) + '" alt="@' + ligo.data.nodes[i].handle + '"></a>');
            }
        }

        return this;
    };

    ligo.getForce = function () {
      if (graph_force === null) {
          graph_force = d3.layout.force()
                            .linkDistance(ligo.d3_linkDistance)
                            .charge(ligo.d3_charge)
                            .gravity(ligo.d3_gravity)
                            .size([graph_width, graph_height]);
      }
      return graph_force;
    };

    ligo.getSvg = function () {
        if (graph_svg === null) {
            $(graph_container).empty();
            graph_svg = d3.select(graph_container).append('svg').attr('width', graph_width).attr('height', graph_height);
        }
        return graph_svg;
    };

    ligo.monitorFps = function() {
        var container = arguments[0] || fps_container;

        if (container != fps_container) {
            fps_container = container;
        }

        $(document).on('click', '#fps-action', function(){
            clearInterval(fps_ticker);
            ligo.maxnodes = Math.round(ligo.maxnodes / 2);
            ligo.renderGraph(graph_container, true).monitorFps(fps_container);
        });

        if ($('#fps-indicator').size() < 1) {
            $(container).html('<div>FPS: <span id="fps-indicator" class="label"></span></div>');
        }


        fps_ticker = setInterval(function(){
            var time = Date.now();

            fps_frames ++;

            if ( time > fps_prevTime + 1000 ) {

                ligo.fps = fps = Math.round( ( fps_frames * 1000 ) / ( time - fps_prevTime ) );
                $('#fps-indicator').text(fps);
                fps_prevTime = time;
                fps_frames = 0;


            }

            if (fps < 5 && fps > 0 && ligo.maxnodes > 10) {
                if (fps_counter < 5) {
                    fps_counter++;
                } else {
                    if ($('#fps-action').size() < 1 && ligo.maxnodes > 10) {
                        $(container).append('<button id="fps-action" class="btn btn-mini btn-link">Improve performance</button>');
                    }
                    fps_counter = 0;
                }
            }
        }, 1000 / 60);

        return this;

    };

    /**
     * Private Methods
     */

    function getSize(current, min, max) {
        var size = Math.round( ( (current - min) * ligo.graph_node_range / (max - min) ) ) + ligo.graph_node_min_size;
        // if the node is out of min/max range - use zero based scale
        if (size < ligo.graph_node_min_size) {
            size = Math.round( current * ligo.graph_node_range / max );
        }
        return size;
    }

}( window.ligo = window.ligo || {}, jQuery ));