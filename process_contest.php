<?php
require_once 'db.php';
session_start();

// 1. Check User Login Status
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Check Contest ID from GET/POST
if (!isset($_GET['contest_id']) || empty($_GET['contest_id'])) {
    header("Location: dashboard.php?error=invalid_contest");
    exit();
}

$contest_id = intval($_GET['contest_id']);

// 3. Fetch Contest Details
$contest_query = mysqli_query($conn, "SELECT * FROM contests WHERE id = '$contest_id' AND status = 'active'");
if (mysqli_num_rows($contest_query) == 0) {
    header("Location: dashboard.php?error=contest_not_found");
    exit();
}

$contest = mysqli_fetch_assoc($contest_query);
$entry_fee = floatval($contest['entry_fee']);

// 4. Fetch User Wallet & Parent Information
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
$user = mysqli_fetch_assoc($user_query);
$wallet_balance = floatval($user['wallet_balance']);
$parent_id_l1 = $user['parent_id']; // Level 1 Direct Sponsor

// 5. Rule Check: Is Wallet Balance Sufficient?
if ($wallet_balance < $entry_fee) {
    echo "<script>
        alert('आपका वॉलेट बैलेंस पर्याप्त नहीं है! प्रतियोगिता में भाग लेने के लिए कृपया पैसे जोड़ें।');
        window.location.href = 'dashboard.php';
    </script>";
    exit();
}

// Transaction Start
mysqli_begin_transaction($conn);

try {
    // A. Deduct Entry Fee from User Wallet
    $new_balance = $wallet_balance - $entry_fee;
    $update_wallet = mysqli_query($conn, "UPDATE users SET wallet_balance = '$new_balance' WHERE id = '$user_id'");
    if (!$update_wallet) {
        throw new Exception("Wallet deduction failed.");
    }

    // Record Entry Fee Transaction
    mysqli_query($conn, "INSERT INTO wallet_transactions (user_id, amount, type, description, created_at) 
        VALUES ('$user_id', '$entry_fee', 'debit', 'Contest Entry Fee: {$contest['title']}', NOW())");

    // B. Register Participant Entry
    $register_participant = mysqli_query($conn, "INSERT INTO participants (contest_id, user_id, joined_at, status) 
        VALUES ('$contest_id', '$user_id', NOW(), 'joined')");

    // C. Dynamic MLM Passive Income Distribution System
    
    // --- LEVEL 1 COMMISSION (10%) ---
    if (!empty($parent_id_l1) && $parent_id_l1 > 0) {
        $l1_comm_rate = 0.10; // 10% Level 1 Commission
        $l1_amount = $entry_fee * $l1_comm_rate;

        // Credit to Level 1 Parent Wallet
        mysqli_query($conn, "UPDATE users SET wallet_balance = wallet_balance + '$l1_amount' WHERE id = '$parent_id_l1'");
        
        // Record Commission Log for Level 1
        mysqli_query($conn, "INSERT INTO network_commissions (leader_id, downline_id, contest_id, level, commission_amount, created_at) 
            VALUES ('$parent_id_l1', '$user_id', '$contest_id', 1, '$l1_amount', NOW())");
            
        // Record Wallet Transaction for Level 1
        mysqli_query($conn, "INSERT INTO wallet_transactions (user_id, amount, type, description, created_at) 
            VALUES ('$parent_id_l1', '$l1_amount', 'credit', 'L1 Team Commission from User #$user_id', NOW())");

        // --- LEVEL 2 COMMISSION (5%) ---
        $l1_parent_query = mysqli_query($conn, "SELECT parent_id FROM users WHERE id = '$parent_id_l1'");
        if (mysqli_num_rows($l1_parent_query) > 0) {
            $l1_parent_data = mysqli_fetch_assoc($l1_parent_query);
            $parent_id_l2 = $l1_parent_data['parent_id'];

            if (!empty($parent_id_l2) && $parent_id_l2 > 0) {
                $l2_comm_rate = 0.05; // 5% Level 2 Commission
                $l2_amount = $entry_fee * $l2_comm_rate;

                // Credit to Level 2 Parent Wallet
                mysqli_query($conn, "UPDATE users SET wallet_balance = wallet_balance + '$l2_amount' WHERE id = '$parent_id_l2'");
                
                // Record Commission Log for Level 2
                mysqli_query($conn, "INSERT INTO network_commissions (leader_id, downline_id, contest_id, level, commission_amount, created_at) 
                    VALUES ('$parent_id_l2', '$user_id', '$contest_id', 2, '$l2_amount', NOW())");

                // Record Wallet Transaction for Level 2
                mysqli_query($conn, "INSERT INTO wallet_transactions (user_id, amount, type, description, created_at) 
                    VALUES ('$parent_id_l2', '$l2_amount', 'credit', 'L2 Team Commission from User #$user_id', NOW())");
            }
        }
    }

    // Commit Transaction
    mysqli_commit($conn);

    // D. Initialize Game Session Data
    $_SESSION['active_contest_id'] = $contest_id;
    $_SESSION['quiz_start_time'] = time();

    // Redirect to Quiz Play Area
    header("Location: play_quiz.php?contest_id=" . $contest_id);
    exit();

} catch (Exception $e) {
    // Rollback Database if any error occurs
    mysqli_rollback($conn);
    echo "<script>
        alert('गेम प्रोसेसिंग में समस्या आई! कृपया पुनः प्रयास करें। Error: " . addslashes($e->getMessage()) . "');
        window.location.href = 'dashboard.php';
    </script>";
    exit();
}
?>
