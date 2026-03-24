<?php
require_once 'includes/db.php';

// 1. Create user_subjects table if not exists
$sql = "CREATE TABLE IF NOT EXISTS user_subjects (
    user_id INT(11) NOT NULL,
    subject_id INT(11) NOT NULL,
    PRIMARY KEY (user_id, subject_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

if ($conn->query($sql) === TRUE) {
    echo "Table user_subjects created or already exists.\n";
} else {
    die("Error creating table: " . $conn->error);
}

// 2. Add sample data for the first user
$user_res = $conn->query("SELECT id FROM users LIMIT 1");
if ($user_res && $user_row = $user_res->fetch_assoc()) {
    $uid = $user_row['id'];
    
    // Get some subject IDs
    $sub_res = $conn->query("SELECT id FROM subjects LIMIT 3");
    if ($sub_res && $sub_res->num_rows > 0) {
        while ($sub_row = $sub_res->fetch_assoc()) {
            $sid = $sub_row['id'];
            $conn->query("INSERT IGNORE INTO user_subjects (user_id, subject_id) VALUES ($uid, $sid)");
        }
        echo "Added sample enrollments for user ID: $uid\n";
    } else {
        echo "No subjects found to enroll.\n";
    }
} else {
    echo "No users found.\n";
}
?>