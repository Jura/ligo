'use strict';
/**
 * User: jura.khrapunov
 * Date: 18.4.2013
 * Time: 16:11
 */

(function( ligo, $, undefined ) {

    //Private Properties
    var inprogress = false,
        graph_container = 'body',
        stats_container = 'body',
        graph_force = null,
        graph_svg = null,
        graph_width = 100,
        graph_height = 100;

    //Public Properties
    ligo.data = null;
    ligo.api_endpoint = null;
    ligo.group = null;
    ligo.maxnodes = null;

    // default setting for graph vis
    ligo.graph_node_min_size = 8;
    ligo.graph_node_range = 64;
    ligo.d3_linkDistance = 200;
    ligo.d3_charge = -2000;
    ligo.d3_gravity = 1;


    /**
     * Public methods
     */

    ligo.init = function(options){
        for (var option in options) {
            if (typeof ligo[option] != 'undefined') {
                ligo[option] = options[option];
            }
        }
        return this;
    };

    ligo.loadData = function () {
        var callback = arguments[0] || null;
        if (inprogress) {
            var i = arguments[1] || 0;
            if (i < 100) {
                setTimeout(function(){ligo.loadData(callback, i++)}, 100);
            } else {
                throw 'data loading takes too long';
            }
        } else if (ligo.data === null) {
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

        // setup container's params if the argument provided
        if (arguments.length > 0 && arguments[0] != graph_container) {
            graph_svg = null;
            graph_container = arguments[0];
            graph_width = $(graph_container).width();
            graph_height = $(graph_container).height();
        }

        if (ligo.data === null) {
            ligo.loadData(ligo.renderGraph);
        } else {
            var force = ligo.getForce(), svg = ligo.getSvg(), data = ligo.data;

            force.nodes(data.nodes).links(data.links).start();

            var link = svg.selectAll(".link").data(data.links).enter().append("line").attr("class", "link");
            var node = svg.selectAll(".node").data(data.nodes).enter().append("g").attr("class", "node").call(force.drag);

            // add legend
            svg.append('svg:rect').attr('rx', 16).attr('ry', 16).attr('y', 16).attr('width', 300).attr('height', 60).classed('graph-legend', true);
            svg.append('svg:circle').attr('r', 8).attr('cx', 16).attr('cy', 32).classed('graph-group-handle', true).style('cursor', 'default');
            svg.append('svg:text').attr('x', 30).attr('y', 36).text(' - members of the group');
            svg.append('svg:circle').attr('r', 8).attr('cx', 16).attr('cy', 56).classed('graph-unknown-handle', true);
            svg.append('svg:text').attr('x', 30).attr('y', 62).text('- Twitter handles outside of the group');

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
                        .on('click', function(e){window.location.href='https://twitter.com/' + data.nodes[i].handle});
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

        // setup container
        if (arguments.length > 0 && arguments[0] != stats_container) {
            stats_container = arguments[0];
        }

        if (ligo.data === null) {
            ligo.loadData(ligo.renderStats);
        } else {
            $(stats_container).empty()
                .append('<h1>' + ligo.data.options.group + '</h1>')
                .append('<p>Members: ' + ligo.data.options.members + '</p>')
                .append('<p>Friends processed: ' + ligo.data.options.friends + '</p>');
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