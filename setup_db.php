<?php
// Aiven Cloud Database Credentials
$host = "mysql-9d9cc53-majdoorsarthi-d1a8.k.aivencloud.com";
$port = 13848;
$username = "avnadmin";
$password = "AVNS_CXh977fYw0GUSdyTCUU";
$dbname = "tcgame"; // Target Database: tcgame

// Connection error report disable for custom handling
mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

if (!@mysqli_real_connect($conn, $host, $username, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die("<h3 style='color:red;'>Database Connection Failed: " . mysqli_connect_error() . "</h3>");
}

// Aiven / DigitalOcean Primary Key Requirements Bypass
mysqli_query($conn, "SET SESSION sql_require_primary_key = 0;");

echo "<div style='font-family: system-ui, sans-serif; padding: 25px; background: #0f172a; color: #f8fafc; min-height: 100vh; line-height: 1.6;'>";
echo "<h2 style='color: #38bdf8; margin-bottom: 20px;'>🚀 Advanced Gaming & MLM Database Setup (Target: tcgame)</h2>";

// 1. Helper Function: Auto-add missing columns to existing tables
function addColumnIfNotExists($conn, $table, $column, $columnDef) {
    $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check && mysqli_num_rows($check) == 0) {
        $alterQuery = "ALTER TABLE `$table` ADD COLUMN `$column` $columnDef";
        if (mysqli_query($conn, $alterQuery)) {
            echo "<p style='color: #a7f3d0;'>🔧 Added missing column <b>{$column}</b> to <b>{$table}</b> table.</p>";
        } else {
            echo "<p style='color: #fca5a5;'>❌ Failed to add column <b>{$column}</b>: " . mysqli_error($conn) . "</p>";
        }
    }
}

// 2. Core Tables Definition
$tables = [
    'users' => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) DEFAULT NULL,
        upi_id VARCHAR(100) DEFAULT NULL,
        wallet_balance DECIMAL(10,2) DEFAULT 0.00,
        total_earnings DECIMAL(10,2) DEFAULT 0.00,
        referral_code VARCHAR(20) DEFAULT NULL,
        referral_by INT DEFAULT NULL,
        status ENUM('active', 'banned') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;",

    'questions' => "CREATE TABLE IF NOT EXISTS questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question_text TEXT NOT NULL,
        option_a VARCHAR(255) NOT NULL,
        option_b VARCHAR(255) NOT NULL,
        option_c VARCHAR(255) NOT NULL,
        option_d VARCHAR(255) NOT NULL,
        correct_option CHAR(1) NOT NULL,
        category VARCHAR(50) DEFAULT 'General'
    ) ENGINE=InnoDB;",

    'contests' => "CREATE TABLE IF NOT EXISTS contests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        entry_fee DECIMAL(10,2) NOT NULL,
        prize_pool DECIMAL(10,2) NOT NULL,
        admin_profit DECIMAL(10,2) NOT NULL,
        mlm_level1_commission DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        mlm_level2_commission DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        status ENUM('active', 'running', 'completed') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;",

    'contest_participants' => "CREATE TABLE IF NOT EXISTS contest_participants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contest_id INT NOT NULL,
        user_id INT NOT NULL,
        score INT DEFAULT 0,
        completion_time_sec DECIMAL(6,2) DEFAULT 999.99,
        prize_won DECIMAL(10,2) DEFAULT 0.00,
        status ENUM('pending', 'played', 'winner') DEFAULT 'pending',
        played_at TIMESTAMP NULL DEFAULT NULL
    ) ENGINE=InnoDB;",

    'mlm_commissions' => "CREATE TABLE IF NOT EXISTS mlm_commissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sponsor_id INT NOT NULL,
        referred_user_id INT NOT NULL,
        contest_id INT NOT NULL,
        level INT NOT NULL,
        commission_amount DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;",

    'admin_earnings' => "CREATE TABLE IF NOT EXISTS admin_earnings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contest_id INT DEFAULT NULL,
        source VARCHAR(100) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;",

    'withdrawals' => "CREATE TABLE IF NOT EXISTS withdrawals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        upi_id VARCHAR(100) NOT NULL,
        status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;",

    'transactions' => "CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        type ENUM('credit', 'debit') NOT NULL,
        category ENUM('contest_entry', 'contest_win', 'mlm_level1', 'mlm_level2', 'withdrawal', 'admin_adjustment') DEFAULT 'admin_adjustment',
        description VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;"
];

// Execute Table Creation
foreach ($tables as $name => $query) {
    if (mysqli_query($conn, $query)) {
        echo "<p style='color: #4ade80;'>✅ Table <b>{$name}</b> created / verified successfully in <i>tcgame</i>.</p>";
    } else {
        echo "<p style='color: #f87171;'>❌ Error creating table <b>{$name}</b>: " . mysqli_error($conn) . "</p>";
    }
}

echo "<hr style='border-color: #334155; margin: 20px 0;'>";
echo "<h3 style='color: #facc15;'>🔄 Auto-Healing Columns</h3>";

// Repair 'users' table columns
addColumnIfNotExists($conn, 'users', 'username', "VARCHAR(50) NOT NULL AFTER id");
addColumnIfNotExists($conn, 'users', 'password', "VARCHAR(255) NOT NULL AFTER username");
addColumnIfNotExists($conn, 'users', 'email', "VARCHAR(100) DEFAULT NULL AFTER password");
addColumnIfNotExists($conn, 'users', 'upi_id', "VARCHAR(100) DEFAULT NULL AFTER email");
addColumnIfNotExists($conn, 'users', 'wallet_balance', "DECIMAL(10,2) DEFAULT 0.00 AFTER upi_id");
addColumnIfNotExists($conn, 'users', 'total_earnings', "DECIMAL(10,2) DEFAULT 0.00 AFTER wallet_balance");
addColumnIfNotExists($conn, 'users', 'referral_code', "VARCHAR(20) DEFAULT NULL AFTER total_earnings");
addColumnIfNotExists($conn, 'users', 'referral_by', "INT DEFAULT NULL AFTER referral_code");
addColumnIfNotExists($conn, 'users', 'status', "ENUM('active', 'banned') DEFAULT 'active' AFTER referral_by");

// Repair 'contests' table columns
addColumnIfNotExists($conn, 'contests', 'admin_profit', "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER prize_pool");
addColumnIfNotExists($conn, 'contests', 'mlm_level1_commission', "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER admin_profit");
addColumnIfNotExists($conn, 'contests', 'mlm_level2_commission', "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER mlm_level1_commission");

// Repair 'transactions' table columns
addColumnIfNotExists($conn, 'transactions', 'category', "ENUM('contest_entry', 'contest_win', 'mlm_level1', 'mlm_level2', 'withdrawal', 'admin_adjustment') DEFAULT 'admin_adjustment' AFTER type");

echo "<hr style='border-color: #334155; margin: 20px 0;'>";
echo "<h3 style='color: #38bdf8;'>🌱 Seeding Demo Data (If Empty)</h3>";

// Seed Questions
$check_q = mysqli_query($conn, "SELECT id FROM questions LIMIT 1");
if ($check_q && mysqli_num_rows($check_q) == 0) {
    $q_stmt = "INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_option, category) VALUES 
    ('भारत का राष्ट्रीय खेल कौन सा है?', 'क्रिकेट', 'हॉकी', 'फुटबॉल', 'कबड्डी', 'B', 'Sports'),
    ('कंप्यूटर का दिमाग (Brain) किसे कहते हैं?', 'RAM', 'Hard Disk', 'CPU', 'Monitor', 'C', 'Tech'),
    ('मध्य प्रदेश की राजधानी कौन सी है?', 'इंदौर', 'भोपाल', 'जबलपुर', 'ग्वालियर', 'B', 'GK'),
    ('विश्व की सबसे लंबी नदी कौन सी है?', 'अमेजन', 'नील', 'गंगा', 'मिसिसिपी', 'B', 'GK'),
    ('सूर्य क्या है?', 'ग्रह', 'तारा', 'उपग्रह', 'उल्कापिंड', 'B', 'Science')";
    if (mysqli_query($conn, $q_stmt)) {
        echo "<p style='color: #facc15;'>⚡ Sample questions inserted into tcgame.</p>";
    }
} else {
    echo "<p style='color: #94a3b8;'>ℹ️ Questions table already has data.</p>";
}

// Seed Contests
$check_c = mysqli_query($conn, "SELECT id FROM contests LIMIT 1");
if ($check_c && mysqli_num_rows($check_c) == 0) {
    $c_stmt = "INSERT INTO contests (title, entry_fee, prize_pool, admin_profit, mlm_level1_commission, mlm_level2_commission, status) VALUES 
    ('Mega Skill Tournament', 100.00, 50.00, 30.00, 13.00, 7.00, 'active'),
    ('Rapid Cash Quiz', 20.00, 10.00, 6.00, 2.60, 1.40, 'active')";
    if (mysqli_query($conn, $c_stmt)) {
        echo "<p style='color: #facc15;'>⚡ Sample contests with Admin & MLM profit split inserted into tcgame.</p>";
    }
} else {
    echo "<p style='color: #94a3b8;'>ℹ️ Contests table already has data.</p>";
}

mysqli_close($conn);

echo "<div style='margin-top: 30px; padding: 15px; background: #1e293b; border-radius: 8px; border: 1px solid #3b82f6;'>";
echo "<h3 style='color: #38bdf8; margin: 0 0 10px 0;'>🎉 Setup Complete!</h3>";
echo "<p style='margin: 0;'>All 8 gaming tables created inside <b>tcgame</b> database. DBeaver refresh karein aur <b>admin.php</b> check karein!</p>";
echo "</div>";
echo "</div>";
?>
