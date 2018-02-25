/**
 * Draw pies
 * @author mickael@avocadoo.com
 */
var pie = {

    /**
     * Draw pie into wrapper selector
     */
    draw: function (selector) {
        jQuery(selector).each(function(e) {
            var elt = jQuery(this);
            if ('simple' === elt.data('complexity')) {
                pie.drawSimple(elt);
            } else {
                pie.drawComplex(elt);
            }
        });
    },

    /**
     * Draw simple pie
     */
    drawSimple: function(elt) {
        // width and height
        var w = elt.width();
        var h = elt.height();

        var radius = (Math.min(w, h) / 2) - 10;
        var outerRadius = radius - 5;
        var innerRadius = outerRadius - 15;
        var translateX = outerRadius + 10;
        var translateY = outerRadius + 10;
        var arc = d3.arc()
            .innerRadius(innerRadius)
            .outerRadius(outerRadius)
            .cornerRadius(50);

        var arcOver = d3.arc()
            .innerRadius(innerRadius)
            .outerRadius(outerRadius + 10)
            .cornerRadius(50);

        var pie = d3.pie();

        var dataset = elt.data('values');
        var nutrientsRaw = elt.data('nutrients');
        // format nutrients
        var nutrientsTemp = nutrientsRaw.replace(/[\[\]]/g, '');
        var nutrients = nutrientsTemp.split(',');
        var data = pie(dataset);

        // create SVG element
        var svg = d3.select('.' + elt.attr('id'))
            .append("svg")
            .attr("width", w)
            .attr("height", h);

        // set up groups
        var arcs = svg.selectAll("g.arc")
            .data(data)
            .enter()
            .append("g")
            // add class to get right color
            .attr("class", function(d, i) {
                return 'arc pie-arc-percent ' + nutrients[i];
            })
            .attr("transform", "translate(" + translateX + "," + translateY + ")");

        // draw arc paths
        arcs.append("path")
            .attr("d", arc)
            // add nutrient name
            .attr('data-nutrient', function(d, i) {
                return nutrients[i];
            })
            .on("mouseenter", function(d) {
                d3.select(this)
                   .attr("stroke","white")
                   .transition()
                   .duration(300)
                   .attr("d", arcOver)
                   .attr("stroke-width",6);

                // highlight percentage value
                jQuery('div.avcd-macronutrients-stats-' + jQuery(d3.select(this)[0][0]).data('nutrient')).addClass('grow');
            })
            .on("mouseleave", function(d) {
                d3.select(this).transition()
                   .attr("d", arc)
                   .attr("stroke","none");

                // highlight percentage value
                jQuery('div.avcd-macronutrients-stats-' + jQuery(d3.select(this)[0][0]).data('nutrient')).removeClass('grow');
            });
    },

    /**
     * Draw complex pie, with range
     */
    drawComplex: function(elt) {
        var pi = Math.PI;

        var w = (undefined === elt.data('width')) ? elt.width() : elt.data('width');
        var h = (undefined === elt.data('height')) ? elt.height() : elt.data('height');

        var radius, outerRadius, innerRadius, translateX, translateY;
        switch (elt.data('size')) {
            case 'xsmall':
                radius = (Math.min(w, h) / 2) - 10;
                outerRadius = radius - 5;
                innerRadius = outerRadius - 5;
                translateX = outerRadius + 10;
                translateY = outerRadius + 10;
                break

            case 'small':
                radius = (Math.min(w, h) / 2) - 10;
                outerRadius = radius - 5;
                innerRadius = outerRadius - 5;
                translateX = outerRadius + 10;
                translateY = outerRadius + 10;
                break

            default:
                radius = (Math.min(w, h) / 2) - 15;
                outerRadius = radius - 5;
                innerRadius = outerRadius - 10;
                translateX = outerRadius + 15;
                translateY = outerRadius + 15;
                break;
        }
        // var radius = ('small' === elt.data('size')) ? (Math.min(w, h) / 2) - 10 : (Math.min(w, h) / 2) - 15;
        // var outerRadius = radius - 5;
        // var innerRadius = ('small' === elt.data('size')) ? outerRadius - 5 : outerRadius - 10;
        // var translateX = ('small' === elt.data('size')) ? outerRadius + 10 : outerRadius + 15;
        // var translateY = ('small' === elt.data('size')) ? outerRadius + 10 : outerRadius + 15;

        var pie = d3.pie();

        // create SVG element
        var svg = d3.select('.' + elt.attr('id'))
            .append("svg")
            .attr('class', 'pie-percent')
            .attr("width", w)
            .attr("height", h);

        /* HANDLE BACKGROUND CHART */
        var radiusBackground = Math.min(w, h);

        var arcBackground = d3.arc()
            .innerRadius(innerRadius)
            .outerRadius(outerRadius)
            .cornerRadius(50);

        // data
        var dataBackground = [{
            data: 100,
            startAngle: 0,
            endAngle: 360 * (pi/180),
            padAngle: 0,
            value: 100
        }];

        // set
        var arcsBackground = svg.selectAll("g.arc-background")
            .data(dataBackground)
            .enter()
            .append("g")
            .attr("class", "pie-arc-background")
            .attr("transform", "translate(" + translateX + "," + translateY + "), scale(.97)");

        // draw
        arcsBackground.append("path")
            .attr("d", arcBackground);

        /* HANDLE PERCENT CHART */
        var arc = d3.arc()
            .innerRadius(innerRadius)
            .outerRadius(outerRadius)
            .cornerRadius(50);

        // data
        var data = [{
            data: elt.data('percent'),
            startAngle: 0,
            endAngle: 360 * elt.data('percent') * (pi / 180),
            padAngle: 0,
            value: elt.data('percent')
        }];

        // set
        var arcs = svg.selectAll("g.pie-arc-percent")
            .data(data)
            .enter()
            .append("g")
            .attr("class", "pie-arc-percent " + elt.data('nutrient'))
            .attr("transform", "translate(" + translateX + "," + translateY + "), scale(.97)");

        // draw
        arcs.append("path")
            .attr("d", arc);

        /* HANDLE RANGE CHART */
        var outerRadiusRange = radius - 3;
        var innerRadiusRange = outerRadiusRange - 2;

        var arcRange = d3.arc()
            .innerRadius(innerRadiusRange)
            .outerRadius(outerRadiusRange)
            .cornerRadius(50);

        // data
        var dataRange = [{
            data: 100,
            startAngle: 360 * elt.data('min') * (pi / 180),
            endAngle: 360 * elt.data('max') * (pi / 180),
            padAngle: 0,
            value: 100,
            strokeWidth: 4
        }];

        // set
        // handle arc range style
        var arcRangeStyle = (undefined !== elt.data('line')) ? elt.data('line') : '';
        var arcsRange = svg.selectAll("g.pie-arc-range")
            .data(dataRange)
            .enter()
            .append("g")
            .attr('class', 'pie-arc-range ' + arcRangeStyle)
            .attr('transform', 'translate(' + translateX + ',' + translateY + ')');

        // draw
        arcsRange.append('path')
            .attr('d', arcRange);

        // add start range labels
        var radiusLabel = radius + 10;
        arcsRange.append('text')
            .attr("transform", "translate(0,0)")
            .attr('transform', function (d) {
                return 'translate(' + elt.find('.pie-arc-range path').attr('d').split("L")[0].split('A')[0].split('M')[1].replace('Z', '') + ')';
            })
            .attr("text-anchor", function(d) {
                // are we past the center?
                return (d.endAngle + d.startAngle)/2 > Math.PI + 0.5 ?
                    "end" : "start";
            })
            .attr('x', function(d) {
                return (d.endAngle + d.startAngle)/2 > Math.PI + 0.5 ? -3 : 3;
            })
            // .attr('dy', 15)
            .text(function (d) {
                return (elt.data('min') * 100) + '%';
            });

        // add end range labels
        radiusLabel = radius + 10;
        arcsRange.append('text')
            .attr('transform', function (d) {
                return 'translate(' + elt.find('.pie-arc-range path').attr('d').split("L")[1].split("A")[0] + ')';
            })
            .attr("text-anchor", function(d) {
                // are we past the center?
                return (d.endAngle + d.startAngle)/2 > Math.PI ?
                    "start" : "start";
            })
            .attr('x', function(d) {
                return (d.endAngle + d.startAngle)/2 > Math.PI ? -5 : 5;
            })
            // .attr('dy', 15)
            .text(function (d) {
                return (elt.data('max') * 100) + '%';
            });

        /* HANDLE VALUE DOT */
        arc = d3.arc()
            .innerRadius(innerRadius + 1)
            .outerRadius(outerRadius - 1)
            .cornerRadius(50);

        // data
        data = [{
            data: elt.data('percent'),
            startAngle: 360 * (elt.data('percent') - 0.025) * (pi / 180),
            endAngle: 360 * (elt.data('percent') - 0.003) * (pi / 180),
            padAngle: 0,
            value: elt.data('percent')
        }];

        // set
        arcs = svg.selectAll('g.arc-value')
            .data(data)
            .enter()
            .append('g')
            .attr('class', 'pie-arc-percent dot')
            .attr('transform', 'translate(' + translateX + ',' + translateY + '), scale(.97)');

        // draw
        arcs.append('path')
            .attr('d', arc);
    }
};