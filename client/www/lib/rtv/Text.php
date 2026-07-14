<?php
 /**
  * Package for text functions.  These functions are intended to be
  * executed statically, without class instantiation.
  *
  * @file Text.php
  * @date 2009-12-11 11:12 HST
  * @author Paul Reuter
  * @version 1.0.4
  *
  * @modifications
  * 1.0.0 - 2009-07-14 - Created
  * 1.0.1 - 2009-10-08 - Add: split_words($str,$breaks="_ ")
  * 1.0.2 - 2009-11-06 - Add: floatVal($str) for parsing exponentials.
  * 1.0.3 - 2009-11-06 - Add: strtotime($str,$ref=null,$tz=null).
  * 1.0.4 - 2009-12-11 - BugFix: strtotime($str) int vs intVal vs (string)int
  */



/**
 * Text-alteration additions can be placed into this class.  
 * This class should only contain static methods.
 * No refereces to "this" should be found within.
 *
 * @package Utilities
 * @subpackage Text
 */
class Text { 

  var $LOWER_CASE = 0;
  var $UPPER_CASE = 1;
  var $DIGIT = 2;
  var $OTHER = 4;


  /**
   * Parse a string representing a float value that may or may not be of 
   * the form: -1.006e-19, return a float value.
   *
   * @param string $str A string containing a float representation.
   * @return float The value represented by $str.
   */
  function floatVal($str) { 
    $expPattern = '/([-+]?)([0-9]*\.?[0-9]+)e([-+]?)([0-9]*)/';
    if( !preg_match($expPattern,$str,$pts) ) {
      return floatVal($str);
    }

    // Store base part of number
    $num = floatVal($pts[2]);
    $num = ($pts[1] == '-') ? (0-$num) : $num;

    // Store exponent part of number
    $expt = ($pts[4] != "") ? intVal($pts[4]) : 0;
    $expt = ($pts[3] == '-') ? (0-$expt) : $expt;

    return $num*pow(10,$expt);
  } // END: function floatVal($str)


  /**
   * Parse a string representation of a date/time.  If $ref is set, uses
   * this date as the reference for relative $str date computations, such
   * as $str="-3 days" from $ref.  If $tz is set, attempts to specify the
   * timezone in which the date occurred.  "2009-01-01 00:00:00" happened
   * in London 8 hours before it happened in Los Angeles.  Which timestamp
   * is returned depends on if $tz="America/Los_Angeles" or otherwise.
   *
   * @param string $str A date/time represented in string form.
   * @param string|int $ref A relative date/time, optionally string or int.
   * @param string $tz The timezone to compute dates for.  If null, will not
   *   attempt to override (faster), and will use TZ environment variable,
   *   so set it before-hand for optimum speed on multiple calls to strtotime.
   * @return int An epoch timestamp representing the date/time in $str.
   */
  function strtotime($str,$ref=null,$tz=null) { 
    if( (string)$str === (string)intVal($str) ) { 
      return intVal($str);
    }
    if( !$ref ) { 
      $ref = time();
    }
    if( !is_numeric($ref) ) { 
      $ref = Text::strtotime($ref,null,$tz);
    }
    if( $tz==null ) { 
      return strtotime($str,$ref);
    }
    if( method_exists('date_default_timezone_get') ) { 
      $tzold = date_default_timezone_get();
      date_default_timezone_set($tz);
      $val = strtotime($str,$ref);
      date_default_timezone_set($tzold);
      return $val;
    }
    $tzold = getenv("TZ");
    $val = strtotime($str,$ref);
    putenv("TZ=$tzold");
    return $val;
  } // END: function strtotime($str,$ref=null,$tz=null)


  /**
   * Truncating to a fixed number of lines
   *
   * Original PHP code by Chirp Internet: www.chirp.com.au 
   */
  function textwrap($input, $chars, $nl="\n", $lines = false) {
    //  the simple case - return wrapped words 
    if(!$lines) return wordwrap($input, $chars, $nl);

    //  truncate to maximum possible number of characters 
    $retval = substr($input, 0, $chars * $lines); 

    // apply wrapping and return first $lines lines
    $retval = wordwrap($retval, $chars, "\n");
    preg_match("/(.+\n?){0,$lines}/", $retval, $regs);
    return $regs[0];
  } // END: function textwrap($input, $chars, $lines=false)


  /**
   * Splitting text evenly into columns
   *
   * Original PHP code by Chirp Internet: www.chirp.com.au 
   */
  function multiCol($string, $numcols) {
    $collength = ceil(strlen($string) / $numcols) + 3;
    $retval = explode("\n", wordwrap(strrev($string), $collength));
    if(count($retval) > $numcols) {
      $retval[$numcols-1] .= " " . $retval[$numcols];
      unset($retval[$numcols]);
    }
    $retval = array_map("strrev", $retval);
    return array_reverse($retval);
  } // END: function multiCol($string,$numcols)


  /**
   * Stop processing the string after $limit has been reached, but
   * wait until the next $break point is encountered.
   * 
   * Original PHP code by Chirp Internet: www.chirp.com.au
   */
  function truncate_about($string, $limit, $break=".", $pad="...") {
    // return with no change if string is shorter than $limit  
    if (strlen($string) <= $limit) {
      return $string;
    }
    // is $break present between $limit and the end of the string?
    if (false !== ($breakpoint = strpos($string, $break, $limit))) {
      if($breakpoint < strlen($string) - 1) {
        $string = substr($string, 0, $breakpoint) . $pad;
      }
    }
    return $string;
  } // END: function truncate_about($string,$limit,$break=".",$pad="...")


  /**
   * Truncate a string to be absolutely less than $limit.
   *
   * Original PHP code by Chirp Internet: www.chirp.com.au 
   */
  function truncate($string, $limit, $break=" ", $pad="...") {
    // return with no change if string is shorter than $limit
    if(strlen($string) <= $limit) {
      return $string;
    }
    $string = substr($string, 0, $limit);
    if(false !== ($breakpoint = strrpos($string, $break))) {
      $string = substr($string, 0, $breakpoint);
    }
    return $string . $pad;
  } // END: function truncate($string,$limit,$break=' ',$pad='...')



  /**
   * Takes a CamelCase string and splits it into separate words.
   *
   * @access public
   * @param string $str A string to break.
   * @param string $breaks A string of characters demarkating word breaks.
   * @return array A list of words.
   */
  function split_words($str,$breaks="_ ") { 
    $words = array();

    // Create an array of word boundary characters
    // from the $breaks string.
    $bkr = array();
    for($i=0,$n=strlen($breaks);$i<$n;$i++) { 
      array_push($bkr,$breaks[$i]);
    }

    // split string into usual words,
    // all $breaks characters are word boundaries
    $str = str_replace($bkr," ",$str);
    $parts = preg_split("/\W+/",$str,-1,PREG_SPLIT_NO_EMPTY);

    // Process each regular word into special meaning words
    foreach($parts as $part) { 
      $part = trim($part);
      if(strlen($part)<=0) { 
        // Skip empty words
        continue;
      }

      $pc = $this->m_sw_class($part[0]); // previous char class
      $frag = $part[0];          // current fragment

      for($i=1,$n=strlen($part);$i<$n;$i++) { 
        $cc = $this->m_sw_class($part[$i]); // current char class

        if($pc==$this->UPPER_CASE && $cc==$this->LOWER_CASE) { 
          if(strlen($frag)>1) { 
            // IAmSam ==> I,AmSam => I,Am,Sam
            $tmp = substr($frag,0,-1); // all but last character stored so far
            $frag = substr($frag,-1);  // only last character
            array_push($words,$tmp);   // Store the complete word
          }
        } else if($pc!=$cc) { 
          // character class changed and not word-type transition
          // iPod => i,Pod
          // sea4you => sea,4you => sea,4,you
          array_push($words,$frag);
          $frag="";
        }
        $frag = $frag.$part[$i];  // Append current character
        $pc = $cc; // Store current class as previous
      }
      array_push($words,$frag);
    }
    return $words;
  } // END: function split_words($str,$breaks="_ ")



  /**
   * Aux function to determine character class for a given character.
   *
   * @private
   * @param char $ch The character to classify.
   * @return enum The character class.
   */
  function m_sw_class($ch) { 
    if($ch>='a' && $ch<='z') {
      return $this->LOWER_CASE;
    }
    if($ch>='A' && $ch<='Z') { 
      return $this->UPPER_CASE;
    }
    if($ch>='0' && $ch<='9') { 
      return $this->DIGIT;
    }
    if(is_numeric($ch)) { 
      return $this->DIGIT;
    }
    return $this->OTHER;
  } // function m_sw_class($ch)


} // END: class Text

// EOF -- Text.php
?>
