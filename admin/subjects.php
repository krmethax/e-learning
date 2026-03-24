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

// Delete Subject
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Get info before delete
    $s_res = $conn->query("SELECT subject_code, subject_name FROM subjects WHERE id = $id");
    $s_info = ($s_res && $row = $s_res->fetch_assoc()) ? $row['subject_code'] . " " . $row['subject_name'] : "Unknown";

    if ($conn->query("DELETE FROM subjects WHERE id = $id")) {
        logEvent($conn, 'Delete Subject', "Subject deleted: $s_info (ID: $id)");
        $message = "ลบรายวิชาเรียบร้อยแล้ว";
    } else {
        $message = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// Fetch all subjects with branch and faculty names
$subjects = $conn->query("
    SELECT s.*, b.branch_name, f.faculty_name 
    FROM subjects s 
    JOIN branches b ON s.branch_id = b.id 
    JOIN faculties f ON b.faculty_id = f.id 
    ORDER BY f.faculty_name, b.branch_name, s.subject_code
");

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">จัดการข้อมูลรายวิชา</h3>
                </div>
                <div class="panel-body">

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="row" style="margin-bottom: 20px;">
                <div class="col-md-12 text-right">
                    <a href="subject_add.php" class="btn btn-primary">เพิ่มรายวิชาใหม่</a>
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title" style="font-size: 16px;">รายการวิชาทั้งหมด</h3>
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>รหัส</th>
                                <th>ชื่อวิชา</th>
                                <th>สาขา/คณะ</th>
                                <th class="text-center" style="width: 100px;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $subjects->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['subject_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($row['faculty_name']); ?><br>
                                        <?php echo htmlspecialchars($row['branch_name']); ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <a href="subject_edit.php?id=<?php echo $row['id']; ?>" title="แก้ไข" style="margin-right: 10px; color: #333;">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                    <a href="subjects.php?delete=<?php echo $row['id']; ?>" title="ลบ" style="color: #333;" onclick="return confirm('ยืนยันการลบรายวิชา?')">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
