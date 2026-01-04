<?php
require_once 'config.php';

$playlistUrl = $playlistUrl ?? '#'; // fallback if not set
$base_host = $_SERVER['HTTP_HOST'] ?? 'Dev Player';


// Load account details from cache
$accountData = null;
$accountFile = 'cache/account.json';
if (file_exists($accountFile)) {
    $jsonData = file_get_contents($accountFile);
    $accountData = json_decode($jsonData, true);
}

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

// Extract user details with fallbacks
$fname = $accountData['details']['js']['fname'] ?? 'Guest User';
$phone = $accountData['details']['js']['phone'] ?? 'Not Available';
$endDate = $accountData['details']['js']['end_date'] ?? 'Unknown';
$tariffPlan = $accountData['details']['js']['tariff_plan'] ?? 'Basic';
$mac = $accountData['details']['js']['mac'] ?? 'Unknown';
$lastChange = $accountData['details']['js']['last_change_status'] ?? 'Unknown';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($base_host) ?> - Dashboard</title>
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

    /* Animated background particles */
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
      0% {
        opacity: 0;
        transform: translateY(100vh) rotate(0deg);
      }
      10% {
        opacity: 1;
      }
      90% {
        opacity: 1;
      }
      100% {
        opacity: 0;
        transform: translateY(-10vh) rotate(720deg);
      }
    }

    /* Splash Screen */
    #splash {
      position: fixed;
      top: 0; 
      left: 0;
      width: 100%; 
      height: 100%;
      background: #0d1a26;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 1000;
      transition: all 0.6s ease;
    }

    .splash-logo {
      font-size: 2.5rem;
      margin-bottom: 2rem;
      color: #00ffff;
      text-shadow: 0 0 5px #00ffff, 0 0 10px #00ffff, 0 0 20px #00ffff;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); opacity: 0.8; }
      50% { transform: scale(1.05); opacity: 1; }
    }

    .loader {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 8px;
    }

    .loader div {
      width: 12px;
      height: 12px;
      background: linear-gradient(45deg, #00ffff, #ff00ff);
      border-radius: 50%;
      animation: bounce 1.4s infinite ease-in-out both;
    }

    .loader div:nth-child(1) { animation-delay: -0.32s; }
    .loader div:nth-child(2) { animation-delay: -0.16s; }
    .loader div:nth-child(3) { animation-delay: 0s; }

    @keyframes bounce {
      0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
      40% { transform: scale(1.2) translateY(-8px); opacity: 1; }
    }

    /* Main Container */
    .container {
      display: none;
      max-width: 800px;
      margin: 1rem auto;
      padding: 1rem;
      animation: fadeInUp 0.8s ease;
    }

    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Header */
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

    /* Cards Grid */
    .cards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 0.6rem;
      margin-bottom: 1.0rem;
    }

    .card {
      background: rgba(13, 26, 38, 0.7);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(0, 255, 255, 0.3);
      border-radius: 12px;
      padding: 1rem;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      box-shadow: 0 0 15px rgba(0, 255, 255, 0.1);
    }

    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 2px;
      background: linear-gradient(90deg, #ff00ff, #00ffff);
    }

    .card:hover {
      transform: translateY(-4px);
      box-shadow: 0 0 25px rgba(0, 255, 255, 0.4);
    }

    .card-icon {
      font-size: 1.5rem;
      margin-bottom: 0.5rem;
      color: #00ffff;
    }

    .card h3 {
      margin-bottom: 0.3rem;
      font-size: 1rem;
      color: #fff;
    }

    .card p {
      font-size: 0.7rem;
      opacity: 0.8;
      word-break: break-all;
    }

    /* Status Badge */
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: rgba(0, 255, 127, 0.1);
      color: #00ff7f;
      padding: 0.4rem 0.8rem;
      border-radius: 20px;
      font-weight: 500;
      font-size: 0.8rem;
      border: 1px solid rgba(0, 255, 127, 0.3);
    }

    /* URL Section */
    .url-section {
      background: rgba(13, 26, 38, 0.7);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(0, 255, 255, 0.3);
      border-radius: 12px;
      padding: 1.5rem;
      text-align: center;
    }
    
    .url-section h3 {
      color: #00ffff;
      text-shadow: 0 0 5px #00ffff;
    }

    .url-input-group {
      display: flex;
      gap: 0.8rem;
      margin-top: 1rem;
      flex-wrap: wrap;
    }

    .url-input {
      flex: 1;
      min-width: 200px;
      padding: 0.8rem;
      border: 1px solid rgba(0, 255, 255, 0.3);
      border-radius: 8px;
      background: rgba(0, 0, 0, 0.3);
      color: #e0e0e0;
      font-size: 0.9rem;
    }

    .url-input::placeholder {
      color: rgba(224, 224, 224, 0.5);
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
      gap: 0.5rem;
      white-space: nowrap;
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

    /* Toast */
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

    .toast.show {
      opacity: 1;
      transform: translateX(0);
    }

    .toast i {
      margin-right: 0.5rem;
      color: #00ff7f;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .container {
        margin: 0.5rem;
        padding: 0.5rem;
      }

      .header h1 {
        font-size: 1.5rem;
      }

      .url-input-group {
        flex-direction: column;
      }
    }

    .fade-out {
      opacity: 0;
      transform: scale(0.95);
    }
  </style>
</head>
<body>
  <!-- Background Animation -->
  <div class="bg-animation" id="bgAnimation"></div>

  <!-- Splash Screen -->
  <div id="splash">
    <div class="splash-logo">
      <i class="fas fa-tv"></i> <?= htmlspecialchars($port) ?>
    </div>
    <div class="loader">
      <div></div><div></div><div></div>
    </div>
  </div>

  <!-- Main UI -->
  <div class="container" id="mainUI">
    <div class="header">
      <h1><i class="fas fa-user-circle"></i> Denver1769, <?= htmlspecialchars($fname) ?>!</h1>
      <div class="status-badge">
        <i class="fas fa-check-circle"></i>
        Account Status: Active
      </div>
    </div>

    <div class="cards-grid">
      <div class="card">
        <div class="card-icon">
          <i class="fas fa-user"></i>
        </div>
        <h3>User Name</h3>
        <p><?= htmlspecialchars($fname) ?></p>
      </div>

      <div class="card">
        <div class="card-icon">
          <i class="fas fa-calendar-alt"></i>
        </div>
        <h3>Expiry Date</h3>
        <p><?= htmlspecialchars($endDate) ?></p>
      </div>

      <div class="card">
        <div class="card-icon">
          <i class="fas fa-crown"></i>
        </div>
        <h3>Plan</h3>
        <p><?= htmlspecialchars($tariffPlan) ?></p>
      </div>
    </div>

    <div class="url-section">
      <h3><i class="fas fa-link"></i> Playlist URL</h3>
      <div class="url-input-group">
        <input type="text" id="playlistUrl" class="url-input" value="<?= htmlspecialchars($playlistUrl) ?>" readonly onclick="this.select()" />
        <button class="btn btn-primary" onclick="copyToClipboard()">
          <i class="fas fa-copy"></i>
          Copy URL
        </button>
        <a href="custom.php" class="btn btn-secondary">
          <i class="fas fa-cog"></i>
          Custom File
        </a>
      </div>
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

    function copyToClipboard() {
    const input = document.getElementById('playlistUrl');
    input.select();
    input.setSelectionRange(0, 99999);
    document.execCommand("copy");
    alert("URL copied to clipboard!");
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

    window.onload = () => {
      createParticles();
      
      setTimeout(() => {
        const splash = document.getElementById("splash");
        if(splash) {
            splash.classList.add("fade-out");
            
            setTimeout(() => {
              splash.style.display = "none";
              const mainUI = document.getElementById("mainUI");
              if(mainUI) mainUI.style.display = "block";
            }, 600);
        }
      }, 1500);
    };
  </script>
</body>
</html>