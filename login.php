<?php
$appName = "Team Hifsa";
$assetVersion = "20260317-65";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="todo-api-base" content="">
  <title>Login | <?php echo $appName; ?></title>
  <link rel="icon" href="data:image/x-icon;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQEAAAAMAtDSzAAAAAEElEQVR42mNkIAAYGBAAAQAA/wEAgP8AAAAASUVORK5CYII=">
  
  <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' media='all' />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css?v=<?php echo $assetVersion; ?>">
  <script src="https://accounts.google.com/gsi/client" async defer></script>

  <style>
    :root {
      --primary-blue: #2563eb;
      --bg-dark: #000000;
      --card-bg: #111111;
      --text-main: #ffffff;
      --text-muted: #94a3b8;
    }

    body {
      background-color: var(--bg-dark);
      color: var(--text-main);
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif !important;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
    }

    .auth-panel {
      background: var(--card-bg);
      border: 1px solid #333;
      padding: 40px;
      border-radius: 20px;
      width: 100%;
      max-width: 450px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }

    .auth-title {
      color: var(--primary-blue);
      font-weight: 800;
      text-align: center;
      margin-bottom: 10px;
    }

    .auth-subtitle {
      color: var(--text-muted);
      text-align: center;
      margin-bottom: 30px;
    }

    .auth-mode-toggle {
      background: #1e293b;
      padding: 5px;
      border-radius: 12px;
      display: flex;
      margin-bottom: 30px;
    }

    .auth-mode-btn {
      flex: 1;
      border: none;
      background: transparent;
      color: var(--text-muted);
      padding: 10px;
      border-radius: 8px;
      font-weight: 600;
      transition: all 0.2s;
    }

    .auth-mode-btn.active {
      background: var(--primary-blue);
      color: white;
    }

    .auth-label {
      color: var(--text-main);
      font-weight: 600;
      margin-bottom: 8px;
      display: block;
    }

    input[type="text"], input[type="email"], input[type="password"], input[type="number"] {
      background: #1e293b !important;
      border: 1px solid #334155 !important;
      color: white !important;
      padding: 12px 16px;
      border-radius: 10px;
      width: 100%;
      margin-bottom: 20px;
    }

    input:focus {
      border-color: var(--primary-blue) !important;
      outline: none;
      box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
    }

    .playlist-btn {
      background: var(--primary-blue);
      color: white;
      border: none;
      padding: 14px;
      border-radius: 10px;
      width: 100%;
      font-weight: 700;
      font-size: 1rem;
      cursor: pointer;
      transition: background 0.2s;
    }

    .playlist-btn:hover {
      background: #1d4ed8;
    }

    .auth-switch-row {
      text-align: center;
      margin-top: 24px;
      color: var(--text-muted);
    }

    .auth-switch-btn {
      background: none;
      border: none;
      color: var(--primary-blue);
      font-weight: 700;
      cursor: pointer;
      padding: 0;
      margin-left: 5px;
    }

    .auth-switch-btn:hover {
      text-decoration: underline;
    }

    #google-login-row {
      border-top: 1px solid #333;
      padding-top: 20px;
      margin-top: 20px;
    }
  </style>
</head>
<body data-page="login">
  <div id="auth-panel" class="auth-panel">
    <div class="text-center mb-4">
        <h1 style="color: var(--primary-blue); font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 10px;">
            <i class="fas fa-check-double"></i> <?php echo $appName; ?>
        </h1>
    </div>
    
    <h2 id="auth-title" class="auth-title">Welcome Back</h2>
    <p id="auth-subtitle" class="auth-subtitle">Sign in to continue managing your tasks.</p>
    
    <div id="auth-status" class="playlist-status"></div>
    
    <div class="auth-mode-toggle">
      <button type="button" id="auth-mode-login-btn" class="auth-mode-btn active">Login</button>
      <button type="button" id="auth-mode-register-btn" class="auth-mode-btn">Register</button>
    </div>

    <!-- Verification OTP Section -->
    <form id="otp-form" class="auth-form" style="display: none;">
      <h2 class="auth-title">Verify Email</h2>
      <p style="text-align: center; color: var(--text-muted); margin-bottom: 24px; font-size: 0.9rem;">
        Code sent to <strong id="otp-email-display" class="text-white">your email</strong>.
      </p>
      <div class="auth-field">
        <label class="auth-label">Verification Code</label>
        <input type="text" id="auth-otp" placeholder="000000" maxlength="6" pattern="\d{6}" style="text-align: center; letter-spacing: 8px; font-size: 1.5rem; font-weight: bold;">
      </div>
      <button type="submit" id="otp-submit-btn" class="playlist-btn">Verify & Login</button>
      <p class="auth-switch-row">
        No code? <a href="#" onclick="location.reload()" class="text-primary">Try again</a>
      </p>
    </form>

    <form id="auth-form" class="auth-form">
      <div id="auth-name-field" class="auth-field app-hidden">
        <label for="auth-name" class="auth-label">Full Name</label>
        <input type="text" id="auth-name" placeholder="John Doe">
      </div>
      <div class="auth-field">
        <label for="auth-email" class="auth-label">Email</label>
        <input type="email" id="auth-email" placeholder="name@example.com">
      </div>
      <div class="auth-field">
        <label for="auth-password" class="auth-label">Password</label>
        <input type="password" id="auth-password" placeholder="••••••••">
      </div>
      <div id="auth-captcha-field" class="auth-field">
        <label for="auth-captcha" class="auth-label">Human Check: <span id="captcha-question" class="text-primary"></span></label>
        <input type="number" id="auth-captcha" placeholder="?">
      </div>
      <button type="submit" id="auth-submit-btn" class="playlist-btn">Login</button>
    </form>

    <div id="google-login-row" class="auth-switch-row" style="display: none;">
      <div id="g_id_onload" data-client_id="" data-callback="handleGoogleLogin" data-auto_prompt="false"></div>
      <div class="g_id_signin" data-type="standard" data-size="large" data-theme="filled_blue" data-text="sign_in_with" data-shape="rectangular" data-logo_alignment="left" data-width="100%"></div>
    </div>

    <p class="auth-switch-row">
      <span id="auth-switch-label">Don’t have an account?</span>
      <button type="button" id="auth-switch-btn" class="auth-switch-btn">Create account</button>
    </p>
  </div>

  <div id="app-container" class="app-container app-hidden"></div>
  <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js'></script>
  <script src="script.js?v=<?php echo $assetVersion; ?>"></script>
</body>
</html>
