<?php
$servername = getenv('DB_HOST') ?: "localhost";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') ?: "";
$dbname = getenv('DB_NAME') ?: "elearning_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set UTF-8
$conn->set_charset("utf8");

// --- Central Database Migration/Sync ---
function syncDatabase($conn) {
    // 1. Check/Add columns to users table
    $columns_to_check = [
        'firstname' => "VARCHAR(100) DEFAULT NULL",
        'lastname' => "VARCHAR(100) DEFAULT NULL",
        'email' => "VARCHAR(255) DEFAULT NULL",
        'email_display' => "INT(1) DEFAULT 1",
        'moodlenet_id' => "VARCHAR(255) DEFAULT NULL",
        'city' => "VARCHAR(100) DEFAULT NULL",
        'country' => "VARCHAR(100) DEFAULT NULL",
        'timezone' => "VARCHAR(50) DEFAULT 'Asia/Bangkok'",
        'description' => "TEXT DEFAULT NULL",
        'profile_image' => "VARCHAR(255) DEFAULT NULL",
        'role' => "VARCHAR(20) DEFAULT 'user'",
        'last_access' => "TIMESTAMP NULL DEFAULT NULL"
    ];

    foreach ($columns_to_check as $col => $def) {
        $check = $conn->query("SHOW COLUMNS FROM `users` LIKE '$col'");
        if ($check && $check->num_rows == 0) {
            $conn->query("ALTER TABLE `users` ADD `$col` $def");
            // Set first user as admin if role was just added
            if ($col === 'role') {
                $conn->query("UPDATE `users` SET `role` = 'admin' WHERE id = (SELECT id FROM (SELECT id FROM users ORDER BY id ASC LIMIT 1) as t)");
            }
        }
    }

    // 2. Ensure extra tables exist
    $conn->query("CREATE TABLE IF NOT EXISTS user_blogs (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, title VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $conn->query("CREATE TABLE IF NOT EXISTS forum_discussions (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, subject VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $conn->query("CREATE TABLE IF NOT EXISTS learning_plans (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, plan_name VARCHAR(255), status VARCHAR(50) DEFAULT 'Active')");
    $conn->query("CREATE TABLE IF NOT EXISTS browser_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        user_id INT, 
        browser VARCHAR(255), 
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    // Ensure ip_address exists in browser_sessions
    $check_ip = $conn->query("SHOW COLUMNS FROM `browser_sessions` LIKE 'ip_address'");
    if ($check_ip && $check_ip->num_rows == 0) {
        $conn->query("ALTER TABLE `browser_sessions` ADD `ip_address` VARCHAR(45) AFTER `browser` ");
    }
    $conn->query("CREATE TABLE IF NOT EXISTS user_subjects (
        user_id INT NOT NULL, 
        subject_id INT NOT NULL, 
        PRIMARY KEY (user_id, subject_id)
    )");

    // 3. Ensure site_settings table exist
    $conn->query("CREATE TABLE IF NOT EXISTS site_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT
    )");

    // Default settings
    $default_settings = [
        'site_name' => 'E-Learning Platform',
        'site_email' => 'admin@ubu.ac.th',
        'maintenance_mode' => 'off'
    ];
    foreach ($default_settings as $key => $val) {
        $conn->query("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES ('$key', '$val')");
    }
}

function getSetting($conn, $key, $default = '') {
    $res = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = '$key'");
    if ($res && $row = $res->fetch_assoc()) {
        return $row['setting_value'];
    }
    return $default;
}

syncDatabase($conn);
