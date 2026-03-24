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

$message = '';

// Add Instructor
if (isset($_POST['add_instructor'])) {
    $name = $conn->real_escape_string($_POST['instructor_name']);
    if ($conn->query("INSERT INTO instructors (instructor_name) VALUES ('$name')")) {
        $message = "เพิ่มข้อมูลผู้สอนเรียบร้อยแล้ว";
    } else {
        $message = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">เพิ่มผู้สอนใหม่</h3>
                </div>
                <div class="panel-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <form action="instructor_add.php" method="POST" class="form-horizontal" style="margin-top: 20px;">
                        <div class="form-group">
                            <label class="col-sm-3 control-label">ชื่อผู้สอน</label>
                            <div class="col-sm-6">
                                <input type="text" name="instructor_name" class="form-control" required placeholder="ระบุชื่อ-นามสกุล">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-3 col-sm-6">
                                <button type="submit" name="add_instructor" class="btn btn-primary">เพิ่มผู้สอน</button>
                                <a href="instructors.php" class="btn btn-default">ยกเลิก</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
