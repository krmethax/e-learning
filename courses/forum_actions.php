<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$path = '../';
require_once $path . 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'new_topic') {
    $subject_id = (int)$_POST['subject_id'];
    $item_id = (int)$_POST['item_id'];
    $title = $conn->real_escape_string($_POST['title']);
    $message = $conn->real_escape_string($_POST['message']);

    $sql = "INSERT INTO course_forum_discussions (item_id, user_id, title, message) VALUES ($item_id, $user_id, '$title', '$message')";
    if ($conn->query($sql)) {
        header("Location: lesson.php?id=$subject_id&item_id=$item_id");
    } else {
        die("Error: " . $conn->error);
    }
} elseif ($action === 'reply') {
    $subject_id = (int)$_POST['subject_id'];
    $item_id = (int)$_POST['item_id'];
    $topic_id = (int)$_POST['topic_id'];
    $message = $conn->real_escape_string($_POST['message']);

    $sql = "INSERT INTO course_forum_replies (discussion_id, user_id, message) VALUES ($topic_id, $user_id, '$message')";
    if ($conn->query($sql)) {
        header("Location: forum_topic.php?id=$subject_id&item_id=$item_id&topic_id=$topic_id");
    } else {
        die("Error: " . $conn->error);
    }
} else {
    header("Location: " . $path . "index.php");
}
?>
