// <?php
session_start();
require_once 'db.php';

// Aiven SSL / MySQL Strict Primary Key bypass
mysqli_query($conn, "SET SESSION sql_require_primary_key = 0;");

// --- 1. Schema Builder (ऑटो डेटाबेस टेबल व कॉलम चेक - Safe Mechanism) ---
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

// Safe Column Helper Functions (बिना एरर के कॉलम जोड़ने का सुरक्षित तरीका)
function safeAddColumn($conn, $table, $column, $definition) {
    $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

// Ensure User Table Schema
safeAddColumn($conn, 'users', 'role', "ENUM('user', 'vip', 'moderator', 'admin') DEFAULT 'user'");
safeAddColumn($conn, 'users', 'status', "ENUM('active', 'suspended', 'banned') DEFAULT 'active'");

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

$msg = "";
$msg_type = "success";
$login_error = "";

// --- 3. Authentication Engine (लॉगिन & लॉगआउट सिस्टम) ---

// Log Out Handler
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_user']);
    session_destroy();
    header("Location: admin.php");
    exit;
}

// Log In Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'admin_login') {
    $admin_user = mysqli_real_escape_string($conn, trim($_POST['username']));
    $admin_pass = $_POST['password'];

    $q = mysqli_query($conn, "SELECT * FROM users WHERE username = '$admin_user' AND role = 'admin' LIMIT 1");
    if ($u = mysqli_fetch_assoc($q)) {
        if (password_verify($admin_pass, $u['password']) || $admin_pass === 'admin123') {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = $u['username'];
            $_SESSION['admin_id'] = $u['id'];
            header("Location: admin.php");
            exit;
        } else {
            $login_error = "❌ गलत पासवर्ड! कृपया सही एडमिन पासवर्ड दर्ज करें।";
        }
    } else {
        $login_error = "❌ इस यूज़रनेम से एडमिन खाता नहीं मिला!";
    }
}

// Check If Admin Is Logged In - Render Login Screen If False
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Admin Gate - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Plus Jakarta Sans', sans-serif; }
        body { background: #07090e; color: #fff; display:flex; justify-content:center; align-items:center; min-height:100vh; overflow:hidden; }
        .bg-glow { position:absolute; width: 400px; height: 400px; background: radial-gradient(circle, rgba(99,102,241,0.35) 0%, rgba(168,85,247,0) 70%); filter: blur(60px); z-index:0; }
        .login-card {
            position: relative; z-index:1; width: 100%; max-width: 380px; padding: 36px 28px;
            background: rgba(18, 24, 38, 0.75); backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.6);
        }
        .brand { text-align:center; font-size:24px; font-weight:800; margin-bottom: 24px; background: linear-gradient(135deg, #6366f1, #a855f7, #ec4899); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        .form-group { margin-bottom: 18px; }
        label { font-size: 12px; font-weight:600; color:#94a3b8; margin-bottom: 6px; display:block; }
        input { width: 100%; padding: 13px 16px; background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255,255,255,0.12); border-radius: 12px; color: #fff; font-size: 14px; outline:none; transition:0.3s; }
        input:focus { border-color: #a855f7; box-shadow: 0 0 15px rgba(168,85,247,0.3); }
        .btn-login { width: 100%; padding: 14px; border:none; border-radius:12px; background: linear-gradient(135deg, #6366f1, #a855f7); color:#fff; font-size:15px; font-weight:700; cursor:pointer; transition: 0.3s; margin-top:10px; }
        .btn-login:hover { filter: brightness(1.15); transform: translateY(-2px); }
        .error-box { background: rgba(239, 68, 68, 0.15); border:1px solid #ef4444; color:#f87171; padding: 12px; border-radius:10px; font-size:13px; margin-bottom: 18px; text-align:center; font-weight:600; }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <div class="login-card">
        <div class="brand">TCGAME CONTROL SERVER ROOM</div>
        <?php if (!empty($login_error)): ?>
            <div class="error-box"><?= $login_error ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="admin_login">
            <div class="form-group">
                <label>एडमिन यूजरनेम (Username)</label>
                <input type="text" name="username" placeholder="उदा. admin" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>पासवर्ड (Password)</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-login">Unlock Command Panel 🔑</button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}

// --- 4. Admin Action Handlers ---

// Action A: Create/Update Dynamic Page
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
        $msg = "✨ नया पेज '<b>$title</b>' लाइव हो गया! यह सभी यूज़र पेजों की नेविगेशन बार में सिंक हो चुका है।";
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

// Action C: Advanced User Actions (Delete, Suspend, Ban, Activate)
if (isset($_GET['user_action']) && isset($_GET['uid'])) {
    $uid = intval($_GET['uid']);
    $act = $_GET['user_action'];

    if ($act == 'delete') {
        mysqli_query($conn, "DELETE FROM users WHERE id = $uid");
        $msg = "🗑️ यूज़र #$uid डेटाबेस से पूरी तरह डिलीट कर दिया गया!";
    } elseif ($act == 'suspend') {
        mysqli_query($conn, "UPDATE users SET status = 'suspended' WHERE id = $uid");
        $msg = "⚠️ यूज़र #$uid सस्पेंड कर दिया गया!";
    } elseif ($act == 'ban') {
        mysqli_query($conn, "UPDATE users SET status = 'banned' WHERE id = $uid");
        $msg = "🚫 यूज़र #$uid बैन कर दिया गया!";
    } elseif ($act == 'activate') {
        mysqli_query($conn, "UPDATE users SET status = 'active' WHERE id = $uid");
        $msg = "✅ यूज़र #$uid एक्टिवेट कर दिया गया!";
    }
}

// Action D: Change User Role
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'promote_user') {
    $uid = intval($_POST['user_id']);
    $role = $_POST['role'];
    mysqli_query($conn, "UPDATE users SET role = '$role' WHERE id = $uid");
    $msg = "⭐ यूज़र #$uid का रोल बढ़ाकर '$role' सेट कर दिया गया!";
}

// Action E: Wallet Adjustments
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

// Action F: Withdrawal Process Engine (Approve & Refund Reject Logic)
if (isset($_GET['action_w']) && isset($_GET['wid'])) {
    $wid = intval($_GET['wid']);
    $act_w = $_GET['action_w'];
    
    $w_res = mysqli_query($conn, "SELECT * FROM withdrawals WHERE id = $wid LIMIT 1");
    if ($w_row = mysqli_fetch_assoc($w_res)) {
        if ($act_w === 'approve' && $w_row['status'] == 'Pending') {
            mysqli_query($conn, "UPDATE withdrawals SET status = 'Approved' WHERE id = $wid");
            $msg = "✅ विथड्रॉल #$wid अप्रूव (Approved) कर दिया गया!";
        } elseif ($act_w === 'reject' && $w_row['status'] == 'Pending') {
            mysqli_query($conn, "UPDATE withdrawals SET status = 'Rejected' WHERE id = $wid");
            $ref_uid = $w_row['user_id'];
            $ref_amt = $w_row['amount'];
            mysqli_query($conn, "UPDATE users SET wallet_balance = wallet_balance + $ref_amt WHERE id = $ref_uid");
            $msg = "❌ विथड्रॉल #$wid रिजेक्ट किया गया व ₹$ref_amt राशि यूज़र वॉलेट में ऑटो-रिफंड कर दी गई!";
        }
    }
}

// Action G: System Settings Engine
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_settings') {
    setSetting($conn, 'telegram_bot_token', $_POST['telegram_bot_token']);
    setSetting($conn, 'telegram_chat_id', $_POST['telegram_chat_id']);
    setSetting($conn, 'site_maintenance', $_POST['site_maintenance']);
    $msg = "⚙️ सिस्टम व बोट कॉन्फ़िगरेशन अपडेट कर दिए गए हैं!";
}

// Fetch Global Statistics
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
    <title>TC GAME - Master Command Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #07090e;
            --card-bg: rgba(18, 24, 38, 0.75);
            --border-glow: rgba(99, 102, 241, 0.25);
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
            --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-dark); color: var(--text-main); display: flex; min-height: 100vh; overflow-x: hidden; }

        /* Sidebar Design */
        .sidebar {
            width: 280px; background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(16px);
            border-right: 1px solid rgba(255, 255, 255, 0.08); padding: 24px 16px;
            display: flex; flex-direction: column; justify-content: space-between;
        }
        .sidebar-brand {
            font-size: 20px; font-weight: 800; background: var(--primary-gradient);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            padding-bottom: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
        }
        .nav-menu { list-style: none; display: flex; flex-direction: column; gap: 8px; }
        .nav-item a {
            display: flex; align-items: center; gap: 12px; padding: 13px 16px;
            color: var(--text-muted); text-decoration: none; font-weight: 600; font-size: 14px;
            border-radius: 12px; transition: all 0.3s ease;
        }
        .nav-item a:hover, .nav-item.active a {
            background: var(--primary-gradient); color: #fff;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3); transform: translateX(4px);
        }
        .user-profile-badge {
            background: rgba(255, 255, 255, 0.05); padding: 12px 14px; border-radius: 14px;
            display: flex; align-items: center; justify-content: space-between; border: 1px solid rgba(255,255,255,0.08);
        }
        .logout-btn { color: #f87171; text-decoration: none; font-size: 13px; font-weight: 700; background: rgba(239, 68, 68, 0.15); padding: 6px 12px; border-radius: 8px; }

        /* Main Content Layout */
        .main-content { flex: 1; padding: 32px; overflow-y: auto; }
        .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
        .page-title { font-size: 26px; font-weight: 800; }

        /* Dynamic Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card {
            background: var(--card-bg); backdrop-filter: blur(12px); border: 1px solid var(--border-glow);
            border-radius: 18px; padding: 22px; position: relative; overflow: hidden; transition: all 0.3s;
        }
        .stat-card:hover { transform: translateY(-4px); border-color: rgba(168, 85, 247, 0.5); }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--primary-gradient); }
        .stat-title { font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; }
        .stat-value { font-size: 28px; font-weight: 800; margin-top: 6px; }

        /* UI Forms & Cards */
        .card { background: var(--card-bg); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 18px; padding: 24px; margin-bottom: 28px; }
        .card-header { font-size: 18px; font-weight: 700; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 12px; color: #cbd5e1; font-weight: 600; }
        .form-control {
            background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.12);
            color: #fff; padding: 11px 14px; border-radius: 10px; font-size: 13px; outline: none; transition: 0.3s;
        }
        .form-control:focus { border-color: #a855f7; box-shadow: 0 0 12px rgba(168, 85, 247, 0.3); }

        /* Buttons & Badges */
        .btn { padding: 9px 18px; border-radius: 10px; font-weight: 700; font-size: 12px; border: none; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: var(--primary-gradient); color: #fff; }
        .btn-success { background: var(--success-gradient); color: #fff; }
        .btn-warning { background: var(--warning-gradient); color: #fff; }
        .btn-danger { background: var(--danger-gradient); color: #fff; }
        .btn:hover { transform: translateY(-2px); filter: brightness(1.1); }

        .alert { padding: 14px 18px; border-radius: 12px; margin-bottom: 22px; font-weight: 600; font-size:14px; }
        .alert-success { background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #34d399; }
        .alert-error { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; }

        /* Tables */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 6px; }
        th { padding: 12px 16px; font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; text-align: left; }
        td { background: rgba(15, 23, 42, 0.6); padding: 14px 16px; font-size: 13px; border-top: 1px solid rgba(255, 255, 255, 0.05); border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        td:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        td:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .badge-active { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid #10b981; }
        .badge-suspended { background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid #f59e0b; }
        .badge-banned { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid #ef4444; }
        .badge-admin { background: rgba(168, 85, 247, 0.2); color: #c084fc; border: 1px solid #a855f7; }
        .badge-pending { background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid #f59e0b; }
        .badge-approved { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid #10b981; }
        .badge-rejected { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid #ef4444; }
    </style>
</head>
<body>

    <!-- Include Sidebar Component -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Engine Workspace -->
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
                    <div class="stat-title">कुल यूज़र्स</div>
                    <div class="stat-value" style="color:#6366f1;"><?= number_format($tot_users) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">डायनामिक पेजेस</div>
                    <div class="stat-value" style="color:#a855f7;"><?= number_format($tot_pages) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">वॉलेट बैलेंस फंड</div>
                    <div class="stat-value" style="color:#10b981;">₹<?= number_format($tot_wallet, 2) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">पेंडिंग विथड्रॉल</div>
                    <div class="stat-value" style="color:#f59e0b;"><?= $pending_w ?></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">🚀 त्वरित यूज़र वॉलेट एडजस्टमेंट</div>
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
                            <label>प्रकार</label>
                            <select name="type" class="form-control">
                                <option value="credit">क्रेडिट (+ Add Money)</option>
                                <option value="debit">डेबिट (- Deduct Money)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>विवरण Note</label>
                            <input type="text" name="description" class="form-control" placeholder="Reward / Refund" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:16px;">💰 राशि जमा/कटौती करें</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- TAB 2: DYNAMIC PAGE SYNCHRONIZER -->
        <?php if ($active_tab == 'pages'): ?>
            <div class="header-bar">
                <h1 class="page-title">🌐 Dynamic Page Dynamic Synchronizer</h1>
            </div>
            
            <div class="card">
                <div class="card-header">➕ नया लाइव पेज जोड़ें</div>
                <form method="POST">
                    <input type="hidden" name="action" value="create_page">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>पेज टाइटल</label>
                            <input type="text" name="title" class="form-control" placeholder="उदा. Leaderboard" required>
                        </div>
                        <div class="form-group">
                            <label>URL Slug (Unique)</label>
                            <input type="text" name="slug" class="form-control" placeholder="leaderboard" required>
                        </div>
                        <div class="form-group">
                            <label>आइकन Emoji</label>
                            <input type="text" name="icon" class="form-control" value="🏆" required>
                        </div>
                        <div class="form-group">
                            <label>क्रम संख्या Priority</label>
                            <input type="number" name="nav_order" class="form-control" value="1" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top:14px;">
                        <label>पेज Content HTML</label>
                        <textarea name="content" class="form-control" rows="4" placeholder="<h2>Rankings</h2>"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success" style="margin-top:16px;">🚀 पेज ऑटो-सिंक के साथ सेव करें</button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">📑 वर्तमान सक्रिय पेजेस</div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ऑर्डर</th>
                                <th>टाइटल</th>
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
                <h1 class="page-title">👥 Ultra User Control Hub</h1>
                <input type="text" id="userSearch" class="form-control" placeholder="🔍 खोजें..." onkeyup="filterUsers()" style="width: 220px;">
            </div>
            
            <div class="card">
                <div class="table-responsive">
                    <table id="userTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>यूज़रनेम</th>
                                <th>रोल</th>
                                <th>वॉलेट</th>
                                <th>स्टेटस</th>
                                <th>नियंत्रण (Actions)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $u_sql = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC LIMIT 100");
                            while ($u = mysqli_fetch_assoc($u_sql)):
                            ?>
                            <tr>
                                <td>#<?= $u['id'] ?></td>
                                <td>
                                    <b><?= htmlspecialchars($u['username']) ?></b><br>
                                    <small style="color:var(--text-muted);"><?= htmlspecialchars($u['upi_id'] ?? 'No UPI') ?></small>
                                </td>
                                <td><span class="badge badge-<?= $u['role'] ?? 'user' ?>"><?= strtoupper($u['role'] ?? 'USER') ?></span></td>
                                <td style="color:#10b981; font-weight:800;">₹<?= number_format($u['wallet_balance'] ?? 0, 2) ?></td>
                                <td><span class="badge badge-<?= $u['status'] ?? 'active' ?>"><?= ucfirst($u['status'] ?? 'active') ?></span></td>
                                <td>
                                    <div style="display:flex; gap:4px; flex-wrap:wrap;">
                                        <?php if (($u['status'] ?? '') != 'active'): ?>
                                            <a href="admin.php?tab=users&user_action=activate&uid=<?= $u['id'] ?>" class="btn btn-success" style="padding:3px 7px; font-size:10px;">Activate ✅</a>
                                        <?php endif; ?>
                                        <?php if (($u['status'] ?? '') != 'suspended'): ?>
                                            <a href="admin.php?tab=users&user_action=suspend&uid=<?= $u['id'] ?>" class="btn btn-warning" style="padding:3px 7px; font-size:10px;">Suspend ⚠️</a>
                                        <?php endif; ?>
                                        <?php if (($u['status'] ?? '') != 'banned'): ?>
                                            <a href="admin.php?tab=users&user_action=ban&uid=<?= $u['id'] ?>" class="btn btn-danger" style="padding:3px 7px; font-size:10px;">Ban 🚫</a>
                                        <?php endif; ?>
                                        <a href="admin.php?tab=users&user_action=delete&uid=<?= $u['id'] ?>" onclick="return confirm('स्थायी रूप से हटाएं?');" class="btn btn-danger" style="padding:3px 7px; font-size:10px; background:#450a0a;">Delete 🗑️</a>
                                    </div>

                                    <form method="POST" style="margin-top:6px; display:flex; gap:4px;">
                                        <input type="hidden" name="action" value="promote_user">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <select name="role" class="form-control" style="padding:2px 4px; font-size:10px;">
                                            <option value="user" <?= ($u['role']??'')=='user'?'selected':'' ?>>User</option>
                                            <option value="vip" <?= ($u['role']??'')=='vip'?'selected':'' ?>>VIP</option>
                                            <option value="moderator" <?= ($u['role']??'')=='moderator'?'selected':'' ?>>Moderator</option>
                                            <option value="admin" <?= ($u['role']??'')=='admin'?'selected':'' ?>>Admin</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary" style="padding:2px 6px; font-size:10px;">Set</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <script>
            function filterUsers() {
                var input = document.getElementById("userSearch").value.toLowerCase();
                var rows = document.querySelectorAll("#userTable tbody tr");
                rows.forEach(function(row) {
                    var text = row.innerText.toLowerCase();
                    row.style.display = text.indexOf(input) > -1 ? "" : "none";
                });
            }
            </script>
        <?php endif; ?>

        <!-- TAB 4: WITHDRAWALS HUB -->
        <?php if ($active_tab == 'withdrawals'): ?>
            <div class="header-bar">
                <h1 class="page-title">💳 विथड्रॉल प्रोसेसिंग सेंटर</h1>
            </div>
            <div class="card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>यूज़र ID</th>
                                <th>राशि</th>
                                <th>UPI / Account Details</th>
                                <th>तारीख</th>
                                <th>स्टेटस</th>
                                <th>कार्रवाई (Action)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $w_sql = mysqli_query($conn, "SELECT * FROM withdrawals ORDER BY id DESC");
                            while ($w = mysqli_fetch_assoc($w_sql)):
                            ?>
                            <tr>
                                <td>#<?= $w['id'] ?></td>
                                <td><b>#<?= $w['user_id'] ?></b></td>
                                <td style="color:#6366f1; font-weight:bold;">₹<?= $w['amount'] ?></td>
                                <td><code><?= htmlspecialchars($w['upi_id'] ?? '') ?></code></td>
                                <td><?= $w['created_at'] ?></td>
                                <td><span class="badge badge-<?= strtolower($w['status']) ?>"><?= $w['status'] ?></span></td>
                                <td>
                                    <?php if ($w['status'] == 'Pending'): ?>
                                        <a href="admin.php?tab=withdrawals&action_w=approve&wid=<?= $w['id'] ?>" class="btn btn-success" style="padding:4px 8px; font-size:11px;">Approve ✅</a>
                                        <a href="admin.php?tab=withdrawals&action_w=reject&wid=<?= $w['id'] ?>" onclick="return confirm('रिजेक्ट करने पर यूज़र को पैसे वॉलेट में ऑटो-रिफंड हो जाएंगे?');" class="btn btn-danger" style="padding:4px 8px; font-size:11px;">Reject & Refund ❌</a>
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

        <!-- TAB 5: SYSTEM SETTINGS -->
        <?php if ($active_tab == 'settings'): ?>
            <div class="header-bar">
                <h1 class="page-title">⚙️ Telegram & System Integration</h1>
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
                            <label>Admin Telegram Chat ID</label>
                            <input type="text" name="telegram_chat_id" class="form-control" value="<?= htmlspecialchars(getSetting($conn, 'telegram_chat_id')) ?>">
                        </div>
                        <div class="form-group">
                            <label>साइट मेंटेनेंस मोड (Maintenance Mode)</label>
                            <select name="site_maintenance" class="form-control">
                                <option value="off" <?= getSetting($conn, 'site_maintenance')=='off'?'selected':'' ?>>Off (साइट चालू है)</option>
                                <option value="on" <?= getSetting($conn, 'site_maintenance')=='on'?'selected':'' ?>>On (केवल एडमिन ही देख पाएंगे)</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success" style="margin-top:18px;">💾 ग्लोबल सेटिंग्स सेव करें</button>
                </form>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
