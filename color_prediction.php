<?php
// color_prediction.php - Standalone & Synced with Admin Layout
$active_tab = 'color_prediction';

$success_msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['set_manual_result'])) {
    $period_no = $_POST['period_no'];
    $winning_color = $_POST['winning_color'];
    $winning_number = $_POST['winning_number'];
    $success_msg = "Period #{$period_no} के लिए मैनुअल रिजल्ट सफलतापूर्वक सेट कर दिया गया है!";
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>कलर प्रेडिक्शन कंट्रोल - TC GAME MASTER</title>
    <style>
        :root {
            --bg-color: #0f172a;
            --sidebar-bg: #1e1b4b;
            --card-bg: #1e293b;
            --primary-color: #ec4899;
            --text-color: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.08);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-color); display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar Styling */
        .sidebar { width: 260px; background-color: var(--sidebar-bg); display: flex; flex-direction:-column; border-right: 1px solid var(--border-color); overflow-y: auto; }
        .sidebar-brand { padding: 20px; font-size: 18px; font-weight: bold; color: #fff; background: linear-gradient(90deg, #ec4899, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .menu-item { padding: 12px 20px; color: var(--text-muted); text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 14px; transition: 0.2s; border-left: 3px solid transparent; }
        .menu-item:hover, .menu-item.active { background: rgba(255, 255, 255, 0.05); color: #fff; border-left-color: var(--primary-color); }
        
        /* Main Content */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .header-bar { padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(30, 41, 59, 0.5); }
        .page-title { font-size: 20px; font-weight: 600; }
        .content-body { padding: 20px; }
        
        /* Cards & Components */
        .card { background-color: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .card-header { font-size: 15px; font-weight: 600; margin-bottom: 15px; color: #fff; display: flex; align-items: center; gap: 8px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .form-group label { display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; }
        .form-control { width: 100%; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); padding: 10px 12px; border-radius: 8px; color: #fff; font-size: 14px; outline: none; }
        .form-control:focus { border-color: var(--primary-color); }
        .btn { padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; transition: 0.2s; }
        .btn-primary { background: linear-gradient(135deg, #ec4899, #8b5cf6); color: #fff; }
        .btn-primary:hover { opacity: 0.9; }
        table { width: 100%; border-collapse: collapse; color: #fff; font-size: 13px; }
        th { background: rgba(255, 255, 255, 0.05); text-align: left; padding: 12px; font-weight: 600; color: var(--text-muted); }
        td { padding: 12px; border-bottom: 1px solid var(--border-color); }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">⚡ TC GAME MASTER</div>
        <a href="admin.php" class="menu-item">📊 डैशबोर्ड ओवरव्यू</a>
        <a href="admin.php?tab=games" class="menu-item">🎮 गेम मैनेजमेंट</a>
        <a href="color_prediction.php" class="menu-item active">🎯 कलर प्रेडिक्शन कंट्रोल</a>
        <a href="admin.php?tab=gateway" class="menu-item">🌐 पेमेंट गेटवे सेटिंग्स</a>
        <a href="admin.php?tab=deposits" class="menu-item">💰 डिपॉजिट अप्रूवल</a>
        <a href="admin.php?tab=settings" class="menu-item">⚙️ ग्लोबल सेटिंग्स</a>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        <div class="header-bar">
            <h1 class="page-title">🎯 कलर प्रेडिक्शन कंट्रोल & आरटीपी</h1>
        </div>

        <div class="content-body">
            <?php if (!empty($success_msg)): ?>
                <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #10b981; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px;">
                    ✅ <?= htmlspecialchars($success_msg) ?>
                </div>
            <?php endif; ?>

            <!-- Live Game Status Card -->
            <div class="card">
                <div class="card-header">📊 वर्तमान गेम स्टेटस (Live Period Info)</div>
                <div class="form-grid" style="margin-top: 10px;">
                    <div style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 8px;">
                        <div style="font-size: 12px; color: var(--text-muted);">करेंट पीरियड नंबर (Period No)</div>
                        <div style="font-size: 18px; font-weight: 700; color: #fff; margin-top: 5px;">202609141029</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 8px;">
                        <div style="font-size: 12px; color: var(--text-muted);">टाइमर (Time Remaining)</div>
                        <div style="font-size: 18px; font-weight: 700; color: #f59e0b; margin-top: 5px;" id="countdownTimer">01:45</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 8px;">
                        <div style="font-size: 12px; color: var(--text-muted);">कुल बेट्स (Total Pool Amount)</div>
                        <div style="font-size: 18px; font-weight: 700; color: #10b981; margin-top: 5px;">₹14,550</div>
                    </div>
                </div>
            </div>

            <!-- Manual Result Control Form -->
            <div class="card">
                <div class="card-header">🛠️ मैनुअल रिजल्ट मैनिपुलेशन (Admin Control)</div>
                <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 15px;">
                    अगले पीरियड के लिए फिक्स रिजल्ट सेट करें (यदि ऑटो मोड बंद है)।
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
                                <option value="Green">🟢 Green</option>
                                <option value="Red">🔴 Red</option>
                                <option value="Violet">🟣 Violet</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>विनिंग नंबर (0 से 9)</label>
                            <input type="number" class="form-control" name="winning_number" min="0" max="9" value="5" required>
                        </div>
                    </div>
                    <div style="margin-top: 20px;">
                        <button type="submit" name="set_manual_result" class="btn btn-primary">🚀 रिजल्ट लॉक करें (Set Result)</button>
                    </div>
                </form>
            </div>

            <!-- Recent Game History Table -->
            <div class="card">
                <div class="card-header">📜 पिछले परिणाम इतिहास (Recent Results History)</div>
                <div style="overflow-x: auto; margin-top: 10px;">
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
                                <td>202609141028</td>
                                <td>54201</td>
                                <td style="font-weight: bold;">4</td>
                                <td><span style="color: #ef4444; background: rgba(239,68,68,0.1); padding: 3px 8px; border-radius: 4px; font-size: 11px;">Red</span></td>
                            </tr>
                            <tr>
                                <td>202609141027</td>
                                <td>54198</td>
                                <td style="font-weight: bold;">9</td>
                                <td><span style="color: #10b981; background: rgba(16,185,129,0.1); padding: 3px 8px; border-radius: 4px; font-size: 11px;">Green & Violet</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
