<?php
/*
 * File used to return hfrnet data on alfredo
 */
require_once("/var/www/lib/diagnostics/HFRNetwork.php");

parse_str($_SERVER['QUERY_STRING'],$urlargs);
$hfr = new HFRNetwork();

// Return the number of active sites
if( isset( $urlargs['numbersites'] ) ){
  $rows = $hfr->getNumberofActiveSites();
  print($rows[0]["number"]);
  exit;
}

// Return the number of active networks 
if( isset( $urlargs['numbernetworks'] ) ){
  $rows = $hfr->getNumberofActiveNetworks();
  print($rows[0]["number"]);
  exit;
}

// Return a list of stations with their lat lon
if( isset( $urlargs['getstationlatlon'] ) ){
  $rows = $hfr->getStationLatLonList();
  print_json($rows);
  exit;
}


/**
 * Print the results as json
 */
function print_json($data){
  header('Content-Type: application/json');
  print( json_encode($data) );
  exit;
}

?>  
