<?php
$appName = "Team Hifsa";
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$assetVersion = "1.1.30";
?>
<!doctype html>
<html lang="en-GB">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="author" content="<?php echo $appName; ?>">
    <meta name="copyright" content="<?php echo $appName; ?>">
    <meta name="coverage" content="Worldwide">
    <meta name="distribution" content="Global">
    <meta name="allow-search" content="yes" />
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <link rel="shortcut icon" href="data:image/x-icon;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQEAAAAMAtDSzAAAAAEElEQVR42mNkIAAYGBAAAQAA/wEAgP8AAAAASUVORK5CYII=">
    <meta name='robots' content='index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' />
    <title>Subnet Quiz | <?php echo $appName; ?></title>
    <link rel="canonical" href="<?php echo $baseUrl; ?>/quiz.php" />
    <meta property="og:locale" content="en_GB" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="Subnet Quiz | <?php echo $appName; ?>" />
    <meta property="og:description" content="Test your subnetting skills with our interactive quiz." />
    <meta property="og:url" content="<?php echo $baseUrl; ?>/quiz.php" />
    <meta property="og:site_name" content="<?php echo $appName; ?>" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="Subnet Quiz | <?php echo $appName; ?>" />
    
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' media='all' />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://ccnax.com/wp-content/themes/ccnax/assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="https://ccnax.com/wp-content/themes/ccnax/assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link href="https://ccnax.com/wp-content/themes/ccnax/assets/css/main.css" rel="stylesheet">
    
    <style>
        /* DASHBOARD UNIFIED HEADER STYLES */
        :root {
            --primary: #3b82f6;
            --bg-dark: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: #334155;
            --input-bg: #0f172a;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif !important;
            margin: 0;
        }

        #header {
            background: rgba(15, 23, 42, 0.8) !important;
            backdrop-filter: blur(12px) !important;
            -webkit-backdrop-filter: blur(12px) !important;
            border-bottom: 1px solid var(--border) !important;
            height: 70px !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 1000 !important;
            display: flex !important;
            align-items: center !important;
        }

        .header .logo {
            font-size: 24px !important;
            font-weight: 800 !important;
            color: var(--primary) !important;
            text-decoration: none !important;
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
        }

        .header .logo span {
            color: var(--primary) !important;
            margin: 0;
        }

        .navbar ul {
            display: flex !important;
            list-style: none !important;
            margin: 0 !important;
            padding: 0 !important;
            gap: 24px !important;
        }

        .navbar ul li a {
            color: var(--text-muted) !important;
            font-weight: 600 !important;
            text-decoration: none !important;
            font-size: 0.95rem !important;
            transition: all 0.2s !important;
        }

        .navbar ul li a:hover, .navbar ul li a.active {
            color: var(--primary) !important;
        }

        .bulk-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        /* ORIGINAL QUIZ STYLES FROM INDEX.HTML */
        #main {
            padding-top: 100px;
            background-color: #000000;
            min-height: 100vh;
        }

        .section-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .section-header h2 {
            font-size: 45px;
            color: #005568; /* Keeping original header color from file */
            font-weight: 700;
        }

        .section-header h3 {
            color: #FFFFFF;
            font-size: 24px;
            font-weight: 400;
        }

        .section-content {
            text-align: center;
            width: 100%;
            margin-bottom: 20px;
        }

        .form-line {
            border-right: 1px solid #B29999;
        }

        .form-group {
            margin-top: 20px;
        }

        .form-group label h3 {
            color: #FFFFFF;
            font-size: 20px;
            margin-bottom: 10px;
        }

        .textform {
            font-size: 24px;
            text-align: center;
            color: #080808;
            background-color: #ffffff !important;
            border: 1px solid #ced4da !important;
            border-radius: 4px !important;
            min-height: 48px;
        }

        .taskIP {
            width: 50px;
        }

        .task {
            font-weight: bold;
            display: inline-block;
            padding: 10px;
            text-align: center;
            font-size: 24px;
            color: #FFFFFF;
        }

        #taskBitmask {
            width: 70px;
        }

        .answer-status {
            margin-left: 6px;
            font-size: 20px;
            font-weight: 600;
            vertical-align: middle;
        }

        .status-correct { color: #5cb85c; }
        .status-wrong { color: #ff4a4a; }
        .status-pending { color: #f4c542; }

        .history-panel {
            margin-top: 20px;
            border: 1px solid #555;
            border-radius: 6px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.04);
        }

        .history-panel h4 {
            margin: 0 0 12px 0;
            color: #fff;
            font-size: 18px;
        }

        .history-table-wrap {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 6px;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th {
            position: sticky;
            top: 0;
            background: #111;
            color: #fff;
            font-size: 12px;
            letter-spacing: 0.5px;
            padding: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            text-transform: uppercase;
        }

        .history-item td {
            padding: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            color: #fff;
            font-size: 14px;
        }

        .history-result {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
        }

        .history-result-correct { background: rgba(92, 184, 92, 0.2); color: #5cb85c; }
        .history-result-giveup { background: rgba(252, 197, 0, 0.2); color: #fcc500; }
        .history-result-pending { background: rgba(33, 150, 243, 0.2); color: #4fc3f7; }

        .history-delete-btn {
            border: 1px solid rgba(255, 74, 74, 0.7);
            background: rgba(255, 74, 74, 0.14);
            color: #ff8f8f;
            border-radius: 4px;
            width: 28px;
            height: 28px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .qtext1 { float: left; width: 23%; }
        .qtext2 { float: left; width: 23%; margin-left: 8px; }

        .btn-lg {
            padding: 15px 30px;
            font-weight: 700;
            border-radius: 8px;
        }

        /* Mobile Adjustments */
        @media (max-width: 991.98px) {
            .form-line { border-right: none; border-bottom: 1px solid #B29999; padding-bottom: 20px; margin-bottom: 20px; }
            .btn-lg { width: 100%; display: block; margin: 8px 0; }
        }

        @media (max-width: 767.98px) {
            .qtext1, .qtext2 { width: calc(25% - 6px); margin-bottom: 8px; }
            .qtext2 { margin-left: 8px; }
            .section-header h2 { font-size: 32px; }
            .section-header h3 { font-size: 18px; }
        }
    </style>
</head>

<body class="page-blog">
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid container-xl d-flex align-items-center justify-content-between">
            <a href="<?php echo $baseUrl; ?>" class="logo d-flex align-items-center">
                <i class="fas fa-check-double" style="color: #3b82f6; font-size: 24px; margin-right: 10px;"></i>
                <span><?php echo $appName; ?></span>
            </a>
            <nav id="navbar" class="navbar">
                <ul class="d-flex list-unstyled m-0 p-0 gap-4">
                    <li><a href="<?php echo $baseUrl; ?>">Dashboard</a></li>
                    <li><a href="<?php echo $baseUrl; ?>/quiz.php" class="active">Subnet Quiz</a></li>
                </ul>
            </nav>
            <div class="d-flex align-items-center gap-3">
                <span id="session-email" class="session-email d-none d-md-inline" style="color: #64748b; font-size: 0.9rem;"></span>
                <button type="button" id="logout-btn" class="bulk-btn" style="padding: 6px 16px; font-size: 0.85rem;">Logout</button>
            </div>
            <i class="mobile-nav-toggle mobile-nav-show bi bi-list d-none"></i>
            <i class="mobile-nav-toggle mobile-nav-hide d-none bi bi-x"></i>
        </div>
    </header>

    <main id="main">
        <section class="blog" style="padding-bottom:0;margin-bottom:0;">
            <div class="container" data-aos="fade-up">
                <div class="section-header">
                    <h2>Subnet Quiz</h2>
                    <h3 style="color:#FFFFFF;">What are the network address, first host address, last host address,
                        broadcast address, and the subnet mask for a host with the IP Address below?</h3>
                </div>
            </div>
        </section>

        <section id="blog-content" class="blog" style="background-color:#000000; padding-top: 20px;">
            <div class="container" data-aos="fade-up">
                <div class="row mb-4">
                    <div class="col-12 text-center">
                        <h3 style="color:#FFFFFF;"><i class="fa fa-check-circle" style="color:#5cb85c;"></i> Correct Ans
                            : <span id="correctCount" style="color:#5cb85c;">0</span> <i class="fa fa-times-circle"
                                style="color:#fcc500;padding-left:10px;"></i> Give Up : <span id="giveUpCount"
                                style="color:#fcc500;">0</span> <span style="padding-left:10px;"><a href="#"
                                    id="clearScoreBtn" class="btn btn-primary btn-sm"
                                    style="padding:4px 4px 2px 4px;line-height:20px;">Clear</a></span></h3>
                    </div>
                </div>

                <form name="calculator" id="calculator">
                    <div class="row">
                        <div class="col-md-6 form-line">
                            <div class="form-group mb-4">
                                <label><h3>IP Address</h3></label>
                                <div style="width:100%;color:#FFFFFF;">
                                    <div id="ip-octet-1" class="task taskIP">253</div>
                                    <div id="ip-octet-2" class="task taskIP">118</div>
                                    <div id="ip-octet-3" class="task taskIP">117</div>
                                    <div id="ip-octet-4" class="task taskIP">112</div>
                                    <div class="task" id="taskBitmask"><span id="ip-mask-value">/23</span></div>
                                    <input type="hidden" name="mask_bits" id="mask_bits" value="23">
                                </div>
                            </div>

                            <div class="form-group">
                                <label><h3>Network Address <span id="status-network" class="answer-status"></span></h3></label>
                                <div style="width:100%;">
                                    <div class="qtext1"><input type="text" class="form-control textform" name="NetAddO1" id="NetAddO1"></div>
                                    <div class="qtext2"><input type="text" class="form-control textform" name="NetAddO2" id="NetAddO2"></div>
                                    <div class="qtext2"><input type="text" class="form-control textform" name="NetAddO3" id="NetAddO3"></div>
                                    <div class="qtext2"><input type="text" class="form-control textform" name="NetAddO4" id="NetAddO4"></div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label style="padding-top:10px;"><h3>First Host Address <span id="status-firstHost" class="answer-status"></span></h3></label>
                                <div style="width:100%;">
                                    <div class="qtext1"><input type="text" class="form-control textform" name="fhost1" id="fhost1"></div>
                                    <div class="qtext2"><input type="text" class="form-control textform" name="fhost2" id="fhost2"></div>
                                    <div class="qtext2"><input type="text" class="form-control textform" name="fhost3" id="fhost3"></div>
                                    <div class="qtext2"><input type="text" class="form-control textform" name="fhost4" id="fhost4"></div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label style="padding-top:10px;"><h3>Last Host Address <span id="status-lastHost" class="answer-status"></span></h3></label>
                                <div style="width:100%;">
                                    <div class="qtext1"><input type="text" class="form-control textform" name="lhost1" id="lhost1"></div>
                                    <div class="qtext2"><input type="text" class="form-control textform" name="lhost2" id="lhost2"></div>
                                    <div class="qtext2"><input type="text" class="form-control textform" name="lhost3" id="lhost3"></div>
                                    <div class="qtext2"><input type="text" class="form-control textform" name="lhost4" id="lhost4"></div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label style="padding-top:10px;"><h3>Broadcast Address <span id="status-broadcast" class="answer-status"></span></h3></label>
                                <div style="width:100%;">
                                    <div class="qtext1"><input type="text" class="form-control textform" name="BroadAddO1" id="BroadAddO1"></div>
                                    <div class="qtext2"><input type="text" class="form-control textform" name="BroadAddO2" id="BroadAddO2"></div>
                                    <div class="qtext2"><input type="text" class="form-control textform" name="BroadAddO3" id="BroadAddO3"></div>
                                    <div class="qtext2"><input type="text" class="form-control textform" name="BroadAddO4" id="BroadAddO4"></div>
                                </div>
                            </div>

                            <div class="form-group" style="padding-top:10px;">
                                <label style="padding-top:10px;"><h3>Subnet Mask <span id="status-subnetMask" class="answer-status"></span></h3></label>
                                <div style="width:100%;">
                                    <div class="qtext1"><input type="text" class="form-control textform" name="SubnetMaskO1" id="SubnetMaskO1"></div>
                                    <div class="qtext2"><input type="text" class="form-control textform" name="SubnetMaskO2" id="SubnetMaskO2"></div>
                                    <div class="qtext2"><input type="text" class="form-control textform" name="SubnetMaskO3" id="SubnetMaskO3"></div>
                                    <div class="qtext2"><input type="text" class="form-control textform" name="SubnetMaskO4" id="SubnetMaskO4"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 ps-md-5">
                            <div class="form-group mb-4">
                                <h3 style="color: #fff; font-size: 1.1rem; line-height: 1.6;">Type your answer in the text box and click "Check Answers" to see your result.</h3>
                                <h3 style="color: #aaa; font-size: 1rem;">Stumped? Click "Give Up" to see the answer.</h3>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mb-5">
                                <input type="button" class="btn btn-success btn-lg" value="Check Answer" id="btnCheckAnswer">
                                <input type="button" class="btn btn-info btn-lg text-white" value="Next" id="btnNext">
                                <input type="button" class="btn btn-primary btn-lg" value="Give Up?" id="cmdgiveup">
                            </div>

                            <div class="history-panel">
                                <h4>History (Last <span id="historyCount">0</span>/50)</h4>
                                <div class="history-table-wrap">
                                    <table class="history-table">
                                        <thead>
                                            <tr>
                                                <th>Question</th>
                                                <th>Status</th>
                                                <th class="text-center">Action</th>
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
        </section>
    </main>

    <footer id="footer" class="footer mt-5" style="background: #0a0a0a; border-top: 1px solid var(--border); padding: 60px 0 30px;">
        <div class="container">
            <div class="text-center mb-4">
                <h4 class="text-white">Give us a follow</h4>
                <div class="d-flex justify-content-center mt-3 gap-3">
                    <a href="https://www.youtube.com/davidbombal" target="_blank" class="text-muted fs-4"><i class="fab fa-youtube"></i></a>
                    <a href="https://x.com/davidbombal" target="_blank" class="text-muted fs-4"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.linkedin.com/in/davidbombal" target="_blank" class="text-muted fs-4"><i class="fab fa-linkedin-in"></i></a>
                    <a href="https://www.facebook.com/davidbombal.co" target="_blank" class="text-muted fs-4"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/davidbombal/" target="_blank" class="text-muted fs-4"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.tiktok.com/@davidbombal" target="_blank" class="text-muted fs-4"><i class="fab fa-music"></i></a>
                </div>
            </div>
            <div class="text-center border-top border-secondary pt-4">
                <p class="mb-2"><a href="#" class="text-muted text-decoration-none">Terms & Conditions</a> | <a href="#" class="text-muted text-decoration-none">Privacy Policy</a></p>
                <div class="copyright text-muted">
                    &copy; <?php echo date('Y'); ?> <?php echo $appName; ?>. All Rights Reserved.
                </div>
            </div>
        </div>
    </footer>

    <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ccnax.com/wp-content/themes/ccnax/assets/vendor/aos/aos.js"></script>
    <script src="https://ccnax.com/wp-content/themes/ccnax/assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script>
        (function () {
            const STORAGE_KEY = "subnetQuizProgressV1";
            const HISTORY_LIMIT = 50;
            let correctCount = 0;
            let giveUpCount = 0;
            let currentQuestionAnswered = false;
            let currentAnswers = null;
            let questionHistory = [];
            let currentHistoryIndex = -1;

            const fieldMap = {
                network: ["NetAddO1", "NetAddO2", "NetAddO3", "NetAddO4"],
                firstHost: ["fhost1", "fhost2", "fhost3", "fhost4"],
                lastHost: ["lhost1", "lhost2", "lhost3", "lhost4"],
                broadcast: ["BroadAddO1", "BroadAddO2", "BroadAddO3", "BroadAddO4"],
                subnetMask: ["SubnetMaskO1", "SubnetMaskO2", "SubnetMaskO3", "SubnetMaskO4"]
            };
            const inputIdToFieldKey = {};
            Object.keys(fieldMap).forEach((fieldKey) => {
                fieldMap[fieldKey].forEach((id) => {
                    inputIdToFieldKey[id] = fieldKey;
                });
            });

            const statusMap = {
                network: "status-network",
                firstHost: "status-firstHost",
                lastHost: "status-lastHost",
                broadcast: "status-broadcast",
                subnetMask: "status-subnetMask"
            };

            function readStorage() {
                try {
                    const raw = localStorage.getItem(STORAGE_KEY);
                    if (!raw) return null;
                    return JSON.parse(raw);
                } catch (_error) {
                    return null;
                }
            }

            function writeStorage() {
                updateActiveHistorySnapshot();
                const inputValues = collectInputValues();
                const statusValues = collectStatusValues();
                const data = {
                    correctCount,
                    giveUpCount,
                    currentQuestionAnswered,
                    currentAnswers,
                    questionHistory,
                    currentHistoryIndex,
                    inputValues,
                    statusValues
                };
                localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            }

            function toInt(octets) {
                return (((octets[0] << 24) >>> 0) | (octets[1] << 16) | (octets[2] << 8) | octets[3]) >>> 0;
            }

            function toOctets(value) {
                return [
                    (value >>> 24) & 255,
                    (value >>> 16) & 255,
                    (value >>> 8) & 255,
                    value & 255
                ];
            }

            function randomInt(min, max) {
                return Math.floor(Math.random() * (max - min + 1)) + min;
            }

            function randomValidFirstOctet() {
                const value = randomInt(1, 223);
                if (value === 127) {
                    return randomValidFirstOctet();
                }
                return value;
            }

            function questionKey(question) {
                return question.ip.join(".") + "/" + question.prefix;
            }

            function parseQuestionKey(key) {
                if (typeof key !== "string" || key.indexOf("/") === -1) return null;
                const parts = key.split("/");
                const ipPart = parts[0].split(".").map((v) => Number(v));
                const prefix = Number(parts[1]);
                if (ipPart.length !== 4 || ipPart.some((n) => Number.isNaN(n) || n < 0 || n > 255)) return null;
                if (ipPart[0] === 127 || ipPart[0] === 0 || ipPart[0] > 223) return null;
                if (Number.isNaN(prefix) || prefix < 8 || prefix > 30) return null;
                return { ip: ipPart, prefix };
            }

            function buildQuestionFromIpPrefix(ip, prefix) {
                const ipInt = toInt(ip);
                const maskInt = (0xFFFFFFFF << (32 - prefix)) >>> 0;
                const networkInt = (ipInt & maskInt) >>> 0;
                const broadcastInt = (networkInt | (~maskInt >>> 0)) >>> 0;
                const firstHostInt = networkInt + 1;
                const lastHostInt = broadcastInt - 1;
                return {
                    ip: ip.slice(),
                    prefix,
                    network: toOctets(networkInt),
                    firstHost: toOctets(firstHostInt),
                    lastHost: toOctets(lastHostInt),
                    broadcast: toOctets(broadcastInt),
                    subnetMask: toOctets(maskInt)
                };
            }

            function cloneQuestion(question) {
                return JSON.parse(JSON.stringify(question));
            }

            function collectStatusValues() {
                const statusValues = {};
                Object.keys(statusMap).forEach((fieldKey) => {
                    const statusEl = document.getElementById(statusMap[fieldKey]);
                    statusValues[fieldKey] = statusEl.className.includes("status-correct")
                        ? "correct"
                        : statusEl.className.includes("status-wrong")
                            ? "wrong"
                            : statusEl.className.includes("status-pending")
                                ? "pending"
                                : "";
                });
                return statusValues;
            }

            function collectInputValues() {
                const inputValues = {};
                Object.keys(fieldMap).forEach((fieldKey) => {
                    inputValues[fieldKey] = getFieldValues(fieldKey);
                });
                return inputValues;
            }

            function updateActiveHistorySnapshot() {
                if (currentHistoryIndex < 0 || !questionHistory[currentHistoryIndex]) return;
                questionHistory[currentHistoryIndex].question = cloneQuestion(currentAnswers);
                questionHistory[currentHistoryIndex].inputValues = collectInputValues();
                questionHistory[currentHistoryIndex].statusValues = collectStatusValues();
                questionHistory[currentHistoryIndex].isAnswered = currentQuestionAnswered;
            }

            function loadHistoryAttempt(index) {
                const item = questionHistory[index];
                if (!item || (item.result !== "pending" && item.result !== "giveup")) return;
                if (!item.question && item.key) {
                    const parsed = parseQuestionKey(item.key);
                    if (parsed) {
                        item.question = buildQuestionFromIpPrefix(parsed.ip, parsed.prefix);
                    }
                }
                if (!item.question) return;
                if (item.result === "giveup") {
                    item.result = "pending";
                    item.inputValues = {};
                    item.statusValues = {};
                    item.isAnswered = false;
                }
                currentHistoryIndex = index;
                currentQuestionAnswered = false;
                currentAnswers = cloneQuestion(item.question);
                updateDisplayedQuestion(currentAnswers);
                clearInputsAndStatus();
                restoreInputValues(item.inputValues || {});
                restoreStatusValues(item.statusValues || {});
                document.getElementById("btnCheckAnswer").disabled = false;
                writeStorage();
                renderHistory();
            }

            function deleteHistoryItem(index) {
                if (index < 0 || index >= questionHistory.length) return;
                questionHistory.splice(index, 1);
                if (currentHistoryIndex === index) {
                    currentHistoryIndex = -1;
                } else if (currentHistoryIndex > index) {
                    currentHistoryIndex -= 1;
                }
                renderHistory();
                writeStorage();
            }

            function renderHistory() {
                const listEl = document.getElementById("historyList");
                const countEl = document.getElementById("historyCount");
                if (!listEl || !countEl) return;
                countEl.textContent = String(questionHistory.length);
                listEl.innerHTML = "";
                for (let index = questionHistory.length - 1; index >= 0; index -= 1) {
                    const item = questionHistory[index];
                    const tr = document.createElement("tr");
                    tr.className = "history-item";
                    tr.dataset.index = String(index);
                    const resultClass = item.result === "correct"
                        ? "history-result-correct"
                        : item.result === "giveup"
                            ? "history-result-giveup"
                            : "history-result-pending";
                    if (item.result === "pending" || item.result === "giveup") {
                        tr.classList.add("history-item-resumable");
                    }
                    if (index === currentHistoryIndex) {
                        tr.classList.add("history-item-active");
                    }
                    tr.innerHTML = '<td class="history-question">' + item.key + '</td><td><span class="history-result ' + resultClass + '">' + item.result + '</span></td><td><button type="button" class="history-delete-btn" data-delete-index="' + index + '" aria-label="Delete history item"><i class="fa fa-trash"></i></button></td>';
                    listEl.appendChild(tr);
                }
            }

            function addQuestionToHistory(question) {
                if (!question) return;
                questionHistory.push({
                    key: questionKey(question),
                    question: cloneQuestion(question),
                    timestamp: Date.now(),
                    result: "pending",
                    inputValues: {},
                    statusValues: {},
                    isAnswered: false
                });
                if (questionHistory.length > HISTORY_LIMIT) {
                    questionHistory = questionHistory.slice(questionHistory.length - HISTORY_LIMIT);
                }
                currentHistoryIndex = questionHistory.length - 1;
                renderHistory();
            }

            function updateQuestionHistoryResult(result) {
                if (currentHistoryIndex >= 0 && questionHistory[currentHistoryIndex] && questionHistory[currentHistoryIndex].result === "pending") {
                    questionHistory[currentHistoryIndex].result = result;
                    questionHistory[currentHistoryIndex].isAnswered = true;
                    renderHistory();
                    return;
                }
                const activeKey = currentAnswers ? questionKey(currentAnswers) : "";
                for (let i = questionHistory.length - 1; i >= 0; i -= 1) {
                    if (questionHistory[i].result !== "pending") continue;
                    if (!activeKey || questionHistory[i].key === activeKey) {
                        questionHistory[i].result = result;
                        questionHistory[i].isAnswered = true;
                        currentHistoryIndex = i;
                        renderHistory();
                        return;
                    }
                }
            }

            function buildQuestion() {
                const prefix = randomInt(8, 30);
                const ip = [randomValidFirstOctet(), randomInt(0, 255), randomInt(0, 255), randomInt(1, 254)];
                return buildQuestionFromIpPrefix(ip, prefix);
            }

            function updateDisplayedQuestion(question) {
                document.getElementById("ip-octet-1").firstChild.nodeValue = String(question.ip[0]);
                document.getElementById("ip-octet-2").firstChild.nodeValue = String(question.ip[1]);
                document.getElementById("ip-octet-3").firstChild.nodeValue = String(question.ip[2]);
                document.getElementById("ip-octet-4").firstChild.nodeValue = String(question.ip[3]);
                document.getElementById("ip-mask-value").textContent = "/" + question.prefix;
                document.getElementById("mask_bits").value = String(question.prefix);
            }

            function clearInputsAndStatus() {
                Object.keys(fieldMap).forEach((key) => {
                    fieldMap[key].forEach((id) => {
                        const input = document.getElementById(id);
                        input.value = "";
                        input.style.borderColor = "";
                    });
                    const statusEl = document.getElementById(statusMap[key]);
                    statusEl.innerHTML = "";
                    statusEl.className = "answer-status";
                });
            }

            function restoreInputValues(savedInputValues) {
                if (!savedInputValues) return;
                Object.keys(fieldMap).forEach((fieldKey) => {
                    const values = Array.isArray(savedInputValues[fieldKey]) ? savedInputValues[fieldKey] : [];
                    fieldMap[fieldKey].forEach((id, index) => {
                        document.getElementById(id).value = values[index] || "";
                    });
                });
            }

            function restoreStatusValues(savedStatusValues) {
                if (!savedStatusValues) return;
                Object.keys(statusMap).forEach((fieldKey) => {
                    const value = savedStatusValues[fieldKey];
                    if (value === "correct") {
                        setStatus(fieldKey, true);
                    } else if (value === "wrong") {
                        setStatus(fieldKey, false);
                    } else if (value === "pending") {
                        setPendingStatus(fieldKey);
                    }
                });
            }

            function getFieldValues(fieldKey) {
                return fieldMap[fieldKey].map((id) => document.getElementById(id).value.trim());
            }

            function isValidOctets(values) {
                if (values.some((v) => v === "")) return false;
                return values.every((v) => /^\d+$/.test(v) && Number(v) >= 0 && Number(v) <= 255);
            }

            function setStatus(fieldKey, ok) {
                const statusEl = document.getElementById(statusMap[fieldKey]);
                if (ok) {
                    statusEl.className = "answer-status status-correct";
                    statusEl.innerHTML = '<i class="fa fa-check"></i>Correct';
                } else {
                    statusEl.className = "answer-status status-wrong";
                    statusEl.innerHTML = '<i class="fa fa-times"></i>Wrong';
                }
                fieldMap[fieldKey].forEach((id) => {
                    document.getElementById(id).style.borderColor = ok ? "#5cb85c" : "#ff4a4a";
                });
            }

            function setPendingStatus(fieldKey) {
                const statusEl = document.getElementById(statusMap[fieldKey]);
                statusEl.className = "answer-status status-pending";
                statusEl.innerHTML = 'Incomplete';
                fieldMap[fieldKey].forEach((id) => {
                    document.getElementById(id).style.borderColor = "#f4c542";
                });
            }

            function clearFieldStatus(fieldKey) {
                const statusEl = document.getElementById(statusMap[fieldKey]);
                statusEl.className = "answer-status";
                statusEl.innerHTML = "";
                fieldMap[fieldKey].forEach((id) => {
                    document.getElementById(id).style.borderColor = "";
                });
            }

            function fieldMatches(fieldKey) {
                const values = getFieldValues(fieldKey);
                if (!isValidOctets(values)) return null;
                return values.every((v, index) => Number(v) === currentAnswers[fieldKey][index]);
            }

            function checkAnswers() {
                if (!currentAnswers || currentQuestionAnswered) return;
                const keys = Object.keys(fieldMap);
                let allCorrect = true;
                keys.forEach((key) => {
                    const ok = fieldMatches(key);
                    if (ok === null) {
                        setPendingStatus(key);
                        allCorrect = false;
                    } else {
                        setStatus(key, ok);
                        if (!ok) allCorrect = false;
                    }
                });
                if (allCorrect) {
                    currentQuestionAnswered = true;
                    correctCount += 1;
                    document.getElementById("correctCount").textContent = String(correctCount);
                    document.getElementById("btnCheckAnswer").disabled = true;
                    updateQuestionHistoryResult("correct");
                }
                writeStorage();
            }

            function showAnswersAsGiveUp() {
                if (!currentAnswers || currentQuestionAnswered) return;
                Object.keys(fieldMap).forEach((fieldKey) => {
                    fieldMap[fieldKey].forEach((id, idx) => {
                        document.getElementById(id).value = String(currentAnswers[fieldKey][idx]);
                    });
                    setStatus(fieldKey, true);
                });
                currentQuestionAnswered = true;
                giveUpCount += 1;
                document.getElementById("giveUpCount").textContent = String(giveUpCount);
                document.getElementById("btnCheckAnswer").disabled = true;
                updateQuestionHistoryResult("giveup");
                writeStorage();
            }

            function nextQuestion() {
                currentQuestionAnswered = false;
                document.getElementById("btnCheckAnswer").disabled = false;
                currentAnswers = buildQuestion();
                addQuestionToHistory(currentAnswers);
                updateDisplayedQuestion(currentAnswers);
                clearInputsAndStatus();
                writeStorage();
            }

            function clearScores(event) {
                event.preventDefault();
                correctCount = 0;
                giveUpCount = 0;
                currentQuestionAnswered = false;
                questionHistory = [];
                currentHistoryIndex = -1;
                renderHistory();
                document.getElementById("correctCount").textContent = "0";
                document.getElementById("giveUpCount").textContent = "0";
                localStorage.removeItem(STORAGE_KEY);
                nextQuestion();
            }

            function restoreState() {
                const saved = readStorage();
                if (!saved || !saved.currentAnswers) return false;
                currentAnswers = saved.currentAnswers;
                correctCount = Number(saved.correctCount) || 0;
                giveUpCount = Number(saved.giveUpCount) || 0;
                currentQuestionAnswered = Boolean(saved.currentQuestionAnswered);
                questionHistory = Array.isArray(saved.questionHistory) ? saved.questionHistory.slice(-HISTORY_LIMIT) : [];
                currentHistoryIndex = typeof saved.currentHistoryIndex === "number" ? saved.currentHistoryIndex : -1;
                if (currentHistoryIndex >= questionHistory.length) {
                    currentHistoryIndex = questionHistory.length - 1;
                }
                if (currentHistoryIndex < 0 && currentAnswers) {
                    const activeKey = questionKey(currentAnswers);
                    for (let i = questionHistory.length - 1; i >= 0; i -= 1) {
                        if (questionHistory[i].key === activeKey && questionHistory[i].result === "pending") {
                            currentHistoryIndex = i;
                            break;
                        }
                    }
                    if (currentHistoryIndex < 0 && questionHistory.length) {
                        currentHistoryIndex = questionHistory.length - 1;
                    }
                }
                document.getElementById("correctCount").textContent = String(correctCount);
                document.getElementById("giveUpCount").textContent = String(giveUpCount);
                updateDisplayedQuestion(currentAnswers);
                clearInputsAndStatus();
                restoreInputValues(saved.inputValues);
                restoreStatusValues(saved.statusValues);
                document.getElementById("btnCheckAnswer").disabled = currentQuestionAnswered;
                if (!questionHistory.length || questionHistory[questionHistory.length - 1].key !== questionKey(currentAnswers)) {
                    addQuestionToHistory(currentAnswers);
                }
                updateActiveHistorySnapshot();
                renderHistory();
                return true;
            }

            $(document).ready(function() {
                // Handle session display and logout
                const AUTH_TOKEN_KEY = 'todo_auth_token';
                const token = localStorage.getItem(AUTH_TOKEN_KEY);
                if (token) {
                    fetch('/api/auth/me', {
                        headers: { 'Authorization': `Bearer ${token}` }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.email) {
                            $('#session-email').text(data.email).removeClass('d-none');
                        }
                    })
                    .catch(() => {});
                } else {
                    window.location.href = '/login.php';
                }

                $('#logout-btn').click(function() {
                    localStorage.removeItem(AUTH_TOKEN_KEY);
                    window.location.href = '/login.php';
                });

                document.getElementById("btnCheckAnswer").addEventListener("click", function (event) {
                    event.preventDefault();
                    checkAnswers();
                });
                document.getElementById("btnNext").addEventListener("click", function (event) {
                    event.preventDefault();
                    nextQuestion();
                });
                document.getElementById("cmdgiveup").addEventListener("click", function (event) {
                    event.preventDefault();
                    showAnswersAsGiveUp();
                });
                document.getElementById("clearScoreBtn").addEventListener("click", clearScores);
                document.getElementById("historyList").addEventListener("click", function (event) {
                    const deleteButton = event.target.closest("button.history-delete-btn");
                    if (deleteButton) {
                        event.preventDefault();
                        event.stopPropagation();
                        const deleteIndex = Number(deleteButton.dataset.deleteIndex);
                        if (!Number.isNaN(deleteIndex)) {
                            deleteHistoryItem(deleteIndex);
                        }
                        return;
                    }
                    const row = event.target.closest("tr.history-item-resumable");
                    if (!row) return;
                    const index = Number(row.dataset.index);
                    if (Number.isNaN(index)) return;
                    loadHistoryAttempt(index);
                });

                Object.keys(fieldMap).forEach((fieldKey) => {
                    fieldMap[fieldKey].forEach((id) => {
                        const input = document.getElementById(id);
                        if (input) {
                            input.addEventListener("input", function () {
                                if (!currentQuestionAnswered) {
                                    clearFieldStatus(inputIdToFieldKey[id]);
                                }
                                writeStorage();
                            });
                        }
                    });
                });

                if (!restoreState()) {
                    nextQuestion();
                }

                // Auto-tab between octets
                $('.textform').on('input', function() {
                    if (this.value.length >= 3) {
                        const inputs = $('.textform');
                        const index = inputs.index(this);
                        if (index < inputs.length - 1) inputs.eq(index + 1).focus();
                    }
                });
                
                // AOS initialization
                if (typeof AOS !== 'undefined') {
                    AOS.init({
                        duration: 800,
                        easing: 'slide',
                        once: true
                    });
                }
            });
        })();
    </script>
</body>
</html>
