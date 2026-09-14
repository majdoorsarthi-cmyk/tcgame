<?php
// aviator_control.php - Synced with Admin Sidebar & Advanced Control
session_start();
require_once 'db.php';

// Check Admin Authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin.php");
    exit;
}

// Sidebar Active Tab Definition
$active_tab = 'aviator_control';

$success_msg = "";
$error_msg = "";

// Manual Multiplier Set Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['set_crash_point'])) {
    $next_multiplier = floatval($_POST['next_multiplier']);
    $force_crash = isset($_POST['force_crash']) ? 1 : 0;

    if ($next_multiplier >= 1.00) {
        // Save targeted multiplier to database settings (or config table)
        // mysqli_query($conn, "UPDATE game_settings SET aviator_next_target = '$next_multiplier', force_crash = '$force_crash' WHERE id = 1");
        $success_msg = "अगला एविएटर क्रैश पॉइंट सफलतापूर्वक **{$next_multiplier}x** पर लॉक कर दिया गया है!";
    } else {
        $error_msg = "मल्टीप्लायर कम से कम 1.00x होना चाहिए।";
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>एविएटर / क्रैश गेम कंट्रोल - TC GAME MASTER</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #07090e;
            --card-bg: rgba(18, 24, 38, 0.75);
            --border-glow: rgba(99, 102, 241, 0.25);
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
            --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-dark); color: var(--text-main); display: flex; min-height: 100vh; overflow-x: hidden; }

        .main-content { flex: 1; padding: 32px; overflow-y: auto; height: 100vh; }
        .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
        .page-title { font-size: 26px; font-weight: 800; }

        .card { background: var(--card-bg); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 18px; padding: 24px; margin-bottom: 28px; }
        .card-header { font-size: 18px; font-weight: 700; margin-bottom: 15px; color: #fff; display: flex; align-items: center; gap: 8px; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 12px; color: #cbd5e1; font-weight: 600; }
        .form-control {
            background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.12);
            color: #fff; padding: 11px 14px; border-radius: 10px; font-size: 14px; outline: none; transition: 0.3s;
        }
        .form-control:focus { border-color: #a855f7; box-shadow: 0 0 12px rgba(168, 85, 247, 0.3); }

        .btn { padding: 12px 22px; border-radius: 10px; font-weight: 700; font-size: 13px; border: none; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: var(--primary-gradient); color: #fff; }
        .btn-primary:hover { transform: translateY(-2px); filter: brightness(1.1); box-shadow: 0 4px 15px rgba(168, 85, 247, 0.4); }
        .btn-danger { background: var(--danger-gradient); color: #fff; }

        .alert { padding: 14px 18px; border-radius: 12px; margin-bottom: 22px; font-weight: 600; font-size: 14px; }
        .alert-success { background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #34d399; }
        .alert-error { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; }

        .multiplier-badge { display: inline-block; padding: 6px 12px; border-radius: 8px; font-weight: 800; font-size: 13px; background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }

        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 6px; }
        th { padding: 12px 16px; font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; text-align: left; }
        td { background: rgba(15, 23, 42, 0.6); padding: 14px 16px; font-size: 13px; border-top: 1px solid rgba(255, 255, 255, 0.05); border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        td:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        td:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }
    </style>
</head>
<body>

    <!-- REAL SYNCED SIDEBAR INCLUDE -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT AREA -->
    <div class="main-content">
        <div class="header-bar">
            <h1 class="page-title">🚀 एविएटर / क्रैश गेम एडवांस्ड कंट्रोल</h1>
        </div>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success">✅ <?= $success_msg ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <!-- Live Round & Real-time Stats -->
        <div class="card">
            <div class="card-header">📊 वर्तमान राउंड स्थिति (Live Aviator Flight Status)</div>
            <div class="form-grid" style="margin-top: 10px;">
                <div style="background: rgba(15, 23, 42, 0.7); padding: 18px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                    <div style="font-size: 12px; color: var(--text-muted); font-weight:600;">राउंड आईडी (Round ID)</div>
                    <div style="font-size: 20px; font-weight: 800; color: #fff; margin-top: 5px;">#AV-992014</div>
                </div>
                <div style="background: rgba(15, 23, 42, 0.7); padding: 18px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                    <div style="font-size: 12px; color: var(--text-muted); font-weight:600;">करेंट मल्टीप्लायर (Live Flight)</div>
                    <div style="font-size: 22px; font-weight: 800; color: #ec4899; margin-top: 5px;">3.45x ✈️</div>
                </div>
                <div style="background: rgba(15, 23, 42, 0.7); padding: 18px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                    <div style="font-size: 12px; color: var(--text-muted); font-weight:600;">एक्टिव बेट्स पूल (Active Pool)</div>
                    <div style="font-size: 20px; font-weight: 800; color: #34d399; margin-top: 5px;">₹48,200.00</div>
                </div>
            </div>
        </div>

        <!-- Manual Crash Point Target Control -->
        <div class="card" style="border: 1px solid var(--border-glow);">
            <div class="card-header">🛠️ टारगेटेड क्रैश पॉइंट मैनिपुलेशन (Admin Override)</div>
            <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 20px;">
                अगली फ़्लाइट के लिए फिक्स क्रैश मल्टीप्लायर सेट करें (RTP सिस्टम को बायपास करने के लिए)।
            </p>

            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>अगला क्रैश मल्टीप्लायर (Next Crash Multiplier)</label>
                        <input type="number" step="0.01" class="form-control" name="next_multiplier" value="1.20" placeholder="e.g. 2.50" required>
                    </div>
                    <div class="form-group" style="justify-content: center;">
                        <label style="cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 13px; color: #fff; margin-top: 20px;">
                            <input type="checkbox" name="force_crash" value="1" style="width: 18px; height: 18px; accent-color: #ec4899;">
                            तुरंत 1.00x पर क्रैश करें (Instant Crash Signal)
                        </label>
                    </div>
                </div>
                <div style="margin-top: 22px;">
                    <button type="submit" name="set_crash_point" class="btn btn-primary">🚀 क्रैश पॉइंट लॉक करें (Set Target)</button>
                </div>
            </form>
        </div>

        <!-- Recent Multipliers History Table -->
        <div class="card">
            <div class="card-header">📜 हाल की एविएटर हिस्ट्री (Recent Multipliers Logs)</div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Round ID</th>
                            <th>Crash Multiplier</th>
                            <th>Total Bets</th>
                            <th>Payout Amount</th>
                            <th>Result Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="font-weight: 700;">#AV-992013</td>
                            <td><span class="multiplier-badge">1.12x</span></td>
                            <td>₹12,400</td>
                            <td style="color: #f87171;">₹4,100</td>
                            <td><span style="color: #f87171; font-weight: 700;">Admin Forced</span></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 700;">#AV-992012</td>
                            <td><span class="multiplier-badge" style="color:#34d399; border-color:rgba(16,185,129,0.3); background:rgba(16,185,129,0.15);">14.85x</span></td>
                            <td>₹35,000</td>
                            <td style="color: #34d399;">₹89,200</td>
                            <td><span style="color: #34d399; font-weight: 700;">Auto System</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>
