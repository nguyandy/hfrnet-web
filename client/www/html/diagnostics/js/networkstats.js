$(document).ready(function(){

  $.ajax({
    url: 'getdata.php?networksitegrowth',
    contentType: "text/csv",
    dataType: "json"
  })
  .done( function( data ){
    var alldata = {};
    alldata["all"] = [];
    alldata["us"] = [];
    alldata["inter"] = [];
    var previousUSTotal=0;
    var previousInterTotal=0;
    var previousAllTotal=0;
    
    $.each( data, function(index,value) {
      var month=parseInt(value["date"].substring(5,7), 10) - 1; // Subtract 1 because Date.UTC uses 0-based months (0=Jan, 1=Feb, etc.)
      var year=parseInt(value["date"].substring(0,4), 10); 
      var mydate = Date.UTC(year,month,1);

      // Always add US datapoint, even if value is 0
      var usTempdata = [];
      usTempdata.push(mydate);
      usTempdata.push(+value["US"] + +previousUSTotal);
      previousUSTotal += +value["US"];
      alldata["us"].push(usTempdata);

      // Always add Inter datapoint, even if value is 0
      var interTempdata = [];
      interTempdata.push(mydate);
      interTempdata.push(+value["Inter"] + +previousInterTotal);
      previousInterTotal += +value["Inter"];
      alldata["inter"].push(interTempdata);

      // Always add All datapoint
      var allTempdata = [];
      allTempdata.push(mydate);
      allTempdata.push(+previousUSTotal + +previousInterTotal);
      alldata["all"].push(allTempdata);
    });

    var options = {
      chart: {
        renderTo: 'networksitegrowth', zoomType: 'x'/*,
        events: {
          load: function() { makeSumSeries(this) }
        }
*/
      },
      title: { text: 'HFRadar Network Site Growth' }, 
      xAxis: {
        type: 'datetime'
      },
      yAxis: {
        title: { text: 'Number of HF-Radar Sites' }
      },
      series:[
        //{ name: 'All Sites', data: alldata["all"], color: '#f7a35c' },
        { name: 'US', data: alldata["us"], color: '#7cb5ec' },
        //{ name: 'International', data: alldata["inter"], color: '#90ed7d' }
      ]
    };

    var chart = new Highcharts.Chart(options);
  });

});

//http://forum.highcharts.com/highcharts-usage/automatically-create-a-sum-series-from-visible-series-t34250/
var makeSumSeries = function (chart) {
  var series = chart.series,
      each = Highcharts.each,
      sum;
  series[series.length - 1].update({
      data: []
  }, false);
  for (var i = 0; i < chart.xAxis[0].categories.length; i++) {
    sum = 0;
    each(series, function (p, k) {
      if (p.name !== 'All Sites' && p.visible === true) {
        each(p.data, function (ob, j) {
          if (ob.index == i) {
            sum += ob.y;
          }
        });
      }
    });
    series[series.length - 1].addPoint({
      y: parseFloat(sum.toFixed(2))
    }, false);
  }
  chart.redraw();
};
