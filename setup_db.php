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

// Session Primary Key restriction fix for Aiven
mysqli_query($conn, "SET SESSION sql_require_primary_key = 0;");

// 1. Users Table (MLM / Parent Tracking & Wallet)
$table_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    wallet_balance DECIMAL(10,2) DEFAULT 0.00,
    referral_code VARCHAR(20) UNIQUE NOT NULL,
    parent_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL
);";

// 2. Network / MLM Commission Logs
$table_mlm = "CREATE TABLE IF NOT EXISTS network_commissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    leader_id INT NOT NULL,
    player_id INT NOT NULL,
    contest_id INT NOT NULL,
    commission_amount DECIMAL(10,2) NOT NULL,
    level_type INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";

// 3. Transactions Table (Recharge & Withdrawals)
$table_transactions = "CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type ENUM('credit', 'debit') NOT NULL,
    description VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";

echo "<h2>Database Setup for TC Game Portal</h2>";

if (mysqli_query($conn, $table_users)) {
    echo "✅ 'users' table created successfully.<br>";
}
if (mysqli_query($conn, $table_mlm)) {
    echo "✅ 'network_commissions' table created successfully.<br>";
}
if (mysqli_query($conn, $table_transactions)) {
    echo "✅ 'transactions' table created successfully.<br>";
}

mysqli_close($conn);
?>
