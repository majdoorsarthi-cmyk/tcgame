<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$contest_id = isset($_GET['contest_id']) ? intval($_GET['contest_id']) : 1;

// Contest details
$contest_res = mysqli_query($conn, "SELECT * FROM contests WHERE id = '$contest_id'");
$contest = mysqli_fetch_assoc($contest_res);

if (!$contest) {
    die("Invalid Contest ID");
}

// User details
$user_res = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
$user = mysqli_fetch_assoc($user_res);

$msg = "";

// Submission Processing Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_quiz'])) {
    $time_taken = floatval($_POST['time_taken']);
    $user_answers = $_POST['ans'];
    
    // Fetch Questions
    $q_ids = array_keys($user_answers);
    $ids_str = implode(',', array_map('intval', $q_ids));
    $q_res = mysqli_query($conn, "SELECT id, correct_option FROM questions WHERE id IN ($ids_str)");
    
    $score = 0;
    while ($row = mysqli_fetch_assoc($q_res)) {
        if (isset($user_answers[$row['id']]) && $user_answers[$row['id']] === $row['correct_option']) {
            $score += 10;
        }
    }

    // Deduct Entry Fee from Wallet
    if ($user['wallet_balance'] < $contest['entry_fee']) {
        die("<script>alert('Insufficient Wallet Balance!'); window.location.href='dashboard.php';</script>");
    }

    $entry_fee = $contest['entry_fee'];
    mysqli_query($conn, "UPDATE users SET wallet_balance = wallet_balance - $entry_fee WHERE id = '$user_id'");
    
    // Log User Transaction
    mysqli_query($conn, "INSERT INTO transactions (user_id, amount, type, description) VALUES ('$user_id', '$entry_fee', 'debit', 'Contest Entry Fee: {$contest['title']}')");

    // --- MLM Multi-Level Passive Income Distribution Logic ---
    // 15% Split: Level 1 Leader (10%), Level 2 Leader (5%)
    $parent_id = $user['parent_id'];
    
    if ($parent_id) {
        // Level 1 Commission (10%)
        $l1_commission = $entry_fee * 0.10;
        mysqli_query($conn, "UPDATE users SET wallet_balance = wallet_balance + $l1_commission WHERE id = '$parent_id'");
        mysqli_query($conn, "INSERT INTO network_commissions (leader_id, player_id, contest_id, commission_amount, level_type) VALUES ('$parent_id', '$user_id', '$contest_id', '$l1_commission', 1)");
        mysqli_query($conn, "INSERT INTO transactions (user_id, amount, type, description) VALUES ('$parent_id', '$l1_commission', 'credit', 'Level 1 Passive Referral Commission')");

        // Fetch Level 2 Parent
        $parent2_res = mysqli_query($conn, "SELECT parent_id FROM users WHERE id = '$parent_id'");
        $parent2_data = mysqli_fetch_assoc($parent2_res);
        if ($parent2_data && $parent2_data['parent_id']) {
            $parent2_id = $parent2_data['parent_id'];
            // Level 2 Commission (5%)
            $l2_commission = $entry_fee * 0.05;
            mysqli_query($conn, "UPDATE users SET wallet_balance = wallet_balance + $l2_commission WHERE id = '$parent2_id'");
            mysqli_query($conn, "INSERT INTO network_commissions (leader_id, player_id, contest_id, commission_amount, level_type) VALUES ('$parent2_id', '$user_id', '$contest_id', '$l2_commission', 2)");
            mysqli_query($conn, "INSERT INTO transactions (user_id, amount, type, description) VALUES ('$parent2_id', '$l2_commission', 'credit', 'Level 2 Passive Referral Commission')");
        }
    }

    // Save Participant Score
    mysqli_query($conn, "INSERT INTO contest_participants (contest_id, user_id, score, completion_time_sec, status) VALUES ('$contest_id', '$user_id', '$score', '$time_taken', 'played')");

    echo "<script>alert('Quiz Submitted! Score: $score | Time: {$time_taken}s'); window.location.href='dashboard.php';</script>";
    exit();
}

// Fetch Random 3 Questions for Contest
$questions_query = mysqli_query($conn, "SELECT * FROM questions ORDER BY RAND() LIMIT 3");
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TC Game - Play Quiz</title>
    <style>
        body { font-family: Arial, sans-serif; background: #121212; color: #fff; padding: 15px; margin: 0; }
        .quiz-card { background: #1e1e1e; padding: 20px; border-radius: 12px; max-width: 500px; margin: 0 auto; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
        .timer-box { font-size: 24px; font-weight: bold; color: #ff3d00; text-align: center; margin-bottom: 15px; }
        .q-box { background: #2a2a2a; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
        .option-btn { display: block; margin: 8px 0; padding: 10px; background: #333; border: 1px solid #444; border-radius: 6px; color: #fff; }
        .btn-submit { width: 100%; padding: 12px; background: #00e676; border: none; font-weight: bold; font-size: 16px; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>

<div class="quiz-card">
    <h2><?php echo htmlspecialchars($contest['title']); ?> (Entry: ₹<?php echo $contest['entry_fee']; ?>)</h2>
    <div class="timer-box">Time Left: <span id="timer">30</span>s</div>

    <form id="quizForm" method="POST">
        <input type="hidden" name="submit_quiz" value="1">
        <input type="hidden" name="time_taken" id="time_taken" value="0">

        <?php 
        $i = 1;
        while ($q = mysqli_fetch_assoc($questions_query)) { 
        ?>
            <div class="q-box">
                <p><b>Q<?php echo $i++; ?>. <?php echo htmlspecialchars($q['question_text']); ?></b></p>
                <label class="option-btn"><input type="radio" name="ans[<?php echo $q['id']; ?>]" value="A" required> A) <?php echo htmlspecialchars($q['option_a']); ?></label>
                <label class="option-btn"><input type="radio" name="ans[<?php echo $q['id']; ?>]" value="B"> B) <?php echo htmlspecialchars($q['option_b']); ?></label>
                <label class="option-btn"><input type="radio" name="ans[<?php echo $q['id']; ?>]" value="C"> C) <?php echo htmlspecialchars($q['option_c']); ?></label>
                <label class="option-btn"><input type="radio" name="ans[<?php echo $q['id']; ?>]" value="D"> D) <?php echo htmlspecialchars($q['option_d']); ?></label>
            </div>
        <?php } ?>

        <button type="submit" class="btn-submit">Submit Answers</button>
    </form>
</div>

<script>
    let timeLeft = 30;
    let startTime = Date.now();
    const timerElement = document.getElementById('timer');
    const form = document.getElementById('quizForm');

    const countdown = setInterval(() => {
        timeLeft--;
        timerElement.innerText = timeLeft;
        if (timeLeft <= 0) {
            clearInterval(countdown);
            document.getElementById('time_taken').value = 30;
            form.submit();
        }
    }, 1000);

    form.onsubmit = function() {
        let elapsedTime = ((Date.now() - startTime) / 1000).toFixed(2);
        document.getElementById('time_taken').value = elapsedTime;
    };
</script>

</body>
</html>
