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
        /* Restoring original look while integrating unified header */
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
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            height: 70px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .header .logo {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header .logo span {
            color: var(--primary);
            margin: 0;
        }

        .navbar ul li a {
            color: var(--text-muted);
            font-weight: 600;
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.2s;
        }

        .navbar ul li a:hover, .navbar ul li a.active {
            color: var(--primary);
        }

        /* Quiz Specific Overrides for high contrast */
        #main {
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            padding-top: 100px;
        }

        .section-header h2 {
            color: var(--primary) !important;
            font-weight: 800;
        }

        .textform {
            background: #1e293b !important;
            border: 1px solid #334155 !important;
            color: #ffffff !important;
            border-radius: 8px !important;
            padding: 10px !important;
            text-align: center;
            font-size: 1.1rem;
            font-weight: 700;
            transition: all 0.2s;
        }

        .textform:focus {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15) !important;
            outline: none !important;
        }

        label h3 {
            color: #ffffff !important;
            font-size: 1rem !important;
            font-weight: 600 !important;
            margin-bottom: 10px;
        }

        .btn-lg {
            padding: 12px 24px;
            font-weight: 700;
            border-radius: 12px;
            transition: all 0.2s;
        }

        .btn-lg:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.3);
        }

        .history-panel {
            background: var(--card-bg) !important;
            border: 1px solid var(--border) !important;
            border-radius: 16px !important;
        }

        .table-dark {
            background: transparent !important;
        }

        .table-dark th {
            color: var(--text-muted);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom-color: var(--border);
        }

        .table-dark td {
            border-bottom-color: rgba(255,255,255,0.05);
            vertical-align: middle;
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            #header { height: auto; padding: 15px 0; }
            .navbar { display: none; }
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
        <section class="blog" style="padding-bottom:0;margin-bottom:0; padding-top: 100px;">
            <div class="container" data-aos="fade-up">
                <div class="section-header text-center">
                    <h2>Subnet Quiz</h2>
                    <h3 style="color:#FFFFFF; font-size: 1.2rem; font-weight: 400; opacity: 0.8;">What are the network address, first host address, last host address,
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
                                    style="padding:4px 8px; font-size: 0.8rem;">Clear</a></span></h3>
                    </div>
                </div>

                <form name="calculator" id="calculator">
                    <div class="row">
                        <div class="col-md-6 form-line" style="border-right: 1px solid rgba(255,255,255,0.1);">
                            <div class="form-group mb-4">
                                <label><h3 style="color: #fff;">IP Address</h3></label>
                                <div class="d-flex align-items-center gap-2" style="color:#FFFFFF;">
                                    <div id="ip-octet-1" class="task taskIP" style="font-size: 24px; font-weight: bold;">253</div>
                                    <div style="font-size: 24px;">.</div>
                                    <div id="ip-octet-2" class="task taskIP" style="font-size: 24px; font-weight: bold;">118</div>
                                    <div style="font-size: 24px;">.</div>
                                    <div id="ip-octet-3" class="task taskIP" style="font-size: 24px; font-weight: bold;">117</div>
                                    <div style="font-size: 24px;">.</div>
                                    <div id="ip-octet-4" class="task taskIP" style="font-size: 24px; font-weight: bold;">112</div>
                                    <div class="task ms-3" id="taskBitmask" style="font-size: 24px; font-weight: bold; color: #2563eb;">/23</div>
                                </div>
                            </div>

                            <?php 
                            $fields = [
                                'Network Address' => 'NetAddO',
                                'First Host Address' => 'fhost',
                                'Last Host Address' => 'lhost',
                                'Broadcast Address' => 'BroadAddO',
                                'Subnet Mask' => 'SubnetMaskO'
                            ];
                            foreach($fields as $label => $idPrefix): ?>
                            <div class="form-group mb-4">
                                <label><h3 style="color: #fff; font-size: 1.1rem;"><?php echo $label; ?> <span id="status-<?php echo $idPrefix; ?>" class="answer-status"></span></h3></label>
                                <div class="row g-2">
                                    <?php for($i=1; $i<=4; $i++): ?>
                                    <div class="col-3">
                                        <input type="text" class="form-control textform" name="<?php echo $idPrefix.$i; ?>" id="<?php echo $idPrefix.$i; ?>" style="text-align: center; font-size: 18px; font-weight: bold;">
                                    </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
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

                            <div class="history-panel" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 20px;">
                                <h4 style="color: #fff; margin-bottom: 15px;">History (Last <span id="historyCount">0</span>/50)</h4>
                                <div class="table-responsive" style="max-height: 300px;">
                                    <table class="table table-dark table-hover mb-0">
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
            let correctCount = 0;
            let giveUpCount = 0;
            let history = [];
            let currentTask = null;

            function generateTask() {
                const octets = Array.from({length: 4}, () => Math.floor(Math.random() * 256));
                const mask = Math.floor(Math.random() * 23) + 8; // Mask between 8 and 30
                
                currentTask = {
                    ip: octets,
                    mask: mask,
                    answers: calculateSubnet(octets, mask)
                };

                $('#ip-octet-1').text(octets[0]);
                $('#ip-octet-2').text(octets[1]);
                $('#ip-octet-3').text(octets[2]);
                $('#ip-octet-4').text(octets[3]);
                $('#taskBitmask').text('/' + mask);

                // Clear fields
                $('.textform').val('').removeClass('is-valid is-invalid');
                $('.answer-status').html('');
            }

            function calculateSubnet(ip, mask) {
                const ipNum = (ip[0] << 24) | (ip[1] << 16) | (ip[2] << 8) | ip[3];
                const maskNum = -1 << (32 - mask);
                
                const netNum = ipNum & maskNum;
                const broadNum = netNum | ~maskNum;
                
                const firstHost = netNum + 1;
                const lastHost = broadNum - 1;

                const toOctets = (num) => [
                    (num >>> 24) & 0xFF,
                    (num >>> 16) & 0xFF,
                    (num >>> 8) & 0xFF,
                    num & 0xFF
                ];

                return {
                    NetAddO: toOctets(netNum),
                    fhost: toOctets(firstHost),
                    lhost: toOctets(lastHost),
                    BroadAddO: toOctets(broadNum),
                    SubnetMaskO: toOctets(maskNum)
                };
            }

            function checkAnswers() {
                if (!currentTask) return;
                
                let allCorrect = true;
                const fields = ['NetAddO', 'fhost', 'lhost', 'BroadAddO', 'SubnetMaskO'];
                
                fields.forEach(field => {
                    let fieldCorrect = true;
                    for (let i = 1; i <= 4; i++) {
                        const val = parseInt($(`#${field}${i}`).val());
                        if (val !== currentTask.answers[field][i-1]) {
                            fieldCorrect = false;
                            $(`#${field}${i}`).addClass('is-invalid').removeClass('is-valid');
                        } else {
                            $(`#${field}${i}`).addClass('is-valid').removeClass('is-invalid');
                        }
                    }
                    
                    if (fieldCorrect) {
                        $(`#status-${field}`).html('<i class="fa fa-check text-success"></i>');
                    } else {
                        $(`#status-${field}`).html('<i class="fa fa-times text-danger"></i>');
                        allCorrect = false;
                    }
                });

                if (allCorrect) {
                    correctCount++;
                    $('#correctCount').text(correctCount);
                    addToHistory(currentTask, 'Correct');
                    setTimeout(generateTask, 2000);
                }
            }

            function giveUp() {
                if (!currentTask) return;
                
                giveUpCount++;
                $('#giveUpCount').text(giveUpCount);
                
                const fields = ['NetAddO', 'fhost', 'lhost', 'BroadAddO', 'SubnetMaskO'];
                fields.forEach(field => {
                    for (let i = 1; i <= 4; i++) {
                        $(`#${field}${i}`).val(currentTask.answers[field][i-1]).addClass('is-valid').removeClass('is-invalid');
                    }
                    $(`#status-${field}`).html('<i class="fa fa-info-circle text-info"></i>');
                });
                
                addToHistory(currentTask, 'Gave Up');
                $('#btnNext').focus();
            }

            function addToHistory(task, status) {
                const qText = `${task.ip.join('.')}/${task.mask}`;
                history.unshift({q: qText, status: status});
                if (history.length > 50) history.pop();
                
                updateHistoryUI();
            }

            function updateHistoryUI() {
                $('#historyCount').text(history.length);
                let html = '';
                history.forEach(item => {
                    const statusClass = item.status === 'Correct' ? 'text-success' : 'text-warning';
                    html += `<tr>
                        <td>${item.q}</td>
                        <td class="${statusClass}">${item.status}</td>
                        <td class="text-center"><i class="fa ${item.status === 'Correct' ? 'fa-check' : 'fa-times'}"></i></td>
                    </tr>`;
                });
                $('#historyList').html(html);
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

                generateTask();
                
                $('#btnCheckAnswer').click(checkAnswers);
                $('#btnNext').click(generateTask);
                $('#cmdgiveup').click(giveUp);
                $('#clearScoreBtn').click(function(e) {
                    e.preventDefault();
                    correctCount = 0;
                    giveUpCount = 0;
                    history = [];
                    $('#correctCount').text(0);
                    $('#giveUpCount').text(0);
                    updateHistoryUI();
                });

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
