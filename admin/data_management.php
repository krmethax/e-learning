<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$path = '../';
require_once $path . 'includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . $path . "index.php");
    exit();
}

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px; margin-bottom: 40px;">
            <div class="page-header" style="border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 25px;">
                <h2 style="font-weight: 600;">ตั้งค่าข้อมูลระบบ</h2>
            </div>

            <div class="row">
                <!-- Faculty Management -->
                <div class="col-md-3">
                    <div class="panel panel-default text-center" style="padding: 20px;">
                        <span class="glyphicon glyphicon-education" style="font-size: 40px; color: #4285F4; margin-bottom: 15px;"></span>
                        <h4>จัดการข้อมูลคณะ</h4>
                        <p class="text-muted small">เพิ่ม ลบ หรือแก้ไขรายชื่อคณะทั้งหมดในระบบ</p>
                        <a href="faculties.php" class="btn btn-primary btn-block">เข้าสู่เมนู</a>
                    </div>
                </div>

                <!-- Branch Management -->
                <div class="col-md-3">
                    <div class="panel panel-default text-center" style="padding: 20px;">
                        <span class="glyphicon glyphicon-list-alt" style="font-size: 40px; color: #34A853; margin-bottom: 15px;"></span>
                        <h4>จัดการข้อมูลสาขาวิชา</h4>
                        <p class="text-muted small">จัดการสาขาวิชาต่างๆ โดยแยกตามคณะ</p>
                        <a href="branches.php" class="btn btn-success btn-block">เข้าสู่เมนู</a>
                    </div>
                </div>

                <!-- Subject Management -->
                <div class="col-md-3">
                    <div class="panel panel-default text-center" style="padding: 20px;">
                        <span class="glyphicon glyphicon-book" style="font-size: 40px; color: #FBBC05; margin-bottom: 15px;"></span>
                        <h4>จัดการข้อมูลรายวิชา</h4>
                        <p class="text-muted small">กำหนดรหัสวิชา ชื่อวิชา และสังกัดสาขาวิชา</p>
                        <a href="subjects.php" class="btn btn-warning btn-block">เข้าสู่เมนู</a>
                    </div>
                </div>

                <!-- Instructor Management -->
                <div class="col-md-3">
                    <div class="panel panel-default text-center" style="padding: 20px;">
                        <span class="glyphicon glyphicon-user" style="font-size: 40px; color: #EA4335; margin-bottom: 15px;"></span>
                        <h4>จัดการข้อมูลผู้สอน</h4>
                        <p class="text-muted small">จัดการรายชื่อผู้สอนและการมอบหมายวิชา</p>
                        <a href="instructors.php" class="btn btn-danger btn-block">เข้าสู่เมนู</a>
                    </div>
                </div>
            </div>

            <div class="text-center" style="margin-top: 30px;">
                <a href="settings.php" class="btn btn-default"><span class="glyphicon glyphicon-cog"></span> ไปที่หน้าตั้งค่าระบบ</a>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>