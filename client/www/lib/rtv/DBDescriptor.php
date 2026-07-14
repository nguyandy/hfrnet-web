<?php
 /**
  * DBDescriptor Parser.  Parses the main database file.
  * The main db file is the one that doesn't have any relations.
  *
  * @file DBDescriptor.php
  * @date 2010-02-06 16:42 HST
  * @author Paul Reuter
  * @version 1.0.1
  *
  * @modifications
  * 1.0.0 - 2010-02-05 - Created as skeleton
  * 1.0.1 - 2010-02-06 - Parses a DB descriptor assuming key value format.
  */

/**
 * A database descriptor parser.
 *
 * @package Antelope
 * @subpackage DBDescriptor
 */
class DBDescriptor {

  /**
   * @var string $fpath A path to a dbdescriptor file.
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
   * Constructor for a new DBDescriptor.
   *
   * @access public
   * @return DBDescriptor A new instance.
   */
  function DBDescriptor($fpath) { 
    $this->fpath = $fpath;
    return $this;
  } // END: constructor DBDescriptor($fpath)


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


  function getSchema() { 
    if( !$this->parse() ) { 
      return false;
    }
    if( isset($this->m_content["schema"]) ) { 
      return $this->m_content["schema"];
    }
    $this->error = "schema not found in descriptor.";
    return false;
  } // END: function getSchema()


  /**
   * Parse the dbdescriptor file.
   *
   * @access private
   * @param bool $reset If true, will force a reparse of the descriptor file.
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
   * Parse raw descriptor content.
   *
   * @access private
   * @param string &$dat Raw data from a pf file.
   * @return array The contents of $this->m_content.
   */
  function m_parseData($dat) { 
    // Remove comments
    $dat = preg_replace('/\#.*$/m','',$dat);
    // Replace '\r' with '\n'
    $dat = str_replace(array("\r\n","\r"),"\n",$dat);
    // Remove empty lines
    $dat = preg_replace('/^$\n/m','',$dat);
    $dat = rtrim($dat,"\n");

    $result = array();
    foreach( explode("\n",$dat) as $line ) {
      $row = preg_split('/\s+/',$line,2,PREG_SPLIT_NO_EMPTY);
      switch( count($row) ) { 
        case 1:
          $result[] = $row[0];
          break;
        case 2:
          $result[$row[0]] = $row[1];
          break;
        default:
          $result[] = $row;
      }
    }
    return $result;
  } // END: function m_parseData($dat)


} // END: class DBDescriptor


// EOF - DBDescriptor.php
?>
