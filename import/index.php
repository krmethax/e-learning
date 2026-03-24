<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$path = '../';
require_once $path . 'includes/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $path . "login/index.php");
    exit();
}

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">นำเข้ารายชื่อผู้เรียน</h3>
                </div>
                <div class="panel-body">
                    <p>ฟีเจอร์การนำเข้ารายชื่อผู้เรียน (Excel/CSV) กำลังอยู่ระหว่างการพัฒนา...</p>
                    <form action="index.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>เลือกไฟล์ที่ต้องการนำเข้า (.csv, .xlsx)</label>
                            <input type="file" name="import_file" class="form-control" disabled>
                        </div>
                        <button type="submit" class="btn btn-primary" disabled>เริ่มนำเข้าข้อมูล</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
