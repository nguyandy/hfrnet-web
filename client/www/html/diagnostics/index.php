<?php
session_start();
#date_default_timezone_set('America/Los_Angeles');
date_default_timezone_set('UTC');

$subdirectory = getenv('SUBDIRECTORY');
if ($subdirectory === false || $subdirectory === '') {
    $subdirectory_path = '';
} else {
    $subdirectory_path = "/{$subdirectory}";
}
?>

<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="content-type" content="text/html;charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
  <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
  <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js"></script>
  <script type="text/javascript" src="//cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="//cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
  <script type="text/javascript" src="//cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
  <script type-"text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
  <script type="text/javascript" src="//cdn.rawgit.com/bpampuch/pdfmake/0.1.36/build/pdfmake.min.js"></script>
  <script type="text/javascript" src="//cdn.rawgit.com/bpampuch/pdfmake/0.1.36/build/vfs_fonts.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/jstree.min.js"></script>
  <script type="text/javascript" src="../js/highcharts/highcharts.js"></script> 
  <script type="text/javascript" src="../js/highcharts/modules/exporting.js"></script>
  <script type="text/javascript" src="../js/highcharts/modules/no-data-to-display.js"></script>
  <script type="text/javascript" src="js/get_url_parameter.js"></script>
  <script type="text/javascript" src="js/plot_options.js"></script>
  <script type="text/javascript" src="js/index.min.js"></script>
  <script type="text/javascript" src="lib/openlayers/ol.js"></script>
  <script type="text/javascript" src="js/ol3-layerswitcher.js"></script>
  <script type="text/javascript" src="js/jquery.datetimepicker.full.min.js"></script>

  <link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/themes/smoothness/jquery-ui.css" />
  <link rel="stylesheet" href="dist/themes/default/style.min.css" />
  <link rel="stylesheet" href="//cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css" type="text/css"/>
  <link rel="stylesheet" href="//cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css" type="text/css"/>
  <link rel="stylesheet" href="ol3-layerswitcher.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="dist/themes/proton/style.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/themes/default/style.min.css" />
  <link rel='stylesheet' href='index.css' />
  <link rel="stylesheet" href="jquery.datetimepicker.min.css" />
  <title>HFRNet: realtime site diagnostics</title>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
      <button id="openCloseSidebar" type="button" aria-label="Toggle navigation" class="rounded border border-dark bg-secondary" onclick="showHideTree()"><span class="navbar-toggler-icon"></span></button>
      
      <a href="https://www.noaa.gov/" target="_blank">
        <img src="<?= $subdirectory_path ?>/img/noaa-logo.png" alt="NOAA Logo" style="height: 40px; margin-left: 10px;" />
      </a>
      
      <a href="https://ioos.noaa.gov/" target="_blank">
        <img src="<?= $subdirectory_path ?>/img/ioos-logo.png" alt="IOOS Logo" style="height: 40px; margin-left: 10px;" />
      </a>
      
      <a class="navbar-brand px-3" href="#">HF-Radar Network</a> 
      <div id="navbar" class="navbar-collapse collapse">

        <ul class="navbar-nav navbar-right ms-auto mb-2 mb-lg-0">
          <li class="nav-item dropdown moreReports">
            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-expanded="false">Summary Views</a>
            <ul class="dropdown-menu" id="moreSummaryReports" aria-labelledby="navbarDropdown">
              <li><a href="#" class="dropdown-item">Association Uptime</a></li>
              <li><a href="#" class="dropdown-item">Network</a></li>
            </ul>
          </li>
          <li><a href="#" class="nav-link" onclick="returnThisPageURL()">Bookmark This Page</a></li>

          <!-- <li class='nav-item dropdown' id="logged-in" <?php if( !isset($_SESSION['user']) ) echo 'style="display:none;"' ?>>
            <a id='login_link' href='#' class='nav-link dropdown-toggle' data-bs-toggle="dropdown" data-loggedin='true' role='button' aria-expanded='false'>Hello, <?php if( isset($_SESSION['user']) ) echo $_SESSION['user']['username'];?></a>

            <form class="dropdown-menu dropdown-menu-end p-3" style="width:300px">
              <div class="row">
                <div id='userinfo' class="col-xs-7">
                  <span><?php if( isset($_SESSION['user']) ) echo $_SESSION['user']['username']; ?></span>
                  <p class="text-muted small"><?php if( isset($_SESSION['user']) ) echo $_SESSION['user']['email']; ?></p>
                </div>
              </div>
              <div class="row">
                <div class="col"><button type="button" id='changepass' class="btn btn-light btn-sm text-nowrap">Change Password</button></div>
                <div class="col"><button type="button" id='logout' class="btn btn-light btn-sm float-end text-nowrap">Log Out</button></div>
              </div>
            </form>
          </li>
          <li id="log-in" <?php if( isset($_SESSION['user']) ) echo 'style="display:none;"'?> >
            <a id='login_link' href='#' class="nav-link" data-bs-toggle="modal" data-bs-target='#login-modal'>Login</a>
          </li> -->
          <li><a class="nav-link" href="help.php" target="_blank"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-question-circle-fill" viewBox="0 0 16 16">
  <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.496 6.033h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286a.237.237 0 0 0 .241.247zm2.325 6.443c.61 0 1.029-.394 1.029-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94 0 .533.425.927 1.01.927z"/>
</svg></a></li>
        </ul>
      </div>
    </div>
  </nav>
  <div class="modal fade" id="login-modal" tabindex="-1" aria-labelledby="login-modal" aria-hidden="true" >
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header"><h4 class="modal-title">Login to Your Account</h4></div>
        <div class="modal-body">
          <div class="alert alert-danger" id="login-error" style="display:none;"></div>
          <form  method="post">
            <div class="mb-3"><input type="text" class="form-control" id="user" name="user" placeholder="Username"></div>
            <div class="mb-3"><input type="password" class="form-control" id="pass" name="pass" placeholder="Password"></div>
          </form> 
        <!--<div class="login-help"><a href="#">Register</a> - <a href="#">Forgot Password</a></div>-->
         </div> 
         <div class="modal-footer">
           <button type="button" class="btn btn-primary" id="submitLogin">Login</button>
         </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="changePass-modal" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header"><h4>Change Password</h4></div>
        <div class="modal-body">
          <div class="alert alert-danger" id="password-error" style="display:none;"></div>
          <p>Use the form below to change your password. Your password cannot be the same as your username.</p>
          <form  method="post">
            <input type="password" id="pass1" class="form-control" name="pass" placeholder="Current Password">
            <input type="password" id="pass2" class="form-control" name="pass2" placeholder="New Password">
            <input type="password" id="pass3" class="form-control" name="pass3" placeholder="Confirm Password">
          </form> 
        </div>
        <div class="modal-footer">
            <button type="button" id="submitPassword" class="btn btn-primary">Change Password</button>
        </div>
      </div>
    </div>
  </div>
  <div class="container-fluid">
    <div class="row">
      <div id="tree-col" class="col-md-3">
        <div id="tree"></div>
      </div>
      <div class="col-md-9" id="data">
        <div class="row">
          <div class="col-md-12">
            <div id="clock"></div>
          </div>
        </div>
        <div class="row"><div class="col-md-12">
          <img id="loading" src="img/loading.gif" alt="loading" />
        </div></div>
        <div class="row"><div class="col-md-12">
          <table id="alldata"></table>
        </div></div>
        <div class="row"><div class="col-md-12">
          <iframe src="" id="iFrame"></iframe>
        </div></div>
        <div id ="single_station_data" class="row">
          <div id="left_ssd" class="col-md-3">
            <p id="site_meta"></p>
            <p id="disk_usage"></p>
            <p id="idealized_site_meta"></p>
            <p id="measured_site_meta"></p>
            <figure id="mapbox"><figcaption id="recentRadialLabel">Radial Coverage for Most Recent File</figcaption><div id="map"></div></figure>
            <figure id="mapbox2"><figcaption id="radialPercentageLabel">Radial Coverage Percentage</figcaption><div id="map2"></div></figure>
            <img src="<?= $subdirectory_path ?>/img/php/cb.php?range=0.00,100.00&ticks=6&scheme=2&width=250&height=15&padding=15,8&font_size=10&printf=%25d&title=Percentage&bg=0x7fffffff&fg=0x000000" />
          </div> <!-- end left_ssd -->
          <div id="middle_ssd" class="col-md-6">
            <div class="middle_plot" id="db_latency"></div>
            <div class="middle_plot" id="rad_range"></div>
            <div class="middle_plot" id="number_solutions"></div>
            <div class="middle_plot more_plots" id="awg_temperature"></div>
            <div class="middle_plot more_plots" id="tx_forward_power"></div>
            <div class="middle_plot more_plots" id="tx_reflected_power"></div>
            <div class="middle_plot more_plots" id="receiver_temperature"></div>
            <button id="morePlots" type="button" class="btn btn-secondary">+ More Plots</button>
            <div id="disk_usage_table" class="middle_table"></div>
            <div id="hours_records" class="middle_table"></div>
            <table id="parameter_stats" class="middle_table"><tr class="heading"><td>Parameter</td><td>Pattern Type</td><td>Min</td><td>Max</td><td>Median</td><td>Avg</td><td>StdDev</td></tr>
            </table>
          </div> <!-- end middle ssd -->
          <div id="right_ssd" class="col-md-3">
            <div class="right_plot" id="db_latency_stats"></div>
            <div class="right_plot" id="rad_range_stats"></div>
            <div class="right_plot" id="number_solutions_stats"></div>
            <div class="right_plot more_plots" id="awg_temperature_stats"></div>
            <div class="right_plot more_plots" id="tx_forward_power_stats"></div>
            <div class="right_plot more_plots" id="tx_reflected_power_stats"></div>
            <div class="right_plot more_plots" id="receiver_temperature_stats"></div>

            <form class="form">
              <div class="input-group mb-3">
                <span class="input-group-text" id="basic-addon1">Start date</span>
                <input class="form-control" type="text" id="startdatepicker" readonly="true" />
              </div>
              <div class="input-group mb-3">
                <span class="input-group-text" id="basic-addon1">End date</span>
                <input class="form-control" type="text" id="enddatepicker" readonly="true" />
              </div>
              <button id="generate_plots" type="button" class="btn btn-secondary">Generate plots</button>
            </form>
          </div> <!-- end right_ssd -->
        </div> <!-- end single_station_data -->
        <!--<div id="single_station_edit" class="row"> -->

        <!-- Outages -->
        <!-- <div id="station_outages" class="mb-5" > 
          <h3>All Outages</h3>
          <table id="alloutages2" class="compact stripe" style="width:100%;"></table>
          <button type="button" class="btn btn-primary" id="add_outage" data-bs-toggle="modal" data-bs-target="#outage_entry_form" <?php if( !isset($_SESSION['user']) ) echo 'style="display:none;"' ?>>Add Outage</button>
        </div>  -->
        <!-- end div id station_outages -->

        <!-- Station Config -->
        <!-- <div id="station_config" class="mb-5">
          <h3>Site Configuration</h3>
          <table id="siteconfiguration" class="compact stripe" style="width:100%;"></table>
          <div id="config_actions" >
            <button type="button" class="btn btn-primary" id="add_site_config" data-bs-toggle="modal" data-bs-target="#site_config_form">Add</button>
            <button type="button" class="btn btn-primary" id="stop_site_config" data-bs-toggle="modal" data-bs-target="#site_config_stop">Stop</button>
          </div>
        </div>  -->
        <!-- end div network_config -->
      </div> <!-- end div col-md-9 -->
    </div> <!-- end div class row -->
  </div> <!-- End div class container-fluid -->


  <!-- Outage entry form -->
  <div class="modal fade" id="outage_entry_form" tabindex="-1" aria-labelledby="outage_entry_form" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="outageheader">Add Outage</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form class="form add" id="outage-form" data-id="" action="#" role="form" novalidate>
            <div class="mb-3">
              <label class="form-label">Start Date (UTC): </label>
              <input class="form-control" type="text" id="dateOutageStart" readonly="true" />
            </div>
            <div class="mb-3">
              <label class="form-label" for="outages">Record an Outage: </label>
              <select class="form-control" name="outages" id="outages" class="form-control"> </select>
            </div>
            <div class="mb-3">
              <label class="form-label" for="tags">Add Tags: </label>
              <select class="form-control" multiple name="tags" id="tags" class="form-control"> </select>
            </div>
            <div class="mb-3">
              <label class="form-label" for="Notes">Notes: </label>
              <textarea class="form-control" rows="5" cols="40" id="outageNotes" name="outageNotes"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label" >Data Availability: </label>
              <select class="form-control" name="dataavail" id="dataavail" class="form-control"> </select>
            </div>
            <div class="mb-3">
              <label class="form-label" >Estimated Repair Date: </label>
              <select class="form-control" name="time_to_repair" id="time_to_repair" class="form-control"> </select>
            </div>
            <div class="mb-3">
              <label class="form-label" >Date Resolved (UTC): </label>
              <input class="form-control" type="text" id="dateOutageResolved" readonly="true" />
            </div>
          </form> 
        </div>
        <div class="modal-footer">
          <button type="button" id="submit_outage" class="btn btn-primary" data-bs-dismiss="modal" data-submittype="add">Submit</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </div>
  </div>
  <!-- End Outage entry form -->

  <!-- Message -->
  <div id="message_container">
    <div id="message" class="success">
      <p>This is a success message.</p>
    </div>
  </div>

  <!-- Loading message -->
  <div id="loading_container">
    <div id="loading_container2">
      <div id="loading_container3">
        <div id="loading_container4"> Loading, please wait...  </div>
      </div>
    </div>
  </div>

  <!-- Confirm delete modal form -->
  <div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="myModalLabel">Confirm Delete</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>You are about to delete the <span id="recordToDelete"></span>.</p>
          <p>Do you want to proceed?</p>
          <p class="debug-url"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cancel</button>
          <a id='deleterecord' class="btn btn-danger btn-ok">Delete</a>
        </div>
      </div>
    </div>
  </div>
  <!-- End confirm delete modal form -->

  <!-- site config entry form -->
  <div class="modal fade" id="site_config_form" tabindex="-1" aria-labelledby="site_config_form" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header"> 
          <h5 class="modal-title">Add Site Configuration</h5> 
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="post" class="form add" id="siteconfig-form" data-id="" action="#" role="form" novalidate>
            <div class="mb-3">
              <label class="form-label">Start Date/Time (UTC): </label>
              <input class="form-control" type="text" id="site_config_datetime_picker" readonly="true" />
            </div>
            <div class="mb-3" id="form-beampattern">
              <label for="siteconfigs" class="form-label">Beam Pattern: </label>
              <select class="form-control" id="beampatterns">
                <option selected value="measured">measured</option>
                <option value="ideal">ideal</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Radial Minute: </label>
              <input class="form-control" type="number" min='0' max="59" placeholder="0" value="0" id="radialminute" />
            </div>
          </form> 
        </div>
        <div class="modal-footer">
          <button type="button" id="submit_siteconfig" class="btn btn-primary" data-bs-dismiss="modal">Submit</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </div>
  </div> <!-- end site config entry form -->
  <!-- site config stop -->
  <div class="modal fade" id="site_config_stop" tabindex="-1" aria-labelledby="site_config_stop" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header"> 
          <h5 class="modal-title">Stop Site Totals</h5> 
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">This will stop the site from contributing to totals as of the current date/time.<br /><br />
          Are you sure you wish to continue?
<!--
          <form method="post" class="form add" id="siteconfig-form" data-id="" action="#" role="form" novalidate>
          </form> 
-->
        </div>
        <div class="modal-footer">
          <button type="button" id="stop_siteconfig" class="btn btn-primary" data-bs-dismiss="modal">Yes</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
        </div>
      </div>
    </div>
  </div> <!-- end site config stop -->
</body>
</html>

