<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js"></script>
<!--
<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous" />
-->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" />
<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/themes/smoothness/jquery-ui.css" />
<link rel="stylesheet" href="index.css" />
<link rel="stylesheet" href="help.css" />
<title>HFRNet: Help</title>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
      <a class="navbar-brand px-3" href="<?php echo dirname($_SERVER['REQUEST_URI']); ?>">HF-Radar Network</a>
    </div>
  </nav>
  <section><h1>Contents</h1>
    <ul>
      <li><a href="#viewing">Viewing</a>
        <ul>
          <li><a href="#treeview">Tree View</a></li>
          <li><a href="#summaryview">Summary Views</a></li>
          <li><a href="#networkview">Network Views</a></li>
          <li><a href="#stationview">Station View</a></li>
        </ul>
      </li>
      <li><a href="#bookmarking">Bookmarking</a></li>
      <li><a href="#loggingin">Logging in</a></li>
      <li><a href="#outages">Outages</a></li>
      <li><a href="#siteconfig">Site Configuration</a></li>
      <li><a href="#changes">Changes</a></li>
    </ul>
  </section>
  <section><h1 id="viewing" class="anchor">Viewing</h1>
    <article><h2 id="treeview" class="anchor">Tree View</h2>
    The tree view on the left allows the user to navigate between different reports.  Sections of the tree can be expanded/collapsed to show/hide networks and/or stations.  Clicking on the white arrows will expand the tree while clicking on the black arrows will collapse the tree.  
    <figure><img src="img/help/collapsetree.jpg" alt="Collapse or Expand the Tree"><figcaption>Click on the arrows to expand/collapse the tree.</figcaption></figure>
    In addition the tree view can be hidden to maximize the viewing screen by clicking on the <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
</svg>
    <figure><img src="img/help/hidetree.png" alt="Hide or Show the Tree"><figcaption>Click on the <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
</svg> to hide/show the tree</figcaption></figure>
    </article>
 
    <article><h2 id="summaryview" class="anchor">Summary Views</h2>
      Currently there are two summary reports: Association Uptime and Network.  
      To view the Association Uptime report, click on the 'All Stations' text in the tree view. 
      <figure><img src="img/help/hidetree.png" alt="Clicking on 'All Stations'"><figcaption>'All Stations' will open up the Association Uptime report</figcaption></figure>
      Alternatively, clicking on the 'Summary Views' menu option will show a list of summary reports.</article>
      <figure><img src="img/help/summaryview.jpg" alt="Summary Views"><figcaption>Summary Views contain a list of summary reports</figcaption></figure>

    <article><h2 id="networkview" class="anchor">Network Views</h2>
      <ul>
      <li>The network views allow a user to view relevant data on all their sites within their network.</li>
      <li>To view a network's summary page, click on the network name in the tree view.</li>
      <li>Clicking on a station row/column will open up the station view.</li>
      <li>Data within these views can be sorted by clicking on the column headers. </li>
      <li>To tranpose the data, click on 'Transpose Data'</li>
      <li>Variables whose data fall outside of normal thresholds are colored red. </li>
      <li>Highlighted rows/columns indicate the age of the radial files.</li>
      <table class="table table-striped" id='radialage'>
        <tr><th>Row/Column Color</th><th>Age of Radial Files</th></tr>
        <tr><td>White</td><td>Less than 5 hours</td></tr>
        <tr><td>Yellow</td><td>Greater than 5 hours</td></tr>
        <tr><td>Red</td><td>Greater than 10 hours</td></tr>
        <tr><td>Grey</td><td>Greater than 30 days</td></tr>
      </table>
      </ul>
      <figure><img src="img/help/networkview.png" alt="Network View"><figcaption>Default network report showing variables outside of normal thresholds (red) and a station (SDCP) whose radial files are older than 30 days</figcaption></figure>
      <figure><img src="img/help/networkview2.png" alt="Network View Transposed"><figcaption>Network report transposed and sorted by variable</figcaption></figure>
    </article>

    <article><h2 id="stationview" class="anchor">Station View</h2>
      <ul>
      <li>The station view shows plots, maps, and metadata relevant to the station.  It also allows the user to enter and view outages associated with the station.</li>
      <li>Plots within this view default to the last 7 days, although the user can also enter in their own start and end dates.  When the dates are changed, any subsequent station change will default to the selected start and end date.  Thus, if viewing station ABC and the user changes the dates to the last 3 days, clicking on a new station will show the last 3 days instead of the last 7.</li>
      <li>The background color of the page indicates the age of the radial files.  (see Network Views - Age of Radial Files)</li>
      </ul>
      <article><h3>Plots</h3>
        <ul>
          <li>All of the plots can be downloaded by clicking on the <span class="glyphicon glyphicon-menu-hamburger"></span> in the top right of each plot.</li>
          <li>Both ideal and measured are automatically loaded by default in each plot.  To hide/show a particular pattern type, click on the 'Idealized' or 'Measured' text in the legend.</li>
          <li>To zoom into a plot, single click and hold within the plot and drag the mouse to the end of where you wish to zoom into.  To zoom out, click on 'Reset zoom'.</li>
        </ul>  
        <figure><img src="img/help/downloadplot.jpg" alt="Download plot"><figcaption>Options to download the plot</figcaption></figure>
        <figure><img src="img/help/zoomplot.jpg" alt="Zoom plot"><figcaption>Plot zoomed in showing only Idealized pattern type</figcaption></figure>
        <figure><img src="img/help/dates.png" alt="Start and end date"><figcaption>Start date of 11/7/2022 and end date of 11/14/2022.  No more 10 day limitations here.</figcaption></figure>

      </article>
      <article><h3>Radial Coverage</h3>
        <ul>
          <li>Two maps are available, one showing the radial coverage with the most recent file, and the other showing the percent coverage within the last 24 hours.  By default ideal and measured are shown if available.</li>
          <li>To hide/show a pattern type, click on the icon in the top right corner of the map and select the checkbox corresponding to the pattern type.</li>
        </ul>
        <figure><img src="img/help/radialcoverage.jpg" alt="Radial Coverage"><figcaption>The most recent ideal and measured radial coverages</figcaption></figure>
        <figure><img src="img/help/percentcoverage.jpg" alt="Percent Coverage"><figcaption>Percent coverage of the last 24 hours for measured radials</figcaption></figure>
      </article>
    </article>
  
  </section>
  <section><h1 id="bookmarking" class="anchor">Bookmarking</h1>
    Click on 'Bookmark This Page' to create a url that you can use to bookmark your current view.
    The following is a list of items that can be bookmarked:
    <ul>
      <li>The tree view, whether to hide it or show it</li>
      <li>The view (summary, network or station)</li>
      <li>Within the summary view, the type of report</li>
      <li>Within the network view, the network to view and the data transposed or not</li>
      <li>Within the station view, the station to view and the start and end dates</li>
    </ul>
    <figure><img src="img/help/rightnavimenu.jpg" alt="bookmark"><figcaption>Bookmarking your view</figcaption></figure>
  </section>

  <section><h1 id="changes" class="anchor">Changes</h1>
    2016-12-31 - Initial release<br />
    2017-01-30 - Added date resolved and date start and two export buttons (Excel,pdf) for outages. Increased notes size. Fixed tree not working on mobile.<br />
    2017-02-06 - Added csv export button.<br />
    2017-03-29 - Added disk usage.<br />
    2017-12-15 - Fixed outage table - Incorrect date sorts<br />
    2018-11-14 - Fixed metadata not showing latest info between ideal vs measured<br />
    2022-11-10 - Added site config information<br />
    2025-07-09 - Removed Site Configuration and Outages doc<br />
  </section>
</body>
</html>

