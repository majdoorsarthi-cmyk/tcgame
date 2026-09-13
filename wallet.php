<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// यूजर बैलेंस प्राप्त करें
$user_stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$balance = $user_data['wallet_balance'] ?? 0.00;

// ट्रांजैक्शन हिस्ट्री प्राप्त करें
$tx_stmt = $conn->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY id DESC");
$tx_stmt->bind_param("i", $user_id);
$tx_stmt->execute();
$transactions = $tx_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wallet - TC Game</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Wallet</h2>
        <a href="dashboard.php" class="btn btn-secondary btn-sm">Dashboard</a>
    </div>

    <div class="card bg-primary text-white mb-4">
        <div class="card-body text-center">
            <h5>Total Available Balance</h5>
            <h1 class="display-5 fw-bold">₹<?php echo number_format($balance, 2); ?></h1>
            <a href="withdraw.php" class="btn btn-warning mt-2 fw-bold">Withdraw Money</a>
        </div>
    </div>

    <h4>Transaction History</h4>
    <div class="table-responsive bg-white rounded shadow-sm p-3">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Description</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($transactions->num_rows > 0): ?>
                    <?php while ($tx = $transactions->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $tx['id']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $tx['type'] == 'credit' ? 'success' : 'danger'; ?>">
                                    <?php echo strtoupper($tx['type']); ?>
                                </span>
                            </td>
                            <td class="fw-bold">₹<?php echo number_format($tx['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($tx['description']); ?></td>
                            <td><?php echo $tx['created_at']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No transactions found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
