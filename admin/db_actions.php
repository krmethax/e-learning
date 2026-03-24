<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$path = '../';
require_once $path . 'includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access.");
}

$action = $_GET['action'] ?? '';

if ($action === 'export') {
    // Basic SQL Export
    $tables = array();
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }

    $sql_export = "-- E-Learning Platform Backup\n";
    $sql_export .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($tables as $table) {
        $result = $conn->query("SELECT * FROM `$table` ");
        $num_fields = $result->field_count;

        $row2 = $conn->query("SHOW CREATE TABLE `$table` ")->fetch_row();
        $sql_export .= "\n\n" . $row2[1] . ";\n\n";

        while ($row = $result->fetch_row()) {
            $sql_export .= "INSERT INTO `$table` VALUES(";
            for ($j = 0; $j < $num_fields; $j++) {
                if (isset($row[$j])) {
                    $val = $conn->real_escape_string((string)$row[$j]);
                    $val = str_replace("\n", "\\n", $val);
                    $sql_export .= '"' . $val . '"';
                } else {
                    $sql_export .= 'NULL';
                }
                if ($j < ($num_fields - 1)) {
                    $sql_export .= ',';
                }
            }
            $sql_export .= ");\n";
        }
        $sql_export .= "\n\n\n";
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="db_backup_' . date('Ymd_His') . '.sql"');
    echo $sql_export;
    exit();

} elseif ($action === 'reset') {
    // Reset Site Data COMPLETELY
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // List of ALL tables to clear
    $tables_to_clear = [
        'user_subjects', 'user_blogs', 'forum_discussions', 
        'learning_plans', 'browser_sessions', 'subjects', 
        'branches', 'faculties', 'instructors', 'subject_instructors',
        'users', 'site_settings'
    ];
    
    foreach ($tables_to_clear as $table) {
        $conn->query("TRUNCATE TABLE $table");
    }
    
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    // Destroy session and redirect to install
    session_destroy();
    header("Location: ../install/index.php");
    exit();
}
?>