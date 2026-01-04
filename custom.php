<?php
require_once 'config.php';

$playlist_groups = [];

// Get playlist base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$playlistUrl = $protocol . $host . dirname($_SERVER['PHP_SELF']) . '/Playlist.m3u';

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
    

// Fetch groups & author data
$autho = auth($token);
$genres = get_genres($token);

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
    $playlist_groups[$group] = true;
}

$group_names = array_keys($playlist_groups);
unlink($tmp_cookie);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customize Your Playlist</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    * { 
      margin: 0; 
      padding: 0; 
      box-sizing: border-box; 
    }
    
    body, html {
      height: 100%;
      width: 100%;
      font-family: 'Segoe UI', 'Roboto', sans-serif;
      background: #0d1a26;
      background-image: radial-gradient(ellipse at bottom, #0d1a26 0%, #000000 100%);
      color: #e0e0e0;
      overflow-x: hidden;
    }

    .bg-animation {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -1;
      overflow: hidden;
    }

    .particle {
      position: absolute;
      display: block;
      pointer-events: none;
      width: 4px;
      height: 4px;
      background: rgba(0, 255, 255, 0.2);
      border-radius: 50%;
      animation: float 8s infinite linear;
      box-shadow: 0 0 5px rgba(0, 255, 255, 0.5);
    }

    @keyframes float {
      0% { opacity: 0; transform: translateY(100vh) rotate(0deg); }
      10% { opacity: 1; }
      90% { opacity: 1; }
      100% { opacity: 0; transform: translateY(-10vh) rotate(720deg); }
    }

    .container {
      max-width: 800px;
      margin: 1rem auto;
      padding: 1rem;
      animation: fadeInUp 0.8s ease;
    }

    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .header {
      text-align: center;
      margin-bottom: 1.5rem;
    }

    .header h1 {
      font-size: 2rem;
      margin-bottom: 0.5rem;
      color: #00ffff;
      text-shadow: 0 0 5px #00ffff, 0 0 10px #00ffff;
    }

    .main-box {
      background: rgba(13, 26, 38, 0.7);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(0, 255, 255, 0.3);
      border-radius: 12px;
      padding: 2rem;
      text-align: center;
      box-shadow: 0 0 15px rgba(0, 255, 255, 0.1);
      margin-top: 1rem;
    }

    .btn {
      padding: 0.8rem 1.5rem;
      border: none;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      white-space: nowrap;
      margin: 5px;
    }

    .btn-primary {
      background: transparent;
      border: 2px solid #00ffff;
      color: #00ffff;
      box-shadow: inset 0 0 10px rgba(0, 255, 255, 0.5);
    }

    .btn-primary:hover {
      background: #00ffff;
      color: #000;
      box-shadow: 0 0 20px #00ffff;
    }

    .btn-secondary {
      background: transparent;
      border: 2px solid #ff00ff;
      color: #ff00ff;
      box-shadow: inset 0 0 10px rgba(255, 0, 255, 0.5);
    }
    
    .btn-secondary:hover {
      background: #ff00ff;
      color: #000;
      box-shadow: 0 0 20px #ff00ff;
    }
    
    .btn-tertiary {
        background: transparent;
        border: 2px solid #9d72ff;
        color: #9d72ff;
    }

    .btn-tertiary:hover {
        background: #9d72ff;
        color: #000;
        box-shadow: 0 0 20px #9d72ff;
    }

    .checkbox-list {
        text-align: left;
        max-height: 300px;
        overflow-y: auto;
        padding: 1rem;
        border: 1px solid rgba(0, 255, 255, 0.2);
        border-radius: 8px;
        margin: 1rem 0;
        background: rgba(0,0,0,0.2);
    }
    
    .checkbox-list label {
        display: block;
        padding: 8px 12px;
        transition: background-color 0.2s ease;
        border-radius: 6px;
        cursor: pointer;
    }
    
    .checkbox-list label:hover {
        background: rgba(0, 255, 255, 0.1);
    }

    input[type=checkbox] {
        margin-right: 10px;
        accent-color: #00ffff;
        transform: scale(1.2);
    }

    .url-input {
      width: 100%;
      padding: 0.8rem;
      border: 1px solid rgba(0, 255, 255, 0.3);
      border-radius: 8px;
      background: rgba(0, 0, 0, 0.3);
      color: #e0e0e0;
      font-size: 0.9rem;
      margin-bottom: 1rem;
    }
    
    .toast {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: rgba(13, 26, 38, 0.9);
      backdrop-filter: blur(10px);
      color: #e0e0e0;
      padding: 0.8rem 1.2rem;
      border-radius: 8px;
      font-size: 0.9rem;
      opacity: 0;
      transform: translateX(300px);
      transition: all 0.3s ease;
      z-index: 999;
      border: 1px solid rgba(0, 255, 255, 0.3);
    }

    .toast.show { opacity: 1; transform: translateX(0); }
    .toast i { margin-right: 0.5rem; }

  </style>
</head>
<body>

<div class="bg-animation" id="bgAnimation"></div>

<div class="container">
  <div class="header">
    <h1><i class="fas fa-filter"></i> Customize Playlist</h1>
  </div>

  <div id="customFormBox" class="main-box">
    <h2>Select Groups</h2>
    <div>
      <button class="btn btn-primary" onclick="toggleCheckboxes(true)"><i class="fas fa-check-double"></i> Select All</button>
      <button class="btn btn-secondary" onclick="toggleCheckboxes(false)"><i class="fas fa-times"></i> Unselect All</button>
    </div>
    <form id="customForm">
      <div class="checkbox-list">
        <?php foreach ($group_names as $i => $group): ?>
          <label>
            <input type="checkbox" name="groups[]" value="<?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?>">
          <?= $i + 1 ?>. <?= htmlspecialchars($group) ?>
          </label>
        <?php endforeach; ?>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-cogs"></i> Generate URL</button>
       <a href="index.php" class="btn btn-tertiary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </form>
  </div>

  <div id="resultBox" class="main-box" style="display:none;">
    <h3><i class="fas fa-check-circle"></i> Customized Playlist URL</h3>
    <input type="text" id="customUrl" class="url-input" readonly onclick="this.select()">
    <button class="btn btn-primary" onclick="copyUrl()"><i class="fas fa-copy"></i> Copy URL</button>
    <a href="index.php" class="btn btn-tertiary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
  </div>
</div>

<div id="toast" class="toast">
  <i class="fas fa-check"></i>
  <span id="toastMessage">Copied!</span>
</div>

<script>
  function createParticles() {
      const bgAnimation = document.getElementById('bgAnimation');
      if (!bgAnimation) return;
      const particleCount = 40;
      
      for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 8 + 's';
        particle.style.animationDuration = (Math.random() * 5 + 3) + 's';
        bgAnimation.appendChild(particle);
      }
  }

  function showToast(message, type = "success") {
    const toast = document.getElementById("toast");
    const toastMessage = document.getElementById("toastMessage");
    const icon = toast.querySelector('i');
    
    toastMessage.textContent = message;
    
    icon.className = type === "success" ? "fas fa-check" : "fas fa-exclamation-circle";
    icon.style.color = type === "success" ? "#00ff7f" : "#ff4d4d";
    
    toast.classList.add("show");
    setTimeout(() => {
      toast.classList.remove("show");
    }, 3000);
  }

  function toggleCheckboxes(state) {
      document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = state);
  }

  document.getElementById('customForm').addEventListener('submit', function (e) {
      e.preventDefault();

      const checked = Array.from(document.querySelectorAll('input[name="groups[]"]:checked')).map(cb => cb.value);
      if (checked.length === 0) {
          showToast("Please select at least one group.", "error");
          return;
      }

      const id = checked.join('+');
      const playlistBase = <?= json_encode($playlistUrl) ?>;
      const url = `${playlistBase}?id=${id}`;

      document.getElementById('customUrl').value = url;
      document.getElementById('customFormBox').style.display = 'none';
      document.getElementById('resultBox').style.display = 'block';
  });

  function copyUrl() {
      const input = document.getElementById('customUrl');
      input.select();
      input.setSelectionRange(0, 99999);
      try {
        navigator.clipboard.writeText(input.value).then(() => {
           showToast("URL copied to clipboard!");
        });
      } catch (err) {
         document.execCommand("copy"); // Fallback
         showToast("URL copied to clipboard!");
      }
  }

  // Initial setup on page load
  window.onload = () => {
      createParticles();
  };
</script>
</body>
</html>