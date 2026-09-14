<?php
// color_prediction.php - Dynamic & Fully Synced with Admin Layout
session_start();
require_once 'db.php';

// Check Admin Authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin.php");
    exit;
}

// Sidebar Active State Definition
$active_tab = 'color_prediction';

$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['set_manual_result'])) {
    $period_no = mysqli_real_escape_string($conn, $_POST['period_no']);
    $winning_color = mysqli_real_escape_string($conn, $_POST['winning_color']);
    $winning_number = intval($_POST['winning_number']);

    // Example logic to handle manual result setup
    $success_msg = "Period #{$period_no} के लिए मैनुअल रिजल्ट ({$winning_color} - {$winning_number}) सफलतापूर्वक लॉक कर दिया गया है!";
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>कलर प्रेडिक्शन कंट्रोल - TC GAME MASTER</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #07090e;
            --card-bg: rgba(18, 24, 38, 0.75);
            --border-glow: rgba(99, 102, 241, 0.25);
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
            --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-dark); color: var(--text-main); display: flex; min-height: 100vh; overflow-x: hidden; }

        /* --- MAIN CONTENT STYLES --- */
        .main-content { flex: 1; padding: 32px; overflow-y: auto; height: 100vh; }
        .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
        .page-title { font-size: 26px; font-weight: 800; }

        .card { background: var(--card-bg); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 18px; padding: 24px; margin-bottom: 28px; }
        .card-header { font-size: 18px; font-weight: 700; margin-bottom: 15px; color: #fff; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 12px; color: #cbd5e1; font-weight: 600; }
        .form-control {
            background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.12);
            color: #fff; padding: 11px 14px; border-radius: 10px; font-size: 13px; outline: none; transition: 0.3s;
        }
        .form-control:focus { border-color: #a855f7; box-shadow: 0 0 12px rgba(168, 85, 247, 0.3); }

        .btn { padding: 11px 20px; border-radius: 10px; font-weight: 700; font-size: 13px; border: none; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: var(--primary-gradient); color: #fff; }
        .btn-primary:hover { transform: translateY(-2px); filter: brightness(1.1); box-shadow: 0 4px 15px rgba(168, 85, 247, 0.4); }

        .alert { padding: 14px 18px; border-radius: 12px; margin-bottom: 22px; font-weight: 600; font-size: 14px; }
        .alert-success { background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #34d399; }
        .alert-error { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; }

        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 6px; }
        th { padding: 12px 16px; font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; text-align: left; }
        td { background: rgba(15, 23, 42, 0.6); padding: 14px 16px; font-size: 13px; border-top: 1px solid rgba(255, 255, 255, 0.05); border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        td:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        td:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

        .badge-color { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; display: inline-block; }
        .badge-red { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid #ef4444; }
        .badge-green { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid #10b981; }
        .badge-violet { background: rgba(168, 85, 247, 0.2); color: #c084fc; border: 1px solid #a855f7; }
    </style>
</head>
<body>

    <!-- REAL SYNCED SIDEBAR INCLUDE -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT AREA -->
    <div class="main-content">
        <div class="header-bar">
            <h1 class="page-title">🎯 कलर प्रेडिक्शन कंट्रोल & आरटीपी</h1>
        </div>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($success_msg) ?></div>
        <?php endif; ?>

        <!-- Live Game Status Panel -->
        <div class="card">
            <div class="card-header">📊 वर्तमान गेम स्टेटस (Live Period Info)</div>
            <div class="form-grid" style="margin-top: 10px;">
                <div style="background: rgba(15, 23, 42, 0.7); padding: 18px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                    <div style="font-size: 12px; color: var(--text-muted); font-weight:600;">करेंट पीरियड नंबर (Period No)</div>
                    <div style="font-size: 20px; font-weight: 800; color: #fff; margin-top: 5px;">202609141029</div>
                </div>
                <div style="background: rgba(15, 23, 42, 0.7); padding: 18px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                    <div style="font-size: 12px; color: var(--text-muted); font-weight:600;">टाइमर (Time Remaining)</div>
                    <div style="font-size: 20px; font-weight: 800; color: #f59e0b; margin-top: 5px;" id="countdownTimer">01:45</div>
                </div>
                <div style="background: rgba(15, 23, 42, 0.7); padding: 18px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                    <div style="font-size: 12px; color: var(--text-muted); font-weight:600;">कुल बेट्स (Total Pool Amount)</div>
                    <div style="font-size: 20px; font-weight: 800; color: #34d399; margin-top: 5px;">₹14,550.00</div>
                </div>
            </div>
        </div>

        <!-- Manual Result Control Form -->
        <div class="card" style="border: 1px solid var(--border-glow);">
            <div class="card-header">🛠️ मैनुअल रिजल्ट मैनिपुलेशन (Admin Override)</div>
            <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 20px;">
                अगले आने वाले पीरियड का विनिंग नंबर और कलर अपनी मर्जी से सेट करें (यदि ऑटो-मोड बायपास करना हो)।
            </p>

            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>पीरियड नंबर (Period No)</label>
                        <input type="text" class="form-control" name="period_no" value="202609141030" required>
                    </div>
                    <div class="form-group">
                        <label>विनिंग कलर (Winning Color)</label>
                        <select class="form-control" name="winning_color" required>
                            <option value="Green">🟢 Green (हरा)</option>
                            <option value="Red">🔴 Red (लाल)</option>
                            <option value="Violet">🟣 Violet (बैंगनी)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>विनिंग नंबर (0 से 9)</label>
                        <input type="number" class="form-control" name="winning_number" min="0" max="9" value="5" required>
                    </div>
                </div>
                <div style="margin-top: 22px;">
                    <button type="submit" name="set_manual_result" class="btn btn-primary">🚀 रिजल्ट लॉक करें (Set Manual Winner)</button>
                </div>
            </form>
        </div>

        <!-- Recent Game History Table -->
        <div class="card">
            <div class="card-header">📜 पिछले परिणाम इतिहास (Recent Results History)</div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Period No</th>
                            <th>Price</th>
                            <th>Number</th>
                            <th>Result Color</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="font-weight: 700;">202609141028</td>
                            <td style="color: var(--text-muted);">54201</td>
                            <td style="font-weight: 800; font-size: 15px; color: #ef4444;">4</td>
                            <td><span class="badge-color badge-red">Red</span></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 700;">202609141027</td>
                            <td style="color: var(--text-muted);">54198</td>
                            <td style="font-weight: 800; font-size: 15px; color: #10b981;">9</td>
                            <td>
                                <span class="badge-color badge-green">Green</span>
                                <span class="badge-color badge-violet">Violet</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>
