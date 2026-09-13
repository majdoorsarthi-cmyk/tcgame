<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
$user = mysqli_fetch_assoc($user_query);

// Count Total Downline Team Members
$team_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE parent_id = '$user_id'");
$team = mysqli_fetch_assoc($team_query);

$ref_link = "https://" . $_SERVER['HTTP_HOST'] . "/register.php?ref=" . $user['referral_code'];
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TC Game - Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background: #121212; color: #fff; margin: 0; padding: 15px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; padding-bottom: 10px; }
        .wallet-card { background: linear-gradient(135deg, #00c853, #b2ff59); color: #000; padding: 20px; border-radius: 12px; margin: 20px 0; }
        .wallet-card h3 { margin: 0; font-size: 14px; text-transform: uppercase; }
        .wallet-card h1 { margin: 5px 0 0 0; font-size: 32px; }
        .ref-box { background: #1e1e1e; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .ref-box input { width: 100%; padding: 8px; background: #2a2a2a; border: 1px solid #444; color: #fff; border-radius: 4px; box-sizing: border-box; }
        .game-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .game-card { background: #1e1e1e; border: 1px solid #333; border-radius: 8px; padding: 15px; text-align: center; }
        .btn { background: #00e676; color: #000; text-decoration: none; padding: 8px 12px; border-radius: 5px; font-weight: bold; display: inline-block; margin-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h3>Welcome, <?php echo htmlspecialchars($user['name']); ?></h3>
        <a href="login.php" style="color: #ff5252;">Logout</a>
    </div>

    <div class="wallet-card">
        <h3>Wallet Balance</h3>
        <h1>₹<?php echo number_format($user['wallet_balance'], 2); ?></h1>
    </div>

    <div class="ref-box">
        <p style="margin:0 0 8px 0;"><b>Your Referral Link (Direct Team: <?php echo $team['total']; ?> Users)</b></p>
        <input type="text" value="<?php echo $ref_link; ?>" readonly onclick="this.select();">
    </div>

    <h2>Live Skill Quiz Contests</h2>
    <div class="game-grid">
        <div class="game-card">
            <h4>Quick GK Quiz</h4>
            <p>Entry: ₹10 | Winner: ₹10</p>
            <a href="#" class="btn">Play Now</a>
        </div>
        <div class="game-card">
            <h4>Computer Speed Challenge</h4>
            <p>Entry: ₹50 | Winner: ₹50</p>
            <a href="#" class="btn">Play Now</a>
        </div>
    </div>
</body>
</html>
