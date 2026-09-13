<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Fetch User Profile & Wallet Details
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
$user = mysqli_fetch_assoc($user_query);

// 2. Fetch Direct Team Count
$team_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE parent_id = '$user_id'");
$team = mysqli_fetch_assoc($team_query);

// 3. Dynamic MLM Passive Income Calculation
$comm_query = mysqli_query($conn, "SELECT SUM(commission_amount) as total_income FROM network_commissions WHERE leader_id = '$user_id'");
$comm_data = mysqli_fetch_assoc($comm_query);
$total_mlm_income = $comm_data['total_income'] ? $comm_data['total_income'] : 0.00;

// 4. Fetch Active Contests
$contests_query = mysqli_query($conn, "SELECT * FROM contests WHERE status = 'active' ORDER BY id DESC");

// 5. Fetch Direct Team Members
$team_members_query = mysqli_query($conn, "SELECT name, phone, created_at FROM users WHERE parent_id = '$user_id' ORDER BY id DESC LIMIT 10");

$ref_link = "https://" . $_SERVER['HTTP_HOST'] . "/register.php?ref=" . (isset($user['referral_code']) ? $user['referral_code'] : '');
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TC Game - Super Dashboard</title>
    <!-- Bootstrap 5 & FontAwesome CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #0d0f17; color: #e2e8f0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding-bottom: 80px; }
        .navbar-custom { background: rgba(22, 27, 46, 0.9); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255, 255, 255, 0.08); }
        .hero-card { background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%); border-radius: 18px; padding: 22px; box-shadow: 0 10px 25px rgba(0, 114, 255, 0.3); color: #fff; }
        .mlm-card { background: linear-gradient(135deg, #f12711 0%, #f5af19 100%); border-radius: 18px; padding: 22px; box-shadow: 0 10px 25px rgba(245, 175, 25, 0.25); color: #fff; }
        .stat-label { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; }
        .stat-value { font-size: 2.2rem; font-weight: 800; margin-top: 5px; }
        .action-btn { background: rgba(255, 255, 255, 0.2); border: none; color: #fff; padding: 8px 16px; border-radius: 10px; font-weight: 600; font-size: 0.9rem; backdrop-filter: blur(5px); cursor: pointer; }
        .action-btn:hover { background: rgba(255, 255, 255, 0.35); color: #fff; }
        .ref-section, .rules-section, .team-section { background: #161b2e; border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 16px; padding: 20px; }
        .game-card { background: #161b2e; border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 18px; }
        .btn-play { background: linear-gradient(45deg, #00e676, #00b0ff); border: none; color: #000; font-weight: 700; border-radius: 12px; padding: 12px; width: 100%; text-decoration: none; display: block; text-align: center; }
        .accordion-item { background-color: #1a2035; border: 1px solid rgba(255, 255, 255, 0.08); color: #e2e8f0; }
        .accordion-button { background-color: #1a2035; color: #00e676; font-weight: 600; }
        .accordion-button:not(.collapsed) { background-color: #222a45; color: #00e676; }
        .bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(22, 27, 46, 0.95); backdrop-filter: blur(15px); border-top: 1px solid rgba(255, 255, 255, 0.1); display: flex; justify-content: space-around; padding: 10px 0; z-index: 1000; }
        .bottom-nav a { color: #94a3b8; text-decoration: none; font-size: 0.75rem; text-align: center; }
        .bottom-nav a.active { color: #00e676; }
        .bottom-nav i { font-size: 1.2rem; display: block; margin-bottom: 2px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-custom sticky-top mb-4">
        <div class="container">
            <a class="navbar-brand text-white fw-bold fs-4" href="dashboard.php">
                <i class="fa-solid fa-gamepad text-success me-2"></i>TC GAME
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-light d-none d-sm-inline"><i class="fa-solid fa-user-circle me-1 text-info"></i><?php echo htmlspecialchars($user['name']); ?></span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3"><i class="fa-solid fa-right-from-bracket me-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Top Wallet & MLM Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="hero-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="stat-label"><i class="fa-solid fa-wallet me-2"></i>Wallet Balance</div>
                        <span class="badge bg-light text-primary fw-bold">Instant Wallet</span>
                    </div>
                    <div class="stat-value">₹<?php echo number_format(isset($user['wallet_balance']) ? $user['wallet_balance'] : 0, 2); ?></div>
                    <div class="mt-3 d-flex gap-2">
                        <button class="action-btn" data-bs-toggle="modal" data-bs-target="#addMoneyModal"><i class="fa-solid fa-plus-circle me-1"></i>Add Cash</button>
                        <button class="action-btn" data-bs-toggle="modal" data-bs-target="#withdrawModal"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Withdraw</button>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="mlm-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="stat-label"><i class="fa-solid fa-network-wired me-2"></i>Passive Network Income</div>
                        <span class="badge bg-light text-warning fw-bold">2-Level System</span>
                    </div>
                    <div class="stat-value">₹<?php echo number_format($total_mlm_income, 2); ?></div>
                    <div class="mt-3 text-white-50 small">
                        <i class="fa-solid fa-users me-1"></i>Direct Referrals: <strong><?php echo isset($team['total']) ? $team['total'] : 0; ?> Members</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Referral Link Card -->
        <div class="ref-section mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0 fw-bold text-success"><i class="fa-solid fa-share-nodes me-2"></i>Invite & Earn Passive Income</h6>
                <span class="badge bg-secondary">Referral Code: <?php echo isset($user['referral_code']) ? $user['referral_code'] : ''; ?></span>
            </div>
            <p class="text-secondary small mb-3">दोस्तों को शेयर करें और पाएँ: Level 1 से 10% और Level 2 से 5% लाइफटाइम ऑटो-कमीशन!</p>
            <div class="input-group">
                <input type="text" id="refLink" class="form-control bg-dark text-white border-secondary" value="<?php echo $ref_link; ?>" readonly>
                <button class="btn btn-success fw-bold px-4" onclick="copyReferral()"><i class="fa-regular fa-copy me-1"></i>Copy Link</button>
            </div>
        </div>

        <!-- Live Contests Section -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0 text-white"><i class="fa-solid fa-trophy text-warning me-2"></i>Live Skill Quiz Contests</h5>
            <span class="badge bg-success"><i class="fa-solid fa-circle me-1"></i>Live Stream</span>
        </div>

        <div class="row g-3 mb-5">
            <?php 
            if ($contests_query && mysqli_num_rows($contests_query) > 0) {
                while($c = mysqli_fetch_assoc($contests_query)) { 
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="game-card p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="fw-bold text-white mb-0"><?php echo htmlspecialchars($c['title']); ?></h5>
                            <span class="badge bg-danger">HOT</span>
                        </div>
                        
                        <div class="d-flex justify-content-between my-3">
                            <div class="bg-dark p-2 rounded border border-secondary text-center flex-grow-1 me-2">
                                <small class="text-secondary d-block">Entry Fee</small>
                                <strong class="text-warning fs-5">₹<?php echo number_format($c['entry_fee'], 0); ?></strong>
                            </div>
                            <div class="bg-dark p-2 rounded border border-secondary text-center flex-grow-1">
                                <small class="text-secondary d-block">Winning Pool</small>
                                <strong class="text-success fs-5">₹<?php echo number_format($c['prize_pool'], 0); ?></strong>
                            </div>
                        </div>

                        <a href="play_quiz.php?contest_id=<?php echo $c['id']; ?>" class="btn btn-play">
                            <i class="fa-solid fa-play me-2"></i>Play Now & Win
                        </a>
                    </div>
                </div>
            <?php 
                } 
            } else {
                echo '<div class="col-12 text-center text-secondary py-4">कोई भी एक्टिव कॉन्टेस्ट नहीं मिला! (Admin Panel से Contest जोड़ें)</div>';
            }
            ?>
        </div>

        <!-- Direct Team List Section -->
        <div class="team-section mb-4" id="team">
            <h6 class="fw-bold text-info mb-3"><i class="fa-solid fa-users me-2"></i>Your Direct Network Team (Recent)</h6>
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 align-middle">
                    <thead>
                        <tr class="text-secondary">
                            <th>#</th>
                            <th>Member Name</th>
                            <th>Phone</th>
                            <th>Joined Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($team_members_query && mysqli_num_rows($team_members_query) > 0) {
                            $i = 1;
                            while($m = mysqli_fetch_assoc($team_members_query)) {
                                echo "<tr>
                                    <td>{$i}</td>
                                    <td><i class='fa-solid fa-user-ninja text-success me-2'></i>".htmlspecialchars($m['name'])."</td>
                                    <td>".substr($m['phone'], 0, 4)."****".substr($m['phone'], -2)."</td>
                                    <td>".date('d M Y', strtotime($m['created_at']))."</td>
                                </tr>";
                                $i++;
                            }
                        } else {
                            echo "<tr><td colspan='4' class='text-center text-secondary py-3'>अभी तक आपकी टीम में कोई सदस्य नहीं जुड़ा है। अपना लिंक शेयर करें!</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Rules & FAQ Accordion Section -->
        <div class="rules-section mb-4" id="rules">
            <h6 class="fw-bold text-warning mb-3"><i class="fa-solid fa-file-contract me-2"></i>Game Rules & MLM System Terms (शर्तें व नियम)</h6>
            <div class="accordion" id="rulesAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#rule1">
                            1. क्विज़ गेम खेलने के नियम और विजेता का चुनाव
                        </button>
                    </h2>
                    <div id="rule1" class="accordion-collapse collapse show" data-bs-parent="#rulesAccordion">
                        <div class="accordion-body">
                            प्रतिभागियों को निश्चित समय में सही उत्तर देने होते हैं। जो प्रतिभागी सबसे कम समय में सबसे ज्यादा सही जवाब देगा, वही विजेता बनेगा और विनिंग प्राइज़ तुरंत उसके वॉलेट में जमा हो जाएगा।
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#rule2">
                            2. 2-Level Passive MLM Earning Commission System
                        </button>
                    </h2>
                    <div id="rule2" class="accordion-collapse collapse" data-bs-parent="#rulesAccordion">
                        <div class="accordion-body">
                            <ul>
                                <li><strong>Level 1 (Direct Referral):</strong> जब आपकी डायरेक्ट टीम का कोई सदस्य गेम खेलता है, तो उसकी एंट्री फीस का <strong>10%</strong> पैसिव इनकम के रूप में आपको मिलेगा।</li>
                                <li><strong>Level 2 (Indirect Referral):</strong> जब आपके डायरेक्ट सदस्य किसी और को जोड़ते हैं और वे गेम खेलते हैं, तो उनकी फीस का <strong>5%</strong> कमीशन आपको मिलेगा।</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#rule3">
                            3. Wallet & Withdrawal Rules (विथड्रॉल नियम)
                        </button>
                    </h2>
                    <div id="rule3" class="accordion-collapse collapse" data-bs-parent="#rulesAccordion">
                        <div class="accordion-body">
                            न्यूनतम विथड्रॉल सीमा ₹100 है। आप अपनी जीत की राशि और MLM कमीशन इनकम को सीधे अपने UPI (PhonePe / Google Pay / Paytm) या बैंक खाते में ट्रांसफर कर सकते हैं।
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Mobile Nav -->
    <div class="bottom-nav d-sm-none">
        <a href="dashboard.php" class="active"><i class="fa-solid fa-house"></i>Home</a>
        <a href="#rules"><i class="fa-solid fa-trophy"></i>Contests</a>
        <a href="#team"><i class="fa-solid fa-users"></i>My Team</a>
        <a href="logout.php"><i class="fa-solid fa-power-off"></i>Logout</a>
    </div>

    <!-- Add Money Modal -->
    <div class="modal fade" id="addMoneyModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white border-secondary">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title fw-bold text-success"><i class="fa-solid fa-qrcode me-2"></i>Add Cash to Wallet</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="text-secondary mb-2">स्कैन करें और किसी भी UPI ऐप से पेमेंट करें:</p>
                    <div class="bg-white p-3 d-inline-block rounded mb-3">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=upi://pay?pa=7999818451@ybl&pn=TC%20Game" alt="UPI QR" class="img-fluid">
                    </div>
                    <p class="fw-bold mb-1">UPI ID: <span class="text-warning">7999818451@ybl</span></p>
                    <small class="text-muted d-block mb-3">पेमेंट के बाद UTR / Transaction ID एडमिन को भेजें।</small>
                    <input type="number" id="depositAmount" class="form-control bg-secondary text-white border-0 text-center mb-2" placeholder="Enter Amount (e.g. 100)">
                    <button class="btn btn-success w-100 fw-bold" onclick="alert('Payment Request Submitted! Wallet will update shortly.')">Submit Payment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Withdraw Modal -->
    <div class="modal fade" id="withdrawModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white border-secondary">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title fw-bold text-warning"><i class="fa-solid fa-money-bill-transfer me-2"></i>Withdraw Balance</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label text-secondary">Enter Amount (Min: ₹100)</label>
                        <input type="number" class="form-control bg-secondary text-white border-0" placeholder="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary">UPI ID / PhonePe Number</label>
                        <input type="text" class="form-control bg-secondary text-white border-0" placeholder="yourname@upi">
                    </div>
                    <button class="btn btn-warning w-100 fw-bold text-dark" onclick="alert('Withdrawal Request Submitted Successfully!')">Request Withdrawal</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Correct Source Link -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyReferral() {
            var copyText = document.getElementById("refLink");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value);
            alert("Referral Link Copied successfully!");
        }
    </script>
</body>
</html>
