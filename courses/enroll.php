<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$path = '../';
require_once $path . 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $path . "login/index.php");
    exit();
}

if (isset($_GET['subject_id'])) {
    $user_id = $_SESSION['user_id'];
    $subject_id = (int)$_GET['subject_id'];

    // Create table if not exists (just in case)
    $sql_create = "CREATE TABLE IF NOT EXISTS user_subjects (
        user_id INT(11) NOT NULL,
        subject_id INT(11) NOT NULL,
        PRIMARY KEY (user_id, subject_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    $conn->query($sql_create);

    // Insert enrollment
    $stmt = $conn->prepare("INSERT IGNORE INTO user_subjects (user_id, subject_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $subject_id);
    
    if ($stmt->execute()) {
        header("Location: " . $path . "my/index.php");
        exit();
    } else {
        echo "เกิดข้อผิดพลาดในการลงทะเบียน: " . $conn->error;
    }
} else {
    header("Location: index.php");
    exit();
}
?>