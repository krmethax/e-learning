<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$path = ''; 
require_once 'includes/db.php';

// Check if any user exists, if not, redirect to setup
$check_users = $conn->query("SELECT id FROM users LIMIT 1");
if (!$check_users || $check_users->num_rows == 0) {
    header("Location: set_up.php");
    exit();
}

// If logged in, redirect to my/index.php
if (isset($_SESSION['user_id'])) {
    header("Location: my/index.php");
    exit();
}

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-12 text-center" style="margin-top: 50px;">
            <h1>ยินดีต้อนรับสู่ระบบ E-Learning</h1>
            <p>หน้าหลักกำลังอยู่ระหว่างการพัฒนา...</p>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
