<?php
/**
 * More array functions.
 *
 * @file Arrays.php
 * @date 2009-12-02 11:05 HST
 * @author Paul Reuter
 * @version 1.1.2
 *
 * @modifications <pre>
 * 1.0.0 - 2009-06-08 - created
 * 1.0.1 - 2009-06-08 - added collapse function
 * 1.0.2 - 2009-06-22 - Added: flatten($arr) method.
 * 1.0.3 - 2009-06-22 - Changed: find to mimic array_search.
 * 1.0.4 - 2009-06-22 - Changed: collapse, added fillValues parameter.
 * 1.0.5 - 2009-07-10 - Changed: merge order when values are equal.
 * 1.0.6 - 2009-09-29 - Added: mean(&$arr) method.
 * 1.0.7 - 2009-09-30 - Added: array_ min,max,init methods.
 * 1.0.8 - 2009-10-08 - Added: intersect_key($arr1,$arr2,...)
 * 1.0.9 - 2009-10-16 - BugFix: check for is_array on formatMatrixAscii.
 * 1.1.0 - 2009-11-07 - Added: array_combine back-compat method.
 * 1.1.1 - 2009-11-09 - Added: formatTableHtml($head,$body,$foot,$attrs)
 * 1.1.2 - 2009-12-02 - Added: array_unique(&$arr) to find unique sub-arrays.
 * </pre>
 */


/**
 * Some useful functions encapsulated in an array.  All functions should
 * be statically accessable; no need to instantiate.
 */
class Arrays { 

  /**
   * Merge two sorted matricies into a single sorted matrix.
   * Keys are numeric [0..], $cols contains column keys to compare.
   *
   * Duplicate: The b record will come after a when a == b.
   *
   * @access public
   * @static
   * @param array $a A sorted array.
   * @param array $b A sorted array.
   * @return array A sorted array containing both $a and $b.
   */
  function merge(&$a,&$b) {  

    $a_i = 0;
    $a_n = count($a);
    $b_i = 0;
    $b_n = count($b);

    $c = array();

    while ($a_i < $a_n && $b_i < $b_n ) {
      if( $a[$a_i] > $b[$b_i] ) { 
        $c[] = $b[$b_i++];
      } else { 
        $c[] = $a[$a_i++];
      }
    }

    while( $a_i < $a_n ) { 
      $c[] = $a[$a_i++];
    }
    while( $b_i < $b_n ) { 
      $c[] = $b[$b_i++];
    }

    return $c;
  } // END: function merge(&$a,&$b)



  /**
   * Merge two sorted matricies into a single sorted matrix.
   * Keys are numeric [0..], $cols contains column keys to compare.
   *
   * Duplicate: The b record will come after a when a[kz[]] == b[kz[]].
   *
   * @access public
   * @static
   * @param array $a A sorted array (matrix).
   * @param array $b A sorted array (matrix).
   * @param array $cols An array of keys to compare.
   * @return array A sorted array containing both $a and $b.
   */
  function merge2d(&$a,&$b,$cols) {  
    $a_i = 0;
    $a_n = count($a);
    $b_i = 0;
    $b_n = count($b);

    $c = array();

    while ($a_i < $a_n && $b_i < $b_n ) {
      $cmp = 0;
      foreach( $cols as $k ) {
        if( $a[$a_i][$k] === $b[$b_i][$k] ) {
          continue;
        }
        $cmp = ($a[$a_i][$k] < $b[$b_i][$k]) ? -1 : +1;
        break;
      }
      if( $cmp > 0 ) {
        $c[] = $b[$b_i++];
      } else { 
        $c[] = $a[$a_i++];
      }
    }

    while( $a_i < $a_n ) { 
      $c[] = $a[$a_i++];
    }
    while( $b_i < $b_n ) { 
      $c[] = $b[$b_i++];
    }

    return $c;
  } // END: function merge2d(&$a,&$b,$cols)


  /**
   * Merge two sorted matricies into a single sorted matrix.
   * Keys are numeric [0..], $cols contains column keys to compare.
   *
   * Duplicate: The b record will come after a when a[kz[]] == b[kz[]].
   *
   * @access public
   * @static
   * @param array $a A sorted array (matrix).
   * @param array $b A sorted array (matrix).
   * @param callback $callback A callback compare function(a,b) 
   *   returning -1,0,+1.
   * @param array $cols An array of keys to compare.
   * @return array A sorted array containing both $a and $b.
   */
  function mergecmp(&$a,&$b,$callback) {  
    if( !is_callable($callback) ) { 
      return false;
    }
    $a_i = 0;
    $a_n = count($a);
    $b_i = 0;
    $b_n = count($b);

    $c = array();

    while ($a_i < $a_n && $b_i < $b_n ) {
      $cmp = call_user_func($callback,$a[$a_i],$b[$b_i]);
      if( $cmp > 0 ) {
        $c[] = $b[$b_i++];
      } else { 
        $c[] = $a[$a_i++];
      }
    }

    while( $a_i < $a_n ) { 
      $c[] = $a[$a_i++];
    }
    while( $b_i < $b_n ) { 
      $c[] = $b[$b_i++];
    }

    return $c;
  } // END: function mergecmp(&$a,&$b,$callback)


  /**
   * Collapse a multi-keyed matrix, overwriting same-keyed subsequent records 
   * with the later value.  If overwrite is false and there's a key-collision,
   * this function returns false.
   *
   * @access public
   * @static
   * @param matrix &$mat A matrix of records.
   * @param array|int $keys An array of keys, or the number of columns to use
   *  when forming a key.  If not specified, uses entire record.
   * @param array|null $fillValues  Alternative to null, these values
   *   represent null or missing values.
   * @param bool $overwrite Indicates whether key-collisions will be updated
   *  or indicate failure.
   * @return matrix|false A minimized array of records, or false if duplicate
   *  was encountered.
   */
  function collapse(&$mat,$keys=null,$fillValues=null,$overwrite=true) {
    if( count($mat) < 1 ) { 
      return $mat;
    }

    // Benchmark testing on a matrix of 50k records, each a 9-item array.
    // Comparison testing revealed comparitor timings:
    // array()  === array()  Loop time: 0.05422 // full array strict
    // array()  ==  array()  Loop time: 0.08922 // full array loose
    // array[c] === array[c] Loop time: 0.42474 // per-cell strict
    // array[c] ==  array[c] Loop time: 0.47661 // per-cell loose
    // Conclusion: Triple equals on entire array is 7.8x faster.
    if( $overwrite ) { 
    // If allowed to overwrite, collapse sequential records into one.
      $nc = count($mat[0]);   // Number of columns
      $r  = count($mat) - 1;  // Start from last record
      $q  = $r - 1;   // $q: Next untested record, $q < $r always.
      // Loop, comparing current record to previous record:
      while( $q >= 0 ) { 
        // Optimal test: Are rows identical?
        if( $mat[$q] === $mat[$r] ) { 
          // Remove duplicate record.
          $mat[$q] = null;
          $q -= 1;
          continue;
        }
        if( !is_null($keys) ) { 
          // If records have same dimensions, 
          // replace old values with newer, non-null values.
          $isSame = true;
          foreach($keys as $k) { 
            if( $mat[$q][$k] === $mat[$r][$k] ) {
              continue;
            }
            $isSame = false;
            break;
          }
          if( $isSame ) {
            // User provided null value lookup?
            if( is_array($fillValues) ) { 
              for($c=0; $c<$nc; $c++) {
                if( $mat[$r][$c] !== $fillValues[$c] ) { 
                  $mat[$q][$c] = $mat[$r][$c];
                }
              }
            } else { // No null value lookup, just test for null.
              for($c=0; $c<$nc; $c++) {
                // Copy only the non-null values from the newer record.
                // Thereby clobbering old existing values with new ones.
                if( $mat[$r][$c] !== null ) { 
                  $mat[$q][$c] = $mat[$r][$c];
                }
              }
            }
            // mark old as deleted
            $mat[$r] = null;
          }
        }
        $r = $q;
        $q -= 1;
      } // end while( $r > 0 )

    } else { 
    // Any similar, sequential records triggers an error, test for it.
      if( is_null($keys) ) { 
      // Use full record if $keys is null.
        for($r=1,$nr=count($mat); $r<$nr; $r++) {
          if( $mat[$r-1] === $mat[$r] ) { 
            // overwrite is false, can't have identical records
            return false;
          }
        }
      } else { 
      // else iterate over each key in $keys to compare equality.
        for($r=1,$nr=count($mat); $r<$nr; $r++) {
          $isSame = true;
          foreach($keys as $k) { 
            if( $mat[$r-1][$k] === $mat[$r][$k] ) {
              continue;
            }
            $isSame = false;
            break;
          }
          if( $isSame ) {
            // overwrite is false, can't have similar records
            return false;
          }
        }
      }
    } // End if(overwrite): consolidate, else if duplicate: fail

    $b = array();
    foreach($mat as $row) { 
      if( !is_null($row) ) { 
        $b[] = $row;
      }
    }
    return $b;
  } // END: function collapse(&$mat,$keys=null,$overwrite=true)



  /**
   * Display a matrix in clean ascii
   *
   * @access public
   * @static
   * @param array &$mat A matrix of values.
   * @return string Pretty text for display.
   */
  function formatMatrixAscii(&$mat) {
    if( !is_array($mat) ) { 
      return false;
    }
    // Compute max column width 
    $lens = array();
    foreach($mat as $row) {
      foreach( array_keys($row) as $i ) {
        $value = $row[$i];
        $subset[$i] = $value;
        if( !isset($lens[$i]) ) {
          $lens[$i] = strlen($row[$i]);
        } else {
          $lens[$i] = max($lens[$i],strlen($row[$i]));
        }
      }
    }
    // Generate sprintf format strings (right-justified)
    foreach( array_keys($lens) as $i ) {
      $lens[$i] = sprintf("%%%ds",$lens[$i]);
    }
    // Generate formatted text, row-by-row.
    $body = '';
    foreach($mat as $row) {
      foreach( array_keys($row) as $i ) {
        $row[$i] = sprintf($lens[$i],$row[$i]);
      }
      $body .= implode("\t",$row)."\n";
    }
    return $body;
  } // END: function formatMatrixAscii(&$mat)


  /**
   * Build an HTML table from table head, body and footer, all optional.
   *
   * @public
   * @param array|matrix $thead A row or set of rows of header content.
   * @param array|matrix $tbody A row or set of rows of body content.
   * @param array|matrix $tfoot A row or set of rows of footer content.
   * @param hash $attrs A set of table-element attributes.
   * @return string HTML markup.
   */
  function formatTableHtml($thead=null,$tbody=null,$tfoot=null,$attrs=null) {

    $attr = '';
    if( is_array($attrs) ) {
      foreach(array_keys($attrs) as $k) {
        $attr .= ' '.$k.'="'.htmlentities($attrs[$k]).'"';
      }
      $attr = substr($attr,1);
    }

    $html = '<table'.$attr.'>';

    if( is_array($thead) && count($thead)>0 ) {
      if( !is_array($thead[0]) ) {
        $thead = array($thead);
      }
      $html .= '<thead>';
      foreach($thead as $trow) {
        foreach(array_keys($trow) as $i) {
          $trow[$i] = htmlentities($trow[$i]);
        }
        $innerHTML = '<td>'.implode('</td><td>',$trow).'</td>';
        $html .= '<tr>'.$innerHTML.'</tr>';
      }
      $html .= '</thead>';
    }

    if( is_array($tbody) && count($tbody)>0 ) {
      if( !is_array($tbody[0]) ) {
        $tbody = array($tbody);
      }
      $html .= '<tbody>';
      foreach($tbody as $trow) {
        foreach(array_keys($trow) as $i) {
          $trow[$i] = htmlentities($trow[$i]);
        }
        $innerHTML = '<td>'.implode('</td><td>',$trow).'</td>';
        $html .= '<tr>'.$innerHTML.'</tr>';
      }
      $html .= '</tbody>';
    }

    if( is_array($tfoot) && count($tfoot)>0 ) {
      if( !is_array($tfoot[0]) ) {
        $tfoot = array($tfoot);
      }
      $html .= '<tfoot>';
      foreach($tfoot as $trow) {
        foreach(array_keys($trow) as $i) {
          $trow[$i] = htmlentities($trow[$i]);
        }
        $innerHTML = '<td>'.implode('</td><td>',$trow).'</td>';
        $html .= '<tr>'.$innerHTML.'</tr>';
      }
      $html .= '</tfoot>';
    }
    $html .= '</table>';

    return $html;
  } // END: function formatTableHtml($thead,$tbody,$tfoot,$attrs)


  /**
   * PHP's array_search returns the first index of needle in haystack.  This
   *  implementation returns all indicies in haystack matching needle.
   *
   * @private
   * @param mixed $needle What to search for.
   * @param array $haystack Stuff to look at.
   * @param bool $strict Whether the test should be strict.
   * @return array An array of all indicies where needle matches in haystack.
   */
  function find($needle,$haystack,$strict=false) {
    $results = array();
    // This approach is a best effort to duplicate PHP's array_search
    if( $strict ) { 
      $type = getType($needle);
      foreach( array_keys($haystack) as $i ) { 
        if( $haystack[$i] == $needle && gettype($haystack[$i]) == $type ) { 
          $results[] = $i;
        }
      }
    } else { 
      foreach( array_keys($haystack) as $i ) { 
        if( $haystack[$i] == $needle ) { 
          $results[] = $i;
        }
      }
    }

    // 
    // The approach below is 6 times slower than the one above.
    // return array_keys( array_intersect($haystack,array($needle)) );
    //

    return $results;
  } // END: function find($needle,$haystack,$strict=false)



  /**
   * Create a 1-dimensional array of out an array of potentially nested arrays.
   *
   * @param array &$arr Source array.
   * @return array A one-dimensional array containing all elements of $arr.
   * @since 1.0.2
   */
  function flatten(&$arr) { 
    $flat = array();
    if( !is_array($arr) ) { 
      $flat[] = $arr;
      return $flat;
    }
    foreach($arr as $item) { 
      if( is_array($item) ) { 
        array_splice($flat,count($flat),0,Arrays::flatten($item));
      } else { 
        $flat[] = $item;
      }
    }
    return $flat;
  } // END: function flatten(&$a)


  /**
   * Finds the intersection of 2 arrays based on their keys.
   *
   * @link http://www.php.net/manual/en/function.array-intersect-key.php
   * @func intersect_key($arr1, $arr2 [, $arr3...])
   * @param array $arr1 An array with contents.
   * @param array $arr2 An array possibly containing similar keys to $arr1
   * @return array An array of keys that exist in all provided arrays.
   */
  function intersect_key() {
    $arrs = func_get_args();
    $result = array_shift($arrs);
    foreach ($arrs as $array) {
      foreach ($result as $key => $v) {
        if (!array_key_exists($key, $array)) {
          unset($result[$key]);
        }
      }
    }
    return $result;
  } // END: function intersect_key($arr1,$arr2,...)


  function mean(&$arr) { 
    $n = count($arr);
    return ($n>0) ? array_sum($arr)/$n : 0;
  } // END: function mean(&$arr)


  /**
   * Initialize an array with $n values from $x0 to $x1.
   *
   * @param number $x0 The first number in the output array.
   * @param number $x1 The last number in the output array.
   * @param int $n ($n>=2) The size of the array.
   * @return array An initialized array of values.
   */
  function array_init($x0,$x1,$n) {
    if( $n < 1 ) {
      return false;
    }
    if( $x1==$x0 ) {
      return array_fill(0,$n,$x0);
    }
    $xs = array($x0);
    if( $n < 2 ) {
      return $xs;
    }
    $step = ($x1-$x0) / ($n-1);
    $xs = array($x0);
    for($i=1; $i<$n; $i++) {
      $xs[$i] = $xs[$i-1] + $step;
    }
    // Force the boundary to be an exact value.
    // Floating point calculations may not like it otherwise.
    $xs[$n-1] = $x1;
    return $xs;
  } // END: function array_init($x0,$x1,$n)


  function array_min($mixed) {
    if( is_array($mixed) ) {
      $value = null;
      foreach($mixed as $item) {
        if( !is_numeric($item) ) {
          $item = array_min($item);
        }
        if( is_numeric($item) ) {
          if( $value===null || $item < $value ) {
            $value = $item;
          }
        }
      }
      return $value;
    }
    return $mixed;
  } // END: function array_min($mixed)



  function array_max($mixed) {
    if( is_array($mixed) ) {
      $value = null;
      foreach($mixed as $item) {
        if( !is_numeric($item) ) {
          $item = array_max($item);
        }
        if( is_numeric($item) ) {
          if( $value===null || $item > $value ) {
            $value = $item;
          }
        }
      }
      return $value;
    }
    return $mixed;
  } // END: function array_max($mixed)


  /*
   * From the php5 documentation: 
   * Function: array_combine(array keys, array values)
   * Purpose: Creates an array by using one array for keys
   *  and another for its values.
   * URL: http://www.php.net/manual/en/function.array-combine.php
   */
  function array_combine($keys, $values) {
    foreach($keys as $key) {
      $out[$key] = array_shift($values);
    }
    return $out;
  } // END: function array_combine($keys,$values)


  /**
   * Return an array of unique arrays.  The array_unique built-in doesn't
   * work on arrays.
   *
   * @public
   * @param array &$arr An array to find unique values for.
   * @return array An array of unique contents.
   */
  function array_unique(&$arr) { 
    $uu = array();
    foreach($arr as $item) { 
      $seen = false;
      foreach($uu as $single) { 
        if( $item===$single ) { 
          $seen = true;
          break;
        }
      }
      if( !$seen ) { 
        $uu[] = $item;
      }
    }
    return $uu;
  } // END: function array_unique($arr)


} // END: class Arrays

?>
