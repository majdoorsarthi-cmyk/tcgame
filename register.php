<?php
require_once 'db.php';
session_start();

$msg = "";
$ref_code_from_url = isset($_GET['ref']) ? $_GET['ref'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $entered_ref = mysqli_real_escape_string($conn, $_POST['referral_code']);
    
    // Generate unique referral code for new user
    $my_ref_code = "TC" . rand(100000, 999999);

    // Parent User Check
    $parent_id = "NULL";
    if (!empty($entered_ref)) {
        $parent_query = mysqli_query($conn, "SELECT id FROM users WHERE referral_code = '$entered_ref'");
        if (mysqli_num_rows($parent_query) > 0) {
            $parent = mysqli_fetch_assoc($parent_query);
            $parent_id = $parent['id'];
        }
    }

    // Check Phone Exists
    $check_phone = mysqli_query($conn, "SELECT id FROM users WHERE phone = '$phone'");
    if (mysqli_num_rows($check_phone) > 0) {
        $msg = "<p style='color:red;'>Mobile number already registered!</p>";
    } else {
        $sql = "INSERT INTO users (name, phone, password, referral_code, parent_id) VALUES ('$name', '$phone', '$password', '$my_ref_code', $parent_id)";
        if (mysqli_query($conn, $sql)) {
            $msg = "<p style='color:green;'>Registration successful! <a href='login.php'>Login now</a></p>";
        } else {
            $msg = "<p style='color:red;'>Error: " . mysqli_error($conn) . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TC Game - Register</title>
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
    <h2>TC Game Registration</h2>
    <?php echo $msg; ?>
    <form method="POST">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="tel" name="phone" placeholder="Mobile Number" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="text" name="referral_code" placeholder="Referral Code (Optional)" value="<?php echo htmlspecialchars($ref_code_from_url); ?>">
        <button type="submit">Create Account</button>
    </form>
    <a href="login.php" class="link">Already have an account? Login</a>
</div>
</body>
</html>
