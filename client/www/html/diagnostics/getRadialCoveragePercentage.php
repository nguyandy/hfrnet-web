<?php
// getRadial.php
// Retrieves radial summaries from S3 for a given station and time range, outputs GeoJSON points.

require_once '/var/www/lib/diagnostics/lluvfile_s3.php'; // lluvFile class for reading S3 rainfall data

// S3 bucket configuration
$S3_BUCKET    = 'rps-nccf-hfrnet-dissemination-prod';
$LLUV_S3_ROOT = "https://{$S3_BUCKET}.s3.amazonaws.com";

// Default network, station code, and debug flag
$DEFAULT_NET   = 'SIO';
$DEFAULT_STA   = 'SDBP';
$DEBUG         = false;

// Initialize coverage counts and GeoJSON template
$coverage       = [];
$jsonarray      = ['type' => 'FeatureCollection', 'features' => []];
$totalFileCount = 0;

// Parse HTTP query parameters
parse_str($_SERVER['QUERY_STRING'], $urlargs);
$net       = $urlargs['net']       ?? $DEFAULT_NET;
$sta       = strtoupper($urlargs['sta'] ?? $DEFAULT_STA);
$starttime = isset($urlargs['starttime']) ? (int)$urlargs['starttime'] : time() - 86400; // default: last 24 hours
$endtime   = isset($urlargs['endtime'])   ? (int)$urlargs['endtime']   : time();
$pattern   = $urlargs['pattern']   ?? 'm';

// Check that at least one object exists under a given S3 prefix
function s3PrefixExists(string $bucket, string $prefix): bool {
    $url = "https://{$bucket}.s3.amazonaws.com/?prefix={$prefix}&max-keys=1";
    $xml = @simplexml_load_file($url);
    return ($xml && isset($xml->Contents));
}

// Validate station prefix in S3, return 404 if not found
if (! s3PrefixExists($S3_BUCKET, "radials/{$sta}/")) {
    header('HTTP/1.1 404 Not Found');
    exit("Invalid network/station: {$net}/{$sta}");
}

// Build a monthly period from start to end times
$start  = (new DateTime("@$starttime"))->modify('first day of this month');
$end    = (new DateTime("@$endtime"))->modify('first day of next month')->modify('-1 second');
$period = new DatePeriod($start, new DateInterval('P1M'), $end);

// Iterate through each month to list and process files
foreach ($period as $dt) {
    $yearmonth = $dt->format('Y-m');
    $prefix    = "radials/{$sta}/{$yearmonth}/";
    $url       = "{$LLUV_S3_ROOT}/?prefix={$prefix}";
    $xml       = @simplexml_load_file($url);

    if (!$xml || empty($xml->Contents)) {
        continue; // no files for this month
    }

    // Gather all object keys for the month
    $keys = [];
    foreach ($xml->Contents as $c) {
        $keys[] = (string)$c->Key;
    }

    // Select filename regex based on radial type
    switch ($pattern) {
        case 'i':
            $regex = '/^RDLi_/';
            break;
        case 'wera':
        case 'lera':
            $regex = '/^RDL/';
            break;
        default:
            $regex = '/^RDLm_/';
            break;
    }

    // Filter to files matching the chosen pattern
    $files = array_filter($keys, fn($fullKey) => preg_match($regex, basename($fullKey)));
    sort($files);

    // Process each file that falls within the requested time range
    foreach ($files as $key) {
        if (! preg_match('/(\d{4})_(\d{2})_(\d{2})_(\d{2})(\d{2})/', $key, $m)) {
            continue; // skip if timestamp not found
        }
        $file_date = gmmktime((int)$m[4], (int)$m[5], 0, (int)$m[2], (int)$m[3], (int)$m[1]);
        $lastSec   = strtotime('-1 second', strtotime('+1 hour', $file_date));

        // Skip files outside start/end window
        if ($starttime >= $lastSec || $endtime < $file_date) {
            continue;
        }

        // Load and parse lluv data from S3
        $url   = "{$LLUV_S3_ROOT}/{$key}";
        $lluv  = new lluvFile($url);
        $datas = $lluv->getLLUVData();

        $totalFileCount++;
        foreach ($datas as $data) {
            $latlon = $data['LATD'] . '/' . $data['LOND'];
            $coverage[$latlon] = ($coverage[$latlon] ?? 0) + 1;
        }
    }
}

foreach ($coverage as $latlon => $count) {
    list($lat, $lon) = explode('/', $latlon);
    $jsonarray['features'][] = [
        'type'       => 'Feature',
        'geometry'   => [
            'type'        => 'Point',
            'coordinates' => [(float)$lon, (float)$lat],
        ],
        'properties' => [
            'weight' => $count / max(1, $totalFileCount),
            'name'   => 'Radials',
        ],
    ];
}

header('Content-Type: application/json');
echo json_encode($jsonarray, JSON_PRETTY_PRINT);
