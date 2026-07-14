<?php
 /**
  * Perform read operations on a table created by datascope.
  *
  * @file DatascopeTable.php
  * @date 2010-02-07 19:28 HST
  * @author Paul Reuter
  * @version 1.0.1
  *
  * @modifications
  * 1.0.0 - 2010-02-06 - Created
  * 1.0.1 - 2010-02-07 - Completed; first pass usefulness.
  */

require_once(dirname(__FILE__).'/PFFile.php');
require_once(dirname(__FILE__).'/SchemaFile.php');
require_once(dirname(__FILE__).'/DBDescriptor.php');

/**
 * Read a datascope table from a given database.
 * @package Antelope
 * @subpackage DatascopeTable
 */
class DatascopeTable { 

  /**
   * Path to the main database file.
   *
   * @access protected
   * @var string $dbpath A file path to the main database file.
   */
  var $dbpath;

  /**
   * The active table being queried.
   *
   * @access protected
   * @var string $tablename Name of the table.  dbpath.tablename => table.
   */
  var $tablename;

  /**
   * @access private
   * @var PFFile $m_pf The rtexec.pf file for reading antelope config data.
   */
  var $m_pf;

  /**
   * @access private
   * @var DBDescriptor $m_dbd The $dbpath object loaded into memory.
   */
  var $m_dbd;

  /**
   * @access private
   * @var SchemaFile $m_sf Description of the structure of $dpath's tables.
   */
  var $m_sf;


  /**
   * Create a table-querying object.
   *
   * @access public
   * @param string $dbpath The database (dbdescriptor) to use.
   * @param string $tablename The table to use (optional).
   * @return DatascopeTable A new object.
   */
  function DatascopeTable($dbpath,$tablename=null) { 
    $this->dbpath = $dbpath;
    $this->setTable($tablename);

    // Find the rtexec.pf file in ancester directories.
    $dsroot = $this->m_getPfRoot($dbpath);
    if( !$dsroot ) { 
      error_log("Couldn't find rtexec.pf in ancestry.");
      return false;
    }

    // Get ANTELOPE root setting
    $this->m_pf = new PFFile($dsroot.'/rtexec.pf');
    $antelopePath = $this->m_pf->getDefines('ANTELOPE');
    $antelopePath = trim(preg_replace('/^.*?\|\|/','',$antelopePath));
    $antelopePath = rtrim($antelopePath,'/');

    // Get schema object by parsing the database descriptor
    $this->m_dbd = new DBDescriptor($dbpath);
    $schemaFile = $this->m_dbd->getSchema();
    // Load schema file
    $this->m_sf = new SchemaFile($antelopePath.'/data/schemas/'.$schemaFile);

    return $this;
  } // END: function DatascopeTable($dbpath,$tablename=null)


  /**
   * Assign the active table (name only).
   *
   * @access public
   * @param string $tablename Name of a table in $dbpath.
   * @return bool true if table path exists, false otherwise.
   */
  function setTable($tablename) { 
    $this->tablename = $tablename;
    return (file_exists($this->dbpath.'.'.$this->tablename));
  } // END: function setTable($tablename)

  

  function getHeader() { 
    $rel = $this->m_sf->getRelation($this->tablename);
    if( !$rel || !is_object($rel) ) { 
      return false;
    }
    return $rel->getFields();
  }

  function getField($attr) { 
    return $this->m_sf->getAttribute($attr);
  }


  /**
   * Return a matrix of records, limited to and ordered by $hdr (if specified).
   *
   * @access public
   * @param array $hdr A list of table columns to return (by attr name).
   * @return matrix The subset of columns for all rows in the table.
   */
  function getRecords($hdr=null) { 
    $rel = $this->m_sf->getRelation($this->tablename);
    if( !$rel || !is_object($rel) ) { 
      return false;
    }
    $avail = $rel->getFields();

    if( is_array($hdr) ) { 
    // Verify all requested headers are present.
      foreach($hdr as $name) { 
        if( !in_array($name,$avail) ) { 
          $this->error = "Field `$name` not in table `".$this->tablename."`";
          return false;
        }
      }
    } else { 
    // Use all available headers.
      $hdr = $avail;
    }

    // Get line start positions
    $pos = array();
    $startIndex = 0;
    foreach( $avail as $name ) { 
      $att = $this->m_sf->getAttribute($name);
      $pos[$name] = array($startIndex,$att->contentLength);
      $startIndex = $startIndex + $att->contentLength + 1;
    }

    // Load, iterate, extract.
    $mat = array();
    $fpath = $this->dbpath.'.'.$this->tablename;
    // foreach( explode("\n",rtrim(file_get_contents($fpath)),"\n") as $line ) { 
    foreach( str_split(file_get_contents($fpath),$startIndex) as $line ) { 
      $row = array();
      foreach( $hdr as $name ) { 
        list($i,$n) = $pos[$name];
        $row[] = trim(substr($line,$i,$n));
      }
      $mat[] = $row;
    }

    return $mat;
  } // END: function getRecords($hdr=null)


  function getSchemaFile() { 
    return $this->m_sf;
  }

  /**
   * Travel up the directory tree to find the location of rtexec.pf in
   * relation to the $dbpath main database file.
   *
   * @access private
   * @param string $dbpath location of the main database file (dbdescriptor)
   * @return string The directory path containing rtexec.pf.
   */
  function m_getPfRoot($dbpath) { 
    $dsroot = dirname($dbpath);
    while( !file_exists($dsroot.'/rtexec.pf') ) { 
      $dsroot = dirname($dsroot);
      if( !$dsroot ) { 
        return false;
      }
    }
    return $dsroot;
  } // END: function m_getPfRoot($dbpath)


} // END: class DatascopeTable

// EOF -- DatascopeTable.php
?>
