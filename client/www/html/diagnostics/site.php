<?php
/*
Service to retrieve/update site specific things: outages, site configurations
  
Changes:
2022-09-07 - Initial version
2023-06-23 - Added setNetwork to site config 
*/

require_once("/var/www/lib/diagnostics/HFRNetwork.php");
require_once("lib/common.php");

parse_str($_SERVER['QUERY_STRING'],$urlargs);

//$path = parse_url($url, PHP_URL_PATH);
//error_log($_SERVER[PHP_URL_PATH'

$sta = "SDBP";
//TODO if sta is not specified, exit
if( isset( $urlargs['sta'] ) ) { 
  $sta = strtoupper($urlargs['sta']);
}

// Get the list of types of outages
if( isset( $urlargs["outageslist"] ) ){
  $sql = "SELECT * FROM outages ORDER BY outages_id";
  $db = new outagesDB();
  $data = $db->selectQuery($sql);
  printjson($data);
}

// Get the list of tags 
if( isset( $urlargs["tagslist"] ) ){
  $sql = "SELECT * FROM tags ORDER BY tags_id";
  $db = new outagesDB();
  $data = $db->selectQuery($sql);
  printjson($data);
}

// Get the list of data avail options 
if( isset( $urlargs["dataavaillist"] ) ){
  $sql = "SELECT * FROM data_availability ORDER BY data_availability_id";
  $db = new outagesDB();
  $data = $db->selectQuery($sql);
  printjson($data);
}

// Get the list of repair time options 
if( isset( $urlargs["timerepairlist"] ) ){
  $sql = "SELECT * FROM time_to_repair ORDER BY time_to_repair_id";
  $db = new outagesDB();
  $data = $db->selectQuery($sql);
  printjson($data);
}

// Get the list of stations the user has access to 
if( isset( $urlargs['mystations'] ) ){
  if( isset($_SESSION["user"]) ){
    $data = $_SESSION["user"]["editableNetworks"];
    printjson($data);
  }
  else {
    printjson("");
  }
}

// All outages for this station
if( isset( $urlargs['getoutages'] ) ){
  $sql = sprintf("SELECT outr.outage_records_id,outr.date_entered,outr.start_date,o.outages,o.outages_id,outr.notes,ttr.text repairDate,ttr.time_to_repair_id,da.text dataavail,da.data_availability_id,
outr.date_resolved,t.tags,t.t_ids,u.username
FROM 
outage_records outr LEFT JOIN
records_multiple_outages rmo on outr.outage_records_id = rmo.outage_records_id left join
outages o on rmo.outages_id = o.outages_id left join
time_to_repair ttr on outr.time_to_repair_id = ttr.time_to_repair_id left join
data_availability da on outr.data_availability_id = da.data_availability_id left join
hfradar.site s on outr.site_id = s.site_id left join
users u on outr.users_id = u.users_id LEFT JOIN
(SELECT rmt.outage_records_id, group_concat(t.tags_id) t_ids, group_concat(t.text) tags
FROM records_multiple_tags rmt left join
tags t on rmt.tags_id = t.tags_id
group by rmt.outage_records_id) t on outr.outage_records_id = t.outage_records_id
WHERE s.sta='%s'
ORDER BY outr.date_entered", $sta);
  $db = new outagesDB();
  $data = $db->selectQuery($sql);
  printjson($data);
}  

// Site config history
if( isset( $urlargs['getconfighistory'] ) ){
  $db = new RTVProc();
  $db->setSite($urlargs['sta']);
  $data = $db->getHistory();
  printjson($data);

}

// If we're posting something and we're logged in
if( isset( $_POST['id'] ) && isset( $_SESSION['user'] )){

  // Check to see if the user has access to this station/network
  if ( ! checkUserNetworkAccess( $_POST['site'], $_SESSION['user']['editableNetworks']) ){
    exit;
  } 

  // Outages
  // Add,edit, delete an outage
  if( isset( $_POST['job'] ) && $_POST['id'] == "outages" ){
    // Add an outage
    if( $_POST['job'] == "add" ){
      $sql = sprintf('call add_outage(%u,"%s","%s",%d,"%s","%s",%d,%d,%d,%d)',$_POST['date'],$_POST['notes'],$_SESSION['user']['username'],$_POST['time_to_repair'],$_POST['site'],$_POST['tags'],$_POST['dataavail'],intval($_POST['dateResolved'])/1000,$_POST['outage'],intval($_POST['dateStart'])/1000);
    }
    // Edit an outage
    else if( $_POST['job'] == "edit" ){
      $sql = sprintf('call edit_outage(%d,"%s","%s",%d,"%s",%d,%d,%d,%d)',$_POST['outage_records_id'],$_POST['notes'],$_SESSION['user']['username'],$_POST['time_to_repair'],$_POST['tags'],$_POST['dataavail'],intval($_POST['dateResolved'])/1000,$_POST['outage'],intval($_POST['dateStart'])/1000);
    }
    // delete an outage
    else if( $_POST['job'] == "delete" ) {
      $sql = sprintf('call delete_outage(%d)',$_POST['outage_records_id']);
    }
    else {
      echo "false";
      exit;
    }
    $db = new outagesDB();
    $data = $db->updatingQuery($sql);
    if( $data < 0 ) {
      echo "false";
    }
    else {
      echo "true";
    }
  }

  // Site config
  if (isset( $_POST['job'] ) && $_POST['id'] == "config" ){
    $db = new RTVProc();
    $db->setSite($_POST['site']);
    $db->setNetwork($_POST['net']);

    // Add a site config 
    if( $_POST['job'] == "add" ){
      $rs = $db->addNewSiteConfig(gmdate('Y-m-d H:i:s',$_POST['startdate']/1000),$_POST["pattern"],$_POST["radialminute"], false);
      if (! $rs){
        error_log("site.php: Error entering site config");
        echo "false"; 
      }
    }
    // Update a site config (add an end time and then add)
    else if( $_POST['job'] == "update" ){
      //TODO added this error_log message cause hugh is having issues.  Somehow the startdate ends up being 1970-01-01 00:00:00
      error_log("site.php: POST[startdate] is " . $_POST['startdate']/1000);
      $rs = $db->addNewSiteConfig(gmdate('Y-m-d H:i:s',$_POST['startdate']/1000),$_POST["pattern"],$_POST["radialminute"],true);
      if (! $rs){
        error_log("site.php: Error entering site config");
        echo "false"; 
      }
     
    }
    // Stop a site from totals
    else if( $_POST['job'] == "stop" ){
      $rs = $db->stopSiteConfig(gmdate('Y-m-d H:i:s',$_POST['enddate']/1000));
      if (! $rs ) {
        error_log("site.php: Error pausing site from totals");
        echo "false";
      }
    }
    else {
      echo "false";
      exit;
    }
  }
}
