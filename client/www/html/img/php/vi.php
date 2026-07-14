<?php

require($p->site_path."/var/www/lib/inc/colormap.inc.php");


/**
 * Generate an SVG with:
 *   - A background rectangle (optional)
 *   - A circle colored by speed (range_min..range_max) using a gradient from blue -> green -> red.
 *   - A white arrow drawn as a line + marker tip, uniform thickness,
 *     now shorter so it doesn't clip the edges and translated down.
 *
 * Usage:
 *   vi_svg.php?u=-5&v=8&range_min=0&range_max=15&width=100&height=100&bg=ffffff
 */

// ---------------------------------------------------------------------
// 1) NauticalDirection
// ---------------------------------------------------------------------
function nauticalDirection($u, $v) {
    if (abs($u) < 1e-8 && abs($v) < 1e-8) {
        return 0.0;
    }
    // standard atan2 => 0°=East, +90°=North
    $deg = rad2deg(atan2($v, $u));
    // convert to "nautical": 0=North => 90 - deg
    $nautical = 90 - $deg;
    $nautical = fmod($nautical, 360);
    if ($nautical < 0) {
        $nautical += 360;
    }
    return $nautical;
}


// ---------------------------------------------------------------------
// 3) initParams()
// ---------------------------------------------------------------------
function initParams() {
    $params = [
      "u" => 0,
      "v" => 0,
      "range_min" => 0,
      "range_max" => 1,
      "scheme" => 4,
      "reverse" => false,
      "width" => 65,
      "height" => 65,
      "bg" => 0xFFFFFF
    ];

    foreach ($params as $k => $def) {
        if (isset($_REQUEST[$k])) {
            $params[$k] = $_REQUEST[$k];
        }
    }
    // "range=min,max"
    if (isset($_REQUEST["range"])) {
        $tmp = explode(",", $_REQUEST["range"]);
        if (count($tmp) == 2) {
            $params["range_min"] = $tmp[0];
            $params["range_max"] = $tmp[1];
        }
    }
    // legacy min/max
    if (isset($_REQUEST["min"])) {
        $params["range_min"] = $_REQUEST["min"];
    }
    if (isset($_REQUEST["max"])) {
        $params["range_max"] = $_REQUEST["max"];
    }

    // bg: maybe hex
    if (!intval($params["bg"]) && colormap_hex2int($params["bg"])) {
        $params["bg"] = colormap_hex2int($params["bg"]);
    } else {
        $params["bg"] = intval($params["bg"]);
    }

    // reverse => bool
    if (is_string($params["reverse"])) {
        $val = strtolower($params["reverse"]);
        $params["reverse"] = in_array($val, ["1","true","yes"]);
    }

    return $params;
}

// ---------------------------------------------------------------------
// 4) Generate the SVG
// ---------------------------------------------------------------------
function generateSVG($p) {
    // a) Dimensions
    $w = (int)$p["width"];
    $h = (int)$p["height"];

    // b) Background color
    list($rBg, $gBg, $bBg) = colormap_int2rgb($p["bg"]);
    $bgHex = sprintf("#%02x%02x%02x", $rBg, $gBg, $bBg);

    // c) Compute speed and determine circle color
    $u = floatval($p["u"]);
    $v = floatval($p["v"]);
    $speed = sqrt($u*$u + $v*$v);

    $map = colormap_generate(255, $p["scheme"]);
    if ($p["reverse"]) {
        $map = array_reverse($map);
    }
    $dr = floatval($p["range_max"]) - floatval($p["range_min"]);
    if (abs($dr) < 1e-8) {
        $dr = 1.0;
    }
    $ix = ($speed - $p["range_min"]) / $dr * count($map);
    $idx = round($ix);
    $idx = max(0, min(count($map) - 1, $idx));
    list($rc, $gc, $bc) = $map[$idx];
    $circleHex = sprintf("#%02x%02x%02x", $rc, $gc, $bc);

    // d) Arrow rotation: 0° = North, so a line from y=11 to y=5 (center at 8)
    $arrowAngle = nauticalDirection($u, $v);

    // e) Output the SVG
    ob_start();
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    ?>
<svg
    xmlns="http://www.w3.org/2000/svg"
    width="<?php echo $w; ?>"
    height="<?php echo $h; ?>"
    viewBox="0 0 16 16"
    version="1.1"
>
    <!-- Background rectangle -->
    <rect x="0" y="0" width="16" height="16" fill="<?php echo $bgHex; ?>" />

    <!-- Circle filled with speed-based color -->
    <circle cx="8" cy="8" r="8" fill="<?php echo $circleHex; ?>" />

    <!-- Marker definition (arrowhead) -->
    <defs>
      <marker id="arrowHead" markerWidth="4" markerHeight="4" refX="0" refY="2" orient="auto">
        <path d="M0,0 L0,4 L4,2 z" fill="#ffffff" />
      </marker>
    </defs>

    <!-- White arrow line, drawn from (8,11) to (8,5) with translation -->
    <g transform="rotate(<?php echo $arrowAngle; ?>,8,8) translate(0,2)">
      <line
        x1="8" y1="11"
        x2="8" y2="5"
        stroke="#ffffff"
        stroke-width="1"
        marker-end="url(#arrowHead)"
      />
    </g>
</svg>
    <?php
    return ob_get_clean();
}

// ---------------------------------------------------------------------
// 5) Main
// ---------------------------------------------------------------------
function main() {
    $params = initParams();
    header("Content-Type: image/svg+xml");
    echo generateSVG($params);
    exit;
}

main();
