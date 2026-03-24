<?php
// Check if config exists, if not redirect to install
$config_path = __DIR__ . '/config.php';
if (!file_exists($config_path)) {
    $install_path = (file_exists('install/index.php')) ? 'install/index.php' : '../install/index.php';
    // Prevent infinite redirect
    if (strpos($_SERVER['PHP_SELF'], 'install/index.php') === false) {
        header("Location: $install_path");
        exit;
    }
} else {
    require_once $config_path;
}

$servername = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: "localhost");
$username = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: "root");
$password = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') ?: "");
$dbname = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: "elearning_db");

// Create connection
try {
    $conn = @new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        if (strpos($_SERVER['PHP_SELF'], 'install/index.php') === false) {
            die("ฐานข้อมูลยังไม่ได้รับการตั้งค่าอย่างถูกต้อง หรือ MySQL ไม่ทำงาน: " . $conn->connect_error . "<br><a href='install/index.php?force=1'>ไปที่หน้าติดตั้ง</a>");
        }
    }
} catch (mysqli_sql_exception $e) {
    if (strpos($_SERVER['PHP_SELF'], 'install/index.php') === false) {
        die("ฐานข้อมูลยังไม่ได้รับการตั้งค่าอย่างถูกต้อง หรือ MySQL ไม่ทำงาน: " . $e->getMessage() . "<br><a href='install/index.php?force=1'>ไปที่หน้าติดตั้ง</a>");
    }
    // If we are in install/index.php, the exception will be caught there or it might be ignored if we are just including db.php
}

// Set UTF-8
$conn->set_charset("utf8");

// --- Global Timezone Setup ---
if (!function_exists('getSetting')) {
    function getSetting($conn, $key, $default = '') {
        $res = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = '$key'");
        if ($res && $row = $res->fetch_assoc()) {
            return $row['setting_value'];
        }
        return $default;
    }
}

// 1. Get system-wide timezone (default to Asia/Bangkok)
$system_timezone = getSetting($conn, 'site_timezone', 'Asia/Bangkok');

// 2. Override with user's personal timezone if logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$active_timezone = $system_timezone;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $u_res = $conn->query("SELECT timezone FROM users WHERE id = $uid");
    if ($u_res && $u_row = $u_res->fetch_assoc()) {
        if (!empty($u_row['timezone'])) {
            $active_timezone = $u_row['timezone'];
        }
    }
}

// 3. Set PHP Timezone
date_default_timezone_set($active_timezone);

// 4. Set MySQL Session Timezone (to ensure TIMESTAMP columns use correct offset)
$now = new DateTime();
$mins = $now->getOffset() / 60;
$sgn = ($mins < 0 ? -1 : 1);
$mins = abs($mins);
$hrs = floor($mins / 60);
$mins -= $hrs * 60;
$offset = sprintf('%+03d:%02d', $hrs * $sgn, $mins);
$conn->query("SET time_zone='$offset'");

// --- Central Database Migration/Sync ---
if (!function_exists('syncDatabase')) {
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
        // 3. System Logs table
        $conn->query("CREATE TABLE IF NOT EXISTS system_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT NULL,
            action VARCHAR(255) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
            'maintenance_mode' => 'off',
            'site_timezone' => 'Asia/Bangkok'
        ];
        foreach ($default_settings as $key => $val) {
            $conn->query("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES ('$key', '$val')");
        }

        // 4. Update subjects table
        $subject_columns = [
            'subject_name_en' => "VARCHAR(255) DEFAULT NULL",
            'credits' => "VARCHAR(50) DEFAULT NULL",
            'description_th' => "TEXT DEFAULT NULL",
            'description_en' => "TEXT DEFAULT NULL",
            'cover_image' => "VARCHAR(255) DEFAULT NULL",
            'start_date' => "DATETIME DEFAULT NULL", // Enrollment start
            'end_date' => "DATETIME DEFAULT NULL",   // Enrollment end
            'course_start' => "DATETIME DEFAULT NULL",
            'course_end' => "DATETIME DEFAULT NULL",
            'is_visible' => "TINYINT(1) DEFAULT 1",
            'enrollment_type' => "VARCHAR(20) DEFAULT 'open'", // 'open' or 'password'
            'enrollment_key' => "VARCHAR(255) DEFAULT NULL"
        ];
        foreach ($subject_columns as $col => $def) {
            $check = $conn->query("SHOW COLUMNS FROM `subjects` LIKE '$col'");
            if ($check && $check->num_rows == 0) {
                $conn->query("ALTER TABLE `subjects` ADD `$col` $def");
            }
        }

        // 5. Course Content structure
        $conn->query("CREATE TABLE IF NOT EXISTS course_sections (
            id INT AUTO_INCREMENT PRIMARY KEY,
            subject_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            sort_order INT DEFAULT 0,
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        $conn->query("CREATE TABLE IF NOT EXISTS course_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            section_id INT NOT NULL,
            item_name VARCHAR(255) NOT NULL,
            item_type VARCHAR(50) NOT NULL,
            item_content TEXT,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (section_id) REFERENCES course_sections(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        // Explicitly add missing columns to course_items if they don't exist
        $item_cols = [
            'weight' => "DECIMAL(5,2) DEFAULT 0.00",
            'max_score' => "DECIMAL(10,2) DEFAULT 0.00"
        ];
        foreach ($item_cols as $col => $def) {
            $check = $conn->query("SHOW COLUMNS FROM `course_items` LIKE '$col'");
            if ($check && $check->num_rows == 0) {
                $conn->query("ALTER TABLE `course_items` ADD `$col` $def");
            }
        }

        $conn->query("CREATE TABLE IF NOT EXISTS course_grades (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            user_id INT NOT NULL,
            score DECIMAL(10,2) DEFAULT 0.00,
            feedback TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (item_id) REFERENCES course_items(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        $conn->query("CREATE TABLE IF NOT EXISTS course_attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            subject_id INT NOT NULL,
            user_id INT NOT NULL,
            check_date DATE NOT NULL,
            status VARCHAR(20) NOT NULL, -- 'present', 'late', 'absent'
            remarks TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        // 7. Forum Tables
        $conn->query("CREATE TABLE IF NOT EXISTS course_forum_discussions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (item_id) REFERENCES course_items(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        $conn->query("CREATE TABLE IF NOT EXISTS course_forum_replies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            discussion_id INT NOT NULL,
            user_id INT NOT NULL,
            message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (discussion_id) REFERENCES course_forum_discussions(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        // 8. Quiz Tables
        $conn->query("CREATE TABLE IF NOT EXISTS quiz_questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            question_text TEXT NOT NULL,
            question_type VARCHAR(50) DEFAULT 'multiple_choice',
            points DECIMAL(5,2) DEFAULT 1.00,
            sort_order INT DEFAULT 0,
            FOREIGN KEY (item_id) REFERENCES course_items(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        $conn->query("CREATE TABLE IF NOT EXISTS quiz_options (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question_id INT NOT NULL,
            option_text TEXT NOT NULL,
            is_correct TINYINT(1) DEFAULT 0,
            FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        $conn->query("CREATE TABLE IF NOT EXISTS quiz_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            user_id INT NOT NULL,
            score DECIMAL(10,2) DEFAULT 0.00,
            started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            finished_at TIMESTAMP NULL,
            FOREIGN KEY (item_id) REFERENCES course_items(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }
}

if (!function_exists('getSetting')) {
    function getSetting($conn, $key, $default = '') {
        $res = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = '$key'");
        if ($res && $row = $res->fetch_assoc()) {
            return $row['setting_value'];
        }
        return $default;
    }
}

// Global logging function
if (!function_exists('logEvent')) {
    function logEvent($conn, $action, $details = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'NULL';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        $action = $conn->real_escape_string($action);
        $details = ($details !== null) ? "'" . $conn->real_escape_string($details) . "'" : 'NULL';
        
        $sql = "INSERT INTO system_logs (user_id, action, details, ip_address) 
                VALUES ($user_id, '$action', $details, '$ip')";
        return $conn->query($sql);
    }
}

syncDatabase($conn);
