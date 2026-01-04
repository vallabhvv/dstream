<?php
ini_set('memory_limit', '512M');
require_once 'config.php';

// Check if the request accepts HTML (likely from a browser)
if (!empty($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false) {
    http_response_code(404);
    if (file_exists('404.html')) {
        include '404.html';
    } else {
        echo '404 Not Found';
    }
    exit;
}

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$protocol = (!empty($_SERVER['HTTPS']) || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$requestUri = $_SERVER['REQUEST_URI'];
$uriParts = explode('/', trim($requestUri, '/'));
array_pop($uriParts);
$base_url = $protocol . $host . '/' . implode('/', $uriParts);
$stream_url = $base_url . "/index.m3u8";

$headers = array(  
        "Cookie: $cookie_values",  
        "Cookie: $tmp_cookie",  
        "Authorization: Bearer $token",  
        "X-Forwarded-For: $user_ip",  
        "Referer: http://$port/stalker_portal/c/",  
        "User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 254 Safari/533.3",  
        "X-User-Agent: Model: MAG254; Link: WiFi",  
        "Referer: http://$port/stalker_portal/c/",  
    );  

$autho = auth($token);
$genres = get_genres($token);

// Step 1: Build all group names and map with numbers
$group_map = [];
$group_index = 1;

$channel_list_url = "http://$port/stalker_portal/server/load.php?type=itv&action=get_all_channels&JsHttpRequest=1-xml";
   $curl = curl_init($channel_list_url);  
    curl_setopt($curl, CURLOPT_URL, $channel_list_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);  
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);  
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);  
  
    $response = curl_exec($curl);  
    curl_close($curl);  
    
$json = json_decode($response, true);
$channels = $json['js']['data'] ?? [];

foreach ($channels as $ch) {
    $genre_id = $ch['tv_genre_id'] ?? null;
    $group = $genres[$genre_id] ?? 'Addon';
    if (!isset($group_map[$group])) {
        $group_map[$group] = str_pad($group_index++, 2, '0', STR_PAD_LEFT);
    }
}

// Step 2: Reverse map to lookup groups by number
$number_to_group = array_flip($group_map);

// Step 3: Extract selected group numbers from URL
$selected_groups = [];
if (!empty($_GET['id'])) {
    $id_param_raw = trim($_GET['id']);

    // If there are any non-digit separators (space, +, comma, pipe...), split by them.
    if (preg_match('/\D/', $id_param_raw)) {
        $parts = preg_split('/\D+/', $id_param_raw, -1, PREG_SPLIT_NO_EMPTY);
    } else {
        // all digits =>
        $parts = [];
        $len = strlen($id_param_raw);
        for ($i = 0; $i < $len; $i += 2) {
            $parts[] = substr($id_param_raw, $i, 2);
        }
    }

    foreach ($parts as $code) {
        $code = trim($code);
        if ($code === '') continue;

        // Try several ways to match the $number_to_group keys:
        if (isset($number_to_group[$code])) {
            $selected_groups[] = $number_to_group[$code];
            continue;
        }

        // 2) trimmed-leading-zero (e.g. '01' vs '1')
        $nozero = ltrim($code, '0');
        if ($nozero !== '' && isset($number_to_group[$nozero])) {
            $selected_groups[] = $number_to_group[$nozero];
            continue;
        }

        // 3) padded to 2 digits (e.g. '1' -> '01')
        $padded = str_pad($code, 2, '0', STR_PAD_LEFT);
        if (isset($number_to_group[$padded])) {
            $selected_groups[] = $number_to_group[$padded];
            continue;
        }
    }
}

echo "#EXTM3U\n";

// Step 4: Generate playlist entries
foreach ($channels as $ch) {
    $name = $ch['name'] ?? 'Unknown';
    $name = preg_replace('/.*\|\s*/', '', $name);
    
    // Define the base logo URL
    $baseLogoUrl = "http://$port/stalker_portal/misc/logos/320/";

    $id = $ch['id'] ?? 0;
    $logo = $ch['logo'] ?? '';
    $genre_id = $ch['tv_genre_id'] ?? null;
    $group = $genres[$genre_id] ?? 'Addon';
    $cmd_id = preg_match('/\/ch\/(\d+)/', $ch['cmd'] ?? '', $m) ? $m[1] : '0';

    if (!empty($selected_groups) && !in_array($group, $selected_groups)) {
        continue;
    }

    echo "#EXTINF:-1 tvg-id=\"$id\" tvg-logo=\"$baseLogoUrl{$id}.jpg\" group-title=\"$group\",$name\n";
    echo "$stream_url?id=$cmd_id\n";
}

unlink($tmp_cookie);
?>