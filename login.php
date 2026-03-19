<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="todo-api-base" content="">
  <title>Login | Premium To-Do App</title>
  <link rel="icon" href="data:image/x-icon;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQEAAAAMAtDSzAAAAAEElEQVR42mNkIAAYGBAAAQAA/wEAgP8AAAAASUVORK5CYII=">
  <link rel="stylesheet" href="style.css?v=20260317-53">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body data-page="login">
  <div id="auth-panel" class="auth-panel">
    <h2 id="auth-title">Welcome Back</h2>
    <p id="auth-subtitle" class="auth-subtitle">Sign in to continue managing your tasks.</p>
    <div id="auth-status" class="playlist-status"></div>
    <div class="auth-mode-toggle">
      <button type="button" id="auth-mode-login-btn" class="auth-mode-btn active">Login</button>
      <button type="button" id="auth-mode-register-btn" class="auth-mode-btn">Register</button>
    </div>
    <!-- Verification OTP Section (Hidden by default) -->
    <form id="otp-form" class="auth-form" style="display: none;">
      <h2 class="auth-title">Verify Your Email</h2>
      <p style="text-align: center; color: #64748b; margin-bottom: 24px; font-size: 0.9rem;">
        We've sent a 6-digit verification code to <br><strong id="otp-email-display">your email</strong>.
      </p>
      <div class="auth-field">
        <label class="auth-label">Verification Code</label>
        <input type="text" id="auth-otp" placeholder="Enter 6-digit code" maxlength="6" pattern="\d{6}" style="text-align: center; letter-spacing: 8px; font-size: 1.5rem; font-weight: bold;">
      </div>
      <button type="submit" id="otp-submit-btn" class="playlist-btn">Verify & Login</button>
      <p class="auth-switch-row">
        Didn't receive code? <a href="#" onclick="location.reload()">Try again</a>
      </p>
    </form>

    <form id="auth-form" class="auth-form">
      <div id="auth-name-field" class="auth-field app-hidden">
        <label for="auth-name" class="auth-label">Full Name</label>
        <input type="text" id="auth-name" placeholder="Enter your full name">
      </div>
      <div class="auth-field">
        <label for="auth-email" class="auth-label">Email</label>
        <input type="email" id="auth-email" placeholder="Enter your email">
      </div>
      <div class="auth-field">
        <label for="auth-password" class="auth-label">Password</label>
        <input type="password" id="auth-password" placeholder="Enter your password">
      </div>
      <div id="auth-captcha-field" class="auth-field">
        <label for="auth-captcha" class="auth-label">Verify you are human: <span id="captcha-question"></span></label>
        <input type="number" id="auth-captcha" placeholder="Enter answer">
      </div>
      <button type="submit" id="auth-submit-btn" class="playlist-btn">Login</button>
    </form>
    <div id="google-login-row" class="auth-switch-row" style="margin-top: 16px; display: none;">
      <div id="g_id_onload"
           data-client_id=""
           data-callback="handleGoogleLogin"
           data-auto_prompt="false">
      </div>
      <div class="g_id_signin"
           data-type="standard"
           data-size="large"
           data-theme="outline"
           data-text="sign_in_with"
           data-shape="rectangular"
           data-logo_alignment="left"
           data-width="100%">
      </div>
    </div>
    <p class="auth-switch-row">
      <span id="auth-switch-label">Don’t have an account?</span>
      <button type="button" id="auth-switch-btn" class="auth-switch-btn">Create account</button>
    </p>
  </div>

  <div id="app-container" class="app-container app-hidden"></div>
  <script src="script.js?v=20260317-53"></script>
</body>
</html>
