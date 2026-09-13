<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. User Profile Details
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
$user = mysqli_fetch_assoc($user_query);

// 2. Direct Team Count
$team_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE parent_id = '$user_id'");
$team = mysqli_fetch_assoc($team_query);

// 3. Dynamic MLM Passive Income Calculation
$comm_query = mysqli_query($conn, "SELECT SUM(commission_amount) as total_income FROM network_commissions WHERE leader_id = '$user_id'");
$comm_data = mysqli_fetch_assoc($comm_query);
$total_mlm_income = $comm_data['total_income'] ? $comm_data['total_income'] : 0.00;

// 4. Dynamic Live Contests Query
$contests_query = mysqli_query($conn, "SELECT * FROM contests WHERE status = 'active' ORDER BY id DESC");

$ref_link = "https://" . $_SERVER['HTTP_HOST'] . "/register.php?ref=" . $user['referral_code'];
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TC Game - Super Dashboard</title>
    <!-- FontAwesome & Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #0d0f17;
            color: #e2e8f0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 40px;
        }
        .navbar-custom {
            background: rgba(22, 27, 46, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .hero-card {
            background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%);
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 10px 25px rgba(0, 114, 255, 0.3);
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .hero-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        .mlm-card {
            background: linear-gradient(135deg, #f12711 0%, #f5af19 100%);
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 10px 25px rgba(245, 175, 25, 0.25);
            color: #fff;
        }
        .stat-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            margin-top: 5px;
        }
        .ref-section {
            background: #161b2e;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 18px;
        }
        .game-card {
            background: #161b2e;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .game-card:hover {
            transform: translateY(-5px);
            border-color: #00e676;
            box-shadow: 0 8px 20px rgba(0, 230, 118, 0.15);
        }
        .btn-play {
            background: linear-gradient(45deg, #00e676, #00b0ff);
            border: none;
            color: #000;
            font-weight: 700;
            border-radius: 10px;
            padding: 10px 18px;
            width: 100%;
        }
        .badge-entry {
            background: rgba(255, 255, 255, 0.1);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-custom sticky-top mb-4">
        <div class="container">
            <a class="navbar-brand text-white fw-bold fs-4" href="#">
                <i class="fa-solid fa-gamepad text-success me-2"></i>TC GAME
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-secondary d-none d-sm-inline"><i class="fa-solid fa-user me-1"></i><?php echo htmlspecialchars($user['name']); ?></span>
                <a href="login.php" class="btn btn-outline-danger btn-sm rounded-pill"><i class="fa-solid fa-right-from-bracket me-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Wallet & Income Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="hero-card">
                    <div class="stat-label"><i class="fa-solid fa-wallet me-2"></i>Available Wallet Balance</div>
                    <div class="stat-value">₹<?php echo number_format($user['wallet_balance'], 2); ?></div>
                    <div class="mt-2 text-white-50 small"><i class="fa-solid fa-shield-halved me-1"></i>100% Safe Instant Wallet</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mlm-card">
                    <div class="stat-label"><i class="fa-solid fa-users me-2"></i>MLM Passive Team Earnings</div>
                    <div class="stat-value">₹<?php echo number_format($total_mlm_income, 2); ?></div>
                    <div class="mt-2 text-white-50 small"><i class="fa-solid fa-diagram-next me-1"></i>Direct Network: <?php echo $team['total']; ?> Members</div>
                </div>
            </div>
        </div>

        <!-- Referral Link Section -->
        <div class="ref-section mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0 fw-bold text-success"><i class="fa-solid fa-share-nodes me-2"></i>Your Referral Link</h6>
                <span class="badge bg-secondary">Code: <?php echo $user['referral_code']; ?></span>
            </div>
            <div class="input-group">
                <input type="text" id="refLink" class="form-control bg-dark text-white border-secondary" value="<?php echo $ref_link; ?>" readonly>
                <button class="btn btn-success fw-bold" onclick="copyReferral()"><i class="fa-regular fa-copy me-1"></i>Copy Link</button>
            </div>
        </div>

        <!-- Contests Section -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="fa-solid fa-trophy text-warning me-2"></i>Live Skill Quiz Contests</h5>
            <span class="text-secondary small">Auto Refreshed</span>
        </div>

        <div class="row g-3">
            <?php 
            if (mysqli_num_rows($contests_query) > 0) {
                while($c = mysqli_fetch_assoc($contests_query)) { 
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="game-card p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="fw-bold text-white mb-0"><?php echo htmlspecialchars($c['title']); ?></h5>
                            <span class="badge bg-success text-dark fw-bold"><i class="fa-solid fa-bolt me-1"></i>LIVE</span>
                        </div>
                        
                        <div class="d-flex justify-content-between my-3 text-secondary">
                            <span class="badge-entry">Entry: <strong class="text-warning">₹<?php echo number_format($c['entry_fee'], 0); ?></strong></span>
                            <span class="badge-entry">Winner Pool: <strong class="text-success">₹<?php echo number_format($c['prize_pool'], 0); ?></strong></span>
                        </div>

                        <a href="play_quiz.php?contest_id=<?php echo $c['id']; ?>" class="btn btn-play">
                            <i class="fa-solid fa-play me-2"></i>Play Now & Win
                        </a>
                    </div>
                </div>
            <?php 
                } 
            } else {
                echo '<div class="col-12 text-center text-secondary py-4">No Active Contests Found right now!</div>';
            }
            ?>
        </div>
    </div>

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
