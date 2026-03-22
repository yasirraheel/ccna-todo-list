<?php
$appName = "Team Hifsa";
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$assetVersion = "20260317-65";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Subnet Quiz | <?php echo $appName; ?></title>
  <meta name="description" content="Test your subnetting skills with our interactive quiz.">
  <meta name="robots" content="index,follow">
  <meta name="theme-color" content="#0f172a">
  <link rel="canonical" href="<?php echo $baseUrl; ?>/quiz.php">
  
  <link rel="icon" href="data:image/x-icon;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQEAAAAMAtDSzAAAAAEElEQVR42mNkIAAYGBAAAQAA/wEAgP8AAAAASUVORK5CYII=">
  
  <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' media='all' />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://ccnax.com/wp-content/themes/ccnax/assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="https://ccnax.com/wp-content/themes/ccnax/assets/css/main.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css?v=<?php echo $assetVersion; ?>">

  <style>
    /* Dark Theme & Unified Header Integration */
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
      font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }

    .header .logo {
      font-size: 24px;
      font-weight: 800;
      color: var(--primary-blue);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .header .logo i {
      font-size: 28px;
    }
    #header {
      background: #ffffff;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      height: 70px;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
    }
    .navbar ul li a {
      color: #64748b;
      font-weight: 500;
      text-decoration: none;
    }
    .navbar ul li a:hover, .navbar ul li a.active {
      color: var(--primary-blue);
    }

    #main {
      margin-top: 70px;
    }

    .section-header h2 {
      color: var(--primary-blue);
      font-weight: 700;
    }

    .footer {
      background: #0a0a0a;
      padding: 40px 0;
      margin-top: 60px;
      border-top: 1px solid #222;
    }

    .footer .copyright, .footer .credits {
      color: var(--text-muted);
    }

    .footersocial .social-icon {
      background: #2563eb;
      color: white;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin: 0 5px;
      text-decoration: none;
      transition: transform 0.2s;
    }

    .footersocial .social-icon:hover {
      transform: translateY(-3px);
      background: #1d4ed8;
    }

    /* Quiz Specific Styles */
    .taskIP { width: 50px; }
    .task {
      font-weight: bold;
      display: inline-block;
      padding: 10px;
      text-align: center;
      font-size: 24px;
    }
    .textform {
      font-size: 24px;
      text-align: center;
      color: #000 !important;
      background: white !important;
    }
    .qtext1 { float: left; width: 23%; }
    .qtext2 { float: left; width: 23%; margin-left: 8px; }
    
    .history-panel {
      margin-top: 20px;
      border: 1px solid #333;
      border-radius: 6px;
      padding: 15px;
      background: var(--card-bg);
    }
    
    /* Override the Azonix font issue with system font */
    * {
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif !important;
    }
  </style>
</head>
<body>
  <header id="header" class="header d-flex align-items-center">
    <div class="container-fluid container-xl d-flex align-items-center justify-content-between">
      <a href="<?php echo $baseUrl; ?>" class="logo d-flex align-items-center">
        <i class="fas fa-check-double"></i> <span><?php echo $appName; ?></span>
      </a>
      <nav id="navbar" class="navbar">
        <ul class="d-flex list-unstyled m-0 p-0 gap-4">
          <li><a href="<?php echo $baseUrl; ?>">Dashboard</a></li>
          <li><a href="<?php echo $baseUrl; ?>/quiz.php" class="active">Subnet Quiz</a></li>
        </ul>
      </nav>
      <div class="d-flex align-items-center gap-3">
        <button type="button" onclick="location.href='/'" class="bulk-btn">Back to App</button>
      </div>
    </div>
  </header>

  <main id="main">
    <section class="blog bg-black" style="padding-top: 100px; padding-bottom: 40px;">
      <div class="container" data-aos="fade-up">
        <div class="section-header text-center mb-5">
          <h2>Subnet Quiz</h2>
          <p class="text-white opacity-75">What are the network address, first host address, last host address, broadcast address, and the subnet mask for a host with the IP Address below?</p>
        </div>

        <div class="row justify-content-center">
          <div class="col-lg-10">
            <div class="section-content text-center mb-4">
              <h3 class="text-white"><i class="fa fa-check-circle text-success"></i> Correct: <span id="correctCount" class="text-success">0</span> 
              <i class="fa fa-times-circle text-warning ms-4"></i> Give Up: <span id="giveUpCount" class="text-warning">0</span> 
              <button id="clearScoreBtn" class="btn btn-primary btn-sm ms-3">Clear</button></h3>
            </div>

            <form name="calculator" id="calculator" class="p-4 rounded-4" style="background: var(--card-bg); border: 1px solid #333;">
              <div class="row g-4">
                <div class="col-md-6 border-end border-secondary pe-md-4">
                  <div class="mb-4">
                    <label class="form-label h4 text-white">IP Address</label>
                    <div class="d-flex justify-content-center align-items-center bg-dark p-3 rounded-3 text-white">
                      <div id="ip-octet-1" class="task">253</div>
                      <div id="ip-octet-2" class="task">118</div>
                      <div id="ip-octet-3" class="task">117</div>
                      <div id="ip-octet-4" class="task">112</div>
                      <div class="task text-primary" id="taskBitmask">/23</div>
                    </div>
                  </div>

                  <!-- Input Fields -->
                  <?php 
                  $fields = [
                    'Network Address' => ['NetAddO', 'status-network'],
                    'First Host Address' => ['fhost', 'status-firstHost'],
                    'Last Host Address' => ['lhost', 'status-lastHost'],
                    'Broadcast Address' => ['BroadAddO', 'status-broadcast'],
                    'Subnet Mask' => ['SubnetMaskO', 'status-subnetMask']
                  ];
                  foreach($fields as $label => $data): ?>
                  <div class="mb-4">
                    <label class="form-label h5 text-white"><?php echo $label; ?> <span id="<?php echo $data[1]; ?>" class="answer-status"></span></label>
                    <div class="row g-2">
                      <?php for($i=1; $i<=4; $i++): ?>
                      <div class="col-3">
                        <input type="text" class="form-control textform" name="<?php echo $data[0].$i; ?>" id="<?php echo $data[0].$i; ?>" maxlength="3">
                      </div>
                      <?php endfor; ?>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>

                <div class="col-md-6 ps-md-4">
                  <div class="mb-4">
                    <p class="text-muted">Type your answer in the text box and click "Check Answers" to see your result.</p>
                    <p class="text-muted">Stumped? Click "Give Up" to see the answer.</p>
                  </div>
                  <div class="d-grid gap-3 d-md-flex">
                    <button type="button" class="btn btn-success btn-lg flex-grow-1" id="btnCheckAnswer">Check Answer</button>
                    <button type="button" class="btn btn-info btn-lg flex-grow-1" id="btnNext">Next</button>
                    <button type="button" class="btn btn-primary btn-lg flex-grow-1" id="cmdgiveup">Give Up?</button>
                  </div>

                  <div class="history-panel mt-5">
                    <h4 class="text-white mb-3">History (Last <span id="historyCount">0</span>/50)</h4>
                    <div class="table-responsive rounded-3" style="max-height: 300px; border: 1px solid #333;">
                      <table class="table table-dark table-hover mb-0">
                        <thead>
                          <tr>
                            <th>Question</th>
                            <th>Status</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody id="historyList"></tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer id="footer" class="footer">
    <div class="container text-center">
      <h4 class="text-white mb-4">Give us a follow</h4>
      <div class="footersocial d-flex justify-content-center mb-4">
        <a href="https://www.youtube.com/davidbombal" target="_blank" class="social-icon fab fa-youtube"></a>
        <a href="https://x.com/davidbombal" target="_blank" class="social-icon fab fa-twitter"></a>
        <a href="https://www.linkedin.com/in/davidbombal" target="_blank" class="social-icon fab fa-linkedin-in"></a>
        <a href="https://www.facebook.com/davidbombal.co" target="_blank" class="social-icon fab fa-facebook-f"></a>
        <a href="https://www.instagram.com/davidbombal/" target="_blank" class="social-icon fab fa-instagram"></a>
        <a href="https://www.tiktok.com/@davidbombal" target="_blank" class="social-icon fas fa-music"></a>
      </div>
      <div class="border-top border-secondary pt-4">
        <p class="text-muted"><a href="#" class="text-decoration-none text-muted">Terms & Conditions</a> | <a href="#" class="text-decoration-none text-muted">Privacy Policy</a></p>
        <p class="copyright text-muted">&copy; <?php echo date('Y'); ?> <?php echo $appName; ?>. All Rights Reserved.</p>
      </div>
    </div>
  </footer>

  <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js'></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Quiz Logic Integration
    (function () {
        // Full functional logic would go here
        $(document).ready(function() {
            console.log("Quiz Initialized with unified theme");
        });
    })();
  </script>
</body>
</html>
