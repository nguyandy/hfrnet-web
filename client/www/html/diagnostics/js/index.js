/*
 * global vars
 */
window.my_config = {
  page: "",
  net: "",
  sta: "",
  starttime: 0,
  endtime: 0,
  tree: 1,
  view: "",
  transposed: 0,
  stations:"",
  dataTimezone: "UTC"
}

const SUBDIRECTORY = process.env.SUBDIRECTORY;

var EIGHT_DAYS = 8*24*60*60;
var SEVEN_DAYS = 7*24*60*60;
var IDEAL_COLOR = '#F29B22';                                      
var MEASURED_COLOR = '#2279F2';
var WERA_COLOR = IDEAL_COLOR;

var table;
var outageTable;
var configurationTable;
var limits = {};

limits['awg_tmp'] = {};
limits['awg_tmp']['min']=0;
limits['awg_tmp']['max']=40;
  
limits['receiver_chassis_tmp']={};
limits['receiver_chassis_tmp']['min']=0;
limits['receiver_chassis_tmp']['max']=50;

limits['receiver_supply_p24vdc']={};
limits['receiver_supply_p24vdc']['min']=22;
limits['receiver_supply_p24vdc']['max']=26;
      
limits['receiver_supply_p5vdc']={};
limits['receiver_supply_p5vdc']['min']=3;
limits['receiver_supply_p5vdc']['max']=7;
    
limits['receiver_supply_n5vdc']={};
limits['receiver_supply_n5vdc']['min']=-7;
limits['receiver_supply_n5vdc']['max']=-3;
        
limits['receiver_supply_p12vdc']={};
limits['receiver_supply_p12vdc']['min']=11;
limits['receiver_supply_p12vdc']['max']=14;
                   
limits['xmit_chassis_tmp']={};
limits['xmit_chassis_tmp']['min']=0;
limits['xmit_chassis_tmp']['max']=45;
                   
limits['xmit_amp_tmp']={};
limits['xmit_amp_tmp']['min']=0;
limits['xmit_amp_tmp']['max']=45;
                   
limits['xmit_supply_p28vdc']={};
limits['xmit_supply_p28vdc']['min']=24;
limits['xmit_supply_p28vdc']['max']=30;
      
limits['xmit_supply_p5vdc']={};
limits['xmit_supply_p5vdc']['min']=3;
limits['xmit_supply_p5vdc']['max']=7;

limits['transmit_trip']={};
limits['transmit_trip']['min']=-1;
limits['transmit_trip']['max']=1;
      
limits['xmit_fwd_pwr']={};
limits['xmit_fwd_pwr']['min']=10;
limits['xmit_fwd_pwr']['max']=60;
    
limits['xmit_ref_pwr']={};
limits['xmit_ref_pwr']['min']=0;
limits['xmit_ref_pwr']['max']=10;
        
limits['loop1_css_noisefloor']={};
limits['loop1_css_noisefloor']['min']=-200;
limits['loop1_css_noisefloor']['max']=-120;

limits['loop2_css_noisefloor']={};
limits['loop2_css_noisefloor']['min']=-200;
limits['loop2_css_noisefloor']['max']=-120;
                   
limits['mono_css_noisefloor']={};
limits['mono_css_noisefloor']['min']=-200;
limits['mono_css_noisefloor']['max']=-120;

limits['loop1_css_snr']={};
limits['loop1_css_snr']['min']=10;
limits['loop1_css_snr']['max']=100;

limits['loop2_css_snr']={};
limits['loop2_css_snr']['min']=10;
limits['loop2_css_snr']['max']=100;

limits['mono_css_snr']={};
limits['mono_css_snr']['min']=10;
limits['mono_css_snr']['max']=100;


/*
 * Read in all the url arguments first, if present
 */
// Controls the tree view (show '1' or no show '0')
if( get_url_parameter('t') !== null ){
  my_config.tree = get_url_parameter('t');
}
// page (default=summary, net=network, sta=station)
my_config.page = get_url_parameter('p');
// Network
if(get_url_parameter('net') !== null){
  my_config.net = get_url_parameter('net').toUpperCase();
}
// Station
if( get_url_parameter('sta') !== null ){
  my_config.sta = get_url_parameter('sta').toUpperCase();
}
// end time
if( get_url_parameter('endtime') === null ){
  my_config.endtime = Math.round( new Date() / 1000 );
} 
else {
  my_config.endtime = get_url_parameter('endtime');                         
} 
// start time 
if( get_url_parameter('starttime') === null ){
  // The start time is set to midnight                            
  my_config.starttime = Math.round( new Date( (my_config.endtime - SEVEN_DAYS) * 1000 ).setUTCHours(0,0,0,0) / 1000 );
}   
else {
  my_config.starttime = get_url_parameter('starttime');
}   
//View
if( get_url_parameter('v') !== null){
  my_config.view = get_url_parameter('v');
}
// Transposed report 
if( get_url_parameter('tr') !== null){
  my_config.transposed = get_url_parameter('tr');
}

/*
 * My date picker
 */
$(function(){
  $("#startdatepicker").datepicker( {
                          dateFormat: "m/d/yy",
                          //minDate: -10, // Can only get 10 days worth of data.  don't have any more in the database
                          onClose: function( selectedDate ){
                            $("#enddatepicker").datepicker( "option", "minDate", selectedDate )
                          }
  });
  $("#enddatepicker").datepicker( {
                          dateFormat: "m/d/yy",
                          //minDate: -10, // Can only get 10 days worth of data.  don't have any more in the database
                          onClose: function( selectedDate ){
                            $("#startdatepicker").datepicker( "option","maxDate", selectedDate )
                          }
  });
  $("#dateOutageResolved").datepicker( {
                          dateFormat: "yy/mm/dd",
                          onClose: function( selectedDate ){
                            $("#dateOutageResolved").datepicker( )
                          }
  });
  $("#dateOutageStart").datepicker( {
                          dateFormat: "yy/mm/dd",
                          onClose: function( selectedDate ){
                            $("#dateOutageStart").datepicker( )
                          }
  });
/* TODO commenting this out for now.  We'll use the current date time and not allow the user to change it for now.
  $("#site_config_datetime_picker").datetimepicker( {
                          format: "Y/m/d H:i"
  });
*/
});

/* Return a url that we can bookmark
 * p = summary,net,sta
 * v = ra (association uptime), ns (network summary)
 * t = 0,1
 * sta
 * net
 */
function returnThisPageURL(){
  var url="";
 
  switch( my_config.page ){
    // Network page
    case "net":
      url += "?p=net&net="+my_config.net   
      if( my_config.transposed==1 ){
        url += "&tr=1";
      }
      else {
        url += "&tr=0";
      }
      break;
    // station page
    case "sta":
      url += "?p=sta&sta="+my_config.sta+"&starttime="+my_config.starttime+"&endtime="+my_config.endtime;  
      break;
    // summary page
    default:
      switch( my_config.view){
        case "networksummary":
          url += "?v=networksummary";
          break;
        default:
          url += "?v=rametric";
      }
  }

  if( my_config.tree == 0 ){
    // If this is the summary page (default) there's no arguments
    if( url == "" ){
      url = "?t=0";
    }
    else {
      url += "&t=0";
    }
  }
 
  url = window.location.pathname + url;
  if( typeof( history.pushState) != "undefined"){
    var obj = {Title: document.title, Url: url};
    history.pushState( obj, obj.Title, obj.Url );
  }
  else {
    alert("Unable to create url for bookmark");
  }
}

/*
 * Check to see if there are idealized or measured radials.
 * Checks the metadata section to see if something comes up.
 * Does not take into account the age of the last radial file.
 */
function isThisPatternPresent(pattern){
  if( $("#"+pattern+"_site_meta").text() == "" ) {
    return false;
  }
  else {
    return true;
  }
} 

/*
 * Hide/Show the tree view
 */
function showHideTree(){
  if( $("#tree").is(":visible") ){
    my_config.tree=0;
    $("#tree").hide();
    $("#tree-col").removeClass("col-md-3");
    $("#data").removeClass("col-md-9");
    $("#data").addClass("col-md-12");
  }
  else {
    my_config.tree=1;
    $("#tree").show();
    $("#data").removeClass("col-md-12");
    $("#data").addClass("col-md-9");
    $("#tree-col").addClass("col-md-3");
  }
}

function outputDate(epoch){
  var mydate = new Date( epoch * 1000 );
  return mydate.getUTCFullYear() + "/" + ("0" + (mydate.getUTCMonth()+1)).slice(-2) + "/" + ("0"+mydate.getUTCDate()).slice(-2);
  //return (mydate.getUTCMonth()+1) + "/" + mydate.getUTCDate() + "/"+mydate.getUTCFullYear() + " GMT";
}
function outputDateTime(epoch){
  var mydate = new Date( epoch * 1000 );
  //return (mydate.getUTCMonth()+1) + "/" + mydate.getUTCDate() + "/"+mydate.getUTCFullYear() + " " + (mydate.getUTCHours() + 1) + ":" + ("0"+mydate.getUTCMinutes()).slice(-2) + " GMT";
  return mydate.getUTCFullYear() + "/" + ("0" + (mydate.getUTCMonth()+1)).slice(-2) + "/" + ("0"+mydate.getUTCDate()).slice(-2) + " " + (mydate.getUTCHours() ) + ":" + ("0"+mydate.getUTCMinutes()).slice(-2);
}

function getDataTimezoneLabel(){
  return my_config.dataTimezone || "UTC";
}

function getTimeAxisTitle(){
  return "Time (" + getDataTimezoneLabel() + ")";
}

function hideAllData(){
  $("#iFrame").hide();
  $("#alldata_wrapper").hide();
  $("#single_station_data").hide();
  $("#station_outages").hide();
  $("#station_config").hide();
  $("#loading").show();
}

function showNetworkPage(){
  resetDataBackgroundColor();
  my_config.transposed = 0;
  hideAllData();
  $.ajax({
    url: "getdata.php?networksummary&net=" + my_config.net,
    contentType: "text/csv",
    dataType: "json"
  })
  .done( function( data ) {
    $("#loading").hide();
    
    // Destroy my table
    if( table != null ) table.destroy();
    $("#alldata").empty();

    // Load the datatable
    table = $("#alldata").DataTable({
      pageLength: 25,
      paging: false,
      dom: 'Bfrtip',
      buttons: [ {
        text: 'Transpose Data',
        action: function( e, dt, node, config ) {
          showNetworkPage2();
        }
      }],
      data      : data,
      createdRow: function( row, data, index ) {
        // Color the row if the radials are old
        var c=getAgeColor( new Date()/1000-data["time"]);
        $(row).addClass(c);

        // Make sure the values are within a specific limit
        if( data["awg_tmp"] > limits['awg_tmp']['max'] || data["awg_tmp"] < limits['awg_tmp']['min'] ){
          $('td',row).eq(2).addClass('outside_range');
        } 
        if( data["receiver_chassis_tmp"] > limits['receiver_chassis_tmp']['max'] || data["receiver_chassis_tmp"] < limits['receiver_chassis_tmp']['min'] ){
          $('td',row).eq(3).addClass('outside_range');
        } 
        if( data["xmit_chassis_tmp"] > limits['xmit_chassis_tmp']['max'] || data["xmit_chassis_tmp"] < limits['xmit_chassis_tmp']['min'] ){
          $('td',row).eq(4).addClass('outside_range');
        } 
        if( data["xmit_amp_tmp"] > limits['xmit_amp_tmp']['max'] || data["xmit_amp_tmp"] < limits['xmit_amp_tmp']['min'] ){
          $('td',row).eq(5).addClass('outside_range');
        } 
        if( data["xmit_supply_p28vdc"] > limits['xmit_supply_p28vdc']['max'] || data["xmit_supply_p28vdc"] < limits['xmit_supply_p28vdc']['min'] ){
          $('td',row).eq(6).addClass('outside_range');
        } 
        if( data["xmit_supply_p5vdc"] > limits['xmit_supply_p5vdc']['max'] || data["xmit_supply_p5vdc"] < limits['xmit_supply_p5vdc']['min'] ){
          $('td',row).eq(7).addClass('outside_range');
        } 
        if( data["transmit_trip"] > limits['transmit_trip']['max'] || data["transmit_trip"] < limits['transmit_trip']['min'] ){
          $('td',row).eq(8).addClass('outside_range');
        } 
        if( data["receiver_supply_p24vdc"] > limits['receiver_supply_p24vdc']['max'] || data["receiver_supply_p24vdc"] < limits['receiver_supply_p24vdc']['min'] ){
          $('td',row).eq(10).addClass('outside_range');
        } 
        if( data["xmit_fwd_pwr"] > limits['xmit_fwd_pwr']['max'] || data["xmit_fwd_pwr"] < limits['xmit_fwd_pwr']['min'] ){
          $('td',row).eq(11).addClass('outside_range');
        } 
        if( data["xmit_ref_pwr"] > limits['xmit_ref_pwr']['max'] || data["xmit_ref_pwr"] < limits['xmit_ref_pwr']['min'] ){
          $('td',row).eq(12).addClass('outside_range');
        } 
        if( data["mono_css_snr"] > limits['mono_css_snr']['max'] || data["mono_css_snr"] < limits['mono_css_snr']['min'] ){
          $('td',row).eq(13).addClass('outside_range');
        } 
/*
        if( data["disk"] != null ){
          if( /9\d\%/.test(data["disk"])) {
            $('td',row).eq(15).addClass('outside_range');
          }
        }
*/
     
      },
      order     : [[1, "asc"]],
      columns   :[ {title: "Network", data: "net"},
                   {title: "Station", data: "sta"},
                   {title: "AWG Temp (degC)", data: "awg_tmp" },
                   {title: "RX Temp (degC)", data: "receiver_chassis_tmp"},
                   {title: "TX Temp (degC)", data: "xmit_chassis_tmp"},
                   {title: "TX Amp Temp (degC)", data: "xmit_amp_tmp"},
                   {title: "TX +28VDC", data: "xmit_supply_p28vdc"},
                   {title: "TX +5VDC", data: "xmit_supply_p5vdc"},
                   {title: "TX Trip", data: "transmit_trip"},
                   {title: "AWG Runtime", data: "awg_run_time"},
                   {title: "RX +24VDC (DC Systems only)", data: "receiver_supply_p24vdc"},
                   {title: "TX Foward Power (W)", data: "xmit_fwd_pwr"},
                   {title: "TX Reflected Power (W)", data: "xmit_ref_pwr"},
                   {title: "Monopole SNR (db)", data: "mono_css_snr"},
                   {title: "Range (km)", data: "rad_range"},
 //                  {title: "Disk Usage", data: "disk"},
                   {title: "Last data (UTC)", data: "time",
                    render: function( data, type, row ){
                      return outputDateTime( data );
                    }}
                  ]
    });
    // if we click on the station row, load the station info
    $("#alldata tbody").on('click','tr',function(){
      var data=table.row(this).data();
      var sta=data["sta"];
      var net=data["net"];
      my_config.sta=sta;
      my_config.page="sta";
      draw_single_station(); 
    });

  })    
  .fail( function() {
    $("#loading").hide();
    $("#alldata_wrapper").show();
    alert("Unable to retrieve network summary");
  });
} // End showNetworkPage()

// This shows the network tranposed
function showNetworkPage2(){
  resetDataBackgroundColor();
  my_config.transposed = 1;
  var columns = [];
  
  hideAllData();

  $.ajax({
    url: "getdata.php?networksummary2&net=" + my_config.net,
    contentType: "text/csv",
    dataType: "json"
  })
  .done( function( data ) {
    $("#loading").hide();
    
    // Destroy my table
    if( table != null ) table.destroy();
    $("#alldata").empty();

    // Get the column names
    $.each(data, function (key, val) {
      $.each(val, function( key2,val2 ){
        var obj = { title: key2, data: key2 };
        columns.push(obj);
      });
      return false;
    });

    // Load the datatable
    table = $("#alldata").DataTable({
      pageLength: 25,
      paging     : false,
      dom       : 'Bfrtip',
      buttons   :[{
        text: 'Transpose Data',
        action: function( e, dt, node, config ){
          showNetworkPage();
        } 
      }],
      data      : data,
      columns   : columns,
/*
 * TODO having scrollX somehow messes up the formatting.  Tables with a small number of stations will have the columns not lined up
      scrollX   : true,
*/
      columnDefs: [
        { targets: 0,
          render: function( data, type, row ){
            var varMapping = {
              "awg_run_time":"AWG Runtime",
              "awg_tmp":"AWG Temp (degC) ",
              "receiver_chassis_tmp":"RX Temp (degC)",
              "receiver_supply_p24vdc":"RX +24VDC (DC Systems only)",
              "time":"Last data (UTC)",
              "mono_css_snr":"Monopole SNR (db)",
              "rad_range":"Range (km)",
              "transmit_trip":"TX Trip",
              "xmit_amp_tmp":"TX Amp Temp (degC)",
              "xmit_chassis_tmp":"TX Temp (degC)",
              "xmit_fwd_pwr":"TX Forward Power (W)",
              "xmit_ref_pwr":"TX Reflected Power (W)",
              "xmit_supply_p28vdc":"TX +28VDC",
              "xmit_supply_p5vdc":"TX +5VDC"};
            
            return varMapping[data];
          
          }
        }],
      createdRow: function( row, data, index ) {
        // Just check the variables that have limits
        if( typeof limits[data["variable"]] !== "undefined"){
          var count = 0;
          for( var sta in data ){
            if( data[sta] > limits[data["variable"]]['max'] || data[sta] < limits[data["variable"]]['min']) {
              $('td',row).eq(count).addClass('outside_range');
            }
            count++;
          }
        }
        if( data['variable'] == "time" ){
          var count = 0;
          for( var sta in data ){
            if (count==0) {
              count++;
              continue;
            }
            $('td',row).eq(count).html( outputDateTime(data[sta]) );
            count++;
          }
        }
      }
    });
    
    // Figure out which columns (stations) have old data files
    $.each( data, function( row_ix, val ) {
      if( data[row_ix]["variable"] != "time" ) return true;
      count = 0;
      $.each( val, function( sta, time ) {
        if( sta == "variable" ) return true;
        count++; 
        // Change column colors if station is old
        var c=getAgeColor( new Date()/1000 - time );
        $(table.column(count).nodes()).addClass(c);
      });
    });
    
    // If we click on a row, load the station
    $("#alldata tbody").on('click','td',function(){
      var sta=$('#alldata thead tr th').eq($(this).index()).html().trim();
      my_config.sta=sta;
      my_config.page="sta";
      draw_single_station();
    });
   
  })    
  .fail( function() {
    $("#loading").hide();
    $("#alldata_wrapper").show();
    alert("Unable to retrieve network summary");
  });
  
} // End showNetworkPage2()
  
function showStationSummaryList(){
  resetDataBackgroundColor();
  my_config.page="";
  my_config.net="";
  my_config.sta="";
  my_config.view="networksummary";
  hideAllData();

  $.ajax({
    url: "getdata.php?summarylist",
    contentType: "text/csv",
    dataType: "json"
  })
  .done( function( data ) {
    $("#loading").hide();
    
    // Destroy my table
    if( table != null ) table.destroy();
    $("#alldata").empty();

    // Load the datatable
    table = $("#alldata").DataTable({
      createdRow: function( row, data, index ){
        // Color the row if the radials are old
        var c=getAgeColor( new Date()/1000-data["latest_radial_file"]);
        $(row).addClass(c);
      },
      pageLength: 100,
      data      : data,
      order     : [[0, "asc"],[1,"asc"],[2,"asc"]],
      columns   :[ {title: "Regional Association",
                    data: "regional_association"},
                   {title: "Network",
                    data: "net"},
                   {title: "Station",
                    data: "sta"},
                   {title: "Station Name",
                    data: "staname"},
                   {title: "Frequency",
                    data: "cfreq"},
                   {title: "Last Radial File (UTC)",
                    data: "latest_radial_file",
                    render: function( data, type, row ){
                      return outputDateTime( data );
                    }}]
                    
    });
    // if we click on the station row, load the station info
    $("#alldata tbody").on('click','tr',function(){
      var data=table.row(this).data();
      var sta=data["sta"];
      var net=data["net"];
      my_config.sta=sta;
      my_config.page="sta";
      draw_single_station(); 
    });

  })    
  .fail( function() {
    $("#loading").hide();
    $("#alldata_wrapper").show();
    alert("Unable to retrieve station summary");
  });
  
} // End showStationSummaryList()

function showRAMetric(){
  resetDataBackgroundColor();
  my_config.page="";
  my_config.net="";
  my_config.sta="";
  my_config.view="rametric";
  hideAllData();
  $("#iFrame").attr("src",`/${SUBDIRECTORY}/sitediag/metric-FY.php`);
  $("#loading").hide();
  $("#iFrame").show();
/*
  $.ajax({
    url:"getdata.php?metric-fy",
    contentType: "text/csv",
    dataType: "json"
  })
  .done( function( data ){
    $("#loading").hide();
    
    // Destroy my table
    if( table != null ) table.destroy();
    $("#alldata").empty();
    
    // Load the datatable
    table = $("#alldata").DataTable({
      data: data,
      columns: [ {title: "Regional Association",
                  data: "ra"},
                 {title: "Fiscal Quarter",
                  data: "FQ"} ]
    });
  })
  .fail( function(){
    $("#loading").hide();
    $("#alldata_wrapper").show();
    alert("Unable to retrieve station summary");
  });
*/
} // End showRAMetric()
 
function loggedin(){
  $("#logged-in").show();
  $("#log-in").hide();
  $("#user").val("");
  $("#pass").val("");
  $("#login-modal").modal('hide');

  // Figure out which stations the user can edit
  var my_url="site.php?mystations";
  $.ajax({url:my_url})
  .done( function(data){
    //data returned should be a csv list of stations
    if(data==null){
      my_config.stations="";
    }
    else{
      my_config.stations=data;
    }

    // can I edit this station?
    if( my_config.stations.indexOf(my_config.sta)==-1){
      $("#add_outage").hide();
      $(".outage_actions").hide();
      $("#config_actions").hide();
    }
    else {
      $("#add_outage").show();
      $(".outage_actions").show();
      $("#config_actions").show();
    }
  });
} // End login

function notloggedin(){
  my_config.stations = "";
  $("#logged-in").hide();
  $("#log-in").show();
  $("#add_outage").hide();  
  $(".outage_actions").hide();
  $("#config_actions").hide();
} // end logout

$(document).ready(function(){

  // First check to see if we are logged in
  $.ajax({url:'user.php?action=checkloginstatus'})
  .done(function(html){
    if(html==1){
      loggedin();
    }
    else{
      notloggedin();
    }
  });

  // Click on change password button
  $("#changepass").click(function(){
    $("#pass1").val("");
    $("#pass2").val("");
    $("#pass3").val("");
    $("#changePass-modal").modal('toggle');
  });

  // Click on change password in form
  $("#submitPassword").click(function(e){
    e.preventDefault();
    
    // Get rid of old messages
    $("#password-error").hide();

    var pass1=$("#pass1").val().trim();
    var pass2=$("#pass2").val().trim();
    var pass3=$("#pass3").val().trim();

    // Make sure all the fields are filled
    if(pass1==""||pass2==""||pass3==""){
      $("#password-error").text("Please fill out all the fields");
      $("#password-error").show();
      return false;
    } 
    // First check to make sure the pass2 and pass3 are same
    if( pass2 != pass3 ){
      $("#password-error").text("The new password and confirm passwords are not the same");
      $("#password-error").show();
      $("#pass2").val("");
      $("#pass3").val("");
      return false;
    }
    
    $.ajax({
      url: 'user.php?action=changepass',
      type: 'POST',
      data: {'pass1':pass1,'pass2':pass2,'pass3':pass3}
    })
    .fail(function(){
      $("#password-error").text("Something went wrong trying to change the password.");
      $("#password-error").show();
      return false;
    })
    .done(function(html){
      if( html=="0" ){
        $("#password-error").text("Your current password is incorrect");
        $("#pass1").val("");
        $("#password-error").show();
        return false;
      }
      else if( html=="1") {
        show_message("Password changed successfully.",'success');
        $("#changePass-modal").modal('toggle');
      }
      else {
        $("#password-error").text("Something went wrong trying to change the password.");
        $("#password-error").show();
        return false;
      }
    });
    
  });
  // Click on login link
  $("#submitLogin").click(function() {
    $("#login-error").text("");
    $("#login-error").hide();
    var user = $("#user").val().trim();
    var pass = $("#pass").val().trim();
    $.ajax({
      url: 'login.php',
      type: 'POST',
      data:{ 'user': user, 'pass': pass }
    })
    // something went wrong trying to login
    .fail( function() {
      $("#login-error").text("Something went wrong trying to login.");
      $("#login-error").show();
      return false;
    })
    .done( function( html ){
      if( html != 'false'){
        // login is successful; hide the login form and change the 'login' link to the person's name
        $("#login_link").html("Hello, "+user +"<span class='caret'></span>");
        $("#userinfo").html("<span>"+user+"</span><p class='text-muted small'>"+html.split(',')[1]+"</p>");

        loggedin();
      }
      else {
        // If login is unsuccessful, create an error message on the form and display:
        $("#login-error").text("Unable to log in with the supplied username and password.");
        $("#login-error").show();
        notloggedin();
        return false;
      }
    });
    return false;
  });

  // Click on logout link
  $("#logout").click(function(){
    $.ajax({ url: 'logout.php' })
    .done( function( html ){
      if( html == 1 ){
        notloggedin();
      }
    });
    return false;
  });

  // Click on More/Less plots button
  $("#morePlots").click(function(){
    if( $("#morePlots").text() == "- Less Plots" ){
      showMorePlots(false);
    }
    else {
      showMorePlots(true);
    }
  });
  
  // Click on 'Generate plots' button
  $("#generate_plots").click(function() {
    my_config.starttime = new Date(Date.parse( $("#startdatepicker").val())).setUTCHours(0,0,0,0)/1000;
    my_config.endtime = new Date(Date.parse( $("#enddatepicker").val())).setUTCHours(23,59,59,0)/1000;

    // Clear the parameter_stats table first
    $("#parameter_stats").find("tr:gt(0)").remove();

    drawRangePlot(cfreq);
    drawLatencyPlot();
    drawNumberOfSolutionsPlot();

    drawAWGTemperaturePlot();
    drawTXForwardPowerPlot();
    drawTXReflectedPowerPlot();
    drawReceiverTemperaturePlot();
  });

  // Submit outage
  $("#submit_outage").click(function(){
    if( $("#submit_outage").attr("data-submittype") == "add" ){
      saveOutage("add");
    }
    else if ( $("#submit_outage").attr("data-submittype") == "edit" ){
      saveOutage("edit");
    }
    else {
    }
  });

  // Site config modal open
  // Update the datetime to the most current datetime
  $("#site_config_form").bind('show.bs.modal',function(){
    $("#site_config_datetime_picker").val(outputDateTime( Date.now()/1000 ) );
  });

  // Submit site config
  $("#submit_siteconfig").click(function(){
    saveSiteConfig($("#add_site_config").val()); 
  });

  // Submit site stop
  $("#stop_siteconfig").click(function(){
    stopSiteConfig(); 
  });
  
  // Tree view
  if( my_config.tree !== null ){
    // Hide the tree 
    if( my_config.tree == 0 ){
      showHideTree();
    }
  }

  // Page
  if( my_config.page !== null ){
    // Show the network page
    if( my_config.page == "net" ){
      if( my_config.net == "" ){
        my_config.net = "SIO";
      }
      if( my_config.transposed == "1"){
        showNetworkPage2();
      }
      else {
        showNetworkPage(); // default value
      }
    }
    // Show the station page
    else if ( my_config.page == "sta" ){
      if( my_config.sta === null ){
        my_config.sta = "SDBP";
      }
      draw_single_station();
    }
    // Show default summary page
    else {
      if(my_config.view=="networksummary"){showStationSummaryList();}
      else {showRAMetric();}
    }
  }
  else {
    if(my_config.view=="networksummary"){showStationSummaryList();}
    else {showRAMetric();}
  }
 
  $('#tree').jstree({
    'core': {
      'data': {
        'url': 'getdata.php?allsites',
        'dataType': 'JSON',
        'data': function(node){
          return {'id': node.id };
        }
      },
      'multiple': false
    }
  });
  
  $('#tree')
  // If the user clicks on an item in the tree, show the data
  .on( 'changed.jstree', function( e,data ) {
    
    // Make the background white, in case it's not
    $("#data").css("background-color","white");
  
    var i, j, r = [];
    // allows for multiple items to be selected.  currently disabled
    // within jstree.core.multiple
    for( i=0, j = data.selected.length; i < j; i++ ){
      r.push(data.instance.get_node(data.selected[i]).id);
    }
    var selected_in_tree = r.join(", ").split("/");
    var args;

    if( selected_in_tree.length == 1 ){
      // Clicked on 'All Stations'
      if( selected_in_tree[0]=="all" ){
        my_config.page = "";
        showRAMetric();
      }
      // Clicked on a network
      else {
        my_config.page="net";
        my_config.net=selected_in_tree[0];
        showNetworkPage();
      }
    }
    // Clicked on a station
    else { 
      my_config.page="sta";
      my_config.net=selected_in_tree[0];
      my_config.sta=selected_in_tree[1];
      draw_single_station();
    }  
  });

  // Clicked on Summary Views drop down
  $(document).on('click','#moreSummaryReports li', function(){
    switch( $(this).text() ){
      case "Association Uptime":
        showRAMetric();
        break;
      default:
        showStationSummaryList();
    }
  });

  // Add outage button
  $(document).on('click','#add_outage', function(e){
    $("#outageheader").text("Add Outage");
    $("#submit_outage").attr("data-submittype","add");
    clearOutagesForm();
  });

  // Delete my outage
  // First show a popup confirming deletion
  // But first highlight the row to be deleted
  $(document).on('click', '#alloutages2 #deleteoutage', function(){
    var myrow = $(this).parents('tr');
    var d = outageTable.row( myrow ).data();
    var dateentered = outputDateTime(d['date_entered']);
    var recordid = d['outage_records_id'];
    $("#deleterecord").attr('data-outage_records_id',recordid);
    $("#recordToDelete").text(d['outages'] + " outage from " + dateentered);
    $("#confirm-delete").modal('show'); 
  });

  // If the user really wants to delete, then delete
  $(document).on('click','#deleterecord', function(){
    var recordid = $("#deleterecord").attr('data-outage_records_id');
    $.ajax({
      url: "site.php",
      type: 'POST',
      data: {"outage_records_id":recordid,"id":"outages","job":"delete","site":my_config.sta}
    })
    .done( function( html){
      $("#confirm-delete").modal('hide'); 
      if( html == 'false' ){
        hide_loading_message();
        
        show_message("Unable to delete the outage",'error'); 
      }
      else {
        hide_loading_message();
        show_message("Outage deleted successfully",'success'); 
        showOutages();
      }
    });
  });
    
  // Edit my outage
  $(document).on('click', '#alloutages2 #editoutage', function(){

    var d = outageTable.row( $(this).parents('tr')).data();
    var recordid = d['outage_records_id'];
    $("#outage-form").attr("data-outage_records_id",recordid);
    $("#submit_outage").attr("data-submittype","edit");
    $("#outages").val(d['outages_id']);
    $("#dataavail").val(d['data_availability_id']);
    $("#time_to_repair").val(d['time_to_repair_id']);
    $("#outageNotes").val(d['notes']);
    var dateResolved = d["date_resolved"];
    if( dateResolved == 0 || dateResolved==null  ){
      dateResolved = "";
    }
    else {
      dateResolved = outputDate(dateResolved);
    }
    $("#dateOutageResolved").val(dateResolved);
    $("#dateOutageStart").val(outputDate(d['start_date']));
    $("#tags option:selected").prop("selected",false); 
    if( d['tags'] ){
      $.each(d['t_ids'].split(','),function(i,e){
        $("#tags option[value="+e+']').prop("selected","selected");
      });
    }
   
    // Change the title from 'Add outage' to 'Edit outage'
    $("#outageheader").text("Edit outage");

    $("#outage_entry_form").modal('show');
  });

  showClock();
	 			
}); // End $(document).ready()

function showClock(){
  var clock = document.getElementById('clock');
  
  var ticktock = function(){
    var options = {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit', 
      hour12: false
    };

    var o = new Intl.DateTimeFormat('en-CA', options );

    options.timeZone = 'UTC';
    var o_utc = new Intl.DateTimeFormat('en-CA', options );

    var d = new Date();
   
    clock.innerHTML = o.format(d) + " Local<br />" + o_utc.format(d) + " UTC";
  };
	
  ticktock();
	
  // Calling ticktock() every 1 second
  setInterval(ticktock, 1000);
}

// Show all the outages
function showOutages(){
  $.ajax({
    url: "site.php?getoutages&sta="+my_config.sta,
    contentType: "text/csv",
    dataType: "json"
  })
  .done( function(data){
    // Destroy my table
    if( outageTable != null ) outageTable.destroy();
    $("#alloutages2").empty();
    // Load the datatable
    outageTable = $("#alloutages2").DataTable({
      pageLength: 25,
      dom: 'Bfrtip',
      buttons: [{ extend: 'excelHtml5',
                  // TODO: if we add new columns, then we need to adjust which columns get export
                  exportOptions:{ columns: [1,2,3,4,5,6,7,8,9 ] },
                  title:  'Outage Export'},
                { extend: 'csvHtml5',
                  exportOptions:{ columns: [1,2,3,4,5,6,7,8,9 ] },
                  title:  'Outage Export'},
                { extend: 'pdfHtml5',
                  // TODO: if we add new columns, then we need to adjust which columns get export
                  exportOptions:{ columns: [1,2,3,4,5,6,7,8,9 ] },
                  orientation: 'landscape',
                  title:  'Outage Export'} ],
      data: data,
      order: [[1, "desc"]],
      columns:[
        {title: "Record",data:"outage_records_id"},
        {title: "Date Entered (UTC)",data:"date_entered",render: function( data,type,row){
           return outputDateTime(data);}},
        {title: "Date Start (UTC)",data:"start_date",render: function( data,type,row){
           return outputDate(data);}},
        {title: "Outage",data:"outages"},
        {title: "Tags",data:"tags"},
        {title: "Notes",data:"notes"},
        {title: "Estimated Repair Date",data:"repairDate"},
        {title: "Data Availability",data:"dataavail"},
        {title: "Date Resolved (UTC)",data:"date_resolved",render: function(data,type,row){
           if( data==0 || data==null ){
             return null;
           }
           else {
             return outputDate(data);
           }}},
        {title: "User",data:"username"},
        {title:"Actions",data:null,defaultContent:"<i class='bi bi-pencil-fill' id='editoutage' title='Edit outage'></i>&nbsp;<i class='bi bi-trash' id='deleteoutage' title='Delete outage'></i>"},
        {title:"outageid",data:"outages_id"},
        {title:"repairdate",data:"time_to_repair_id"},
        {title:"dataavail",data:"data_availability_id"},
        {title:"tagids",data:"t_ids"}],

      columnDefs:[
        /* Hide the id columns. */
        // TODO: Reminder: if you add new columns you gotta change the below targets
        {targets: [0],visible: false, searchable:false},
        {targets: [10],className:'outage_actions'},
        {targets: [11],visible: false, searchable:false},
        {targets: [12],visible: false, searchable:false},
        {targets: [13],visible: false, searchable:false},
        {targets: [14],visible: false, searchable:false}],
      initComplete: function(settings,json){
        // Hide the actions columns and 'add outage' button if we don't have permission
        if( my_config.stations.indexOf(my_config.sta)==-1){
          $("#add_outage").hide();
          $(".outage_actions").hide();
	  $("#config_actions").hide();
        }
        else {
          $("#add_outage").show();
          $(".outage_actions").show();
          $("#config_actions").show();
        }
      }
    });
  });
}

// Save new/update outages
// job: 'add','edit' 
function saveOutage(job){
  var errorMessage;
  var successMessage;

  show_loading_message();
  
  outageNum = $("#outages").val();
  outageText = $("#outages option:selected").html();
  outageGroup = findOptgroup(document.getElementById("outages").selectedIndex);
  tags = $.map($('#tags option:selected'), function(e,i) { return $(e).val(); });
  tags = tags.join(",");
  outageNotes = $("#outageNotes").val();
  repairDate = $("#time_to_repair option:selected").val();
  dataAvail = $("#dataavail option:selected").val();
  if( $("#dateOutageResolved").val() == "" ){
    dateResolved = 0;
  }
  else {
    dateResolved = new Date($("#dateOutageResolved").val());
    dateResolved = dateResolved.getTime();
  }
  dateOutageStart = new Date($("#dateOutageStart").val());
  dateOutageStart = dateOutageStart.getTime();

  todaysDate = new Date().getTime()/1000;
   
  var my_url = "site.php";
 
  var data = {};
  data["id"] = "outages";
  data["job"] = job;
  data["date"] = todaysDate;
  data["notes"] = outageNotes;
  data["time_to_repair"] = repairDate;
  data["site"] = my_config['sta'];
  data["tags"] = tags;
  data["dataavail"] = dataAvail;
  data["dateResolved"] = dateResolved;
  data["dateStart"] = dateOutageStart;
  data["outage"] = outageNum;
  // If we are updating, then we need the outage_records_id
  if( job == "edit" ){
    data["outage_records_id"] = $("#outage-form").attr("data-outage_records_id");
    successMessage = "Outage updated successfully";
    errorMessage = "Unable to edit the outage";
  }
  else {
    successMessage = "Outage added successfully";
    errorMessage = "Unable to add the outage";
  }
 
  $.ajax({
    url: my_url,
    type: 'POST',
    data: data
  })
  .done( function(html){
    if( html == 'false' ){
      hide_loading_message();
      show_message(errorMessage,'error');
    }
    else {
      hide_loading_message();
      show_message(successMessage, 'success');
      showOutages();
    }
  });
} // End saveOutage

// load the outages into the dropdown
function getOutagesInfo(){

  // Load the outages into the dropdown
  var my_url = "site.php?outageslist";
  $.ajax({
    url: my_url,
    contentType: "text/csv",
    dataType: "json"
  }) 
  .done( function( data ) {
    var html = "";
    $.each( data, function( key, value ) {
      // This is a group 
      if( value["outages_id"] % 100 == 0 ) {
        if (html != ""){
          html += "</optgroup>";
        }
        html += "<optgroup label='"+value["outages"]+"'>";
      }
      else if(  value["outages_id"] == 999 ){
        html += "</optgroup><option value='"+value["outages_id"]+"'>"+value["outages"]+"</option>";

      }
      else {
        html += "<option value='"+value["outages_id"]+"'>"+value["outages"]+"</option>"; 
      }
    });
    $("#outages").html(html);
  });

  // Load tags
  my_url = "site.php?tagslist";
  $.ajax({
    url: my_url,
    contentType: "text/csv",
    dataType: "json"
  })
  .done( function(data) {
    var html ="";
    $.each( data, function( key, value ){
      html += "<option value='"+value["tags_id"]+"'>"+value["text"]+"</option>";
    });
    $("#tags").html(html); 
  });

  // Load data availability
  my_url = "site.php?dataavaillist";
  $.ajax({
    url: my_url,
    contentType: "text/csv",
    dataType: "json"
  })
  .done( function(data) {
    var html ="";
    $.each( data, function( key, value ){
      html += "<option value='"+value["data_availability_id"]+"'>"+value["text"]+"</option>";
    });
    $("#dataavail").html(html); 
  });
  
  // Load estimated repair date 
  my_url = "site.php?timerepairlist";
  $.ajax({
    url: my_url,
    contentType: "text/csv",
    dataType: "json"
  })
  .done( function(data) {
    var html ="";
    $.each( data, function( key, value ){
      html += "<option value='"+value["time_to_repair_id"]+"'>"+value["text"]+"</option>";
    });
    $("#time_to_repair").html(html); 
  });

}

// Clear the outages form
function clearOutagesForm(){
  $("#outages optgroup option:first").attr("selected","selected");
  $("#dataavail option:first-child").attr("selected","selected");
  $("#time_to_repair option:first-child").attr("selected","selected");
  $("#tags option:selected").removeAttr("selected");
  $("#dateOutageStart").val(outputDate( Date.now()/1000 ) );
  $("#dateOutageResolved").val("");
  $("#outageNotes").val("");
}

// Show station configuration
function showConfiguration(){
  $.ajax({
    url: "site.php?getconfighistory&sta="+my_config.sta,
    contentType: "text/csv",
    dataType: "json"
  })
  .done( function(data){
    // Check the data, if the last (or maybe first) entry has an endtime, than don't show stop button and change the value for the add button to either update or add
    if( data[0]["end_time"] == null ){
      $("#stop_site_config").show();
      $("#add_site_config").val("update");
    }
    else {
      $("#stop_site_config").hide();
      $("#add_site_config").val("add");
    }
    
    // Change beam pattern in the form to whatever it isn't already
    if( data[0]["beampattern"] == "ideal" ){
      $("#beampatterns").val("measured");
    }
    else{
      $("#beampatterns").val("ideal");
    }
    
    
    // Destroy my table
    if( configurationTable != null ) configurationTable.destroy();
    $("#siteconfiguration").empty();
    // Load the datatable
    configurationTable = $("#siteconfiguration").DataTable({
      pageLength: 10,
      dom: 'Bfrtip',
      buttons: [{ extend: 'excelHtml5',
                  // TODO: if we add new columns, then we need to adjust which columns get export
                  //exportOptions:{ columns: [1,2,3,4,5 ] },
                  title:  'Configuration Export'},
                { extend: 'csvHtml5',
                  //exportOptions:{ columns: [1,2,3,4,5 ] },
                  title:  'Configuration Export'},
                { extend: 'pdfHtml5',
                  // TODO: if we add new columns, then we need to adjust which columns get export
                  //exportOptions:{ columns: [1,2,3,4,5 ] },
                  orientation: 'landscape',
                  title:  'Configuration Export'} ],
      data: data,
      order: [[1, "desc"]],
      columns:[
        {title: "Resolution", data:"resolution"},
        {title: "Start Time (UTC)", data:"start_time"},
        {title: "End Time (UTC)", data:"end_time"},
        {title: "Beam Pattern", data:"beampattern"},
        {title: "Radial Minute", data:"use_radial_minute"}]
    });
  });
} // End showConfiguration

// save site config 
// job: 'add','update'
function saveSiteConfig(job){
  var errorMessage = "Unable to add the configuration";
  var successMessage = "Configuration added successfully";

  show_loading_message();
 
  startdate = new Date($("#site_config_datetime_picker").val()+"Z");
  startdate = startdate.getTime();
  pattern = $("#beampatterns").val();
  radialminute = $("#radialminute").val();
  
  var my_url = "site.php";
  
  var data = {};
  data["id"] = "config";
  data["job"] = job;
  data["startdate"] = startdate;
  data["pattern"] = pattern;
  data["radialminute"] = radialminute;
  data["site"] = my_config['sta'];
  data["net"] = my_config['net'];

  $.ajax({
    url: my_url,
    type: 'POST',
    data: data
  })
  .done( function(html){
    if( html == 'false' ){
      hide_loading_message();
      show_message(errorMessage,'error');
    }
    else {
      hide_loading_message();
      show_message(successMessage, 'success');
      showConfiguration();
    }
  }); 
} // End saveSiteConfig

// stop site config
function stopSiteConfig(){
  var errorMessage = "Unable to stop";
  var successMessage = "Configuration added successfully";

  show_loading_message();
 
/*
  enddate = new Date($("#site_config_datetime_picker").val()+"Z");
  enddate = enddate.getTime();
*/
  enddate = new Date().getTime();
  
  var my_url = "site.php";
  
  var data = {};
  data["id"] = "config";
  data["job"] = "stop";
  data["enddate"] = enddate;
  data["site"] = my_config['sta'];

  $.ajax({
    url: my_url,
    type: 'POST',
    data: data
  })
  .done( function(html){
    if( html == 'false' ){
      hide_loading_message();
      show_message(errorMessage,'error');
    }
    else {
      hide_loading_message();
      show_message(successMessage, 'success');
      showConfiguration();
    }
  }); 
}

function findOptgroup(idx) {
	var sel=document.getElementById('outages');
        return sel.options[idx].parentNode.label;
} 

function draw_single_station(){

  // Get my data and plots
  hideAllData();
  clearAllData();
  showMorePlots(false);
 
  var my_url = "getdata.php?lastdata&sta="+my_config.sta;
  $.ajax({
    url: my_url,
    contentType: "text/csv",
    dataType: "json"
  })
  .done( function( data ) {
    $("#loading").hide();
    $("#single_station_data").show();
    $("#station_outages").show();
    $("#station_config").show();
 
    // If manufacturer is not CODAR, hide, beampattern option and change beampattern value to ideal
    if( ! data[0]['manufacturer'].match(/CODAR/i) ){
      $("#form-beampattern").hide();
      $("#beampatterns").val("ideal");
    }
    else{
      $("#form-beampattern").show();
      $("#beampatterns").val("measured");
    }

    // 3 outcomes with data
    // 1. data returns normally
    // 2. site present but no radial files
    // 3. no site
    
    if( data == undefined || data == null ){

    } 
    else{
      getStationsMeta(data);

      // Check to see if the site has a radial file
      if( data[0]["dfile"] == undefined || data[0]["dfile"] == null ){

      }
      else {
        getOutagesInfo();

        // Setup net variable in case the url only has the station
        my_config.net = data[0]['net'];
        cfreq = parseFloat(data[0]['cfreq']).toFixed(3);
        lat = parseFloat(data[0]['lat']);
        lon = parseFloat(data[0]['lon']);
        dfile = data[0]['dfile'];
        manufacturer = data[0]['manufacturer'];
        drawRangePlot(cfreq);
        drawLatencyPlot();
        drawNumberOfSolutionsPlot();
        drawRadialsMap(cfreq,lat,lon,manufacturer);
        drawHeatmapCoverage(cfreq,lat,lon,manufacturer);
      }
    }
  });
  
  // Show the outages
  showOutages();
  showConfiguration();
 
  // Setup my date fields
  $("#startdatepicker").val(((new Date(my_config.starttime*1000)).getUTCMonth()+1)+"/"+(new Date(my_config.starttime*1000)).getUTCDate()+"/"+(new Date(my_config.starttime*1000)).getUTCFullYear());
  $("#enddatepicker").val(((new Date(my_config.endtime*1000)).getUTCMonth()+1)+"/"+(new Date(my_config.endtime*1000)).getUTCDate()+"/"+(new Date(my_config.endtime*1000)).getUTCFullYear());
  $("#dateOutageStart").val(outputDate( Date.now()/1000 ) );
// TODO at 4pm datetime_picker has an hour of 24 instead of advancing ot the next day
  $("#site_config_datetime_picker").val(outputDateTime( Date.now()/1000 ) );
}

/*
 * Draws the heatmap showing coverage
 */
function drawHeatmapCoverage(center_frequency,lat,lon,manufacturer){
  // Clear out the map first
  $("#map2").empty();

  myZoom = getZoomLevel(center_frequency);

  // Add the base layer
  var map = new ol.Map({ target: 'map2' });
  var osmLayer = new ol.layer.Tile({
    source: new ol.source.OSM()
  });
  map.addLayer( osmLayer );

  // Create my view
  var view = new ol.View({
    center: ol.proj.transform([ parseFloat(lon),parseFloat(lat) ], 'EPSG:4326','EPSG:3857'),
    zoom: myZoom
  });
  map.setView( view );

  // Get wera radials
  if (manufacturer.toLowerCase().indexOf("lera") >= 0 ){
    map.addLayer( getRadialCoveragePercentageLayer("lera") );
  }
  else if ( manufacturer.toLowerCase().indexOf("wera") >= 0){
    map.addLayer( getRadialCoveragePercentageLayer("wera") );
  }
  // Hawaii
  else if ( manufacturer.toLowerCase().indexOf("ghfdr") >= 0){
    map.addLayer( getRadialCoveragePercentageLayer("lera") );
  }
  else {
    // Get measured
    if( isThisPatternPresent("measured") ){ 
      map.addLayer( getRadialCoveragePercentageLayer("m") );
    }
    if( isThisPatternPresent("idealized") ){ 
      map.addLayer( getRadialCoveragePercentageLayer("i") );
    }
  }
  // Add a control to switch layers
  var layerSwitcher = new ol.control.LayerSwitcher({tipLabel: 'Pattern Types' });
  map.addControl(layerSwitcher);
}

/*
 * Returns the layer for the coverage
 *
 * @param pattern - 'i','m','wera'
 * @return layer 
 */
function getRadialCoveragePercentageLayer(pattern){
 
  if( pattern == "wera" ){
    title = "WERA";
  }
  else if( pattern == "lera" ){
    title = "LERA";
  }
  else if( pattern == "i" ){
    title = "Idealized";
  }
  else if( pattern == "m" ){
    title = "Measured";
  } 
  else {
    return null;
  }
  // Get last 24 hours of radials 
  starting = my_config.endtime - 60*60*24;
  geojson_source = new ol.source.Vector({
      url: 'getRadialCoveragePercentage.php?net='+my_config.net+'&sta='+my_config.sta+'&starttime='+starting+'&endtime='+my_config.endtime+'&pattern='+pattern,
      format: new ol.format.GeoJSON()
  })
  if( geojson_source !== undefined ){
    radial_m_layer = new ol.layer.Heatmap({
      title: title,
      source: geojson_source,
      opacity: 1,
      radius: 1,
      //gradient: ['#4000ff','#0040ff','#00bfff','#40ff00','#ffff00','#ff4000','#ff3000','#ff2000','#ff1000','#ff0000'],
      //         dark blue  blue      green     orange    red     dark red
      //gradient: ['#000000','#3300ff','#00ccff','#33cc00','#ffff00','#ff4000','#ff2000','#660000'],
      gradient: ['#0000cc','#0000ff','#009900','#ffa500','#ff0000','#990000'],
      blur: 0
    });

    s = outputDateTime( starting );
    e = outputDateTime( my_config.endtime );
   
    $("#radialPercentageLabel").text("Percent Coverage from " + s + " to " + e);
    return radial_m_layer;
  }
}

/*
 * function getRadialCoverageLayer
 */
function getRadialCoverageLayer(pattern){
  if( pattern == "wera" ){
    title = "WERA";
    color = WERA_COLOR;
  }
  else if( pattern == "lera" ){
    title = "LERA";
    color = WERA_COLOR;
  }
  else if( pattern == "i" ){
    title = "Idealized";
    color = IDEAL_COLOR;
  }
  else if( pattern == "m" ){
    title = "Measured";
    color = MEASURED_COLOR;
  } 
  else {
    return null;
  }
  geojson_source = new ol.source.Vector({
      url: 'getRadialCoverage.php?pattern='+pattern+'&sta='+my_config.sta+'&net='+my_config.net,
      format: new ol.format.GeoJSON()
  })
  if( geojson_source !== undefined ) {
    radial_m_layer = new ol.layer.Vector({
      title: title,
      source: geojson_source,
      style: new ol.style.Style({
        stroke: new ol.style.Stroke({color: color, width: 2}),
      })
    });
  }
  return radial_m_layer;
}

/*
 * function drawRadialsMap
 * Draws the map showing the coverage of the site
 */
function drawRadialsMap(center_frequency,lat,lon,manufacturer){

  var myZoom;

  // Clear out the map first
  $("#map").empty();

  myZoom = getZoomLevel(center_frequency);
  
  // Add the base layer
  var map = new ol.Map({ target: 'map' });
  var osm_layer = new ol.layer.Tile({
        source: new ol.source.OSM
  });
  map.addLayer( osm_layer );
  
  // Create my view
  var view = new ol.View({
      center: ol.proj.transform( [parseFloat(lon), parseFloat(lat)], 'EPSG:4326', 'EPSG:3857'),
      zoom: myZoom
  });
  map.setView( view );
  
  // Get wera radials
  if( manufacturer.toLowerCase().indexOf("lera") >= 0){
    layer = getRadialCoverageLayer("lera");
    map.addLayer( layer );
  }
  else if( manufacturer.toLowerCase().indexOf("wera") >= 0 ){
    layer = getRadialCoverageLayer("wera");
    map.addLayer( layer );
  }
  // Hawaii
  else if( manufacturer.toLowerCase().indexOf("ghfdr") >= 0 ){
    layer = getRadialCoverageLayer("lera");
    map.addLayer( layer );
  }
  
  else {

    // Get measured radials
    if( isThisPatternPresent("measured") ){
      layer = getRadialCoverageLayer("m");
      map.addLayer( layer );
    } // end if( isThisPatternPresent )

    // Get ideal radials
    if( isThisPatternPresent("idealized") ){
      layer = getRadialCoverageLayer("i");
      map.addLayer( layer );
    }
  }

  var layerSwitcher = new ol.control.LayerSwitcher({tipLabel: 'Pattern Types' });

  map.addControl(layerSwitcher);

} // end function drawRadialsMap

/* Used to return the css_class/color for a specified age */
function getAgeColor(ageSeconds){
  if(ageSeconds >= 30*24*60*60) { 
    return "age3";
  } else if(ageSeconds >= 10*60*60) { 
    return "age2";
  } else if(ageSeconds >= 5*60*60) { 
    return "age1";
  } else {
    return "age0";
  }
} // End getAgeColor

/* sets the background color of the page */
//function setDataBackgroundColor(ageHours){
function setDataBackgroundColor(ageSeconds){
  resetDataBackgroundColor();
  var cl=getAgeColor(ageSeconds);
  $("#data").addClass(cl);
}
function resetDataBackgroundColor(){
  $("#data").removeClass("age0");
  $("#data").removeClass("age1");
  $("#data").removeClass("age2");
  $("#data").removeClass("age3");

}

/*
 * function getURL: - build a getdata url
 * argument: dataset - what dataset to retrieve
 *           sta - station
 * returns: url
 */
function getURL(dataset){
  var my_net;

  if( my_config.net != "" ){
    my_net = "&net="+my_config.net;
  }
  else {
    my_net = "";
  }
  
  return "getdata.php?"+dataset+"&sta="+my_config.sta+my_net+"&starttime="+my_config.starttime+"&endtime="+my_config.endtime;
}

/*
 * function showMorePlots
 * Show/hide other plots
 */
function showMorePlots(show){

  if( show == false ){
    $(".more_plots").hide();
    $("#morePlots").html( "+ More Plots" );
  }
  else {
    drawAWGTemperaturePlot();
    drawTXForwardPowerPlot();
    drawTXReflectedPowerPlot();
    drawReceiverTemperaturePlot();

    $(".more_plots").show();
    $("#morePlots").html( "- Less Plots" );
  }
}

/* 
 * function average
 * Arguments a: multidim array of numbers [[1234567,1],[1234568,2],[1234569,2]]
 * Returns obj containing mean, deviation, median, min, max and variance
 * x = average(array)
 * x.mean
 * x.deviation
 * x.variance
 */
function average(a2){
  //Get rid of nonnumbers in the data
  a = a2.filter(function(e){ return e[1]>=0 || e[1]<0;});
  var medianarray = [];
  var r = {mean: 0, variance: 0, deviation: 0, min: 999999999, max: 0, median:0}, t = a.length;
  for(var m, s = 0, l = t; l--; s += a[l][1]){
    if( r.min > a[l][1] ) r.min = a[l][1];
    if( r.max < a[l][1] ) r.max = a[l][1];
    medianarray.push( a[l][1] );
  }
  for(m = r.mean = s / t, l = t, s = 0; l--; s += Math.pow(a[l][1] - m, 2)){}

  // get the median, but first sort the array
  medianarray.sort(function(a,b){ return a-b });
  var middle = Math.floor( ( medianarray.length - 1 ) / 2 ); 
  if( medianarray.length %2 ){
    r.median = medianarray[middle];
  }
  else {
    r.median = ( medianarray[middle] + medianarray[middle + 1] ) / 2;
  }
  
  return r.deviation = Math.sqrt(r.variance = s / t), r;
}
// End function average

/*
 * function createDataStatsTable
 * arguments: data: multidim obj: data{i} = [0][0/1]
 *            parameter - string - parameter name
 */
function createDataStatsTable(data,parameter){
  var d;
  var sitedata = "";

  // First get idealized
  if( data["i"].length > 0 ){
    d = average( data["i"] );    
    sitedata += "<tr><td>"+parameter+"</td><td>Idealized</td><td>"+(d.min).toFixed(2)+"</td><td>"+(d.max).toFixed(2)+"</td><td>"+(d.median).toFixed(2)+"</td><td>"+(d.mean).toFixed(2)+"</td><td>"+(d.deviation).toFixed(2)+"</td></tr>"

    $( '#parameter_stats tr:last').after(sitedata);
  }  

  // Measured
  if( data["m"].length > 0 ){
    sitedata = "";
    d = average( data["m"] );
    sitedata += "<tr><td>"+parameter+"</td><td>Measured</td><td>"+(d.min).toFixed(2)+"</td><td>"+(d.max).toFixed(2)+"</td><td>"+(d.median).toFixed(2)+"</td><td>"+(d.mean).toFixed(2)+"</td><td>"+(d.deviation).toFixed(2)+"</td></tr>"

    $( '#parameter_stats tr:last').after(sitedata);
  }  
}
// End function createDataStatsTable


function clearDataStatsTable(){
  $('#parameter_stats').find("tr:gt(0)").remove();
}

function clearAllData(){
  $("#site_meta").html("");
  $("#idealized_site_meta").html("");
  $("#measured_site_meta").html("");
  $("#map").html("");
  $(".middle_plot").html("");
  $(".right_plot").html("");
  $("#hours_records").html("");
  clearDataStatsTable();
}

/*
 * Plot the stats plots 
 * data{patterntype} = [time,data][time,data]
 */
function dataToHistogramPlots(datas,chartid,chartText,xaxisText,yaxisText,bins){
  var ret = {};
  var idealized = [];
  var measured = []; 

  bins = typeof bins !== 'undefined' ? bins : 7;

  // No data to histo?
  if( datas.length < 1 ) {
    return ret;
  }
  
  // create the chart options
  var options = {
    chart: {
      renderTo: chartid,
      zoomType: 'x',
      type: 'column'
    },
    title:{ text: chartText, },
    tooltip: {
      headerFormat: '<span style="font-size: 10px">{point.key}</span><br/>'
    },
    xAxis:{ title:{ text: xaxisText } },
    yAxis:{ title:{ text: yaxisText } },
    series:[ ]
  };

  $.each( datas, function( key, data ){

    // Get just the data we want.  Remove the time element
    data = jQuery.map( data, function( a ) {
      return a[1];
    });  
    // Remove elements with NaN in it
    data = jQuery.map(data,function(a){
      return isNaN(a) ? null : a;
    });

    var min = Math.floor( Math.min.apply(null, data) );
    var max = Math.ceil( Math.max.apply(null, data) );
    var step = Math.ceil((max-min) / bins); 
    if( step < 1 ) step = 1;

    // Set the labels as the average value in the container
    var half = Math.ceil(step/2);
    if( half < 1 ) half = 1;
    if( step == min && step == max ) half = 0;

    // Initialize my bins
    ret[ key ] = {}; 
    for( var i=min; i<max; i+=step ){
      ret[key][i] = 0;
    }
    
    // Count items per bin
    $.each( data, function( idx, res ) {
      var val = min + step*Math.floor((res-min)/step);
      if( val == max && max != min ) val -= step;
      ret[key][val]++;
    }) 
    var i_data = [];
    $.each( ret[key], function( idx, res ) {
      i_data.push( [parseFloat(idx),parseFloat(res)] );
    }) 

    var color;
    if ( key == "m" ){
      color = MEASURED_COLOR;
      key = "Measured";
    }
    else{
      color = IDEAL_COLOR;
      if( key == "i") key = "Idealized"; 
    }
    // Add series
    options.series.push({  
      name: key,
      color: color,
      data: i_data
    }) 
  });
 
  var chart = new Highcharts.Chart(options);
}
// end function dataToHistogramPlots

// Start station meta on left side
function getStationsMeta(data){
   
  var radialAgeSeconds=99999999999;
  var more_plots_available = false;
  var radialtime = 0;

  // data can sometimes have 2 elements data[0] and data[1].  One for ideal and one for measured
  $.each( data, function( index, element ){
      
    // Figure out our pattern type
    var pattern_type = "";
    if( element['patterntype'] == 'm' ){
      pattern_type = "Measured";
    }
    else {
      pattern_type = "Idealized";
    }

    var age = "";
    var sitedata = "";
    var d = new Date();

    if (element['mtime'] != null){ 
      var arrivalTime = new Date( element['mtime'] * 1000);
      var rightnow = new Date()/1000;
      var diffdays;
      var diffhours;
      var diffminutes;
      var datediff = Math.abs(rightnow - element['time']);
      
      // Get the lowest radial age hours from measured and idealized 
      if( datediff < radialAgeSeconds ){
        radialAgeSeconds = datediff;
      }

      if( datediff > 21600 ){
        datediffold = 1;
      }
      else {
        datediffold = 0;
      }
      if( diffdays=Math.floor(datediff/86400) ) {
        datediff = datediff % 86400;
        age = diffdays + " days ";
      }
      if( diffhours=Math.floor(datediff/3600) ){
        datediff = datediff % 3600;
      }
      if( diffminutes=Math.floor(datediff/60) ){
        datediff = datediff % 60;
        diffseconds = ("0"+datediff).slice(-2);
      }
      age = age + ("0"+diffhours).slice(-2) + ":" + ("0"+diffminutes).slice(-2);

      sitedata = "Arrival Time: " + arrivalTime.toUTCString() + "<br />" +
                 "Pattern Type: " + pattern_type + "<br />" +
                 "Most Recent File: " + element['dfile'] + "<br />" +
                 "File Format: " + element['dfile'].substr( element['dfile'].indexOf('.')+1 ) + "<br />" +
                 "Age: " + age + " (H:MM)<br />"; 
    }
 
    // Get the latest data (either ideal or measured)
    var sitemeta;               
    if( element['mtime'] >= radialtime ){
      sitemeta = element['staname'] + " (" + element['sta'] + ")<br />" +
                   "Network: " + element['net'] + "<br />" +
                   "Latitude: " + parseFloat(element['lat']).toFixed(4) + "<br />" +
                   "Longitude: " + parseFloat(element['lon']).toFixed(4) + "<br />";
      if( element['cfreq'] != null ){
        sitemeta += "Center Frequency: " + parseFloat(element['cfreq']).toFixed(3) + " MHz<br />";
      }
      if( element['xmit_fwd_pwr'] != null ){
        sitemeta += "Tx Forward Power: " + parseFloat(element['xmit_fwd_pwr']).toFixed(2) + " W<br />";
        more_plots_available = true;
      }
      if( element['xmit_ref_pwr'] != null ){
        sitemeta += "Tx Reflected Power: " + parseFloat(element['xmit_ref_pwr']).toFixed(2) + " W<br />";
        more_plots_available = true;
      }
      if( element['receiver_chassis_tmp'] != null ){
        sitemeta += "Receiver Temp: " + element['receiver_chassis_tmp'] + "&deg;C<br />";
        more_plots_available = true;
      }
      if( element['awg_tmp'] != null ){
        sitemeta += "AWG 3-Module Temp: " + element['awg_tmp'] + "&deg;C<br />";
        more_plots_available = true;
      }
      
      // Hide the More plots button if more_plots_availble = false
      $(".more_plots").hide();
      if( more_plots_available == false ){
        $("#morePlots").hide();
      }
      else {
        $("#morePlots").show();
      }
      radialtime = element['mtime'];
      sitemeta += "Page Generated: " + d.toUTCString() + "<br />"; 
      $("#site_meta").html( sitemeta );
    }

    if( element["patterntype"] == "m" ){
      $("#measured_site_meta").html(sitedata);
    }
    else {
      $("#idealized_site_meta").html(sitedata);
    }
  });

  setDataBackgroundColor(radialAgeSeconds);
}
// End station meta on left side

// Start rad_range plot
function drawRangePlot(center_frequency){

  //TODO
  url = getURL( "rad_range" );
  $.ajax({
    url:url,
    contentType: "text/csv",
    async: true,
    dataType: "json"
  })
  .done( function( data ){
    var patterntypes = {};
    var elements_i = [];
    var elements_m = [];
    patterntypes["i"] = elements_i;
    patterntypes["m"] = elements_m;
    if( data != null ){
      var i=data.length;
      while(i--){
        if(i!=0){
          if( data[i]["time"] - data[i-1]["time"] >= 60*60*2 && data[i]["patterntype"]==data[i-1]["patterntype"]){
            var newelem={ 
              patterntype: data[i]["patterntype"],
              time: (Number(data[i-1]["time"]) + 1).toString()
            }
            patterntypes[ data[i]["patterntype"] ].unshift( [parseFloat(data[i]["time"])*1000,parseFloat( data[i]["rad_range"]  )] );
            data.splice(i,0,newelem);
          }
        }
           
        patterntypes[ data[i]["patterntype"] ].unshift( [parseFloat(data[i]["time"])*1000,parseFloat( data[i]["rad_range"] )] );
      }
    }
    dataToHistogramPlots(patterntypes,rad_range_stats,"Range (km) Stats","km","#",10);

    var options = {
      chart:{
        renderTo: 'rad_range', zoomType: 'x'
      },
      title:{ text: 'Range (km)' },
      xAxis:{
        title:{ text: getTimeAxisTitle() },
        max: my_config.endtime * 1000,
        min: my_config.starttime * 1000,
        type: 'datetime'
      },
      yAxis:{ title:{text: 'km' }},
      series:[
        {name: 'Idealized', data: patterntypes['i'],color:IDEAL_COLOR},
        {name: 'Measured', data: patterntypes['m'],color:MEASURED_COLOR}
      ]
    };  
    var chart = new Highcharts.Chart(options);

    // Create data stats table
    createDataStatsTable( patterntypes, "Range" );
  })
  .fail( function() {
    alert( "Unable to retrieve radial range plots");
  });
}
// End rad_range plot

// Start database latency plot
// and hours/records stats
function drawLatencyPlot(){

  url = getURL("dbLatency");
  $.ajax({
    url:url,
    contentType: "text/csv",
    async: true,
    dataType: "json"
  })
  .done( function( data ){
    var patterntypes = {};
    var elements_i = [];
    var elements_m = [];
    patterntypes["i"] = elements_i;
    patterntypes["m"] = elements_m;
    if( data != null ){
      var i = data.length;
      while (i--){
        if( i!=0 ){
          if( data[i]["time"] - data[i-1]["time"] >= 60*60*1.5  && data[i]["patterntype"]==data[i-1]["patterntype"]){
            var newelem={ 
              patterntype: data[i]["patterntype"],
              time: (Number(data[i-1]["time"]) + 1).toString()
            }
            patterntypes[ data[i]["patterntype"] ].unshift( [parseFloat(data[i]["time"])*1000,parseFloat(data[i]["latency"]/(60*60))] );
            data.splice(i,0,newelem);
          }
        }
        patterntypes[ data[i]["patterntype"] ].unshift( [parseFloat(data[i]["time"])*1000,parseFloat(data[i]["latency"]/(60*60))] );
      }
    }
    dataToHistogramPlots(patterntypes,db_latency_stats,"Latency (hrs) Stats","hrs","#");

    var options = {
      chart:{
        renderTo: 'db_latency', zoomType: 'x'
      },
      title:{ text: 'Database Latency (hours)' },
      xAxis:{
        title:{ text: getTimeAxisTitle() },
        max: my_config.endtime * 1000,
        min: my_config.starttime * 1000,
        type: 'datetime'
      },
      yAxis:{ title:{text: 'hours' }},
      series:[
        {name: 'Idealized', data: patterntypes['i'],color:IDEAL_COLOR},
        {name: 'Measured', data: patterntypes['m'],color:MEASURED_COLOR}
      ]
    };  

    var chart = new Highcharts.Chart(options);
    //
    // Create hours/records stats table
    var delta_hours = Math.ceil( (my_config.endtime - my_config.starttime)/3600 );
    // boundary condition; dates straddle boundaries of 2 hours
    if( (my_config.starttime - my_config.endtime)%3600 == 0 ) delta_hours += 1;
  
    //Get rid of nonnumbers in the data
    pi = patterntypes['i'].filter(function(e){ return e[1]>=0 || e[1]<0;});
    pm = patterntypes['m'].filter(function(e){ return e[1]>=0 || e[1]<0;});
    var recordCountIdeal = pi.length;
    var recordCountMeasured = pm.length;
    var hoursrecords = "<table><tr class='heading'><td>Pattern Type</td><td># Hours</td><td># Records</td><td># Missing</td><td>% Available</td></tr>";
    if( recordCountIdeal > 0 ){
      hoursrecords += "<tr><td>Idealized</td><td>"+delta_hours+"</td><td>"+recordCountIdeal+"</td><td>"+(delta_hours-recordCountIdeal)+"</td><td>"+((recordCountIdeal/delta_hours)*100).toFixed(2)+"%</td></tr>";
    }
    if( recordCountMeasured > 0 ){
      hoursrecords += "<tr><td>Measured</td><td>"+delta_hours+"</td><td>"+recordCountMeasured+"</td><td>"+(delta_hours-recordCountMeasured)+"</td><td>"+((recordCountMeasured/delta_hours)*100).toFixed(2)+"%</td></tr>";
    }
    hoursrecords += "</table>"

    $("#hours_records").html( hoursrecords );

    // Create data stats table
    createDataStatsTable( patterntypes, "Latency");
 
  })
  .fail( function() {
    alert( "Unable to retrieve database latency plots");
  });
}
// End database latency plot

// Number of solutions
function drawNumberOfSolutionsPlot(){  
  
  url = getURL( "number_solutions" );
  $.ajax({
    url:url,
    contentType: "text/csv",
    async: true,
    dataType: "json"
  })
  .done( function( data ){
    var patterntypes = {};
    var elements_i = [];
    var elements_m = [];
    patterntypes["i"] = elements_i;
    patterntypes["m"] = elements_m;
    if ( data != null ){
      var i=data.length;
      while (i--){ 
        if(i!=0){
          if( data[i]["time"] - data[i-1]["time"] >= 60*60*1.5 && data[i]["patterntype"]==data[i-1]["patterntype"]){
            var newelem={ 
              patterntype: data[i]["patterntype"],
              time: (Number(data[i-1]["time"]) + 1).toString()
            }
            patterntypes[ data[i]["patterntype"] ].unshift( [parseFloat(data[i]["time"])*1000,parseFloat(data[i]["nrads"])] );
            data.splice(i,0,newelem);
          }
        }
        patterntypes[ data[i]["patterntype"] ].unshift( [parseFloat(data[i]["time"])*1000,parseFloat(data[i]["nrads"])] );
      }
    }
    dataToHistogramPlots(patterntypes,number_solutions_stats,"Number of Solutions Stats","#","#",8);

    var options = {
      chart:{
        renderTo: 'number_solutions', zoomType: 'x'
      },
      title:{ text: 'Number of Solutions' },
      xAxis:{
        title:{ text: getTimeAxisTitle() },
        max: my_config.endtime * 1000,
        min: my_config.starttime * 1000,
        type: 'datetime'
      },
      yAxis:{ title:{text: '#' }},
      series:[
        {name: 'Idealized', data: patterntypes['i'],color:IDEAL_COLOR},
        {name: 'Measured', data: patterntypes['m'],color:MEASURED_COLOR}
      ]
    };  

    var chart = new Highcharts.Chart(options);
    // Create data stats table
    createDataStatsTable( patterntypes, "# Solutions");
  })
  .fail( function() {
    alert( "Unable to retrieve number of solutions plots");
  });
}
// End number of solutions plot

// Receiver Temperature plot 
function drawReceiverTemperaturePlot(){  

  url = getURL( "receivertemp" );
  $.ajax({
    url:url,
    contentType: "text/csv",
    async: true,
    dataType: "json"
  })
  .done( function( data ){
    var mydata = {};
    mydata["Temperature"] = [];
    if( data != null ){
      var i=data.length;
      while (i--){
        // if tmp is below zero, get rid of it
        if( data[i]["receiver_chassis_tmp"] < 0 ) continue;

        if(i!=0){
          if( data[i]["time"] - data[i-1]["time"] >= 60*60*1.5 ){
            var newelem={ 
              time: (Number(data[i-1]["time"]) + 1).toString()
            }
            data.splice(i,0,newelem);
          }
        }
        mydata["Temperature"].unshift( [parseFloat( data[i]["time"])*1000, parseFloat(data[i]["receiver_chassis_tmp"])] );  
      }
    }
    dataToHistogramPlots(mydata,receiver_temperature_stats,"Receiver Temperature Stats","°C","#",10);

    var options = {
      chart:{
        renderTo: 'receiver_temperature', zoomType: 'x'
      },
      title:{ text: 'Receiver Temperature (°C)' },
      xAxis:{
        title:{ text: getTimeAxisTitle() },
        max: my_config.endtime * 1000,
        min: my_config.starttime * 1000,
        type: 'datetime'
      },
      yAxis:{ title:{text: '°C' }},
      series:[
        {name: 'Temperature', data: mydata["Temperature"],color:IDEAL_COLOR}
      ]
    };  
    var chart = new Highcharts.Chart(options);
   
  })
  .fail( function() {
    alert( "Unable to retrieve receiver temperature plot");
  });
}
// End Receiver temperature plot

// Tx Reflected Power 
function drawTXReflectedPowerPlot(){  

  url = getURL( "txreflectedpower" );
  $.ajax({
    url:url,
    contentType: "text/csv",
    async: true,
    dataType: "json"
  })
  .done( function( data ){
    var mydata = {};
    mydata["Watts"] = [];
    if( data != null ){
      var i = data.length;
      while (i--){
        if( i!=0 ){
          if( data[i]["time"] - data[i-1]["time"] >= 60*60*1.5 ){
            var newelem={ 
              time: (Number(data[i-1]["time"]) + 1).toString()
            }
            data.splice(i,0,newelem);
          }
        }
        mydata["Watts"].unshift( [parseFloat( data[i]["time"])*1000, parseFloat(data[i]["xmit_ref_pwr"])] );  
      }

      $.each( data, function( idx, res ) {
      });
    }
    dataToHistogramPlots(mydata,tx_reflected_power_stats,"Transmission Reflected Power Stats","Watts","#",10);

    var options = {
      chart:{
        renderTo: 'tx_reflected_power', zoomType: 'x'
      },
      title:{ text: 'TX Reflected Power (Watts)' },
      xAxis:{
        title:{ text: getTimeAxisTitle() },
        max: my_config.endtime * 1000,
        min: my_config.starttime * 1000,
        type: 'datetime'
      },
      yAxis:{ title:{text: 'Watts' }},
      series:[
        {name: 'Power', data: mydata['Watts'],color:IDEAL_COLOR}
      ]
    };  
    var chart = new Highcharts.Chart(options);
   
  })
  .fail( function() {
    alert( "Unable to retrieve Transmission Reflected Power plot");
  });
}
// End Transmission Reflected Power plot

// Tx Forward Power 
function drawTXForwardPowerPlot(){  

  url = getURL( "txforwardpower" );
  $.ajax({
    url:url,
    contentType: "text/csv",
    async: true,
    dataType: "json"
  })
  .done( function( data ){
    var mydata = {};
    mydata["Watts"] = [];
    if( data != null ){
      var i = data.length;
      while (i--){
        if( i!=0 ){
          if( data[i]["time"] - data[i-1]["time"] >= 60*60*1.5 ){
            var newelem={ 
              time: (Number(data[i-1]["time"]) + 1).toString()
            }
            data.splice(i,0,newelem);
          }
        }
        mydata["Watts"].unshift( [parseFloat( data[i]["time"])*1000, parseFloat(data[i]["xmit_fwd_pwr"])] );  
      }
    }
    dataToHistogramPlots(mydata,tx_forward_power_stats,"Transmission Forward Power Stats","Watts","#",10);

    var options = {
      chart:{ renderTo: 'tx_forward_power', zoomType: 'x' },
      title:{ text: 'TX Forward Power (Watts)' },
      xAxis:{
        title:{ text: getTimeAxisTitle() },
        max: my_config.endtime * 1000,
        min: my_config.starttime * 1000,
        type: 'datetime'
      },
      yAxis:{ title:{text: 'Watts' }},
      series:[
        {name: 'Power', data: mydata["Watts"],color:IDEAL_COLOR}
      ]
    };  

    var chart = new Highcharts.Chart(options);
   
  })
  .fail( function() {
    alert( "Unable to retrieve Transmission Forward Power plot");
  });
}
// End Transmission Foward Power plot

// AWG Temperature 
function drawAWGTemperaturePlot(){  

  url = getURL( "awgtemp" );
  $.ajax({
    url:url,
    contentType: "text/csv",
    async: true,
    dataType: "json"
  })
  .done( function( data ){
    var mydata = {};
    mydata["Temperature"] = [];
    if( data != null ){
      var i = data.length;
      while (i--){
        // if tmp is below zero, get rid of it
        if( data[i]["awg_tmp"] < 0 ) continue;
        if( i!=0 ){
          if( data[i]["time"] - data[i-1]["time"] >= 60*60*1.5 ){
            var newelem={ 
              time: (Number(data[i-1]["time"]) + 1).toString()
            }
            data.splice(i,0,newelem);
          }
        }
        mydata["Temperature"].unshift( [parseFloat( data[i]["time"])*1000, parseFloat(data[i]["awg_tmp"])] );  
      }
    }
    dataToHistogramPlots(mydata,awg_temperature_stats,"AWG Temperature Stats","°C","#",10);
    var options = {
      chart:{ renderTo: 'awg_temperature', zoomType: 'x' },
      title:{ text: 'AWG 3-Module Temperature (°C)' },
      xAxis:{
        title:{ text: getTimeAxisTitle() },
        max: my_config.endtime * 1000,
        min: my_config.starttime * 1000,
        type: 'datetime'
      },
      yAxis:{ title:{text: '°C' }},
      series:[
        {name: 'Temperature', data: mydata["Temperature"],color:IDEAL_COLOR}
      ]
    };  
    var chart = new Highcharts.Chart(options);
   
  })
  .fail( function() {
    alert( "Unable to retrieve AWG 3-Module Temperature plot");
  });
}
// End awg temperature plot

/*
 * Get the zoom level for the maps based on center freq
 */
function getZoomLevel(center_freq){
  if( center_freq < 6 ){
    zoom = 7;
  }
  else if( center_freq < 20 ){
    zoom = 8;
  }
  else {
    zoom = 9;
  }
  return zoom;
} // End getZoomLevel

// Show loading message
function show_loading_message(){
  $('#loading_container').show();
}
// Hide loading message
function hide_loading_message(){
  $('#loading_container').hide();
}

// Show message
function show_message(message_text, message_type){
  $('#message').html('<p>' + message_text + '</p>').attr('class', message_type);
  $('#message_container').show();
  if (typeof timeout_message !== 'undefined'){
    window.clearTimeout(timeout_message);
  }
  timeout_message = setTimeout(function(){
    hide_message();
  }, 8000);
}
// Hide message
function hide_message(){
  $('#message').html('').attr('class', '');
  $('#message_container').hide();
}
