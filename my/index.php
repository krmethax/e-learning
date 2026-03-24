<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$path = '../';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $path . "login/index.php");
    exit();
}
?>

<?php include $path . 'includes/header.php'; ?>
<?php include $path . 'includes/navbar.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 50px;">
            <div class="jumbotron">
                <h1>สวัสดีคุณ <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
                <p>ยินดีต้อนรับสู่ระบบจัดการข้อมูลส่วนตัวของคุณ</p>
                <p>
                    <a class="btn btn-primary btn-lg" href="#" role="button">ดูคอร์สเรียนของฉัน</a>
                    <a class="btn btn-default btn-lg" href="../login/logout.php" role="button">ออกจากระบบ</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
