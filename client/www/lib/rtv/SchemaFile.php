<?php
 /**
  * Parse a Datascope Schema File.
  *
  * @file SchemaFile.php
  * @date 2010-02-07 19:27 HST
  * @author Paul Reuter
  * @version 1.0.2
  *
  * @modifications
  * 1.0.0 - 2010-02-06 - Created
  * 1.0.1 - 2010-02-07 - Added SchemaFileAttribute, SchemaFileRelation classes.
  * 1.0.2 - 2010-02-07 - First-pass usefulness.
  */


/**
 * Parse a schema file.
 *
 * @package Antelope
 * @subpackage SchemaFile
 */
class SchemaFile {

  /**
   * The path to a schema file.
   *
   * @access protected
   * @var string $fpath
   */
  var $fpath;


  /**
   * Records the last error encountered by this class.
   *
   * @access public
   * @var string $error Tracks the last error encountered.
   */
  var $error;

  /**
   * Stores information about this schema.
   *
   * @access protected
   * @var SchemaFileAttribute $schema Schema-only attributes.
   */
  var $schema;

  /**
   * Stores information about attributes.
   *
   * @access protected
   * @var SchemaFileAttribute[] $attributes Array of attribute objects.
   */
  var $attributes = array();

  /**
   * Stores information about relations.
   *
   * @access protected
   * @var SchemaFileRelation[] $relations Array of relation objects.
   */
  var $relations = array();


  /**
   * Create a new instance of a SchemaFile
   *
   * @access public
   * @param string $fpath Path to a schema file.
   * @return SchemaFile A new instance.
   */
  function SchemaFile($fpath) { 
    $this->fpath = $fpath;
    return $this;
  } // END: constructor SchemaFile($fpath)


  function getSchema() {
    if( !$this->parse() ) { 
      return false;
    }
    return $this->schema;
  } // END: function getSchema()


  function getAttribute($name) { 
    $arr = $this->getAttributes();
    if( !is_array($arr) ) { 
      error_log($this->error);
      return false;
    }
    $ix = $this->m_indexOf_name($arr,$name);
    return ($ix<0) ? null : $arr[$ix];
  } // END: function getAttribute($name)


  function getAttributes() { 
    if( !$this->parse() ) { 
      error_log($this->error);
      return false;
    }
    return $this->attributes;
  } // END: function getAttributes()


  function getRelation($name) { 
    $arr = $this->getRelations();
    if( !is_array($arr) ) { 
      error_log("error: ".$this->error);
      return false;
    }
    $ix = $this->m_indexOf_name($arr,$name);
    return ($ix<0) ? null : $arr[$ix];
  } // END: function getRelation($name)


  function getRelations() { 
    if( !$this->parse() ) { 
      error_log($this->error);
      return false;
    }
    return $this->relations;
  } // END: function getRelations()


  function m_indexOf_name(&$arr,$name) { 
    foreach( array_keys($arr) as $i ) { 
      if( is_object($arr[$i]) && $arr[$i]->name === $name ) { 
        return $i;
      }
    }
    return -1;
  } // END: function m_indexOf_name(&$arr,$name)


  /**
   * Parse the schema file.
   *
   * @access protected
   * @param bool $reset If true, will force a reparse of the schema file.
   * @return bool true for success, false for failure.
   */
  function parse($reset=false) {
    if( $reset ) {
      // Reset was requested.
      $this->schema = null;
      $this->attributes = array();
      $this->relations = array();
    }

    if( !empty($this->attributes) || !empty($this->relations) ) { 
      // Previously parsed, reset not requested.
      return true;
    }

    if( !file_exists($this->fpath) ) {
      $this->error = "file not found: ".$this->fpath;
      return false;
    }
    $dat = file_get_contents($this->fpath);
    if( !$dat ) {
      $this->error = "Couldn't read contents of file.";
      return false;
    }

    return $this->m_parseData($dat);
  } // END: function parse($reset=false)


  /**
   * Parse raw schema content.
   * Stores contents to $this->schema, this->attributes, this->relations.
   *
   * @access private
   * @param string &$dat Raw data from a schema file.
   * @return bool true if parsed succesfully, false otherwise.
   */
  function m_parseData(&$dat) { 
    // Replace '\r' with '\n'
    $dat = str_replace(array("\r\n","\r"),"\n",$dat);
    // Remove empty lines
    $dat = preg_replace('/^$\n/m','',$dat);
    $dat = rtrim($dat,"\n");

    $seenError = false;

    // Definition block
    $pat0 = '/^(Schema|Attribute|Relation)\s+(\S+)\s+((?:\([^\)]*;[^\)]*\)|\{[^\}]*;[^\}]*\}|.*?)*)\s*;/ms';
    if( preg_match_all($pat0,$dat,$matches,PREG_SET_ORDER) ) { 
      foreach( $matches as $set ) { 
        $dat = str_replace($set[0],'',$dat);
        if( !$this->m_parseDataBlock($set[1],$set[2],$set[3]) ) { 
          $this->error = "Couldn't parse: ".$set[1];
          $seenError = true;
        }
      }
    }

    // Remove empty lines
    $dat = preg_replace('/^$\n/m','',$dat);
    $dat = rtrim($dat,"\n");

    return !$seenError;
  } // END: function m_parseData(&$dat)


  function m_parseDataBlock($key,$value,$block) { 
    // NB: The order does matter.
    // Key { value... }
    $pat1 = '/^\s*(\S+)\s*\{\s*(.*?)\s*\}/ms';
    // Key (value)
    $pat2 = '/^\s*(\S+)\s*\(\s*(.*?)\s*\)\s*$/m';
    // Key value
    $pat3 = '/^\s*(\S+)\s+(\S+)\s*$/m';

    // Parsed settings
    $hash = array();

    if( preg_match_all($pat1,$block,$matches,PREG_SET_ORDER) ) { 
      foreach($matches as $set) { 
        list($junk,$k,$v) = $set;
        $block = str_replace($junk,'',$block);
        $hash[$k] = $this->m_stringScrub($v);
      }
    }

    if( preg_match_all($pat2,$block,$matches,PREG_SET_ORDER) ) { 
      foreach($matches as $set) { 
        list($junk,$k,$v) = $set;
        $block = str_replace($junk,'',$block);
        $hash[$k] = $this->m_stringScrub($v);
      }
    }

    if( preg_match_all($pat3,$block,$matches,PREG_SET_ORDER) ) { 
      foreach($matches as $set) { 
        list($junk,$k,$v) = $set;
        $block = str_replace($junk,'',$block);
        $hash[$k] = $this->m_stringScrub($v);
      }
    }

    switch($key) { 
      case "Schema":
        $this->schema  = new SchemaFileAttribute($value,$hash);
        break;
      case "Attribute":
        $this->attributes[] = new SchemaFileAttribute($value,$hash);
        break;
      case "Relation":
        $this->relations[] = new SchemaFileRelation($value,$hash);
        break;
      default:
        $this->error = "Unrecognized key: $key";
        return false;
    }

    return true;
  } // END: function m_parseDataBlock($block)


  function m_stringScrub($str) { 
    $str = preg_replace('/^\s*\"(.*)\"\s*$/','\\1',$str);
    return preg_replace('/\s*\n\s*/s',' ',$str);

  } // END: function m_stringScrub($str)


} // END: class SchemaFile


/**
 * Information about schema attributes (ie: table columns or table fields).
 *
 * @package Antelope
 * @subpackage SchemaFile
 */
class SchemaFileAttribute { 
  var $name; // Attribute name
  var $dataType; // Data type (Real|String|Time|...)
  var $Format; // Printf ("%9.4f"|"%-50s"|"%17.5f"|...)
  var $contentLength; // Character length of text (9,50,17,...)
  var $fillValue; // Null value "-9999999999.99900"
  var $Units;
  var $Range;
  var $Description;
  var $Detail;

  var $Timedate; // Used only by Schema

  function SchemaFileAttribute($name,$attrs=null) { 
    $this->name = $name;
    if( $attrs !== null ) { 
      $this->setAttributes($attrs);
    }
    return $this;
  } // END: constructor SchemaFileAttribute($name)


  function setAttributes($attrs) {
    $seenError = false;
    foreach( array_keys($attrs) as $k ) {
      if( !$this->setAttribute($k,$attrs[$k]) ) {
        $seenError = true;
      }
    }
    return ($seenError) ? false : true;
  } // END: function setAttributes($attrs)


  function setAttribute($k,$v) {
    if( property_exists($this,$k) ) {
      $this->$k = $v;
      return true;
    }
    if( $k=="String" || $k=="Real" || $k=="Integer" || $k=="Time" ) { 
      $this->dataType = $k;
      $this->contentLength = $v;
      return true;
    }
    if( $k=="Null" ) { 
      $this->fillValue = $v;
      return true;
    }
    error_log("Property not found: $k");
    return false;
  } // END: function setAttribute($k,$v)


} // END: class SchemaFileAttribute


/**
 * Information about table layout, keys, and alt keys.
 *
 * @package Antelope
 * @subpackage SchemaFile
 */
class SchemaFileRelation { 
  var $name;
  var $Fields = array();
  var $Primary = array();
  var $Alternate = array();
  var $Defines;
  var $Description;
  var $Detail;

  function SchemaFileRelation($name,$attrs=null) { 
    $this->name = $name;
    if( $attrs !== null ) { 
      $this->setAttributes($attrs);
    }
    return $this;
  } // END: constructor SchemaFileRelation()


  function getFields() { 
    return $this->Fields;
  } // END: function getFields()


  function setAttributes($attrs) { 
    $seenError = false;
    foreach( array_keys($attrs) as $k ) {
      if( !$this->setAttribute($k,$attrs[$k]) ) { 
        $seenError = true;
      }
    }
    return ($seenError) ? false : true;
  } // END: function setAttributes($attrs)


  function setAttribute($k,$v) { 
    if( $k=="Primary" || $k=="Alternate" || $k=="Fields" ) { 
      if( !is_array($v) ) { 
        $v = explode(" ",trim($v));
      }
    }
    if( property_exists($this,$k) ) { 
      $this->$k = $v;
      return true;
    }
    error_log("Property not found: $k");
    return false;
  } // END: function setAttribute($k,$v)


} // END: class SchemaFileRelation


// EOF -- SchemaFile.php
?>
