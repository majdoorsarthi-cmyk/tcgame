<?php
session_start();
require_once 'db.php';

// सुरक्षा: केवल एडमिन के एक्सेस के लिए (ज़रूरत अनुसार Session check लागू कर सकते हैं)

$msg = "";
$error = "";

// 1. विथड्रॉल अप्रूव / रिजेक्ट एक्शन हैंडलर
if (isset($_POST['action_withdraw'])) {
    $withdraw_id = intval($_POST['withdraw_id']);
    $action = $_POST['action_withdraw']; // 'approve' या 'reject'

    // विथड्रॉल डिटेल्स प्राप्त करें
    $w_stmt = $conn->prepare("SELECT user_id, amount, status FROM withdrawals WHERE id = ?");
    $w_stmt->bind_param("i", $withdraw_id);
    $w_stmt->execute();
    $w_data = $w_stmt->get_result()->fetch_assoc();

    if ($w_data && $w_data['status'] === 'Pending') {
        if ($action === 'approve') {
            $up_stmt = $conn->prepare("UPDATE withdrawals SET status = 'Approved' WHERE id = ?");
            $up_stmt->bind_param("i", $withdraw_id);
            $up_stmt->execute();
            $msg = "विथड्रॉल रिक्वेस्ट #$withdraw_id सफलतापूर्वक अप्रूव कर दी गई!";
        } elseif ($action === 'reject') {
            $conn->begin_transaction();
            try {
                // विथड्रॉल स्टेटस Rejected करें
                $up_stmt = $conn->prepare("UPDATE withdrawals SET status = 'Rejected' WHERE id = ?");
                $up_stmt->bind_param("i", $withdraw_id);
                $up_stmt->execute();

                // यूजर को बैलेंस वापस रिफंड करें
                $ref_stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $ref_stmt->bind_param("di", $w_data['amount'], $w_data['user_id']);
                $ref_stmt->execute();

                // ट्रांजैक्शन लॉग दर्ज करें
                $desc = "Withdrawal #$withdraw_id Rejected & Refunded";
                $tx_stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'credit', ?)");
                $tx_stmt->bind_param("ids", $w_data['user_id'], $w_data['amount'], $desc);
                $tx_stmt->execute();

                $conn->commit();
                $msg = "विथड्रॉल #$withdraw_id रिजेक्ट कर दिया गया और यूजर को बैलेंस रिफंड कर दिया गया!";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "त्रुटि: " . $e->getMessage();
            }
        }
    }
}

// 2. नया क्विज़ कॉन्टेस्ट जोड़ने का एक्शन
if (isset($_POST['add_contest'])) {
    $title = trim($_POST['title']);
    $entry_fee = floatval($_POST['entry_fee']);
    $prize_pool = floatval($_POST['prize_pool']);

    if (!empty($title) && $entry_fee >= 0 && $prize_pool >= 0) {
        $stmt = $conn->prepare("INSERT INTO contests (title, entry_fee, prize_pool, status) VALUES (?, ?, ?, 'active')");
        $stmt->bind_param("sdd", $title, $entry_fee, $prize_pool);
        $stmt->execute();
        $msg = "नया कॉन्टेस्ट सफलतापूर्वक पब्लिश हो गया!";
    } else {
        $error = "कृपया कॉन्टेस्ट की सही जानकारी भरें।";
    }
}

// 3. यूजर वॉलेट में मैनुअल फंड जोड़ने / घटाने का एक्शन
if (isset($_POST['update_wallet'])) {
    $target_user_id = intval($_POST['target_user_id']);
    $amount = floatval($_POST['amount']);
    $type = $_POST['type']; // 'credit' या 'debit'
    $remark = trim($_POST['remark']) ?: "Admin Adjustment ($type)";

    if ($target_user_id > 0 && $amount > 0) {
        $conn->begin_transaction();
        try {
            if ($type === 'credit') {
                $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
            } else {
                $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
            }
            $stmt->bind_param("di", $amount, $target_user_id);
            $stmt->execute();

            $tx_stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, ?, ?)");
            $tx_stmt->bind_param("idss", $target_user_id, $amount, $type, $remark);
            $tx_stmt->execute();

            $conn->commit();
            $msg = "यूजर ID #$target_user_id का वॉलेट अपडेट कर दिया गया!";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "प्रोसेस करने में त्रुटि आई!";
        }
    } else {
        $error = "वैध यूजर ID और राशि दर्ज करें।";
    }
}

// प्लेटफार्म स्टेटिस्टिक्स (Metrics Overview)
$total_users = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'] ?? 0;
$total_wallet = $conn->query("SELECT SUM(wallet_balance) AS total FROM users")->fetch_assoc()['total'] ?? 0.00;
$pending_w_data = $conn->query("SELECT COUNT(*) AS cnt, SUM(amount) AS total FROM withdrawals WHERE status = 'Pending'")->fetch_assoc();
$pending_w_count = $pending_w_data['cnt'] ?? 0;
$pending_w_amount = $pending_w_data['total'] ?? 0.00;
$active_contests = $conn->query("SELECT COUNT(*) AS total FROM contests WHERE status = 'active'")->fetch_assoc()['total'] ?? 0;

// डेटा लिस्ट्स प्राप्त करें
$pending_withdrawals = $conn->query("SELECT w.*, u.username FROM withdrawals w JOIN users u ON w.user_id = u.id WHERE w.status = 'Pending' ORDER BY w.id DESC");
$all_contests = $conn->query("SELECT * FROM contests ORDER BY id DESC");
$recent_users = $conn->query("SELECT id, username, wallet_balance, created_at FROM users ORDER BY id DESC LIMIT 15");
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TC Game - Advanced Admin Control Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-dark text-light">

<div class="container-fluid py-4 px-md-5">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary">
        <h2 class="text-warning fw-bold"><i class="fa-solid fa-user-shield me-2"></i>TC Game Admin Panel</h2>
        <div>
            <a href="dashboard.php" class="btn btn-outline-info me-2"><i class="fa-solid fa-house me-1"></i> User Dashboard</a>
            <a href="logout.php" class="btn btn-danger"><i class="fa-solid fa-power-off me-1"></i> Logout</a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Overview Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100 shadow border-0">
                <div class="card-body">
                    <h6>Total Registered Users</h6>
                    <h2 class="fw-bold mb-0"><?php echo number_format($total_users); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white h-100 shadow border-0">
                <div class="card-body">
                    <h6>Total User Wallet Balance</h6>
                    <h2 class="fw-bold mb-0">₹<?php echo number_format($total_wallet, 2); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark h-100 shadow border-0">
                <div class="card-body">
                    <h6>Pending Withdrawals</h6>
                    <h2 class="fw-bold mb-0"><?php echo $pending_w_count; ?> <small class="fs-6">(₹<?php echo number_format($pending_w_amount, 2); ?>)</small></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-dark h-100 shadow border-0">
                <div class="card-body">
                    <h6>Active Contests</h6>
                    <h2 class="fw-bold mb-0"><?php echo $active_contests; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Section -->
    <div class="row g-4">
        
        <!-- Left Column: Pending Withdrawals & User Directory -->
        <div class="col-lg-8">
            
            <!-- Pending Withdrawal Requests -->
            <div class="card bg-secondary text-white shadow border-0 mb-4">
                <div class="card-header bg-dark text-warning fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-money-bill-transfer me-2"></i>Pending Withdrawal Requests</span>
                    <span class="badge bg-warning text-dark"><?php echo $pending_w_count; ?> Pending</span>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-dark table-striped table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>UPI ID</th>
                                <th>Date</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($pending_withdrawals->num_rows > 0): ?>
                                <?php while ($w = $pending_withdrawals->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $w['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($w['username']); ?></strong> (ID: #<?php echo $w['user_id']; ?>)</td>
                                        <td class="text-warning fw-bold">₹<?php echo number_format($w['amount'], 2); ?></td>
                                        <td><code><?php echo htmlspecialchars($w['upi_id']); ?></code></td>
                                        <td><?php echo date('d M Y, h:i A', strtotime($w['created_at'])); ?></td>
                                        <td class="text-center">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="withdraw_id" value="<?php echo $w['id']; ?>">
                                                <button type="submit" name="action_withdraw" value="approve" class="btn btn-success btn-sm me-1 fw-bold" onclick="return confirm('क्या आप इस विथड्रॉल को अप्रूव करना चाहते हैं?')">Approve</button>
                                                <button type="submit" name="action_withdraw" value="reject" class="btn btn-danger btn-sm fw-bold" onclick="return confirm('क्या आप इस विथड्रॉल को रिजेक्ट करके रिफंड करना चाहते हैं?')">Reject</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-3 text-muted">कोई पेंडिंग विथड्रॉल रिक्वेस्ट नहीं है।</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Active Contests List -->
            <div class="card bg-secondary text-white shadow border-0 mb-4">
                <div class="card-header bg-dark text-info fw-bold">
                    <i class="fa-solid fa-trophy me-2"></i>All Contests Overview
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-dark table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Entry Fee</th>
                                <th>Prize Pool</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($c = $all_contests->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $c['id']; ?></td>
                                    <td><?php echo htmlspecialchars($c['title']); ?></td>
                                    <td>₹<?php echo number_format($c['entry_fee'], 2); ?></td>
                                    <td class="text-success fw-bold">₹<?php echo number_format($c['prize_pool'], 2); ?></td>
                                    <td><span class="badge bg-<?php echo $c['status'] == 'active' ? 'success' : 'secondary'; ?>"><?php echo strtoupper($c['status']); ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Users List -->
            <div class="card bg-secondary text-white shadow border-0">
                <div class="card-header bg-dark text-light fw-bold">
                    <i class="fa-solid fa-users me-2"></i>Recent Users List
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-dark table-striped mb-0">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Username</th>
                                <th>Wallet Balance</th>
                                <th>Joined Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($u = $recent_users->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $u['id']; ?></td>
                                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                                    <td class="text-warning fw-bold">₹<?php echo number_format($u['wallet_balance'], 2); ?></td>
                                    <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Right Column: Add Contest & Wallet Adjustment -->
        <div class="col-lg-4">
            
            <!-- Add New Contest Form -->
            <div class="card bg-secondary text-white shadow border-0 mb-4">
                <div class="card-header bg-success text-white fw-bold">
                    <i class="fa-solid fa-plus-circle me-2"></i>नया क्विज़ कॉन्टेस्ट जोड़ें
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">कॉंटेस्ट का नाम (Title)</label>
                            <input type="text" name="title" class="form-control bg-dark text-white border-secondary" placeholder="उदा: Mega Skill Challenge" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">प्रवेश शुल्क (Entry Fee ₹)</label>
                            <input type="number" step="0.01" name="entry_fee" class="form-control bg-dark text-white border-secondary" placeholder="50" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">विजेता राशि (Prize Pool ₹)</label>
                            <input type="number" step="0.01" name="prize_pool" class="form-control bg-dark text-white border-secondary" placeholder="100" required>
                        </div>
                        <button type="submit" name="add_contest" class="btn btn-success w-100 fw-bold">पब्लिश करें</button>
                    </form>
                </div>
            </div>

            <!-- Add / Subtract User Wallet Money -->
            <div class="card bg-secondary text-white shadow border-0">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="fa-solid fa-wallet me-2"></i>यूजर के वॉलेट में फंड जोड़ें / घटाएं
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">User ID</label>
                            <input type="number" name="target_user_id" class="form-control bg-dark text-white border-secondary" placeholder="उदा: 5" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">राशि (Amount ₹)</label>
                            <input type="number" step="0.01" name="amount" class="form-control bg-dark text-white border-secondary" placeholder="100" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">एक्शन (Action Type)</label>
                            <select name="type" class="form-select bg-dark text-white border-secondary">
                                <option value="credit">Credit (पैसे जोड़ें)</option>
                                <option value="debit">Debit (पैसे काटें)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">रिमार्क (Reason / Remark)</label>
                            <input type="text" name="remark" class="form-control bg-dark text-white border-secondary" placeholder="उदा: Promo Bonus या Admin Adjustment">
                        </div>
                        <button type="submit" name="update_wallet" class="btn btn-primary w-100 fw-bold">वॉलेट अपडेट करें</button>
                    </form>
                </div>
            </div>

        </div>

    </div>
</div>

<script href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
