<?php
/**
 * lluv file
 *
 * @file    lluvfile.php
 * @package HFRadar
 * @date    2016-07-15 PDT
 * @author  Joseph Chen
 * @version 1.1
 * 
 * @modifications<pre>
 * 1.0 - Initial
 * 1.1 - Added getPatternType
 *</pre>
 */

/**
 * Extends the Exception class
 */ 
class NotFoundException extends Exception{}

/**
 * Class designed to extract the elements of a lluv file and insert the
 * data into a database
 */
class lluvFile{ 

  private $file;
  private $hardwareDiagnosticsSQLColumns = array( "receiver_chassis_tmp" => "TMP",
                                                  "awg_tmp" => "MTMP",
                                                  "transmit_trip" => "XTRP",
                                                  "awg_run_time" => "RUNT",
                                                  "receiver_supply_p24vdc" => "SP24",
                                                  "receiver_supply_p5vdc" => "SP05",
                                                  "receiver_supply_n5vdc" => "SN05",
                                                  "receiver_supply_p12vdc" => "SP12",
                                                  "xmit_chassis_tmp" => "XPHT",
                                                  "xmit_amp_tmp" => "XAHT",
                                                  "xmit_fwd_pwr" => "XAFW",
                                                  "xmit_ref_pwr" => "XARW",
                                                  "xmit_supply_p28vdc" => "XP28",
                                                  "xmit_supply_p5vdc" => "XP05",
                                                  "gps_receive_mode" => "GRMD",
                                                  "gps_discipline_mode" => "GDMD",
                                                  "npll_unlock" => "PLLL",
                                                  "receiver_hires_tmp" => "HTMP",
                                                  "receiver_humidity" => "HUMI",
                                                  "vdc_draw" => "RBIA",
                                                  "cpu_runtime" => "CRUN");
  private $radialDiagnosticsSQLColumns = array("loop1_amp_calc" => "AMP1",
                                               "loop2_amp_calc" => "AMP2",
                                               "loop1_phase_calc" => "PH13",
                                               "loop2_phase_calc" => "PH23",
                                               "loop1_phase_corr" => "CPH1",
                                               "loop2_phase_corr" => "CPH2",
                                               "loop1_css_noisefloor" => "SNF1",
                                               "loop2_css_noisefloor" => "SNF2",
                                               "mono_css_noisefloor" => "SNF3",
                                               "loop1_css_snr" => "SSN1",
                                               "loop2_css_snr" => "SSN2",
                                               "mono_css_snr" => "SSN3",
                                               "diag_range_cell" => "DGRC",
                                               "valid_doppler_cells" => "DOPV",
                                               "dual_angle_pcnt" => "DDAP",
                                               "rad_vect_count" => "RADV",
                                               "avg_rads_per_range" => "RAPR",
                                               "nrange_proc" => "RARC",
                                               "rad_range" => "RADR",
                                               "max_rad_spd" => "RMCV",
                                               "avg_rad_spd" => "RACV",
                                               "avg_rad_bearing" => "RABA",
                                               "rad_type" => "RTYP",
                                               "spectra_type" => "STYP");
  private $radialMetaSQLColumns = array( #"format"=> "",
                                         "lat" => "Origin",
                                         "lon" => "Origin",
                                         "cfreq" => "TransmitCenterFreqMHz",
                                         "range_res" => "RangeResolutionKMeters",
                                         "ref_bearing" => "ReferenceBearing",
                                         "nrads" => "TableRows",
                                         "dres" => "DopplerResolutionHzPerBin",
                                         "manufacturer" => "Manufacturer",
                                         "xmit_sweep_rate" => "TransmitSweepRateHz",
                                         "xmit_bandwidth" => "TransmitBandwidthKHz",
                                         "max_curr_lim" => "CurrentVelocityLimit",
                                         "min_rad_vect_pts" => "RadialMinimumMergePoints",
                                         "loop1_amp_corr" => "PatternAmplitudeCorrections",
                                         "loop2_amp_corr" => "PatternAmplitudeCorrections",
                                         "loop1_phase_corr" => "PatternPhaseCorrections",
                                         "loop2_phase_corr" => "PatternPhaseCorrections",
                                         "bragg_smooth_pts" => "BraggSmoothingPoints",
                                         "rad_bragg_peak_dropoff" => "RadialBraggPeakDropOff",
                                         "second_order_bragg" => "BraggHasSecondOrder",
                                         "rad_bragg_peak_null" => "RadialBraggPeakNull",
                                         "rad_bragg_noise_thr" => "RadialBraggNoiseThreshold",
                                         "music_param_01" => "RadialMusicParameters",
                                         "music_param_02" => "RadialMusicParameters",
                                         "music_param_03" => "RadialMusicParameters",
                                         "ellip" => "GreatCircle",
                                         #"earth_radius" => "",
                                         "ellip_flatten" => "GreatCircle",
                                         "ctf_ver" => "CTF",
                                         "lluvspec_ver" => "LLUVSpec",
                                         "geod_ver" => "GeodVersion",
                                         "patt_date" => "PatternDate",
                                         "patt_res" => "PatternResolution",
                                         "patt_smooth" => "PatternSmoothing",
                                         "spec_range_cells" => "SpectraRangeCells",
                                         "spec_doppler_cells" => "SpectraDopplerCells",
                                         #"curr_ver" => "",
                                         #"codartools_ver" => "",
                                         "first_order_calc" => "FirstOrderCalc",
                                         "lluv_tblsubtype" => "TableType",
                                         #"proc_by" => "",
                                         "merge_method" => "MergeMethod",
                                         "patt_method" => "PatternMethod",
                                         #"dir" => "",
                                         #"dfile" => "",
                                         #"mtime" => "",
                                         #"sampling_period_hrs" => "",
                                         "nmerge_rads" => "MergedCount",
                                         "range_bin_start" => "RangeStart",
                                         "range_bin_end" => "RangeEnd",
                                         "loop1_amp_calc" => "PatternAmplitudeCalculations",
                                         "loop2_amp_calc" => "PatternAmplitudeCalculations",
                                         "loop1_phase_calc" => "PatternPhaseCalculations",
                                         "loop2_phase_calc" => "PatternPhaseCalculations");

  private $processingToolData = array( "rad_merger_ver" => "RadialMerger",
                                       "spec2rad_ver" => "SpectraToRadial",
                                       "rad_slider_ver" => "RadialSlider",
                                       "rad_archiver_ver" => "RadialArchiver",
                                       "codartools_ver" => "codar_rb2lluv.pl", # not sure if this is correct
                                       "curr_ver" => "Currents",
                                       "proc_time" => "ProcessedTimeStamp");

  function __construct($file){
    if( ! file_exists( $file ) ) {
      throw new NotFoundException();
    } 
    $this->file = $file;
  }

  /**
   * Get the pattern type of the file.  Some WERA (SC) have no pattern types in their file
   * thus return 'i'
   *
   * @return str "i" or "m" 
   */
  public function getPatternType(){
    exec( "awk '/PatternType/{print \$NF}' $this->file", $pattern );

    // Some Wera (SC) have no pattern type in their files
    if( empty( $pattern ) ) return "i";

    switch ($pattern[0]){
      case "Measured":
        return "m";
      case "Idealized":
        return "i";
      default: // for wera
        return "i";
    }
  } // end function getPatternType

  /** 
   * Return the headers for a particular section as an array with the header name being the value 
   * and the key being the index 
   *
   * @param string $line Line containing the header
   *
   * @return array Returns the header name as the value and the key being the index
   */
  private function getHeaders($line){
    $line_split = explode(":",$line);
    //return array_flip(explode( " ", trim($line_split[1]) ));
    return explode( " ", trim($line_split[1]) );
  }

  /**
   * Get the data for a specific table type
   *
   * @param string $start The Start of the table e.g. '%TableType: rads rad1' 
   * @param string $end The end of the table e.g. '%TableEnd: 2'
   * @param int $starttime optional Epoch start time 
   * @param int $endtime optional Epoch end time
   *
   * @return array Associative array with keys being the column type (TIME,AMP1...) and values being the data
   */
  private function getTableData($start,$end,$starttime=null, $endtime=null){
    $data = array();
    $alldata = array();
    $headerfound = false;

    // Use sed to extract the data and put into variable $lines
    exec( "sed -e '/$start/,/$end/!d' $this->file",$lines );
    if( count($lines) == 0 ) return null;

    foreach( $lines as $line ){

      // Header
      $header_pattern = '/TableColumnTypes/';
      if( preg_match( $header_pattern, $line ) ){
        $headers = $this->getHeaders( $line );
        $headerfound = true;
      }
      if( $headerfound == false ) continue;

      // Data
      $line = trim($line,"%");
      $line = trim($line);
      if( count($headers) == count( preg_split( '/\s+/',$line ) ) ) {
        $data = preg_split( '/\s+/', $line );
        // Skip anything that isn't our data
        if( ! is_numeric($data[0]) ) continue;

        $data = array_combine( $headers, $data );

        // Get the date only for radial diagnostics and hardware diag.  lluv data doesn't have dates
        if( isset( $data["THRS"] ) ){
          $date = gmmktime( intval($data["THRS"]), intval($data["TMIN"]),intval($data["TSEC"]),intval($data["TMON"]),intval($data["TDAY"]),intval($data["TYRS"]) );
          if( $starttime <> NULL ){
            if( $date < $starttime ) continue;
          }
          if( $endtime <> NULL ){
            if( $date > $endtime ) continue;
          }
        } // end if (isset( $data["THRS"] ) )

        $alldata[] = $data;
      }
    } // End foreach ( $lines as $line )

    return $alldata;
  } // End function getTableData

  /**
   * Gets the data from the 'rads rad1' table.  
   *
   * @param int $starttime Optional Epoch start time
   * @param int $endtime Optional Epoch end time
   * 
   * @return array Associative array with keys being the column type (TIME, AMP1...) and values being the data 
   */
  public function getRadialDiagnostics($starttime=null, $endtime=null){
    return $this->getTableData("%TableType: rads rad1","%TableEnd: 2",$starttime, $endtime);
  }

  /**
   * Gets the data from the 'rcvr rcv2' table.  
   *
   * @param int $starttime Optional Epoch start time
   * @param int $endtime Optional Epoch end time
   * 
   * @return array Associative array with keys being the column type (TIME, AMP1...) and values being the data 
   */
  public function getHardwareDiagnostics($starttime=null,$endtime=null){
    return $this->getTableData("%TableType: rcvr rcv2","%TableEnd: 3",$starttime, $endtime);
  }
  /**
   * Gets the data from the 'rads rad1' table converting the column type (TIME, AMP1) to database value (time,...)
   *
   * @param int $starttime Optional Epoch start time
   * @param int $endtime Optional Epoch end time
   * 
   * @return array Associative array with keys being the column type and values being the data 
   */
  public function getHardwareDiagnosticsSQL($starttime=null,$endtime=null){
    return $this->convertMetaToSQL($this->getHardwareDiagnostics( $starttime,$endtime ),$this->hardwareDiagnosticsSQLColumns);
  }

  /**
   * Gets the data from the 'LLUV RDL' table.  
   *
   * @return array Associative array with keys being the column type (TIME, AMP1...) and values being the data 
   */
  public function getLLUVData(){
    return $this->getTableData("%TableType: LLUV RDL","%TableEnd:");
  }
  
  /**
   * Gets the data containing text with 'Process', generally the end of the file.  
   *
   * @return array Associative array with keys being the column type (TIME, AMP1...) and values being the data 
   */
  public function getProcessInfo(){
    $data = array();

    exec( "grep Process $this->file",$lines );
    if( count( $lines ) == 0 ) return $data;
    
    foreach( $lines as $line ){
      if( strpos( $line, "ProcessedTimeStamp" ) ){
        $arr = preg_split( "/\s+/",$line );
        $data[ "ProcessedTimeStamp" ] = gmmktime( $arr[4],$arr[5],$arr[6],$arr[2],$arr[3],$arr[1] );
      }
      else {
        $line = str_replace( "%ProcessingTool: ", "", $line );    
        $line = str_replace( "\"","",$line );
        $line_array = explode( " ", trim($line) );
        $data[ $line_array[0] ] = $line_array[1];
      }
    }
    
    #$alldata = array_combine($this->processingToolData,$alldata); 
    return $data; 
  }
  /**
   * Gets the data containing text with 'Process', generally the end of the file, with the columns mapped to SQL columns
   *
   * @return array Associative array with keys being the column type (TIME, AMP1...) and values being the data 
   */
  public function getProcessInfoSQL(){
    return $this->convertMetaToSQL($this->getProcessInfo(), $this->processingToolData);
  }
  
  /**
   * Gets the meta data   
   *
   * @return array Associative array with keys being the column type (TIME, AMP1...) and values being the data 
   */
  public function getRadialMeta(){
    $data = array();
    $start = "%CTF";
    $end = "%TableRows";

    exec( "sed -e '/$start/,/$end/!d' $this->file",$lines );
    if( count( $lines ) == 0 )  return null;

    foreach( $lines as $line ){
      $line = trim( $line, "%" );
      $line = explode( ":",$line,2 );
      $data[ $line[0] ] = trim($line[1]);
    } // end foreach( $lines as line )

    return $data;
  } // End public function getRadialMeta

  /**
   * Gets the meta data returned as their sql column name
   *
   * @return array Associative array with keys being the column type (TIME, AMP1...) and values being the data 
   */
  public function getRadialMetaSQL(){
    $data = $this->getRadialMeta();
    $dataSQL = array();
    foreach( $data as $key=>$value ){
      $arr = preg_split( "/\s+/", $value );
      //TODO: mising radialmeta.format,patterntype
      //             radialfile.mtime,format,patterntype

      switch ($key){
        case "RangeResolutionMeters":
          $dataSQL["range_res"] = $arr[0] /1000;
          break;
        case "LLUVSpec":
          $dataSQL["lluvspec_ver"] = $arr[0];
          break;
        case "Origin":
          $dataSQL["lat"] = $arr[0];
          $dataSQL["lon"] = $arr[1];
          break;
        case "GreatCircle":
          $dataSQL["ellip"] = trim($arr[0],"\"");
          $dataSQL["ellip_flatten"] = $arr[2];
          break;
        case "GeodVersion":
          // some of the double quoted values have spaces in between, this keeps the double
          // quote value together
          preg_match_all('/"(?:\\\\.|[^\\\\"])*"|\S+/',$value,$arr);      
          $dataSQL["geod_ver"] = trim($arr[0][0],"\"")." ".$arr[0][1];
          break;
        case "ReferenceBearing":
          $dataSQL["ref_bearing"] = $arr[0];
          break;
        case "PatternDate":
          $dataSQL["patt_date"] = gmmktime($arr[3],$arr[4],$arr[5],$arr[1],$arr[2],$arr[0]);
          break;
        case "PatternResolution":
          $dataSQL["patt_res"] = $arr[0];
          break;
        case "PatternSmoothing":
          $dataSQL["patt_smooth"] = $arr[0];
          break;
        case "PatternAmplitudeCorrections":
          $dataSQL["loop1_amp_corr"] = $arr[0];
          $dataSQL["loop2_amp_corr"] = $arr[1];
          break;
        case "PatternPhaseCorrections":
          $dataSQL["loop1_phase_corr"] = $arr[0];
          $dataSQL["loop2_phase_corr"] = $arr[1];
          break;
        case "PatternAmplitudeCalculations":
          $dataSQL["loop1_amp_calc"] = $arr[0];
          $dataSQL["loop2_amp_calc"] = $arr[1];
          break;
        case "PatternPhaseCalculations":
          $dataSQL["loop1_phase_calc"] = $arr[0];
          $dataSQL["loop2_phase_calc"] = $arr[1];
          break;
        case "RadialMusicParameters":
          $dataSQL["music_param_01"] = $arr[0];
          $dataSQL["music_param_02"] = $arr[1];
          $dataSQL["music_param_03"] = $arr[2];
          break;
        case "MergeMethod":
          $dataSQL["merge_method"] = $arr[0];
          break;
        case "TableType":
          $dataSQL["lluv_tblsubtype"] = $arr[1];
          break;
        case "TimeStamp":
          $dataSQL["time"] = gmmktime($arr[3],$arr[4],$arr[5],$arr[1],$arr[2],$arr[0]);
          $dataSQL["endtime"] = gmmktime($arr[3],$arr[4],$arr[5],$arr[1],$arr[2],$arr[0]);
          break;
        case "TimeCoverage":
          $dataSQL["sampling_period_hrs"] = $arr[0]/60;
          break;
        case "PatternMethod":
          $dataSQL["patt_method"] = $arr[0];
          break;
        default:
          if( $sqlKey = array_search( $key, $this->radialMetaSQLColumns ) ){
            $dataSQL[$sqlKey] = $value;
          }
          else {
            $dataSQL[$key] = $value;
          }
      } // End switch( $key);
    } // end foreach( $data as $key => $value )
    return $dataSQL;
  } // End function getRadialMetaSQL

  /**
   * Combines the data from radial meta and process info
   *
   * @return array Associative array with keys being the column type (TIME, AMP1...) and values being the data 
   */
  public function getRadialFileMeta(){
    $data = array_merge($this->getRadialMetaSQL(), $this->getProcessInfoSQL());
    return $data;
  } // End function getRadialFileMeta

  /**
   * Converts variables from the lluv files to their respective sql variable name
   *
   * @param array string $data array containing the lluv variables
   * @param array string $sqlColumns Array containing the mapped sql columns
   * @return array Associative array with keys being the column type (TIME, AMP1...) and values being the data 
   */
  public function convertMetaToSQL($data,$sqlColumns){
    if ($data == null) return array();
    $keys = array_keys( $data ); 
    foreach ($sqlColumns as $key=>$value) {
      if( ! array_key_exists($value, $data ) ) continue;
      $keys[ array_search( $value, $keys ) ] = $key;
        
    }
    return array_combine( $keys,$data ); 
  }
}
?>
