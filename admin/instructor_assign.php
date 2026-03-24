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

// Assign Instructor to Subject
if (isset($_POST['assign_instructor'])) {
    $instructor_id = (int)$_POST['instructor_id'];
    $subject_id = (int)$_POST['subject_id'];
    if ($conn->query("INSERT IGNORE INTO subject_instructors (subject_id, instructor_id) VALUES ($subject_id, $instructor_id)")) {
        $message = "มอบหมายวิชาให้ผู้สอนเรียบร้อยแล้ว";
    } else {
        $message = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// Fetch all instructors for assign dropdown
$instructors_list = $conn->query("SELECT * FROM instructors ORDER BY instructor_name");

// Fetch subjects for assign dropdown
$subjects_list = $conn->query("SELECT * FROM subjects ORDER BY subject_code");

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">มอบหมายวิชาให้ผู้สอน</h3>
                </div>
                <div class="panel-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <form action="instructor_assign.php" method="POST" class="form-horizontal" style="margin-top: 20px;">
                        <div class="form-group">
                            <label class="col-sm-3 control-label">ผู้สอน</label>
                            <div class="col-sm-6">
                                <select name="instructor_id" class="form-control" required>
                                    <option value="">เลือกผู้สอน...</option>
                                    <?php 
                                    while($i = $instructors_list->fetch_assoc()): ?>
                                        <option value="<?php echo $i['id']; ?>"><?php echo htmlspecialchars($i['instructor_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">วิชาที่สอน</label>
                            <div class="col-sm-6">
                                <select name="subject_id" class="form-control" required>
                                    <option value="">เลือกวิชา...</option>
                                    <?php 
                                    while($s = $subjects_list->fetch_assoc()): ?>
                                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['subject_code'] . " " . $s['subject_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-3 col-sm-6">
                                <button type="submit" name="assign_instructor" class="btn btn-success">มอบหมายวิชา</button>
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
