<?php
// Aiven Cloud Database Credentials
$host = "mysql-9d9cc53-majdoorsarthi-d1a8.k.aivencloud.com";
$port = 13848;
$username = "avnadmin";
$password = "AVNS_CXh977fYw0GUSdyTCUU";
$dbname = "defaultdb";

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
if (!@mysqli_real_connect($conn, $host, $username, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

// Aiven / DigitalOcean Primary Key Requirements Bypass
mysqli_query($conn, "SET SESSION sql_require_primary_key = 0;");

echo "<div style='font-family: sans-serif; padding: 20px; background: #0f172a; color: #f8fafc; min-height: 100vh;'>";
echo "<h2 style='color: #38bdf8;'>🚀 Advanced Gaming & MLM Database Setup</h2>";

// 1. Users Table (MLM Referral System & Wallet)
$table_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    upi_id VARCHAR(100) DEFAULT NULL,
    wallet_balance DECIMAL(10,2) DEFAULT 0.00,
    total_earnings DECIMAL(10,2) DEFAULT 0.00,
    referral_code VARCHAR(20) NOT NULL UNIQUE,
    referral_by INT DEFAULT NULL,
    status ENUM('active', 'banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;";

// 2. Questions Table (Quiz Questions Bank)
$table_questions = "CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_text TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_option CHAR(1) NOT NULL,
    category VARCHAR(50) DEFAULT 'General'
) ENGINE=InnoDB;";

// 3. Contests Table (Game Rooms with Profit Split Logic)
// Distribution Model: Prize Pool (e.g. 60%), Admin Net Profit (e.g. 25%), Level 1 MLM (10%), Level 2 MLM (5%)
$table_contests = "CREATE TABLE IF NOT EXISTS contests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    entry_fee DECIMAL(10,2) NOT NULL,
    prize_pool DECIMAL(10,2) NOT NULL,
    admin_profit DECIMAL(10,2) NOT NULL,
    mlm_level1_commission DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    mlm_level2_commission DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'running', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;";

// 4. Contest Participants Log & Score Tracker
$table_participants = "CREATE TABLE IF NOT EXISTS contest_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contest_id INT NOT NULL,
    user_id INT NOT NULL,
    score INT DEFAULT 0,
    completion_time_sec DECIMAL(6,2) DEFAULT 999.99,
    prize_won DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('pending', 'played', 'winner') DEFAULT 'pending',
    played_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB;";

// 5. MLM Commission Log Table (Ref-Earnings History)
$table_mlm_commissions = "CREATE TABLE IF NOT EXISTS mlm_commissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sponsor_id INT NOT NULL,
    referred_user_id INT NOT NULL,
    contest_id INT NOT NULL,
    level INT NOT NULL,
    commission_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;";

// 6. Admin Revenue Ledger (100% Guaranteed Admin Profit Tracking)
$table_admin_earnings = "CREATE TABLE IF NOT EXISTS admin_earnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contest_id INT DEFAULT NULL,
    source VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;";

// 7. Withdrawals Table (Payout Requests)
$table_withdrawals = "CREATE TABLE IF NOT EXISTS withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    upi_id VARCHAR(100) NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;";

// 8. Transactions Table (All Account Credits & Debits)
$table_transactions = "CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type ENUM('credit', 'debit') NOT NULL,
    category ENUM('contest_entry', 'contest_win', 'mlm_level1', 'mlm_level2', 'withdrawal', 'admin_adjustment') NOT NULL,
    description VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;";

// Execute Tables Creation
$tables = [
    'users' => $table_users,
    'questions' => $table_questions,
    'contests' => $table_contests,
    'contest_participants' => $table_participants,
    'mlm_commissions' => $table_mlm_commissions,
    'admin_earnings' => $table_admin_earnings,
    'withdrawals' => $table_withdrawals,
    'transactions' => $table_transactions
];

foreach ($tables as $name => $query) {
    if (mysqli_query($conn, $query)) {
        echo "<p style='color: #4ade80;'>✅ Table <b>{$name}</b> updated / verified successfully.</p>";
    } else {
        echo "<p style='color: #f87171;'>❌ Error creating table <b>{$name}</b>: " . mysqli_error($conn) . "</p>";
    }
}

// Seed Demo Data if tables are empty
$check_q = mysqli_query($conn, "SELECT id FROM questions LIMIT 1");
if (mysqli_num_rows($check_q) == 0) {
    mysqli_query($conn, "INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_option, category) VALUES 
    ('भारत का राष्ट्रीय खेल कौन सा है?', 'क्रिकेट', 'हॉकी', 'फुटबॉल', 'कबड्डी', 'B', 'Sports'),
    ('कंप्यूटर का दिमाग (Brain) किसे कहते हैं?', 'RAM', 'Hard Disk', 'CPU', 'Monitor', 'C', 'Tech'),
    ('मध्य प्रदेश की राजधानी कौन सी है?', 'इंदौर', 'भोपाल', 'जबलपुर', 'ग्वालियर', 'B', 'GK'),
    ('विश्व की सबसे लंबी नदी कौन सी है?', 'अमेजन', 'नील', 'गंगा', 'मिसिसिपी', 'B', 'GK'),
    ('सूर्य क्या है?', 'ग्रह', 'तारा', 'उपग्रह', 'उल्कापिंड', 'B', 'Science')");
    echo "<p style='color: #facc15;'>⚡ Sample questions inserted.</p>";
}

$check_c = mysqli_query($conn, "SELECT id FROM contests LIMIT 1");
if (mysqli_num_rows($check_c) == 0) {
    // ₹100 Entry Fee Breakdown:
    // Winner Prize = ₹50 (50%)
    // Admin Profit = ₹30 (30%)
    // Level 1 Sponsor = ₹13 (13%)
    // Level 2 Sponsor = ₹7 (7%)
    mysqli_query($conn, "INSERT INTO contests (title, entry_fee, prize_pool, admin_profit, mlm_level1_commission, mlm_level2_commission, status) VALUES 
    ('Mega Skill Tournament', 100.00, 50.00, 30.00, 13.00, 7.00, 'active'),
    ('Rapid Cash Quiz', 20.00, 10.00, 6.00, 2.60, 1.40, 'active')");
    echo "<p style='color: #facc15;'>⚡ Sample contests with Admin & MLM profit split inserted.</p>";
}

mysqli_close($conn);
echo "<hr style='border-color: #334155;'><h3 style='color: #38bdf8;'>🎉 All Tables & Profit Models Successfully Configured!</h3>";
echo "</div>";
?>
