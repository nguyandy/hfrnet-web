<?php
/**
 * Utility to parse and a string time and return epoch timestamp.
 *
 * @file strtotime.php
 * @date 2009-12-10 12:09 HST
 * @author Paul Reuter
 * @version 1.0.0
 *
 * @modifications
 *  0.0.1 - 2006-10-11 - Initial beta release
 *  0.2.0 - 2006-11-16 - Initial public release
 *  0.2.1 - 2007-11-06 - isset check on function call
 *  1.0.0 - 2009-12-10 - Rewritten properly.
 */
 
function _strtotime($str,$ref=null) { 
  if( $ref!==null && (string)intVal($ref)!=(string)$ref ) { 
    $ref = _strtotime($ref);
  }

  $str = trim($str);
  if( strtolower($str) === 'latest' || strtolower($str) === 'now' ) { 
    return time();
  }

  return strtotime($str,$ref);
} // END: function _strtotime($str,$ref=null)



function main() { 

  $str = time();
  if( isset($_REQUEST["str"]) ) { 
    $str = $_REQUEST["str"];
  } else if( isset($_REQUEST["txt"]) ) { 
    $str = $_REQUEST["txt"];
  }

  $ref = time();
  if( isset($_REQUEST["ref"]) ) { 
    $ref = $_REQUEST["ref"];
  } else if( isset($_REQUEST["t"]) ) { 
    $ref = $_REQUEST["t"];
  }

  putenv("TZ=UTC");
  header("Content-type: text/plain");
  echo( _strtotime($str,$ref) );

  return true;
} // END: function main()

main();

// EOF -- strtotime.php
?>
