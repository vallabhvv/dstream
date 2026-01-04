<?php
require_once 'config.php';

// @Denver1769 script
$id = $_GET['id'] ?? null;

if (empty($id)) {
    // Show fallback HTML if ID is missing
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $playlistUrl = $protocol . $host . dirname($_SERVER['PHP_SELF']) . '/Playlist.m3u';
    $base_host = explode('.', $host)[0];
    include 'fallback_ui.php';
    exit;
}

// Ensure cache dirs exist
if (!is_dir($base_cache_dir))   mkdir($base_cache_dir, 0777, true);
if (!is_dir($base_chunked_dir)) mkdir($base_chunked_dir, 0777, true);

// chunked paths
$chunked   = "$base_chunked_dir/{$id}.json";
$accountFile = "$base_cache_dir/account.json";

// ==========================
// ✅ RETURN CHANNEL FROM CACHE IF EXISTS AND FRESH (5 minutes)
// ==========================
if (file_exists($chunked) && (time() - filemtime($chunked) < 60)) {
    $cached = json_decode(file_get_contents($chunked), true);
    if (!empty($cached['url'])) {
        header('Content-Type: application/vnd.apple.mpegurl');
        header('Content-Disposition: attachment; filename="' . $id . '.m3u8"');
        header("Location: " . $cached['url']);
        exit;
    }
}

// get CMD url
function getCh($id, $token)
{
    global $port, $cookie_values, $tmp_cookie, $user_ip, $serial, $deviceid;

    $url = "http://$port/stalker_portal/server/load.php?type=itv&action=create_link&cmd=ffrt%20http://localhost/ch/$id&series=&forced_storage=0&disable_ad=0&download=0&force_ch_link_check=0&JsHttpRequest=1-xml";

    $headers = [
        "Cookie: $cookie_values",
        "Cookie: $tmp_cookie",
        "Authorization: Bearer $token",
        "X-Forwarded-For: $user_ip",
        "Referer: http://$port/stalker_portal/c/",
        "User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 254 Safari/533.3",
        "X-User-Agent: Model: MAG254; Link:",
    ];

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
    ]);

    $resp = curl_exec($curl);
    curl_close($curl);

    $resp_url = json_decode($resp, true);
    return $resp_url['js']['cmd'] ?? null;
}

// ==========================
// AUTH + FETCH
// ==========================
$token  = genToken();
$autho  = auth($token);
$cmd_url = getCh($id, $token);

// ==========================
// ✅ ACCOUNT DETAILS CACHE (30 days)
// ==========================
if (!file_exists($accountFile) || (time() - filemtime($accountFile) > 2592000)) { // 30d = 2592000s
    $details = account($token);
    if (!empty($details)) {
        file_put_contents($accountFile, json_encode([
            'details' => $details,
            'time'    => time()
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}

if (empty($cmd_url)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to get stream URL.']);
    exit;
}

// Save channel to cache (5m)
file_put_contents($chunked, json_encode([
    'url'  => $cmd_url,
    'time' => time()
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// Output
header("Connection: keep-alive");
header("Access-Control-Allow-Origin: *");
header("Cache-Control: no-store, no-cache, must-revalidate");
header('Location: ' . $cmd_url);