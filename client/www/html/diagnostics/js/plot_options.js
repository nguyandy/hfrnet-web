Highcharts.setOptions({
  time: { useUTC: true },
  tooltip: {
    headerFormat: '<span style="font-size: 10px">{point.key} UTC</span><br/>'
  }
});

function PlotOptions(){
  
  this.chart = {};
  this.chart.renderTo = 'mydiv';
  this.chart.zoomType = 'x';
  this.chart.defaultSeriesType = 'spline';
 
  this.title = {};
  this.title.text = 'My chart title';
 
  this.lang = {};
  this.lang.noData = 'No data to display';

  this.xAxis = {};
  this.xAxis.type = 'datetime';
  this.xAxis.max = (new Date).getTime()*1000;
  
  this.yAxis = {};
  this.yAxis.title = {};
  this.yAxis.title.text = 'hours';

  this.series = [];

}

PlotOptions.prototype={
  constructor: PlotOptions,

  addIdealizedSeries: function(data){
    var s = {};
    s.name = "Idealized";
    s.color = "#f29B22";
    s.data = data;
    this.series.push(s);
  },
  addMeasuredSeries: function(data){
    var s = {};
    s.name = "Measured";
    s.color = "#2279F2";
    s.data = data;
    this.series.push(s);
  },
  addSeries: function(name,color,data){
    var s = {};
    s.name = name;
    s.color = color;
    s.data = data;
    this.series.push(s);
  }, 
  setChartRenderTo: function(to){
    this.chart.renderTo = to;
  },
  setChartZoomType: function(x){
    this.chart.zoomType = x;
  },
  setChartDefaultSeriesType: function(x){
    this.chart.defaultSeriesType = x;
  },
  setTitleText: function(x){
    this.title.text = x;
  },
  setLangNoData: function(x){
    this.lang.noData = x;
  },
  setXAxisType: function(x){
    this.xAxis.type = x;
  },
  setXAxisMax: function(x){
    this.xAxis.max = x;
  },
  setYAxisTitleText: function(x){
    this.yAxis.title.text = x;
  }

  
}

