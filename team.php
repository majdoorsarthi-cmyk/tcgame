<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Level 1 Referrals (डायरेक्ट जुड़ने वाले यूजर)
$l1_stmt = $conn->prepare("SELECT id, username, created_at FROM users WHERE referral_by = ?");
$l1_stmt->bind_param("i", $user_id);
$l1_stmt->execute();
$level1_users = $l1_stmt->get_result();

// Level 2 Referrals (Level 1 के द्वारा जोड़े गए यूजर)
$l2_stmt = $conn->prepare("SELECT u2.id, u2.username, u2.created_at, u1.username AS parent_name 
                            FROM users u1 
                            JOIN users u2 ON u1.id = u2.referral_by 
                            WHERE u1.referral_by = ?");
$l2_stmt->bind_param("i", $user_id);
$l2_stmt->execute();
$level2_users = $l2_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My MLM Team - TC Game</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Referral Network</h2>
        <a href="dashboard.php" class="btn btn-secondary btn-sm">Dashboard</a>
    </div>

    <!-- Level 1 Team -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Level 1 Members (Direct Referrals)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Joined Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($level1_users->num_rows > 0): ?>
                            <?php while ($row = $level1_users->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo $row['created_at']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center">No Level 1 members yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Level 2 Team -->
    <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Level 2 Members (Indirect Referrals)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Referred By</th>
                            <th>Joined Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($level2_users->num_rows > 0): ?>
                            <?php while ($row = $level2_users->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['parent_name']); ?></td>
                                    <td><?php echo $row['created_at']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center">No Level 2 members yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
