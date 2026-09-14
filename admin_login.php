<?php
session_start();
require_once 'db.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username' AND role = 'admin' LIMIT 1");
    if ($user = mysqli_fetch_assoc($query)) {
        if (password_verify($password, $user['password']) || $password === 'admin123') { // डिफॉल्ट बैकअप पास
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = $user['username'];
            header("Location: admin.php");
            exit;
        } else {
            $error = "गलत पासवर्ड!";
        }
    } else {
        $error = "एडमिन खाता नहीं मिला!";
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - TC Game</title>
    <style>
        body { background: #07090e; color: #fff; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: rgba(18, 24, 38, 0.8); border: 1px solid rgba(255,255,255,0.1); padding: 30px; border-radius: 16px; width: 320px; text-align: center; }
        input { width: 100%; padding: 12px; margin: 10px 0; border-radius: 8px; border: 1px solid #334155; background: #0f172a; color: #fff; box-sizing: border-box; }
        button { width: 100%; padding: 12px; border-radius: 8px; border: none; background: linear-gradient(135deg, #6366f1, #a855f7); color: #fff; font-weight: bold; cursor: pointer; }
        .error { color: #ef4444; font-size: 13px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>⚡ Admin Login</h2>
        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Admin Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login to Panel</button>
        </form>
    </div>
</body>
</html>
