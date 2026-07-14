<?php
require_once("/var/www/lib/diagnostics/HFRNetwork.php");

parse_str($_SERVER['QUERY_STRING'],$urlargs);

$hfr = new HFRNetwork();
//TODO Move the hfrMetrics db from hfrnet to somewhere else?
#$hfrMets = new HFRMetrics();

if( count($urlargs) == 0 ) {
  $data = $hfr->getNumberOfSitesByAffiliation(true);
  printjson($data);
}

if( isset( $urlargs["net"] ) ) $hfr->setNetwork($urlargs["net"]);
if( isset( $urlargs["sta"] ) ) $hfr->setSite($urlargs["sta"]);
if( isset( $urlargs["starttime"] ) ) $hfr->setStartTime($urlargs["starttime"]);
if( isset( $urlargs["endtime"] ) ) $hfr->setEndTime($urlargs["endtime"]);

/*
if( isset($urlargs["getnetwork"]) ){
  $data = $hfr->getStationNetwork();
  print( json_encode($data) );
}
*/
  
if( isset($urlargs["metric-fy"]) ){
  //TODO: year needs to be a variable
  $data = $hfrMets->getMetricUptime(2016);
  printjson($data);
}
if( isset($urlargs["allsites"]) ) {
  $data = $hfr->getTreeViewData();
  printjson($data);
}

if( isset($urlargs["lastdata"]) ){
  $data = $hfr->getLastData();
  printjson($data);
}

if( isset($urlargs["latestRadialFiles"]) ){
  $data = $hfr->getLatestRadialFiles();
  printjson($data);
}

//TODO: Not sure what this is for.  Combines ideal/measured data (dfile, nrads) into one variable instead of having two sets of data with the same info
if( isset($urlargs["lastdata2"]) ){

  $datas = $hfr->getLastData();

  # Combine rows based on station id.
  $newdata = array();
  # array containing station ids as key and array index for $newdata as value
  $stationsfound = array();

  foreach( $datas as $data ){
    # check to see if the sta id exists 
    # If it exists, combine some of the rows
    if( array_key_exists($data["sta"], $stationsfound) ){
      $index = $stationsfound[$data["sta"]];
      $pattern = $data["patterntype"];

      if( $pattern = "i" ){
        $newdata[$index]["dfile"] = $data["dfile"] . " / " . $newdata[$index]["dfile"];
        $newdata[$index]["nrads"] = $data["nrads"] . " / " . $newdata[$index]["nrads"];
      }
      else {
        $newdata[$index]["dfile"] = $newdata[$index]["dfile"] . " / " . $data["dfile"];
        $newdata[$index]["nrads"] = $newdata[$index]["nrads"] . " / " . $data["nrads"];
      }
    }
    # it doesn't exist
    else {
      $newdata[] = $data;
      $stationsfound[$data["sta"]] = count($newdata) - 1;
    }
  }
  print_r($newdata);
}

/*
 * Get Receiver Temperature 
 */
if( isset( $urlargs["receivertemp"] ) ){
  $data = $hfr->getReceiverTemperature();
  printjson($data);
}
/*
 * Get Transmission Reflected Power 
 */
if( isset( $urlargs["txreflectedpower"] ) ){
  $data = $hfr->getTXReflectedPower();
  printjson($data);
}
/*
 * Get Transmission Forward Power 
 */
if( isset( $urlargs["txforwardpower"] ) ){
  $data = $hfr->getTXForwardPower();
  printjson($data);
}
/*
 * Get AWG Temperature
 */
if( isset( $urlargs["awgtemp"] ) ){
  $data = $hfr->getAWGTemperature();
  printjson($data);
}

/*
 * Get database latency
 */
if( isset( $urlargs["dbLatency"] ) ){
  $data = $hfr->getDatabaseLatency();
  printjson($data);
}

/*
 * get radial range data for plots
 */
if( isset( $urlargs["rad_range"] ) ){
  $data = $hfr->getRadialRange();
  printjson($data);
}

/*
 * get number of solutions data for plots
 */
if( isset( $urlargs["number_solutions"] ) ){
  $data = $hfr->getNumberSolutions();
  printjson($data);
}

/*
 * get hardware diagnostics data
 */
if( isset( $urlargs["latest_hardware_diag"] ) ){
  $data = $hfr->getLatestHardwareDiagnostics();
  printjson($data);
}

/*
 * get radial diagnostics data
 */
if( isset( $urlargs["latest_radial_diag"] ) ){
  $data = $hfr->getLatestRadialDiagnostics();
  printjson($data);
}
  
/*
 * get a summary list of all stations
 */
if( isset( $urlargs["summarylist"] ) ) {
  $data = $hfr->getStationListSummary();
  printjson($data);
}

/*
 * get network summary from hardwarediag
 * TODO make sure network is selected, otherwise return an error message
 */
if( isset( $urlargs["networksummary"] ) ) {
  $data = $hfr->getNetworkSummary();
  printjson($data);
}
/*
 * Get network summary from hardwarediag
 * data is transposed with each column representing a station and each row 
 * representing a variable (transmit_trip)
 */
if( isset( $urlargs["networksummary2"] ) ) {
  $datas = $hfr->getNetworkSummary();
  $transposed = array();
  $singleVariableData = array();
  foreach( $datas[0] as $key => $value ){
    // Exclude this stuff from the table
    if( $key=="sta" || $key=="staname" || $key=="net" || $key=="site_id" || $key=="lat" || $key=="lon" ) continue;
    $singleVariableData["variable"] = $key;
    foreach( $datas as $data ){
      $singleVariableData[$data["sta"]] = $data[$key];
    }
    array_push($transposed, $singleVariableData);
  }
  printjson($transposed);

}

if( isset( $urlargs['networksitegrowth'] ) ){
  // Load precalculated baseline data
  $baselineFile = '/var/www/lib/diagnostics/network-site-growth-baseline.json';
  $baseline = json_decode(file_get_contents($baselineFile), true);
  
  // Get the last date from baseline (e.g., "2025-06")
  $lastBaselineDate = end($baseline)['date'];
  
  // Query only for data after the baseline
  $newData = $hfr->getNetworkSiteGrowthAfterDate($lastBaselineDate);
  
  // Merge baseline with new data
  if (!is_array($newData)) $newData = array();
  $datas = array_merge($baseline, $newData);

  // Extend chart to current month if last entry is in the past
  $currentMonth = date('Y-m');
  $lastDate = end($datas)['date'];
  if ($lastDate < $currentMonth) {
    $datas[] = array('date' => $currentMonth, 'US' => '0', 'Inter' => '0');
  }

  printjson($datas);
}

if( isset( $urlargs['diskusage'] ) ){
  $datas = $hfr->getDiskUsage();
  printjson($datas);
}

function printjson($data){
  // header('Content-Type: application/json');
  print( json_encode($data) );
  exit;
}
?>
