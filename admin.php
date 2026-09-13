<?php
require_once 'db.php';
session_start();

// सुरक्षा के लिए आप यहाँ अपना एडमिन चेक लगा सकते हैं
// अभी के लिए इसे सिंपल रखा गया है

if (isset($_POST['add_contest'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $entry_fee = floatval($_POST['entry_fee']);
    $prize_pool = floatval($_POST['prize_pool']);
    
    $query = "INSERT INTO contests (title, entry_fee, prize_pool, status) VALUES ('$title', '$entry_fee', '$prize_pool', 'active')";
    mysqli_query($conn, $query);
    $success = "नया कॉन्टेस्ट सफलतापूर्वक जोड़ दिया गया है!";
}
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <title>TC Game - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white p-4">
    <div class="container" style="max-width: 600px;">
        <h3 class="text-success mb-4"><i class="fa-solid fa-user-shield"></i> TC Game Admin Panel</h3>
        
        <?php if(isset($success)) { echo "<div class='alert alert-success'>$success</div>"; } ?>

        <div class="card bg-secondary text-white p-4 border-0 rounded-4">
            <h5 class="mb-3">नया क्विज़ कॉन्टेस्ट जोड़ें</h5>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">कॉंटेस्ट का नाम (Title)</label>
                    <input type="text" name="title" class="form-control" placeholder="जैसे: Rapid Skill Challenge" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">प्रवेश शुल्क (Entry Fee ₹)</label>
                    <input type="number" name="entry_fee" class="form-control" placeholder="20" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">विजेता राशि (Winning Pool ₹)</label>
                    <input type="number" name="prize_pool" class="form-control" placeholder="10" required>
                </div>
                <button type="submit" name="add_contest" class="btn btn-success w-100 fw-bold">कॉंटेस्ट पब्लिश करें</button>
            </form>
        </div>
        <div class="mt-3">
            <a href="dashboard.php" class="text-info text-decoration-none">&larr; वापस डैशबोर्ड पर जाएँ</a>
        </div>
    </div>
</body>
</html>
