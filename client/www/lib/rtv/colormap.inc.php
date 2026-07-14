<?php
 /**
  * Colormap functions.
  *
  * @file colormap.inc.php
  * @package Graphics
  * @date 2010-06-23 12:07 PDT
  * @author Paul Reuter
  * @version 1.0.8
  * 
  * @modifications <pre>
  * 1.0.0 - 2009-11-20 - Created from aggregate of color maps.
  * 1.0.1 - 2010-02-04 - Add: colormap_hex2rgba($h)
  * 1.0.2 - 2010-02-04 - Add: rgb2hsv, hsv2rgb functions.
  * 1.0.3 - 2010-02-04 - BugFix: int should be (a<<24 | r<<16 | g<<8 | b);
  * 1.0.4 - 2010-02-05 - BugFix: hex2int added and fixed hex parsing.
  * 1.0.5 - 2010-02-24 - BugFix: ocean stopped at 95%; undefined vars.
  * 1.0.6 - 2010-06-14 - Add: style 13, a color wheel.
  * 1.0.7 - 2010-06-19 - Add: style 14 (swapped with style 13); strict wheel
  * 1.0.8 - 2010-06-23 - Add: diff,brightness functions.
  * </pre>
  */


/**
 * Create a colormap of style=STYLE_ID.
 */
function colormap($style=0) {
  switch($style) {
    case 0: // CM_STYLE_MATLAB:
      return array(
        "r" => array(  0,   0, 128, 255,   255, 128),
        "g" => array(  0, 128, 255, 255,     0,   0),
        "b" => array(128, 255, 128,   0,     0,   0),
        "a" => array(  0,   0,   0,    0,    0,   0),
        "p" => array(  0, 0.2, 0.4,  0.6, 0.75,   1)
      );
    case 1: // CM_STYLE_HEAT:
      return array(
        "r" => array(255,  255,  255, 128),
        "g" => array(255,  255,  128,   0),
        "b" => array(255,    0,    0,   0),
        "a" => array(  0,    0,    0,   0),
        "p" => array(  0, 0.35, 0.69,   1)
      );
    case 2: // CM_STYLE_MORE_BLUE:
      return array(
        "r" => array(  0,    0,   68,   79,  255,  255, 128),
        "g" => array( 40,   80,  194,  194,  128,    0,   0),
        "b" => array(116,  233,  151,   34,    0,    0,   0),
        "a" => array(  0,    0,    0,    0,    0,    0,   0),
        "p" => array(  0, 0.12, 0.24, 0.40, 0.61, 0.80,   1)
      );
    case 3: // CM_STYLE_COLD:
      return array(
        "r" => array( 48, 224),
        "g" => array(192,  16),
        "b" => array(192, 160),
        "a" => array(  0,   0),
        "p" => array(  0,   1)
      );
    case 4: // CM_STYLE_ROGB:
      return array(
        "r" => array(  0,   84,  224, 128),
        "g" => array( 32,  192,  112,   0),
        "b" => array(128,   32,    0,   0),
        "a" => array(  0,    0,    0,   0),
        "p" => array(  0, 0.35, 0.69,   1)
      );
    case 5: // CM_STYLE_PGO:
      return array(
        "r" => array(112,   0, 255),
        "g" => array( 20,  96, 128),
        "b" => array(112,  32,   0),
        "a" => array(  0,   0,   0),
        "p" => array(  0, 0.5,   1)
      );
    case 6: // CM_STYLE_BLUE_RED:
      return array(
        "r" => array(  0,     0,   255,   255,   255, 132),
        "g" => array(  0,    32,   255,   255,    32,   0),
        "b" => array(132,   255,   255,   255,     0,   0),
        "a" => array(  0,     0,     0,     0,     0,   0),
        "p" => array(  0, 0.115, 0.495, 0.505, 0.885,   1)
      );
    case 7: // CM_STYLE_BLUE_RED2:
      return array(
        "r" => array(  0,     0,     0,   255,   255,  255, 128),
        "g" => array(  0,     0,   255,   255,   255,    0,   0),
        "b" => array(128,   255,   255,   255,     0,    0,   0),
        "a" => array(  0,     0,     0,     0,     0,    0,   0),
        "p" => array(  0, 0.200, 0.400, 0.500,  0.60, 0.80,   1)
      );
    case 8: // CM_STYLE_OCEAN:
      return array(
        "r" => array( 211, 172,   86,    0,    0,    0,    0, 0),
        "g" => array( 250, 245,  197,  150,   80,   10,    5, 0),
        "b" => array( 211, 168,  184,  200,  125,   50,   25, 0),
        "a" => array(   0,   0,    0,    0,    0,    0,    0, 0),
        "p" => array(   0, 0.2, 0.38, 0.53, 0.70, 0.85, 0.95, 1)
      );
    case 9: // CM_STYLE_GRAYSCALE:
      return array(
        "r" => array(  0,   255),
        "g" => array(  0,   255),
        "b" => array(  0,   255),
        "a" => array(  0,     0),
        "p" => array(  0,     1)
      );
    case 10: // CM_STYLE_THERMAL:
      return array(
        "r" => array( 128,   0, 128, 128, 255, 255, 128 ),
        "g" => array(   0, 128, 255, 255, 255, 128,   0 ),
        "b" => array( 128, 255, 255, 128, 128,   0,   0 ),
        "a" => array(   0,   0,   0,   0,   0,   0,   0 ),
        "p" => array(   0, 0.2, 0.4, 0.5, 0.6, 0.8, 1.0 )
      );
    case 11: // CM_STYLE_HEATMAP:
      return array(
        "r" => array( 48,    0,    0,    0,  255,  255, 255 ),
        "g" => array( 48,    0,  240,  192,  255,    0, 240 ),
        "b" => array( 64,  128,  255,   64,    0,    0, 240 ),
        "a" => array(  0,    0,    0,    0,    0,    0,   0 ),
        "p" => array(  0, 0.13, 0.27, 0.46, 0.67, 0.89, 1.0 )
      );
    case 12: // CM_STYLE_STOPLIGHT:
      return array(
        "r" => array(   0, 255, 255 ),
        "g" => array( 255, 255,   0 ),
        "b" => array(   0,   0,   0 ),
        "a" => array(   0,   0,   0 ),
        "p" => array(   0,  0.5,  1 )
      );
    case 13: // CM_STYLE_WHEEL: (for direction)
      return array(
        "r" => array(  0,255,255,255,  0,  0,  0),
        "g" => array(  0,  0,  0,255,255,255,  0),
        "b" => array(255,255,  0,  0,  0,255,255),
        "a" => array(  0,  0,  0,  0,  0,  0,  0),
        "p" => array(0,0.16666667,0.33333333,0.50,0.66666667,0.83333333,1.0),
      );
    case 14: // CM_STYLE_ALT_WHEEL: (for direction)
      return array(
        "r" => array(0,99,197,255,255,255,255,255,140,15,0,0,0),
        "g" => array(16,0,0,0,102,148,197,255,199,173,163,100,16),
        "b" => array(165,165,124,0,0,0,0,0,0,0,199,181,165),
        "a" => array(0,0,0,0,0,0,0,0,0,0,0,0,0),
        "p" => array(0,0.083333,0.166667,0.25,0.333333,0.416667,0.5,0.583333,0.666667,0.75,0.833333,0.916667,1)
      );
    default:
      return array(
        "r" => array(  0,   0, 128, 255,   255, 128),
        "g" => array(  0, 128, 255, 255,     0,   0),
        "b" => array(128, 255, 128,   0,     0,   0),
        "a" => array(  0,   0,   0,   0,     0,   0),
        "p" => array(  0, 0.2, 0.4,  0.6, 0.75,   1)
      );
  }
  // unreachable statement
  return false;
} // END: function colormap(Enum $style=0)


function colormap_int2rgba($value) { 
  return array(
    ($value>>16) & 0xff,
    ($value>>8 ) & 0xff,
    ($value    ) & 0xff,
    ($value>>24) & 0xff
  );
} // END; function int2rgba($int)


function colormap_rgb2int($r,$g,$b) { 
  return ($b&0xff)|(($g<<8)&0xff)|(($r<<16)&0xff);
} // END: function colormap_rgb2int($r,$g,$b)



/**
 * Convert a hex string ((#|\\|0?[xX])HHHHHH[HH]) to an int.
 *
 * @param string $h Hexidecimal string.
 * @return int Integer version of the hex string.
 */
function colormap_hex2int($h) { 
  if( !preg_match('/^\s*(?:[0\\\\]?[x]|#)?([0-9a-f]+)\s*$/i',$h,$pts) ) {
    return false;
  }
  if( strlen($pts[1])%2 == 1 ) { 
    $pts[1] = '0'.$pts[1];
  }
  return hexdec($pts[1]);
} // END: function colormap_hex2int($h)


function colormap_hex2rgba($h) {
  return colormap_int2rgba(colormap_hex2int($h));
} // END: function colormap_hex2rgba($h)


function colormap_rgba2int($r,$g,$b,$a=0) { 
  return ($b&0xff)|(($g<<8)&0xff)|(($r<<16)&0xff)|(($a<<24)|0xff);
} // END: function colormap_rgba2int($r,$g,$b,$a=0)


/**
 * Convert RGB values to HSV.
 *
 * @param int $red [0,255]
 * @param int $green [0,255]
 * @param int $blue [0,255]
 * @return array [$h,$s,$v]
 */
function colormap_rgb2hsv($red, $green, $blue) {
  $maxc = max(max($red,$green),$blue);
  $minc = min(min($red,$green),$blue);

  $delta = $maxc - $minc;
  $v = $maxc;

  if ( $maxc == $minc ) {
    $h = 0;
    $s = 0;
  } else {
    $s = $delta/$maxc;
    if ( $maxc == $red ) {
      $h = ((60 * ($green-$blue) / $delta) + 360 ) % 360;
    } else if ( $maxc == $green ) {
      $h = ((60 * ($blue-$red) / $delta) + 120 );
    } else {
      $h = ((60 * ($red-$green) / $delta) + 240 );
    }
  }
  return array($h,$s,$v);
} // END: function colormap_rgb2hsv($r,$g,$b)


/**
 * Convert HSV values to RGB.
 *
 * @param int $h Hue [0,360)
 * @param int $s Saturation [0,1]
 * @param int $v Value [0,255]
 * @return array [$r,$g,$b]
 */
function colormap_hsv2rgb($h, $s, $v) {
  $hs = ($h/60);
  $hi = floor($hs) % 6;
  $f = $hs - floor($hs);

  $p = $v * (1 - $s);
  $q = $v * (1 - ($f*$s));
  $t = $v * (1 - ((1-$f)*$s));

  switch($hi) {
    case 0: return array($v,$t,$p);
    case 1: return array($q,$v,$p);
    case 2: return array($p,$v,$t);
    case 3: return array($p,$q,$v);
    case 4: return array($t,$p,$v);
    case 5: return array($v,$p,$q);
  }
  return false;
} // END: function colormap_hsv2rgb($h,$s,$v)


/**
 * Calculate the brightness [0,1] of an image.
 *
 * @param uint $r Red color [0,255]
 * @param uint $g Green color [0,255]
 * @param uint $b Blue color [0,255]
 * @return float brightness [0,1]
 */
function colormap_brightness($r,$g,$b) { 
  return (($r*299) + ($g*587) + ($b*114)) / 255000;
} // END: function colormap_brightness($r,$g,$b)


function colormap_diff($c0,$c1) {
  list($r0,$g0,$b0,$a0) = colormap_int2rgba($c0);
  list($r1,$g1,$b1,$a1) = colormap_int2rgba($c1);
  return (
   (max($r0,$r1)-min($r0,$r1)) +
   (max($g0,$g1)-min($g0,$g1)) +
   (max($b0,$b1)-min($b0,$b1)) 
  );
} // END: function colormap_diff($c0,$c1)


function colormap_generate($ncolors=256,$style=0) { 
  $meta = colormap($style);
  if( $ncolors < 2 ) { 
    return array( $meta["r"][0], $meta["g"][0], $meta["b"][0], $meta["a"][0] );
  }
  $ncolors -= 1;

  $nc = 0;
  $pct = 0;
  $vals = array();
  for($i=0,$n=count($meta["p"])-1; $i<$n; $i++) {
    while( $pct <= $meta["p"][$i+1] ) { 
      $off = ($pct-$meta["p"][$i]) / ($meta["p"][$i+1]-$meta["p"][$i]);
      $vals[$nc] = array(
        round(
          $meta["r"][$i] + ($meta["r"][$i+1]-$meta["r"][$i])*$off
        ),
        round(
          $meta["g"][$i] + ($meta["g"][$i+1]-$meta["g"][$i])*$off
        ),
        round(
          $meta["b"][$i] + ($meta["b"][$i+1]-$meta["b"][$i])*$off
        ),
        round(
          $meta["a"][$i] + ($meta["a"][$i+1]-$meta["a"][$i])*$off
        )
      );

      $nc += 1;
      $pct = $nc / $ncolors;
    } 
  }
  return $vals;
} // END: function colormap_generate($ncolors=256,$style=0)

?>
