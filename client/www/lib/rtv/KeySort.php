<?php
 /**
  * Sort records based on specified keys.
  *
  * @file KeySort.php
  * @date 2009-10-13 13:30 HST
  * @author Paul Reuter
  * @version 1.2.1
  *
  * @modifications
  * 1.0.0 -      ?     - prior work somewhere
  * 1.1.0 - 2009-02-26 - Rewritten
  * 1.1.1 - 2009-04-01 - added sort($matrix) function.
  * 1.1.2 - 2009-05-11 - Change constructor to accept int or array.
  * 1.1.3 - 2009-07-01 - Fixed timestamp in file header.
  * 1.2.0 - 2009-10-13 - KeySort with negative keys => sort desc.
  * 1.2.1 - 2009-10-13 - Add: setKeys($keys) method for recycling.
  *
  * @limitations
  * -- Negative key sort only works when key is integer > 0.  
  *    Workaround:  Invert sorting logic, if you want desc, use asc,
  *    if you want asc, specify desc.  Sort.  Reverse the matrix.
  */



/**
 * Ever want to compare two arrays based on unordered keys?  
 * Just instantiate, provide keys in the order you wish to compare them,
 * and usort.
 *
 * Example:
 *   $data = array( array(9,3,1,5,'b'=>4), array(1,3,5,3,'b'=>9) );
 *   $kSort = new KeySort(array(3,1,'b'));
 *   usort($data,array($kSort,'cmp'));
 *   // $data => array( array(1,3,5,3,'b'=>9), array(9,3,1,5,'b'=>4) );
 */
class KeySort { 
  /** @private */
  var $keys;

  /**
   * Create an object that can sort based on a subset of array keys.
   *
   * @public
   * @param array $keys An array of keys to compare by.
   * @return KeySort A new KeySort object.
   */
  function KeySort($keys) {
    $this->setKeys($keys);
    return $this;
  } // END: function KeySort($keys)


  /**
   * Update an existing object's array keys.
   *
   * @public
   * @param array $keys An array of keys to compare by.
   * @return bool Always true.
   */
  function setKeys($keys) { 
    if( is_int($keys) ) { 
      $keys = range(0,$keys-1);
    }
    $this->keys = (is_array($keys)) ? $keys : array($keys);
    return true;
  } // END: function setKeys($keys)

  
  /**
   * Function which compares two arrays, $a and $b, by a set of keys $ks.
   *
   * @private
   * @param array $a First array.
   * @param array $b Second array.
   * @return int -1: $a lt $b; +1: $a gt $b; 0: $a eq $b
   */
  function cmp($a,$b) { 
    foreach( $this->keys as $i ) {
      if( $i < 0 ) { 
      // Sort (compare) descending
        $i = 0 - $i;
        if( $a[$i] === $b[$i] ) { 
          continue;
        }
        return ($a[$i] < $b[$i]) ? +1 : -1;
      } else { 
      // Sort (compare) ascending
        if( $a[$i] === $b[$i] ) { 
          continue;
        }
        return ($a[$i] < $b[$i]) ? -1 : +1;
      }
    }
    return 0;
  } // END: function cmp($a,$b)


  /**
   * API function for sorting a matrix of values.
   *
   * @public
   * @param array An array of records.
   * @return boolean success or failure.
   * @since 2009-04-01
   */
  function sort(&$mat) { 
    return usort($mat,array($this,'cmp'));
  } // END: function sort(&$mat)


} // END: class KeySort($keys)

?>
