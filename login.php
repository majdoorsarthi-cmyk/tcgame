<?php
require_once 'db.php';
session_start();

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = $_POST['password'];

    $result = mysqli_query($conn, "SELECT * FROM users WHERE phone = '$phone'");
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header("Location: dashboard.php");
            exit();
        } else {
            $msg = "<p style='color:red;'>Incorrect password!</p>";
        }
    } else {
        $msg = "<p style='color:red;'>User not found!</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TC Game - Login</title>
    <style>
        body { font-family: Arial, sans-serif; background: #121212; color: #fff; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: #1e1e1e; padding: 25px; border-radius: 12px; width: 100%; max-width: 380px; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
        h2 { text-align: center; color: #00e676; margin-bottom: 20px; }
        input { width: 100%; padding: 12px; margin: 8px 0; border-radius: 6px; border: 1px solid #333; background: #2a2a2a; color: #fff; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #00e676; border: none; color: #000; font-weight: bold; border-radius: 6px; cursor: pointer; margin-top: 12px; }
        .link { text-align: center; margin-top: 15px; display: block; color: #bbb; text-decoration: none; }
    </style>
</head>
<body>
<div class="card">
    <h2>TC Game Login</h2>
    <?php echo $msg; ?>
    <form method="POST">
        <input type="tel" name="phone" placeholder="Mobile Number" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
    <a href="register.php" class="link">Don't have an account? Register</a>
</div>
</body>
</html>
