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

// Delete Instructor assignment
if (isset($_GET['delete_assign'])) {
    $sid = (int)$_GET['subject_id'];
    $iid = (int)$_GET['instructor_id'];
    if ($conn->query("DELETE FROM subject_instructors WHERE subject_id = $sid AND instructor_id = $iid")) {
        $message = "ยกเลิกการมอบหมายวิชาเรียบร้อยแล้ว";
    } else {
        $message = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// Delete Instructor (Entirely)
if (isset($_GET['delete_instructor'])) {
    $id = (int)$_GET['delete_instructor'];
    if ($conn->query("DELETE FROM instructors WHERE id = $id")) {
        $message = "ลบข้อมูลผู้สอนเรียบร้อยแล้ว";
    } else {
        $message = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// Fetch all instructors
$instructors = $conn->query("SELECT * FROM instructors ORDER BY instructor_name");

// Fetch instructors with their subjects
$assignments = $conn->query("
    SELECT i.instructor_name, s.subject_name, s.subject_code, si.subject_id, si.instructor_id
    FROM subject_instructors si
    JOIN instructors i ON si.instructor_id = i.id
    JOIN subjects s ON si.subject_id = s.id
    ORDER BY i.instructor_name, s.subject_code
");

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">จัดการข้อมูลผู้สอน</h3>
                </div>
                <div class="panel-body">

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="row" style="margin-bottom: 20px;">
                <div class="col-md-12 text-right">
                    <a href="instructor_add.php" class="btn btn-primary">เพิ่มผู้สอนและมอบหมายวิชา</a>
                </div>
            </div>

            <div class="row">
                <!-- Assignment List -->
                <div class="col-md-8">
                    <div class="panel panel-default">
                        <div class="panel-heading"><h3 class="panel-title" style="font-size: 16px;">รายการการสอน</h3></div>
                        <div class="panel-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ชื่อผู้สอน</th>
                                        <th>วิชาที่สอน</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $assignments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['instructor_name']); ?></td>
                                        <td>
                                            <small><?php echo htmlspecialchars($row['subject_code']); ?></small><br>
                                            <?php echo htmlspecialchars($row['subject_name']); ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-heading"><h3 class="panel-title" style="font-size: 16px;">รายชื่อผู้สอนทั้งหมด</h3></div>
                        <div class="panel-body">
                            <ul class="list-group">
                                <?php 
                                while($i = $instructors->fetch_assoc()): ?>
                                    <li class="list-group-item">
                                        <?php echo htmlspecialchars($i['instructor_name']); ?>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
