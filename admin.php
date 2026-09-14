<?php
require_once 'db.php';

// Aiven SSL / MySQL Strict Primary Key bypass
mysqli_query($conn, "SET SESSION sql_require_primary_key = 0;");

// --- 1. Auto Schema Builder (ऑटो डेटाबेस टेबल क्रिएशन) ---
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
) ENGINE=InnoDB;");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS dynamic_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50) DEFAULT '📄',
    content LONGTEXT,
    nav_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;");

// Ensure User Table has required columns for advanced actions
mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('user', 'vip', 'moderator', 'admin') DEFAULT 'user';");
mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('active', 'suspended', 'banned') DEFAULT 'active';");

// --- 2. Helper Functions ---
function getSetting($conn, $key, $default = '') {
    $res = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key = '$key'");
    if ($res && $row = mysqli_fetch_assoc($res)) return $row['setting_value'];
    return $default;
}

function setSetting($conn, $key, $value) {
    $val = mysqli_real_escape_string($conn, $value);
    mysqli_query($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES ('$key', '$val') ON DUPLICATE KEY UPDATE setting_value = '$val'");
}

function sendTelegramNotification($bot_token, $chat_id, $message) {
    if (empty($bot_token) || empty($chat_id)) return false;
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    $data = ['chat_id' => $chat_id, 'text' => $message, 'parse_mode' => 'HTML'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

$msg = "";
$msg_type = "success";

// --- 3. Action Processing Engine ---

// Action A: Create/Update Dynamic Page (Auto-Sync Across All User Pages)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_page') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['slug'])));
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $order = intval($_POST['nav_order']);

    $q = "INSERT INTO dynamic_pages (title, slug, icon, content, nav_order) 
          VALUES ('$title', '$slug', '$icon', '$content', $order)
          ON DUPLICATE KEY UPDATE title='$title', icon='$icon', content='$content', nav_order=$order";
    
    if (mysqli_query($conn, $q)) {
        $msg = "✨ नया पेज '<b>$title</b>' सफलता से बन गया! यह यूज़र साइट की नेविगेशन बार में ऑटो-सिंक हो चुका है।";
    } else {
        $msg = "❌ त्रुटि: " . mysqli_error($conn);
        $msg_type = "error";
    }
}

// Action B: Delete Dynamic Page
if (isset($_GET['delete_page'])) {
    $pid = intval($_GET['delete_page']);
    mysqli_query($conn, "DELETE FROM dynamic_pages WHERE id = $pid");
    header("Location: admin.php?tab=pages");
    exit;
}

// Action C: Advanced User Actions (Delete, Suspend, Ban, Promote, Wallet)
if (isset($_GET['user_action']) && isset($_GET['uid'])) {
    $uid = intval($_GET['uid']);
    $act = $_GET['user_action'];

    if ($act == 'delete') {
        mysqli_query($conn, "DELETE FROM users WHERE id = $uid");
        $msg = "🗑️ यूज़र #$uid डेटाबेस से पूरी तरह डिलीट कर दिया गया!";
    } elseif ($act == 'suspend') {
        mysqli_query($conn, "UPDATE users SET status = 'suspended' WHERE id = $uid");
        $msg = "⚠️ यूज़र #$uid सस्पेंड (Suspended) कर दिया गया!";
    } elseif ($act == 'ban') {
        mysqli_query($conn, "UPDATE users SET status = 'banned' WHERE id = $uid");
        $msg = "🚫 यूज़र #$uid पर बैन (Banned) लगा दिया गया!";
    } elseif ($act == 'activate') {
        mysqli_query($conn, "UPDATE users SET status = 'active' WHERE id = $uid");
        $msg = "✅ यूज़र #$uid एक्टिवेट कर दिया गया!";
    }
}

// Action D: Promote / Change User Role
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'promote_user') {
    $uid = intval($_POST['user_id']);
    $role = $_POST['role'];
    mysqli_query($conn, "UPDATE users SET role = '$role' WHERE id = $uid");
    $msg = "⭐ यूज़र #$uid का रोल बदलकर '$role' कर दिया गया है!";
}

// Action E: Wallet Adjustment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'adjust_wallet') {
    $uid = intval($_POST['user_id']);
    $amount = floatval($_POST['amount']);
    $type = $_POST['type'];
    $desc = mysqli_real_escape_string($conn, $_POST['description']);

    if ($type == 'credit') {
        mysqli_query($conn, "UPDATE users SET wallet_balance = wallet_balance + $amount WHERE id = $uid");
    } else {
        mysqli_query($conn, "UPDATE users SET wallet_balance = wallet_balance - $amount WHERE id = $uid");
    }
    
    mysqli_query($conn, "INSERT INTO transactions (user_id, amount, type, category, description) VALUES ($uid, $amount, '$type', 'admin_adjustment', '$desc')");
    $msg = "💰 यूज़र #$uid का वॉलेट सफलतापूर्वक अपडेट हुआ!";
}

// Action F: System & Social Bot Settings
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_settings') {
    setSetting($conn, 'telegram_bot_token', $_POST['telegram_bot_token']);
    setSetting($conn, 'telegram_chat_id', $_POST['telegram_chat_id']);
    setSetting($conn, 'whatsapp_number', $_POST['whatsapp_number']);
    setSetting($conn, 'facebook_link', $_POST['facebook_link']);
    $msg = "⚙️ सोशल मीडिया व बोट कॉन्फ़िगरेशन सेव हो गए!";
}

// Fetch Global Dashboard Stats
$tot_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users"))['c'] ?? 0;
$tot_wallet = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(wallet_balance) as s FROM users"))['s'] ?? 0;
$pending_w = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM withdrawals WHERE status='Pending'"))['c'] ?? 0;
$tot_pages = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM dynamic_pages"))['c'] ?? 0;

$active_tab = $_GET['tab'] ?? 'overview';
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TC GAME - Master Dynamic Command Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #07090e;
            --card-bg: rgba(18, 24, 38, 0.75);
            --border-glow: rgba(99, 102, 241, 0.2);
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
            --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-dark); color: var(--text-main); display: flex; min-height: 100vh; overflow-x: hidden; }

        /* Glassmorphism Sidebar */
        .sidebar {
            width: 280px;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(16px);
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
        }
        .sidebar-brand {
            font-size: 22px; font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            padding-bottom: 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }
        .nav-menu { list-style: none; display: flex; flex-direction: column; gap: 8px; }
        .nav-item a {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 18px; color: var(--text-muted);
            text-decoration: none; font-weight: 600; font-size: 14px;
            border-radius: 12px; transition: all 0.3s ease;
        }
        .nav-item a:hover, .nav-item.active a {
            background: var(--primary-gradient);
            color: #fff; box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
            transform: translateX(4px);
        }

        /* Main Display Layout */
        .main-content { flex: 1; padding: 36px; overflow-y: auto; }
        .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title { font-size: 28px; font-weight: 800; letter-spacing: -0.5px; }

        /* Dynamic Neon Glow Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 32px; }
        .stat-card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-glow);
            border-radius: 20px; padding: 24px;
            position: relative; overflow: hidden;
            transition: all 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); border-color: rgba(168, 85, 247, 0.5); }
        .stat-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px;
            background: var(--primary-gradient);
        }
        .stat-title { font-size: 13px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-value { font-size: 32px; font-weight: 800; margin-top: 8px; }

        /* Custom UI Forms & Panels */
        .card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px; padding: 28px; margin-bottom: 30px;
        }
        .card-header { font-size: 20px; font-weight: 700; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-size: 13px; color: #cbd5e1; font-weight: 600; }
        .form-control {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #fff; padding: 12px 16px; border-radius: 10px; font-size: 14px; outline: none; transition: 0.3s;
        }
        .form-control:focus { border-color: #a855f7; box-shadow: 0 0 12px rgba(168, 85, 247, 0.3); }

        /* Buttons & Badges */
        .btn {
            padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 13px;
            border: none; cursor: pointer; transition: all 0.3s; text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px; justify-content: center;
        }
        .btn-primary { background: var(--primary-gradient); color: #fff; }
        .btn-success { background: var(--success-gradient); color: #fff; }
        .btn-warning { background: var(--warning-gradient); color: #fff; }
        .btn-danger { background: var(--danger-gradient); color: #fff; }
        .btn:hover { transform: translateY(-2px); filter: brightness(1.1); }

        .alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; }
        .alert-success { background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #34d399; }
        .alert-error { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; }

        /* Modern Tables */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        th { padding: 14px 18px; font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; }
        td { background: rgba(15, 23, 42, 0.6); padding: 16px 18px; font-size: 14px; border-top: 1px solid rgba(255, 255, 255, 0.05); border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        td:first-child { border-top-left-radius: 12px; border-bottom-left-radius: 12px; }
        td:last-child { border-top-right-radius: 12px; border-bottom-right-radius: 12px; }

        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-active { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid #10b981; }
        .badge-suspended { background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid #f59e0b; }
        .badge-banned { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid #ef4444; }
        .badge-admin { background: rgba(168, 85, 247, 0.2); color: #c084fc; border: 1px solid #a855f7; }
    </style>
</head>
<body>

    <!-- Sidebar Engine -->
    <div class="sidebar">
        <div class="sidebar-brand">⚡ TC MASTER ENGINE</div>
        <ul class="nav-menu">
            <li class="nav-item <?= $active_tab == 'overview' ? 'active' : '' ?>"><a href="admin.php?tab=overview">📊 डैशबोर्ड ओवरव्यू</a></li>
            <li class="nav-item <?= $active_tab == 'pages' ? 'active' : '' ?>"><a href="admin.php?tab=pages">🌐 dynamic Page Synchronizer</a></li>
            <li class="nav-item <?= $active_tab == 'users' ? 'active' : '' ?>"><a href="admin.php?tab=users">👥 Ultra User Control</a></li>
            <li class="nav-item <?= $active_tab == 'withdrawals' ? 'active' : '' ?>"><a href="admin.php?tab=withdrawals">💳 विथड्रॉल हब (<?= $pending_w ?>)</a></li>
            <li class="nav-item <?= $active_tab == 'social_bot' ? 'active' : '' ?>"><a href="admin.php?tab=social_bot">🤖 Telegram & Social API</a></li>
        </ul>
    </div>

    <!-- Main Content Engine -->
    <div class="main-content">
        
        <?php if (!empty($msg)): ?>
            <div class="alert alert-<?= $msg_type ?>"><?= $msg ?></div>
        <?php endif; ?>

        <!-- TAB 1: OVERVIEW -->
        <?php if ($active_tab == 'overview'): ?>
            <div class="header-bar">
                <h1 class="page-title">📊 सिस्टम डैशबोर्ड</h1>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-title">कुल रजिस्टर्ड यूज़र्स</div>
                    <div class="stat-value" style="color:#6366f1;"><?= number_format($tot_users) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">लाइव सिस्टम पेजेस</div>
                    <div class="stat-value" style="color:#a855f7;"><?= number_format($tot_pages) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">कुल यूज़र वॉलेट बैलेंस</div>
                    <div class="stat-value" style="color:#10b981;">₹<?= number_format($tot_wallet, 2) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">पेंडिंग विथड्रॉल</div>
                    <div class="stat-value" style="color:#f59e0b;"><?= $pending_w ?></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">🚀 क्विक यूज़र वॉलेट एडजस्टमेंट</div>
                <form method="POST">
                    <input type="hidden" name="action" value="adjust_wallet">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>यूज़र ID</label>
                            <input type="number" name="user_id" class="form-control" placeholder="उदा. 1" required>
                        </div>
                        <div class="form-group">
                            <label>राशि (₹)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" placeholder="100.00" required>
                        </div>
                        <div class="form-group">
                            <label>प्रकार (Type)</label>
                            <select name="type" class="form-control">
                                <option value="credit">क्रेडिट (+ Add Money)</option>
                                <option value="debit">डेबिट (- Deduct Money)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>विवरण (Note)</label>
                            <input type="text" name="description" class="form-control" placeholder="Bonus / Adjustment" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:20px;">💰 बैलेंस अपडेट करें</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- TAB 2: DYNAMIC PAGE SYNCHRONIZER -->
        <?php if ($active_tab == 'pages'): ?>
            <div class="header-bar">
                <h1 class="page-title">🌐 Dynamic Page Dynamic Synchronizer</h1>
            </div>
            
            <div class="card">
                <div class="card-header">➕ नया पेज/लिंक बनाएं (यूज़र के सभी पेजों में Auto Sync होगा)</div>
                <form method="POST">
                    <input type="hidden" name="action" value="create_page">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>पेज टाइटल</label>
                            <input type="text" name="title" class="form-control" placeholder="उदा. Daily Rewards" required>
                        </div>
                        <div class="form-group">
                            <label>URL Slug (Unique Keyword)</label>
                            <input type="text" name="slug" class="form-control" placeholder="daily-rewards" required>
                        </div>
                        <div class="form-group">
                            <label>आइकन Emoji</label>
                            <input type="text" name="icon" class="form-control" value="🎁" required>
                        </div>
                        <div class="form-group">
                            <label>नेविगेशन ऑर्डर Priority</label>
                            <input type="number" name="nav_order" class="form-control" value="1" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top:16px;">
                        <label>पेज कंटेंट HTML / Custom Text</label>
                        <textarea name="content" class="form-control" rows="5" placeholder="<h1>Welcome to Daily Rewards</h1>"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success" style="margin-top:20px;">🚀 पेज सेव करें और ऑल-पेज सिंक चालू करें</button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">📑 वर्तमान लाइव डायनामिक पेजेस</div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ऑर्डर</th>
                                <th>आइकन & टाइटल</th>
                                <th>URL Slug</th>
                                <th>तारीख</th>
                                <th>एक्शन</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $pages_sql = mysqli_query($conn, "SELECT * FROM dynamic_pages ORDER BY nav_order ASC");
                            while ($pg = mysqli_fetch_assoc($pages_sql)):
                            ?>
                            <tr>
                                <td><b>#<?= $pg['nav_order'] ?></b></td>
                                <td><?= $pg['icon'] ?> <b><?= htmlspecialchars($pg['title']) ?></b></td>
                                <td><code>/page.php?slug=<?= $pg['slug'] ?></code></td>
                                <td><?= $pg['created_at'] ?></td>
                                <td>
                                    <a href="admin.php?tab=pages&delete_page=<?= $pg['id'] ?>" onclick="return confirm('क्या आप इस पेज को डिलीट करना चाहते हैं?');" class="btn btn-danger" style="padding: 4px 10px; font-size:11px;">Delete 🗑️</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB 3: ULTRA USER CONTROL -->
        <?php if ($active_tab == 'users'): ?>
            <div class="header-bar">
                <h1 class="page-title">👥 Ultra User Control Engine</h1>
            </div>
            
            <div class="card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>यूज़र डिटेल्स</th>
                                <th>रोल (Role)</th>
                                <th>वॉलेट बैलेंस</th>
                                <th>स्टेटस</th>
                                <th>कंट्रोल एक्शन्स (Actions)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $u_sql = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC LIMIT 50");
                            while ($u = mysqli_fetch_assoc($u_sql)):
                            ?>
                            <tr>
                                <td>#<?= $u['id'] ?></td>
                                <td>
                                    <b><?= htmlspecialchars($u['username']) ?></b><br>
                                    <small style="color:var(--text-muted);"><?= htmlspecialchars($u['upi_id'] ?? 'No UPI') ?></small>
                                </td>
                                <td><span class="badge badge-<?= $u['role'] ?>"><?= strtoupper($u['role']) ?></span></td>
                                <td style="color:#10b981; font-weight:800;">₹<?= number_format($u['wallet_balance'], 2) ?></td>
                                <td><span class="badge badge-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
                                <td>
                                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                        <!-- Activate / Suspend / Ban Controls -->
                                        <?php if ($u['status'] != 'active'): ?>
                                            <a href="admin.php?tab=users&user_action=activate&uid=<?= $u['id'] ?>" class="btn btn-success" style="padding:4px 8px; font-size:11px;">Activate ✅</a>
                                        <?php endif; ?>
                                        <?php if ($u['status'] != 'suspended'): ?>
                                            <a href="admin.php?tab=users&user_action=suspend&uid=<?= $u['id'] ?>" class="btn btn-warning" style="padding:4px 8px; font-size:11px;">Suspend ⚠️</a>
                                        <?php endif; ?>
                                        <?php if ($u['status'] != 'banned'): ?>
                                            <a href="admin.php?tab=users&user_action=ban&uid=<?= $u['id'] ?>" class="btn btn-danger" style="padding:4px 8px; font-size:11px;">Ban 🚫</a>
                                        <?php endif; ?>

                                        <!-- Permanent Delete Control -->
                                        <a href="admin.php?tab=users&user_action=delete&uid=<?= $u['id'] ?>" onclick="return confirm('क्या आप इस यूज़र को स्थायी रूप से मिटाना चाहते हैं?');" class="btn btn-danger" style="padding:4px 8px; font-size:11px; background:#450a0a;">Delete 🗑️</a>
                                    </div>

                                    <!-- Promote Role Form -->
                                    <form method="POST" style="margin-top:8px; display:flex; gap:4px;">
                                        <input type="hidden" name="action" value="promote_user">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <select name="role" class="form-control" style="padding:2px 6px; font-size:11px;">
                                            <option value="user" <?= $u['role']=='user'?'selected':'' ?>>User</option>
                                            <option value="vip" <?= $u['role']=='vip'?'selected':'' ?>>VIP</option>
                                            <option value="moderator" <?= $u['role']=='moderator'?'selected':'' ?>>Moderator</option>
                                            <option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>Admin</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary" style="padding:2px 8px; font-size:10px;">Set Role</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB 4: WITHDRAWALS -->
        <?php if ($active_tab == 'withdrawals'): ?>
            <div class="header-bar">
                <h1 class="page-title">💳 विथड्रॉल हब</h1>
            </div>
            <div class="card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>यूज़र ID</th>
                                <th>राशि</th>
                                <th>Paytm/UPI</th>
                                <th>तारीख</th>
                                <th>स्टेटस</th>
                                <th>एक्शन</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $w_sql = mysqli_query($conn, "SELECT * FROM withdrawals ORDER BY id DESC");
                            while ($w = mysqli_fetch_assoc($w_sql)):
                            ?>
                            <tr>
                                <td>#<?= $w['id'] ?></td>
                                <td>#<?= $w['user_id'] ?></td>
                                <td style="color:#6366f1; font-weight:bold;">₹<?= $w['amount'] ?></td>
                                <td><code><?= htmlspecialchars($w['upi_id']) ?></code></td>
                                <td><?= $w['created_at'] ?></td>
                                <td><span class="badge badge-<?= strtolower($w['status']) ?>"><?= $w['status'] ?></span></td>
                                <td>
                                    <?php if ($w['status'] == 'Pending'): ?>
                                        <a href="admin.php?tab=withdrawals&action_w=approve&wid=<?= $w['id'] ?>" class="btn btn-success" style="padding:4px 8px; font-size:11px;">Approve ✅</a>
                                        <a href="admin.php?tab=withdrawals&action_w=reject&wid=<?= $w['id'] ?>" class="btn btn-danger" style="padding:4px 8px; font-size:11px;">Reject ❌</a>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:12px;">Processed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB 5: TELEGRAM & SOCIAL BOT -->
        <?php if ($active_tab == 'social_bot'): ?>
            <div class="header-bar">
                <h1 class="page-title">🤖 Telegram & Social Integration</h1>
            </div>
            <div class="card">
                <form method="POST">
                    <input type="hidden" name="action" value="save_settings">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Telegram Bot Token</label>
                            <input type="text" name="telegram_bot_token" class="form-control" value="<?= htmlspecialchars(getSetting($conn, 'telegram_bot_token')) ?>">
                        </div>
                        <div class="form-group">
                            <label>Admin Chat ID</label>
                            <input type="text" name="telegram_chat_id" class="form-control" value="<?= htmlspecialchars(getSetting($conn, 'telegram_chat_id')) ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success" style="margin-top:20px;">💾 सेटिंग्स सेव करें</button>
                </form>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
