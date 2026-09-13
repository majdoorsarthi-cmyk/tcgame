<?php
// Aiven Cloud Database Credentials
$host = "mysql-9d9cc53-majdoorsarthi-d1a8.k.aivencloud.com";
$port = 13848;
$username = "avnadmin";
$password = "AVNS_CXh977fYw0GUSdyTCUU";
$dbname = "defaultdb";

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
mysqli_real_connect($conn, $host, $username, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

mysqli_query($conn, "SET SESSION sql_require_primary_key = 0;");

// 1. Questions Table
$table_questions = "CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_text TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_option CHAR(1) NOT NULL,
    category VARCHAR(50) DEFAULT 'GK'
);";

// 2. Contests Table (Game Rooms)
$table_contests = "CREATE TABLE IF NOT EXISTS contests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    entry_fee DECIMAL(10,2) NOT NULL,
    prize_pool DECIMAL(10,2) NOT NULL,
    admin_profit DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";

// 3. Contest Participants Log & Score Tracker
$table_participants = "CREATE TABLE IF NOT EXISTS contest_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contest_id INT NOT NULL,
    user_id INT NOT NULL,
    score INT DEFAULT 0,
    completion_time_sec DECIMAL(5,2) DEFAULT 99.99,
    status ENUM('played', 'pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";

echo "<h2>Executing Database Updates...</h2>";
if (mysqli_query($conn, $table_questions)) echo "✅ 'questions' table ready.<br>";
if (mysqli_query($conn, $table_contests)) echo "✅ 'contests' table ready.<br>";
if (mysqli_query($conn, $table_participants)) echo "✅ 'participants' table ready.<br>";

// Insert Sample Demo Contest & Questions if Empty
$check_q = mysqli_query($conn, "SELECT id FROM questions LIMIT 1");
if (mysqli_num_rows($check_q) == 0) {
    mysqli_query($conn, "INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_option) VALUES 
    ('मध्य प्रदेश की राजधानी कहाँ है?', 'इंदौर', 'भोपाल', 'जबलपुर', 'ग्वालियर', 'B'),
    ('कंप्यूटर का मस्तिष्क किसे कहा जाता है?', 'RAM', 'Hard Disk', 'CPU', 'Monitor', 'C'),
    ('भारत का राष्ट्रीय खेल कौन सा है?', 'क्रिकेट', 'हॉकी', 'फुटबॉल', 'कबड्डी', 'B')");
    echo "✅ Sample questions added.<br>";
}

$check_c = mysqli_query($conn, "SELECT id FROM contests LIMIT 1");
if (mysqli_num_rows($check_c) == 0) {
    // ₹20 Entry Fee -> ₹10 Prize (50%), ₹7 Admin (35%), ₹3 Commission (15%)
    mysqli_query($conn, "INSERT INTO contests (title, entry_fee, prize_pool, admin_profit) VALUES ('Rapid Skill Challenge', 20.00, 10.00, 7.00)");
    echo "✅ Sample contest added.<br>";
}

mysqli_close($conn);
?>
