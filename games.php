<?php
// games.php - Game Management & RTP Control Panel
session_start();
require_once 'db.php';

// Safe MySQL Session Setting
mysqli_query($conn, "SET SESSION sql_require_primary_key = 0;");

// 1. Check Admin Authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin.php");
    exit;
}

// 2. Schema Builder (गेम्स टेबल ऑटो-क्रिएट सुरक्षित मैकेनिज्म)
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS platform_games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_code VARCHAR(50) NOT NULL UNIQUE,
    game_name VARCHAR(100) NOT NULL,
    category VARCHAR(50) DEFAULT 'General',
    status ENUM('Active', 'Maintenance') DEFAULT 'Active',
    house_edge DECIMAL(5,2) DEFAULT 2.50,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;");

// डिफ़ॉल्ट डेटा इंजेक्ट करें यदि टेबल खाली है
$check_games = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM platform_games");
$count = mysqli_fetch_assoc($check_games)['cnt'] ?? 0;
if ($count == 0) {
    mysqli_query($conn, "INSERT INTO platform_games (game_code, game_name, category, status, house_edge) VALUES 
        ('color_prediction', 'Color Prediction (3 Min)', 'Lottery', 'Active', 2.50),
        ('aviator', 'Aviator Crash Game', 'Crash / Instant', 'Active', 4.00),
        ('andar_bahar', 'Andar Bahar', 'Casino', 'Maintenance', 3.00)");
}

// 3. Database Action Engine (डेटाबेस अपडेट रिस्पॉन्स)
$msg = "";
$msg_type = "success";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_game'])) {
    $game_id = intval($_POST['game_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $house_edge = floatval($_POST['house_edge']);

    $update_query = "UPDATE platform_games SET status = '$status', house_edge = $house_edge WHERE id = $game_id";
    if (mysqli_query($conn, $update_query)) {
        $msg = "✨ गेम सेटिंग्स सफलता से अपडेट हो गईं!";
    } else {
        $msg = "❌ अपडेट में त्रुटि: " . mysqli_error($conn);
        $msg_type = "error";
    }
}

// Sidebar Sync Variable (साइटबार में '🎮 गेम मैनेजमेंट' सक्रिय दिखाने के लिए)
$active_tab = 'games';

// Fetch Global Statistics for Sidebar & Stats Badge
$pending_w = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM withdrawals WHERE status='Pending'"))['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Management & RTP Control - TC GAME</title>
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

        .main-content { flex: 1; padding: 32px; overflow-y: auto; }
        .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
        .page-title { font-size: 26px; font-weight: 800; }

        .card { background: var(--card-bg); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 18px; padding: 24px; margin-bottom: 28px; }
        .card-header { font-size: 18px; font-weight: 700; margin-bottom: 15px; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 12px; color: #cbd5e1; font-weight: 600; }
        .form-control {
            background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.12);
            color: #fff; padding: 11px 14px; border-radius: 10px; font-size: 13px; outline: none; transition: 0.3s;
        }
        .form-control:focus { border-color: #a855f7; box-shadow: 0 0 12px rgba(168, 85, 247, 0.3); }

        .btn { padding: 9px 18px; border-radius: 10px; font-weight: 700; font-size: 12px; border: none; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: var(--primary-gradient); color: #fff; }
        .btn-success { background: var(--success-gradient); color: #fff; }
        .btn-danger { background: var(--danger-gradient); color: #fff; }
        .btn:hover { transform: translateY(-2px); filter: brightness(1.1); }

        .alert { padding: 14px 18px; border-radius: 12px; margin-bottom: 22px; font-weight: 600; font-size: 14px; }
        .alert-success { background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #34d399; }
        .alert-error { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; }

        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 6px; }
        th { padding: 12px 16px; font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; text-align: left; }
        td { background: rgba(15, 23, 42, 0.6); padding: 14px 16px; font-size: 13px; border-top: 1px solid rgba(255, 255, 255, 0.05); border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        td:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        td:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .badge-active { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid #10b981; }
        .badge-maintenance { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid #ef4444; }
    </style>
</head>
<body>

    <!-- Dynamic Sidebar Sync Include -->
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        
        <div class="header-bar">
            <h1 class="page-title">🎮 गेम मैनेजमेंट & आरटीपी कंट्रोल</h1>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-<?= $msg_type ?>"><?= $msg ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">🕹️ प्लेटफ़ॉर्म एक्टिव गेम्स और विनिंग मार्जिन (House Edge)</div>
            <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 20px;">
                यहाँ से आप तय कर सकते हैं कि किस गेम का स्टेटस लाइव रहेगा और प्लेटफॉर्म का हाउस प्रॉफिट मार्जिन (House Edge) कितना होगा।
            </p>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>गेम का नाम</th>
                            <th>कैटेगरी</th>
                            <th>स्टेटस</th>
                            <th>हाउस एज / मार्जिन</th>
                            <th>अंतिम अपडेट</th>
                            <th>एक्शन</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $games_query = mysqli_query($conn, "SELECT * FROM platform_games ORDER BY id ASC");
                        while ($g = mysqli_fetch_assoc($games_query)):
                        ?>
                        <tr>
                            <td style="font-weight: 700;">
                                <?= htmlspecialchars($g['game_name']) ?>
                                <br><small style="color:var(--text-muted); font-weight:normal; font-size:11px;">(Code: <?= $g['game_code'] ?>)</small>
                            </td>
                            <td style="color: var(--text-muted);"><?= htmlspecialchars($g['category']) ?></td>
                            <td>
                                <?php if ($g['status'] == 'Active'): ?>
                                    <span class="badge badge-active">🟢 Active</span>
                                <?php else: ?>
                                    <span class="badge badge-maintenance">🔴 Maintenance</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight: 700; color:#a855f7;"><?= number_format($g['house_edge'], 2) ?>%</td>
                            <td style="color: var(--text-muted); font-size:11px;"><?= $g['updated_at'] ?></td>
                            <td>
                                <button type="button" class="btn btn-primary" style="padding: 6px 12px; font-size: 11px;" 
                                        onclick="openEditModal(<?= $g['id'] ?>, '<?= htmlspecialchars(addslashes($g['game_name'])) ?>', '<?= $g['status'] ?>', '<?= $g['house_edge'] ?>')">
                                    Configure ⚙️
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Real-Time Edit Form Box -->
        <div class="card" id="editGameBox" style="display: none; border: 1px solid var(--border-glow);">
            <div class="card-header" id="modalTitle">⚙️ गेम सेटिंग्स एडिट करें</div>
            <form method="POST">
                <input type="hidden" name="update_game" value="1">
                <input type="hidden" name="game_id" id="inputGameId">
                <div class="form-grid">
                    <div class="form-group">
                        <label>गेम का नाम</label>
                        <input type="text" class="form-control" id="inputGameName" readonly style="opacity: 0.7; cursor: not-allowed;">
                    </div>
                    <div class="form-group">
                        <label>गेम स्टेटस</label>
                        <select class="form-control" name="status" id="inputStatus">
                            <option value="Active">Active (Live)</option>
                            <option value="Maintenance">Maintenance (Off)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>हाउस एज / प्रॉफिट मार्जिन (%)</label>
                        <input type="number" step="0.01" class="form-control" name="house_edge" id="inputHouseEdge" required>
                    </div>
                </div>
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success">💾 बदलाव सेव करें</button>
                    <button type="button" class="btn btn-danger" style="background:#4b5563;" onclick="document.getElementById('editGameBox').style.display='none'">रद्द करें</button>
                </div>
            </form>
        </div>

    </div>

    <script>
    function openEditModal(id, name, status, edge) {
        document.getElementById('editGameBox').style.display = 'block';
        document.getElementById('inputGameId').value = id;
        document.getElementById('inputGameName').value = name;
        document.getElementById('inputStatus').value = status;
        document.getElementById('inputHouseEdge').value = edge;
        document.getElementById('modalTitle').innerText = '⚙️ कस्टमाइज़ गेम: ' + name;
        document.getElementById('editGameBox').scrollIntoView({ behavior: 'smooth' });
    }
    </script>
</body>
</html>
