<?php
require_once 'db.php';
$slug = mysqli_real_escape_string($conn, $_GET['slug'] ?? '');

$page_query = mysqli_query($conn, "SELECT * FROM dynamic_pages WHERE slug = '$slug' AND status = 'active'");
$page_data = mysqli_fetch_assoc($page_query);

if (!$page_data) {
    die("<h1>404 - Page Not Found</h1>");
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_data['title']) ?></title>
    <style>
        body { background: #07090e; color: #fff; font-family: sans-serif; padding: 40px; }
        .page-card { background: #121826; border: 1px solid #1e293b; padding: 30px; border-radius: 16px; }
    </style>
</head>
<body>
    <div class="page-card">
        <h1><?= $page_data['icon'] ?> <?= htmlspecialchars($page_data['title']) ?></h1>
        <hr style="border-color:#334155; margin: 20px 0;">
        <div>
            <?= $page_data['content'] ?>
        </div>
    </div>
</body>
</html>
