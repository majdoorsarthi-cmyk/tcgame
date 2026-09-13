<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// वर्तमान बैलेंस निकालें
$user_stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$balance = $user_stmt->get_result()->fetch_assoc()['wallet_balance'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $upi_id = trim($_POST['upi_id']);

    if ($amount <= 0 || empty($upi_id)) {
        $error = "कृपया सही राशि और UPI ID दर्ज करें।";
    } elseif ($amount > $balance) {
        $error = "आपके वॉलेट में पर्याप्त बैलेंस नहीं है!";
    } else {
        $conn->begin_transaction();
        try {
            // बैलेंस डिडक्ट करें
            $deduct = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
            $deduct->bind_param("di", $amount, $user_id);
            $deduct->execute();

            // विथड्रॉल रिकॉर्ड डालें
            $req = $conn->prepare("INSERT INTO withdrawals (user_id, amount, upi_id, status) VALUES (?, ?, ?, 'Pending')");
            $req->bind_param("ids", $user_id, $amount, $upi_id);
            $req->execute();

            // ट्रांजैक्शन लॉग जोड़ें
            $desc = "Withdrawal request to UPI: " . $upi_id;
            $tx = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'debit', ?)");
            $tx->bind_param("ids", $user_id, $amount, $desc);
            $tx->execute();

            $conn->commit();
            $message = "Withdrawal request successfully submitted!";
            $balance -= $amount;
        } catch (Exception $e) {
            $conn->rollback();
            $error = "कुछ गड़बड़ी हुई, कृपया पुनः प्रयास करें।";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw Funds - TC Game</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width: 500px;">
    <div class="card shadow">
        <div class="card-header bg-dark text-white text-center">
            <h4>Withdraw Request</h4>
        </div>
        <div class="card-body">
            <p><strong>Available Balance:</strong> ₹<?php echo number_format($balance, 2); ?></p>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Withdraw Amount (₹)</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required min="1" max="<?php echo $balance; ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">UPI ID (PhonePe/Google Pay/Paytm)</label>
                    <input type="text" name="upi_id" class="form-control" placeholder="example@upi" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Submit Request</button>
            </form>
            <div class="mt-3 text-center">
                <a href="wallet.php" class="text-decoration-none">← Back to Wallet</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
