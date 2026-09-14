<?php
require_once 'db.php';

// Aiven SSL / Primary key requirement handling
mysqli_query($conn, "SET SESSION sql_require_primary_key = 0;");

// Auto-create site_settings table if not exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
) ENGINE=InnoDB;");

// Helper function to get setting
function getSetting($conn, $key, $default = '') {
    $res = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key = '$key'");
    if ($res && $row = mysqli_fetch_assoc($res)) {
        return $row['setting_value'];
    }
    return $default;
}

// Helper function to set setting
function setSetting($conn, $key, $value) {
    $val = mysqli_real_escape_string($conn, $value);
    mysqli_query($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES ('$key', '$val') ON DUPLICATE KEY UPDATE setting_value = '$val'");
}

// Helper function to send Telegram Bot Message
function sendTelegramNotification($bot_token, $chat_id, $message) {
    if (empty($bot_token) || empty($chat_id)) return false;
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
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

// --- Handle Form Submissions ---

// 1. Save Telegram & Social Settings
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_settings') {
    setSetting($conn, 'telegram_bot_token', $_POST['telegram_bot_token']);
    setSetting($conn, 'telegram_chat_id', $_POST['telegram_chat_id']);
    setSetting($conn, 'whatsapp_number', $_POST['whatsapp_number']);
    setSetting($conn, 'facebook_link', $_POST['facebook_link']);
    setSetting($conn, 'mlm_l1_percent', $_POST['mlm_l1_percent']);
    setSetting($conn, 'mlm_l2_percent', $_POST['mlm_l2_percent']);
    
    $msg = "⚙️ सभी सेटिंग्स, Telegram और Social API कॉन्फ़िगरेशन सफलतापूर्वक सेव हो गए!";
}

// 2. Test Telegram Bot
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'test_telegram') {
    $token = getSetting($conn, 'telegram_bot_token');
    $chat_id = getSetting($conn, 'telegram_chat_id');
    if ($token && $chat_id) {
        $test_msg = "🚀 <b>TC Game Bot Connection Successful!</b>\n\nआपका Telegram Bot अब सर्वर्स से कनेक्टेड है और लाइव अलर्ट भेजने के लिए तैयार है।";
        sendTelegramNotification($token, $chat_id, $test_msg);
        $msg = "🤖 टेलीग्राम टेस्ट मैसेज भेज दिया गया है! अपना Telegram चेक करें।";
    } else {
        $msg = "❌ कृपया पहले Telegram Bot Token और Admin Chat ID दर्ज करें!";
        $msg_type = "error";
    }
}

// 3. User Wallet Adjustment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'adjust_wallet') {
    $uid = intval($_POST['user_id']);
    $amount = floatval($_POST['amount']);
    $type = $_POST['type']; // credit or debit
    $desc = mysqli_real_escape_string($conn, $_POST['description']);

    if ($type == 'credit') {
        mysqli_query($conn, "UPDATE users SET wallet_balance = wallet_balance + $amount WHERE id = $uid");
    } else {
        mysqli_query($conn, "UPDATE users SET wallet_balance = wallet_balance - $amount WHERE id = $uid");
    }
    
    mysqli_query($conn, "INSERT INTO transactions (user_id, amount, type, category, description) VALUES ($uid, $amount, '$type', 'admin_adjustment', '$desc')");
    $msg = "💰 यूज़र ID #$uid का वॉलेट सफलता पूर्वक अपडेट किया गया!";
}

// 4. User Status Change (Ban / Unban)
if (isset($_GET['toggle_ban'])) {
    $uid = intval($_GET['toggle_ban']);
    $status = $_GET['status'] == 'banned' ? 'active' : 'banned';
    mysqli_query($conn, "UPDATE users SET status = '$status' WHERE id = $uid");
    header("Location: admin.php?tab=users");
    exit;
}

// 5. Create Contest
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_contest') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $fee = floatval($_POST['entry_fee']);
    $prize = floatval($_POST['prize_pool']);
    $admin_p = floatval($_POST['admin_profit']);
    $l1 = floatval($_POST['mlm_l1']);
    $l2 = floatval($_POST['mlm_l2']);

    mysqli_query($conn, "INSERT INTO contests (title, entry_fee, prize_pool, admin_profit, mlm_level1_commission, mlm_level2_commission, status) VALUES ('$title', $fee, $prize, $admin_p, $l1, $l2, 'active')");
    $msg = "🏆 नया गेम/कॉन्टेस्ट '$title' सफलतापूर्वक चालू किया गया!";
}

// 6. Create Question
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_question') {
    $q = mysqli_real_escape_string($conn, $_POST['question_text']);
    $a = mysqli_real_escape_string($conn, $_POST['option_a']);
    $b = mysqli_real_escape_string($conn, $_POST['option_b']);
    $c = mysqli_real_escape_string($conn, $_POST['option_c']);
    $d = mysqli_real_escape_string($conn, $_POST['option_d']);
    $ans = $_POST['correct_option'];
    $cat = mysqli_real_escape_string($conn, $_POST['category']);

    mysqli_query($conn, "INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_option, category) VALUES ('$q', '$a', '$b', '$c', '$d', '$ans', '$cat')");
    $msg = "❓ नया सवाल सफ़लतापूर्वक बैंक में जोड़ा गया!";
}

// 7. Process Withdrawal Request
if (isset($_GET['action_w']) && isset($_GET['wid'])) {
    $wid = intval($_GET['wid']);
    $status = $_GET['action_w'] == 'approve' ? 'Approved' : 'Rejected';
    
    // Fetch withdrawal details
    $w_res = mysqli_query($conn, "SELECT * FROM withdrawals WHERE id = $wid");
    if ($w_row = mysqli_fetch_assoc($w_res)) {
        if ($w_row['status'] == 'Pending') {
            mysqli_query($conn, "UPDATE withdrawals SET status = '$status' WHERE id = $wid");
            
            $uid = $w_row['user_id'];
            $amt = $w_row['amount'];

            if ($status == 'Rejected') {
                // Refund money back to user wallet
                mysqli_query($conn, "UPDATE users SET wallet_balance = wallet_balance + $amt WHERE id = $uid");
                mysqli_query($conn, "INSERT INTO transactions (user_id, amount, type, category, description) VALUES ($uid, $amt, 'credit', 'withdrawal', 'Withdrawal Rejected Refund')");
            }

            // Send Telegram Bot Notification to Admin
            $bot_token = getSetting($conn, 'telegram_bot_token');
            $admin_chat = getSetting($conn, 'telegram_chat_id');
            if ($bot_token && $admin_chat) {
                $icon = $status == 'Approved' ? '✅' : '❌';
                $t_text = "{$icon} <b>Withdrawal {$status}!</b>\n\n<b>User ID:</b> #{$uid}\n<b>Amount:</b> ₹{$amt}\n<b>UPI ID:</b> {$w_row['upi_id']}";
                sendTelegramNotification($bot_token, $admin_chat, $t_text);
            }

            $msg = "💳 विथड्रॉल ID #$wid $status कर दिया गया है!";
        }
    }
}

// --- Fetch Dashboard Counters ---
$tot_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users"))['c'] ?? 0;
$tot_wallet = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(wallet_balance) as s FROM users"))['s'] ?? 0;
$pending_w = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM withdrawals WHERE status='Pending'"))['c'] ?? 0;
$tot_profit = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as s FROM admin_earnings"))['s'] ?? 0;

$active_tab = $_GET['tab'] ?? 'overview';
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TC Game - Super Admin Control Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: #0b0f19; color: #f1f5f9; display: flex; min-height: 100vh; }
        
        /* Sidebar Navigation */
        .sidebar { width: 260px; background: #111827; border-right: 1px solid #1f2937; padding: 20px 0; display: flex; flex-direction: column; }
        .sidebar-brand { padding: 0 20px 20px; font-size: 20px; font-weight: 700; color: #38bdf8; border-bottom: 1px solid #1f2937; display: flex; align-items: center; gap: 10px; }
        .nav-menu { list-style: none; margin-top: 20px; }
        .nav-item a { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #9ca3af; text-decoration: none; font-weight: 500; font-size: 14px; transition: all 0.2s; }
        .nav-item a:hover, .nav-item.active a { background: #1e293b; color: #38bdf8; border-left: 4px solid #38bdf8; }
        
        /* Main Layout */
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-title { font-size: 24px; font-weight: 700; color: #f8fafc; }
        
        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 20px; }
        .stat-title { font-size: 13px; color: #94a3b8; text-transform: uppercase; font-weight: 600; margin-bottom: 8px; }
        .stat-value { font-size: 26px; font-weight: 700; color: #f8fafc; }
        .stat-subtitle { font-size: 12px; color: #38bdf8; margin-top: 4px; }
        
        /* Cards & Forms */
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 24px; margin-bottom: 25px; }
        .card-header { font-size: 18px; font-weight: 600; color: #f8fafc; margin-bottom: 20px; border-bottom: 1px solid #334155; padding-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 13px; color: #cbd5e1; font-weight: 500; }
        .form-control { background: #0f172a; border: 1px solid #334155; color: #fff; padding: 10px 14px; border-radius: 8px; font-size: 14px; outline: none; }
        .form-control:focus { border-color: #38bdf8; }
        
        /* Buttons */
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; border: none; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-primary { background: #0284c7; color: #fff; }
        .btn-primary:hover { background: #0369a1; }
        .btn-success { background: #16a34a; color: #fff; }
        .btn-success:hover { background: #15803d; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-warning { background: #d97706; color: #fff; }
        
        /* Alert Message */
        .alert { padding: 14px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 500; font-size: 14px; }
        .alert-success { background: #064e3b; color: #6ee7b7; border: 1px solid #047857; }
        .alert-error { background: #7f1d1d; color: #fca5a5; border: 1px solid #b91c1c; }
        
        /* Table Styling */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #0f172a; padding: 12px 16px; font-size: 13px; color: #94a3b8; font-weight: 600; text-transform: uppercase; border-bottom: 1px solid #334155; }
        td { padding: 14px 16px; border-bottom: 1px solid #334155; font-size: 14px; color: #e2e8f0; }
        tr:hover { background: #1a2332; }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .badge-active { background: #065f46; color: #34d399; }
        .badge-banned { background: #831843; color: #f472b6; }
        .badge-pending { background: #78350f; color: #fbbf24; }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">⚡ TC GAME ADMIN</div>
        <ul class="nav-menu">
            <li class="nav-item <?= $active_tab == 'overview' ? 'active' : '' ?>"><a href="admin.php?tab=overview">📊 डैशबोर्ड ओवरव्यू</a></li>
            <li class="nav-item <?= $active_tab == 'users' ? 'active' : '' ?>"><a href="admin.php?tab=users">👥 यूज़र मैनेजमेंट</a></li>
            <li class="nav-item <?= $active_tab == 'withdrawals' ? 'active' : '' ?>"><a href="admin.php?tab=withdrawals">💳 विथड्रॉल (<?= $pending_w ?>)</a></li>
            <li class="nav-item <?= $active_tab == 'contests' ? 'active' : '' ?>"><a href="admin.php?tab=contests">🏆 कॉन्टेस्ट मैनेजर</a></li>
            <li class="nav-item <?= $active_tab == 'questions' ? 'active' : '' ?>"><a href="admin.php?tab=questions">❓ क्वेश्चन बैंक</a></li>
            <li class="nav-item <?= $active_tab == 'social_bot' ? 'active' : '' ?>"><a href="admin.php?tab=social_bot">🤖 Telegram & Social Bot</a></li>
            <li class="nav-item <?= $active_tab == 'revenue' ? 'active' : '' ?>"><a href="admin.php?tab=revenue">📈 एडमिन कमाई लेजर</a></li>
        </ul>
    </div>

    <!-- Main Content -->
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
                    <div class="stat-value"><?= number_format($tot_users) ?></div>
                    <div class="stat-subtitle">लाइव डेटाबेस</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">यूज़र कुल वॉलेट बैलेंस</div>
                    <div class="stat-value">₹<?= number_format($tot_wallet, 2) ?></div>
                    <div class="stat-subtitle">लाइव लायबिलिटी</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">पेंडिंग विथड्रॉल</div>
                    <div class="stat-value" style="color: #fbbf24;"><?= $pending_w ?></div>
                    <div class="stat-subtitle">तुरंत कार्रवाई योग्य</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">कुल एडमिन नेट प्रॉफिट</div>
                    <div class="stat-value" style="color: #4ade80;">₹<?= number_format($tot_profit, 2) ?></div>
                    <div class="stat-subtitle">100% गारंटीकृत कमाई</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">🚀 क्विक वॉलेट एडजस्टमेंट (किसी भी यूज़र को पैसे भेजें या काटें)</div>
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
                                <option value="credit">क्रेडिट (+) बैलेंस बढ़ाएं</option>
                                <option value="debit">डेबिट (-) बैलेंस काटें</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>विवरण / रिज़न</label>
                            <input type="text" name="description" class="form-control" placeholder="Bonus / Adjustment" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">💰 बैलेंस अपडेट करें</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- TAB 2: USERS MANAGEMENT -->
        <?php if ($active_tab == 'users'): ?>
            <div class="header-bar">
                <h1 class="page-title">👥 यूज़र मैनेजमेंट</h1>
            </div>
            <div class="card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>यूज़रनेम</th>
                                <th>UPI ID</th>
                                <th>वॉलेट बैलेंस</th>
                                <th>कुल कमाई</th>
                                <th>रिफ़रल कोड</th>
                                <th>रिफ़र किया गया ID</th>
                                <th>स्टेटस</th>
                                <th>एक्शन</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $u_sql = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC LIMIT 50");
                            while ($u = mysqli_fetch_assoc($u_sql)):
                            ?>
                            <tr>
                                <td>#<?= $u['id'] ?></td>
                                <td><b><?= htmlspecialchars($u['username']) ?></b></td>
                                <td><?= htmlspecialchars($u['upi_id'] ?? 'N/A') ?></td>
                                <td style="color:#4ade80; font-weight:bold;">₹<?= $u['wallet_balance'] ?></td>
                                <td>₹<?= $u['total_earnings'] ?></td>
                                <td><code><?= $u['referral_code'] ?></code></td>
                                <td><?= $u['referral_by'] ? '#'.$u['referral_by'] : 'None' ?></td>
                                <td><span class="badge badge-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
                                <td>
                                    <a href="admin.php?toggle_ban=<?= $u['id'] ?>&status=<?= $u['status'] ?>" class="btn btn-<?= $u['status'] == 'active' ? 'danger' : 'success' ?>" style="padding: 4px 10px; font-size:12px;">
                                        <?= $u['status'] == 'active' ? 'Ban User' : 'Unban' ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB 3: WITHDRAWAL REQUESTS -->
        <?php if ($active_tab == 'withdrawals'): ?>
            <div class="header-bar">
                <h1 class="page-title">💳 विथड्रॉल रिक्वेस्ट्स (Payouts)</h1>
            </div>
            <div class="card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>यूज़र ID</th>
                                <th>राशि (Amount)</th>
                                <th>Paytm / UPI ID</th>
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
                                <td style="color:#38bdf8; font-weight:bold;">₹<?= $w['amount'] ?></td>
                                <td><code><?= htmlspecialchars($w['upi_id']) ?></code></td>
                                <td><?= $w['created_at'] ?></td>
                                <td><span class="badge badge-<?= strtolower($w['status']) ?>"><?= $w['status'] ?></span></td>
                                <td>
                                    <?php if ($w['status'] == 'Pending'): ?>
                                        <a href="admin.php?tab=withdrawals&action_w=approve&wid=<?= $w['id'] ?>" class="btn btn-success" style="padding: 4px 10px; font-size:12px;">Approve ✅</a>
                                        <a href="admin.php?tab=withdrawals&action_w=reject&wid=<?= $w['id'] ?>" class="btn btn-danger" style="padding: 4px 10px; font-size:12px;">Reject ❌</a>
                                    <?php else: ?>
                                        <span style="color:#94a3b8; font-size:12px;">Processed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB 4: CONTESTS MANAGER -->
        <?php if ($active_tab == 'contests'): ?>
            <div class="header-bar">
                <h1 class="page-title">🏆 कॉन्टेस्ट मैनेजर</h1>
            </div>
            <div class="card">
                <div class="card-header">➕ नया कॉन्टेस्ट / क्विज़ रूम बनाएं</div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_contest">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>कॉन्टेस्ट टाइटल</label>
                            <input type="text" name="title" class="form-control" placeholder="Rapid Quiz Tournament" required>
                        </div>
                        <div class="form-group">
                            <label>एंट्री फ़ी (₹)</label>
                            <input type="number" step="0.01" name="entry_fee" class="form-control" placeholder="100.00" required>
                        </div>
                        <div class="form-group">
                            <label>विजेता प्राइज पूल (₹)</label>
                            <input type="number" step="0.01" name="prize_pool" class="form-control" placeholder="50.00" required>
                        </div>
                        <div class="form-group">
                            <label>एडमिन नेट प्रॉफिट (₹)</label>
                            <input type="number" step="0.01" name="admin_profit" class="form-control" placeholder="30.00" required>
                        </div>
                        <div class="form-group">
                            <label>Level 1 MLM कमीशन (₹)</label>
                            <input type="number" step="0.01" name="mlm_l1" class="form-control" placeholder="13.00" required>
                        </div>
                        <div class="form-group">
                            <label>Level 2 MLM कमीशन (₹)</label>
                            <input type="number" step="0.01" name="mlm_l2" class="form-control" placeholder="7.00" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">🏆 कॉन्टेस्ट पब्लिश करें</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- TAB 5: QUESTION BANK -->
        <?php if ($active_tab == 'questions'): ?>
            <div class="header-bar">
                <h1 class="page-title">❓ क्वेश्चन बैंक मैनेजर</h1>
            </div>
            <div class="card">
                <div class="card-header">➕ नया सवाल जोड़ें</div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_question">
                    <div class="form-group" style="margin-bottom:15px;">
                        <label>सवाल (Question Text)</label>
                        <input type="text" name="question_text" class="form-control" placeholder="भारत की राजधानी कहाँ है?" required>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Option A</label>
                            <input type="text" name="option_a" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Option B</label>
                            <input type="text" name="option_b" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Option C</label>
                            <input type="text" name="option_c" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Option D</label>
                            <input type="text" name="option_d" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>सही उत्तर (Correct Option)</label>
                            <select name="correct_option" class="form-control">
                                <option value="A">Option A</option>
                                <option value="B">Option B</option>
                                <option value="C">Option C</option>
                                <option value="D">Option D</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>कैटेगरी</label>
                            <input type="text" name="category" class="form-control" value="GK" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:10px;">➕ सवाल सेव करें</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- TAB 6: TELEGRAM & SOCIAL BOT CONTROL -->
        <?php if ($active_tab == 'social_bot'): ?>
            <div class="header-bar">
                <h1 class="page-title">🤖 Telegram Bot & Social API Control</h1>
            </div>
            <div class="card">
                <div class="card-header">⚙️ बॉट एवं सोशल मीडिया लिंक्स सेटिंग्स</div>
                <form method="POST">
                    <input type="hidden" name="action" value="save_settings">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Telegram Bot Token (BotFather से प्राप्त)</label>
                            <input type="text" name="telegram_bot_token" class="form-control" value="<?= htmlspecialchars(getSetting($conn, 'telegram_bot_token')) ?>" placeholder="123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ">
                        </div>
                        <div class="form-group">
                            <label>Admin Telegram Chat ID (अलर्ट प्राप्त करने के लिए)</label>
                            <input type="text" name="telegram_chat_id" class="form-control" value="<?= htmlspecialchars(getSetting($conn, 'telegram_chat_id')) ?>" placeholder="987654321">
                        </div>
                        <div class="form-group">
                            <label>WhatsApp सपोर्ट नंबर</label>
                            <input type="text" name="whatsapp_number" class="form-control" value="<?= htmlspecialchars(getSetting($conn, 'whatsapp_number')) ?>" placeholder="+919876543210">
                        </div>
                        <div class="form-group">
                            <label>Facebook Page / Group Link</label>
                            <input type="text" name="facebook_link" class="form-control" value="<?= htmlspecialchars(getSetting($conn, 'facebook_link')) ?>" placeholder="https://facebook.com/yourpage">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">💾 सेटिंग्स सेव करें</button>
                </form>
                
                <hr style="border-color:#334155; margin: 20px 0;">
                
                <form method="POST">
                    <input type="hidden" name="action" value="test_telegram">
                    <button type="submit" class="btn btn-primary">⚡ Telegram Bot कनेक्शन टेस्ट करें</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- TAB 7: REVENUE LEDGER -->
        <?php if ($active_tab == 'revenue'): ?>
            <div class="header-bar">
                <h1 class="page-title">📈 एडमिन कमाई लेजर</h1>
            </div>
            <div class="card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>कॉन्टेस्ट ID</th>
                                <th>कमाई का जरिया (Source)</th>
                                <th>नेट प्रॉफिट (Amount)</th>
                                <th>तारीख</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $e_sql = mysqli_query($conn, "SELECT * FROM admin_earnings ORDER BY id DESC");
                            while ($e = mysqli_fetch_assoc($e_sql)):
                            ?>
                            <tr>
                                <td>#<?= $e['id'] ?></td>
                                <td>#<?= $e['contest_id'] ?? 'N/A' ?></td>
                                <td><b><?= htmlspecialchars($e['source']) ?></b></td>
                                <td style="color:#4ade80; font-weight:bold;">+₹<?= $e['amount'] ?></td>
                                <td><?= $e['created_at'] ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
