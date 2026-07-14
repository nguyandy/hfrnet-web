<?php
 /**
  * PFFile Parser. Parses rtexec.pf (built to parse).
  *
  * @file PFFile.php
  * @date 2010-02-05 17:38 HST
  * @author Paul Reuter
  * @version 1.0.0
  *
  * @modifications
  * 1.0.0 - 2010-02-05 - Created as skeleton
  */

/**
 * A .pf file parser.
 *
 * @package Antelope
 * @subpackage PFFile
 */
class PFFile {

  /**
   * @var string $fpath A path to a .pf file.
   * @access protected
   */
  var $fpath;

  /**
   * @var string $error Tracks the last error encountered.
   * @access public
   */
  var $error;

  /**
   * @var hash $m_content Keyed hash of parsed contents.
   * @access private
   */
  var $m_content;

  /**
   * Constructor for a new PFFile.
   *
   * @access public
   * @return PFFile A new instance.
   */
  function PFFile($fpath) { 
    $this->fpath = $fpath;
    return $this;
  } // END: constructor PFFile($fpath)


  /**
   * Get a full, parsed structure of a pf file.
   *
   * @access protected
   * @return array A structured hash of the pf file's contents.
   */
  function get() { 
    if( !$this->parse() ) { 
      return false;
    }
    return $this->m_content;
  } // END: function get()


  /**
   * Get an item from the Defines block of a pf file.
   *
   * @access public
   * @param string $key The item to return. Null returns a hash of items.
   * @return mixed The value of $key or a hash of key=>values.
   */
  function getDefines($key=null) { 
    if( !$this->parse() ) { 
      return false;
    }
    if( $key === null ) { 
      return $this->m_content['Defines'];
    }
    if( isset( $this->m_content['Defines'][$key] )  ) { 
      return $this->m_content['Defines'][$key];
    }
    return null;
  } // END: function getDefines($key=null)


  /**
   * Parse the pf file.
   *
   * @access private
   * @param bool $reset If true, will force a reparse of the pf file.
   * @return bool true for success, false for failure.
   */
  function parse($reset=false) { 
    if( $reset ) { 
      // Reset was requested.
      $this->m_content = array();
    }

    if( !empty($this->m_content) ) { 
      // Previously parsed, reset not requested.
      return true;
    }

    if( !file_exists($this->fpath) ) { 
      $this->error = "file not found.";
      return false;
    }
    $dat = file_get_contents($this->fpath);
    if( !$dat ) { 
      $this->error = "Couldn't read contents of file.";
      return false;
    }

    $this->m_content = $this->m_parseData($dat);
    return true;
  } // END: function parse($reset=false)


  /**
   * Parse raw pf content.
   *
   * @access private
   * @param string &$dat Raw data from a pf file.
   * @return array The contents of $this->m_content.
   */
  function m_parseData($dat) { 
    $dat = preg_replace('/#.*$/m',"\n",$dat);
    $pat1 = '/^\s*([a-zA-Z_][a-zA-Z0-9_]*)\s+&(Tbl|Arr){([^}]*)}/ms';
    $pat2 = '/^\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*(.*)$/m';

    $result = array();

    if( preg_match_all($pat1,$dat,$matches,PREG_SET_ORDER) ) { 
      foreach($matches as $set) { 
        list($junk,$block,$type,$content) = $set;
        $dat = str_replace($junk,'',$dat);
        $content = preg_replace('/^\s*|\s*?$/','',$content);
        switch($type) { 
          case "Arr":
            $result[$block] = $this->m_parseBlockArr($content);
            break;
          case "Tbl":
            $result[$block] = $this->m_parseBlockTbl($content);
            break;
          default:
            error_log("Unknown type: $type");
        }
      }
    }
    
    if( preg_match_all($pat2,$dat,$matches,PREG_SET_ORDER) ) { 
      foreach($matches as $set) { 
        list($junk,$key,$value) = $set;
        $result[$key] = $value;
      }
    }

    return $result;
  } // END: function m_parseData(&$dat)


  /**
   * Parse an Arr block.
   *
   * @access private
   * @return array An array or hash.
   */
  function m_parseBlockArr($content) { 
    $result = array();
    foreach( explode("\n",$content) as $line ) { 
      if( strlen(trim($line)) > 0 ) { 
        $row = preg_split('/\s+/',$line,2,PREG_SPLIT_NO_EMPTY);
        if( count($row) == 2 ) { 
          $result[$row[0]] = $row[1];
        } else { 
          $result[] = $row[0];
        }
      }
    }
    return $result;
  } // END: function m_parseBlockArr($content)


  /**
   * Parse a Tbl block.
   *
   * @access private
   * @return table A matrix of rows by columns.
   */
  function m_parseBlockTbl($content) { 
    $result = array();
    foreach( explode("\n",$content) as $line ) { 
      if( strlen($line) > 0 ) { 
        $result[] = preg_split('/\s+/',$line,-1,PREG_SPLIT_NO_EMPTY);
      }
    }
    return $result;
  } // END: function m_parseBlockTbl($content)


} // END: class PFFile



// EOF - PFFile.php
?>
