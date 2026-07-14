<?php
 /**
  * Generate a KML document that displays vector icons for lluv.
  *
  * @file vectorKml2.php
  * @date 2022-07-29 16:50 PDT
  * @author Paul Reuter/Joseph Chen
  * @verison 2.0.1
  *
  * @modifications
  * 1.0.0 - 2010-05-06 - Created in a rush
  * 1.0.1 - 2010-07-29 - Add: AKNS in anticipation.
  * 1.0.2 - 2020-06-26 - Changed next_record to get_results
  * 2.0.0 - 2021-12-14 - Use netcdf instead of db and asc files
  * 2.0.1 - 2022-07-29 - Remove references to projects/mapping
  */

$regions = array('AKNS','GAK','PRVI','USEGC','USHI','USWC');
//TODO Maybe it would be better if the bbox default was in a config file
$bbox_default = array(80,-40,15,-179 ); // bounds: north, east, south, west
$ROOT_DIR = "/exports/hfradar/hfrnet/hfrtv";

function isInt($val) { 
  return ((string)intVal($val)===(string)$val);
}
function isNum($val) { 
  return ((string)floatVal($val)===(string)$val);
}

function getRange() { 
  $rng = array(0,50);
  if( isset($_REQUEST["rng"]) ) { 
    $rng = explode(",",$_REQUEST["rng"],2);
  }
  $rng[0] = (isInt($rng[0])) ? max(0,$rng[0]) : 0;
  $rng[1] = (isInt($rng[1])) ? min(250,$rng[1]) : 50;
  return $rng;
} // END: function getRange()


function getPrefix() { 
  $pfx = "a";
  if( isset($_REQUEST["pfx"]) ) { 
    $pfx = trim($_REQUEST["pfx"]);
  }
  if( !in_array($pfx,array("a","h")) ) { 
    $pfx = "a";
  }
  return $pfx;
} // END: function getPrefix()

function getBounds() { 
  // bounds: north, east, south, west
  global $bbox_default;
  if( isset($_REQUEST["bbox"]) ) { 
    $bbox = explode(",",$_REQUEST["bbox"]);
    if( count($bbox) === 4 ) { 
      for($i=0; $i<4; $i++) { 
        if( !isNum(trim($bbox[$i])) ) { 
          return $bbox_default;
        }
      }
      return $bbox;
    }
  }
  return $bbox_default;
} // END: function getBounds()

function getResolution() { 
  $res = "6km";
  if( isset($_REQUEST["res"]) ) { 
    $res = trim($_REQUEST["res"]);
  }
  if( !in_array($res,array("6km","2km","1km","500m")) ) { 
    $res = "6km";
  }
  return $res;
} // END: function getResolution()


function getTimestamp($res,$pfx) { 
  $ts = null;
  if( isset($_REQUEST["ts"]) && isInt($_REQUEST["ts"]) ) { 
    return intVal($_REQUEST["ts"]);
  }
  return getLatestTimestamp($res,$pfx);
} // END: function getTimestamp($res,$pfx)

function getColorScheme() { 
  $cs = (isset($_REQUEST["cs"])) ? strtoupper(trim($_REQUEST["cs"])) : "ROGB";
  $allowed = array("BLUE_BIAS","BLUE_RED","BURR","COLD","GREY","HEAT","HEATMAP","JET","JUICE","OCEAN","ROGB","STOPLIGHT","THERMAL");
  return (in_array($cs,$allowed)) ? $cs : "ROGB";
} // END: function getColorScheme()


function getProcessFromPrefix($pfx) { 
  return ($pfx=='a') ? '25hr Avg' : 'Hourly';
}

function getLLUVVectors($res,$pfx,$ts,$bbox){
  $vects = array();
  $url = sprintf('https://hfrnet.ucsd.edu/map/?callback=eqfeed_callback&lat1=%s&lon1=%s&lat2=%s&lon2=%s&time=%s&prod=%s', $bbox[0],$bbox[3],$bbox[2],$bbox[1],$ts,$pfx.'_'.$res);

  $json = file_get_contents($url);
  // Remove the eqfeed_callback function in the beginning and the last charater 
  $json = substr($json,strlen('eqfeed_callback('));
  $json = substr($json, 0, -1);
  $json = str_replace("'",'"',$json); // Replace single quotes with double quotes otherwise json_decode won't work
  $jo = json_decode($json);
  //print_r($jo);
  foreach( $jo->features as $value ){
    $latlon = $value->geometry->coordinates;
    $line = sprintf("%s,%s,%s,%s",$latlon[1], $latlon[0],$value->properties->u, $value->properties->v);
    $vects[] = explode(",",$line); 
  }
  return $vects;
}

function getLatestTimestamp($res,$pfx) { 
  $time = gmdate("Y_m");
  $lastfiles = array();
  global $regions;
  global $ROOT_DIR;

  foreach( $regions as $region ){
    if ($pfx == "h"){
      $file = sprintf("*_%s_*uwls_SIO.nc", $res);
    }
    else {
      $file = sprintf("*_%s_*uwls_25hr_average_SIO.nc", $res);
    }

    $dir = sprintf("%s/%s/%s/NetCDF/%s", $ROOT_DIR, $region, $time, $file);
    $foundfiles = glob($dir);

    array_push($lastfiles, basename(array_pop( $foundfiles )));
  }

  sort($lastfiles);
  $lastfile = array_pop($lastfiles);
  // Get datetime from lastfile
  $regex = "/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})/";
  preg_match($regex,$lastfile,$dt);

  $latest = gmmktime($dt[4],$dt[5],0,$dt[2],$dt[3],$dt[1]);
  return $latest;
}


function emitKmlHeader($res,$pfx,$ts) { 
  header("Content-Type: application/vnd.google-earth.kml+xml");
  header("Content-Disposition: inline; filename=hfrnet.kml");

  if( isset($_SERVER['HTTP_ACCEPT_ENCODING'])
  &&  substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') ) {
    ob_start("ob_gzhandler");
  } else {
    ob_start();
  }

  echo(
    '<'.'?xml version="1.0" encoding="UTF-8"?'.">\n".
    '<kml xmlns="http://www.opengis.net/kml/2.2" '.
      'xmlns:gx="http://www.google.com/kml/ext/2.2" '.
      'xmlns:kml="http://www.opengis.net/kml/2.2" '.
      'xmlns:atom="http://www.w3.org/2005/Atom">'."\n".
    '<Folder><name>'.gmdate("Y-m-d H:i",$ts).' UTC, '.
    $res.' '.getProcessFromPrefix($pfx)."</name>\n".
    '<Style><ListStyle><listItemType>checkHideChildren</listItemType>'.
      '<bgColor>00ffffff</bgColor><maxSnippetLines>2</maxSnippetLines>'.
    '</ListStyle></Style>'
  );
} // END: function emitKmlHeader()


function emitKmlVector($vect,$ts,$lo,$hi,$cs="ROGB") { 
  list($lat,$lon,$u,$v) = $vect;
  $u = $u*100;
  $v = $v*100;
  $m = round(sqrt($u*$u + $v*$v));
  $d = atan2($v,$u);
  $h = fmod(450-rad2deg($d),360);
  $t = gmdate("Y-m-d\TH:i:s\Z",$ts);
  $dr = ($hi==$lo) ? 0.001 : $hi-$lo;
  $s = max(0.15, min(1.00, 0.15 + ($m-$lo)/($dr) * 0.85 ));
  $i = max(0,min(250, round(($m-$lo)/($dr)*250) ));

  echo('
<Placemark>
  <name>'.$m.' cm/s</name>
  <StyleMap>
    <Pair>
      <key>normal</key>
      <Style>
        <IconStyle>
          <color>ffffffff</color>
          <scale>'.$s.'</scale>
          <heading>'.$h.'</heading>
          <Icon>
            <href>http://cordc.ucsd.edu/projects/hfrnet/img/ico/'.$cs.'/arrow_'.$i.'.png</href>
          </Icon>
        </IconStyle>
        <LabelStyle>
          <scale>0</scale>
        </LabelStyle>
      </Style>
    </Pair>
    <Pair>
      <key>highlight</key>
      <Style>
        <IconStyle>
          <scale>'.$s.'</scale>
          <heading>'.$h.'</heading>
          <Icon>
            <href>http://cordc.ucsd.edu/projects/hfrnet/img/ico/'.$cs.'/arrow_'.$i.'.png</href>
          </Icon>
        </IconStyle>
        <LabelStyle>
          <scale>1</scale>
        </LabelStyle>
      </Style>
    </Pair>
  </StyleMap>
  <Point>
    <coordinates>'.$lon.','.$lat.',0</coordinates>
  </Point>
</Placemark>
');
} // END: emitKmlVector($v,$ts,$lo,$hi)


function emitKmlFooter() { 
  echo('</Folder></kml>');
  ob_end_flush();
} // END: function emitKmlFooter()


function main() { 
  $res = getResolution();
  $pfx = getPrefix();
  $bbox = getBounds();
  list($lo,$hi) = getRange();
  $ts  = getTimestamp($res,$pfx);
  //$vect = getLLUVVectors($res,$pfx,$ts);
  $vect = getLLUVVectors($res,$pfx,$ts,$bbox);
  $cs = getColorScheme();
  emitKmlHeader($res,$pfx,$ts);
  foreach($vect as $v) { 
    emitKmlVector($v,$ts,$lo,$hi,$cs);
  }
  emitKmlFooter();
} // END: function main()


main();
// EOF -- vectorKml.php
?>
