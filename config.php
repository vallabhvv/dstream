<?php
error_reporting(0);  

// CONFIG  
$port = "4k.tvstb.me";  
$mac = "00:1A:79:30:20:62";  
$deviceid = "1B7E07A64F07EC96966C8BBACD5279F1B580A307C972EB6CEAB95C751A658A34";  
$serial = "0FCC7C6985560";
$currentTimestamp = time();  
$user_ip = $_SERVER['REMOTE_ADDR'];  
$cacheDuration = 12 * 60 * 60; // 12 hours in seconds  
  
$common_headers = [    
    "User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 254 Safari/533.3",  
    "X-Forwarded-For: $user_ip",  
    "X-User-Agent: Model: MAG254; Link: WiFi",  
    "Referer: http://$port/stalker_portal/c/",  
    "Accept: */*",    
];    
  
// Convert $port to safe filename:  
$safePort = str_replace(  
    [".", ":"],  
    "_",  
    $port  
);  
  
// Base paths  
$base_cache_dir   = __DIR__ . '/cache';  
$base_chunked_dir = $base_cache_dir . '/chunked';  
  
// Ensure cache directories exist  
if (!is_dir($base_cache_dir)) {  
    mkdir($base_cache_dir, 0777, true);  
}  
if (!is_dir($base_chunked_dir)) {  
    mkdir($base_chunked_dir, 0777, true);  
}  
  
// File paths  
$cacheFile = "$base_cache_dir/token.json";  
  
// Function to get cached token  
function getCachedToken() {  
    global $cacheFile, $cacheDuration;  
  
    if (file_exists($cacheFile)) {  
        $data = json_decode(file_get_contents($cacheFile), true);  
        if ($data && time() - $data['time'] < $cacheDuration) {  
            return $data;  
        }  
    }  
  
    return null;  
}  
  
// Function to store token in cache  
function cacheToken($token, $random) {  
    global $cacheFile;  
  
    $data = [  
        'token' => $token,  
        'random' => $random,  
        'time' => time()  
    ];  
    file_put_contents($cacheFile, json_encode($data));  
}  
  
// MAIN: get token from cache or regenerate  
$cached = getCachedToken();  
  
if ($cached) {  
    $token = $cached['token'];  
    $random = $cached['random'];  
} else {  
    $newToken = getToken();  
    $token = $newToken['token'];  
    $random = $newToken['random'];  
    cacheToken($token, $random);  
}  
  
$tmp_cookie = tempnam(sys_get_temp_dir(), 'cookie');  
$cookie_values = "mac=$mac; timezone=Asia/Kolkata; adid=c2ea0d0592f6a1399ec8b0c1e01598f4";    
  
// Function to fetch fresh token  
function getToken() {  
    global $port, $cookie_values, $user_ip, $tmp_cookie;  
  
    $url = "http://$port/stalker_portal/server/load.php?type=stb&action=handshake&token=&JsHttpRequest=1-xml";  
  
    $headers = [  
        "Cookie: $cookie_values",  
        "Cookie: $tmp_cookie",  
        "Referer: http://$port/stalker_portal/c/",  
        "X-Forwarded-For: $user_ip",  
        "User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 254 Safari/533.3",  
        "X-User-Agent: Model: MAG254; Link: WiFi",  
    ];  
  
    $curl = curl_init($url);  
    curl_setopt_array($curl, [  
        CURLOPT_URL => $url,  
        CURLOPT_RETURNTRANSFER => true,  
        CURLOPT_HTTPHEADER => $headers,  
        CURLOPT_SSL_VERIFYHOST => 0,  
        CURLOPT_SSL_VERIFYPEER => 0,  
    ]);  
    $resp = curl_exec($curl);  
    curl_close($curl);  
  
    $zurl = json_decode($resp, true);  
      
      $token = $zurl['js']['token'] ?? null;  
    $random = $zurl['js']['random'] ?? null;  
  
    // ✅ If no token found  
    if (empty($token)) {  
        header('Content-Type: application/json');  
        echo json_encode([  
            'error' => 'Failed to retrieve token from server.',  
            'response' => $zurl  
        ], JSON_PRETTY_PRINT);  
        exit;  
    }  
  
    return [  
        'token' => $zurl['js']['token'] ?? null,  
        'random' => $zurl['js']['random'] ?? null  
    ];  
}  
  
// Function to handshake (only if needed)  
function handShake($token) {  
    global $port, $cookie_values, $user_ip, $tmp_cookie;  
  
    $url = "http://$port/stalker_portal/server/load.php?type=stb&action=handshake&token=$token&JsHttpRequest=1-xml";  
  
    $headers = [  
        "Cookie: $cookie_values",  
        "Cookie: $tmp_cookie",  
        "Referer: http://$port/stalker_portal/c/", 
        "X-Forwarded-For: $user_ip",  
        "User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML,    like Gecko) MAG200 stbapp ver: 2 rev: 254 Safari/533.3",  
        "X-User-Agent: Model: MAG254; Link: WiFi",  
    ];  
  
    $curl = curl_init($url);  
    curl_setopt_array($curl, [  
        CURLOPT_URL => $url,  
        CURLOPT_RETURNTRANSFER => true,  
        CURLOPT_HTTPHEADER => $headers,  
        CURLOPT_SSL_VERIFYHOST => 0,  
        CURLOPT_SSL_VERIFYPEER => 0,  
    ]);  
  
    $resp = curl_exec($curl);  
    curl_close($curl);  
  
    $data = json_decode($resp, true);  
    return $data['js']['token'] ?? null;  
}  
  
function genToken()  
{  
    $ourl = getToken();  
    auth($ourl);  
    return handShake($ourl);  
}  
  
function auth($token)  
{ global $port, $cookie_values, $tmp_cookie, $currentTimestamp, $random, $user_ip, $serial, $deviceid;  
    error_reporting(0);  
    $url = "http://$port/stalker_portal/server/load.php?type=stb&action=get_profile&hd=1&ver=ImageDescription%3A%200.2.18-r14-pub-250%3B%20ImageDate%3A%20Fri%20Jan%2015%2015%3A20%3A44%20EET%202016%3B%20PORTAL%20version%3A%205.1.0%3B%20API%20Version%3A%20JS%20API%20version%3A%20328%3B%20STB%20API%20version%3A%20134%3B%20Player%20Engine%20version%3A%200x566&num_banks=2&sn=$serial&stb_type=MAG254&image_version=218&video_out=hdmi&device_id=$deviceid&device_id2=$deviceid&signature=&auth_second_step=1&hw_version=1.7-BD-00&not_valid_token=0&client_type=STB&hw_version_2=67b4af2c2f4a6559a5068aaf31ec0378&timestamp=$currentTimestamp&api_signature=263&metrics=%7B%22mac%22%3A%22$mac%22%2C%22sn%22%3A%22$serial%22%2C%22model%22%3A%22MAG254%22%2C%22type%22%3A%22STB%22%2C%22uid%22%3A%22%22%2C%22random%22%3A%22$random%22%7D&JsHttpRequest=1-xml";  
    $curl = curl_init($url);  
    curl_setopt($curl, CURLOPT_URL, $url);  
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);  
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
  
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);  
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);  
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);  
  
    $resp = curl_exec($curl);  
    curl_close($curl);  
    return $resp;  
}

function curl_get($url, $headers, $cookie_file, $cookie_values) {  
    $ch = curl_init($url);  
    curl_setopt_array($ch, [  
        CURLOPT_RETURNTRANSFER => true,  
        CURLOPT_HTTPHEADER => $headers,  
        CURLOPT_COOKIE => $cookie_values,  
        CURLOPT_COOKIEFILE => $cookie_file,  
        CURLOPT_COOKIEJAR => $cookie_file,  
        CURLOPT_SSL_VERIFYPEER => false,  
        CURLOPT_SSL_VERIFYHOST => false,  
    ]);  
    $response = curl_exec($ch);  
    $curl_error = curl_error($ch);  
    curl_close($ch);  
  
    if ($curl_error) {  
        return false;  
    }
    return $response;
}


// Function to get account details
function account($token) {  
    global $port, $cookie_values, $user_ip, $tmp_cookie;  

    $url = "http://$port/stalker_portal/server/load.php?type=account_info&action=get_main_info&JsHttpRequest=1-xml";  

    $headers = [  
        "Cookie: $cookie_values",  
        "Cookie: $tmp_cookie",  
        "Authorization: Bearer $token",  
        "Referer: http://$port/stalker_portal/c/",  
        "X-Forwarded-For: $user_ip",  
        "User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 254 Safari/533.3",  
        "X-User-Agent: Model: MAG254; Link: WiFi",  
    ];  

    $curl = curl_init($url);  
    curl_setopt_array($curl, [  
        CURLOPT_RETURNTRANSFER => true,  
        CURLOPT_HTTPHEADER     => $headers,  
        CURLOPT_SSL_VERIFYHOST => 0,  
        CURLOPT_SSL_VERIFYPEER => 0,  
    ]);  

    $resp = curl_exec($curl);  
    curl_close($curl);  

    // Decode and return
    if ($resp) {
        $decoded = json_decode($resp, true);
        return $decoded ?: ["error" => "Invalid JSON response", "raw" => $resp];
    }
}

function get_genres($token) {
    global $port, $mac, $user_ip, $tmp_cookie;

    $url = "http://$port/stalker_portal/server/load.php?type=itv&action=get_genres&JsHttpRequest=1-xml";
    $headers = [
        "Authorization: Bearer $token",
        "Cookie: mac=$mac; stb_lang=en; timezone=GMT",
        "Referer: http://$port/stalker_portal/c/",
        "X-Forwarded-For: $user_ip",
        "User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3",
        "X-User-Agent: Model: MAG250; Link: WiFi",
        "Accept: */*",
    ];

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_COOKIEJAR => $tmp_cookie,
        CURLOPT_COOKIEFILE => $tmp_cookie,
    ]);
    $response = curl_exec($curl);
    curl_close($curl);

    $json = json_decode($response, true);  
    $genres_data = $json['js'] ?? [];  
  
    $genres = [];  
    foreach ($genres_data as $genre) {  
        if (isset($genre['id']) && isset($genre['title'])) {  
            $genres[$genre['id']] = $genre['title'];  
        }  
    }  
    return $genres;

}

