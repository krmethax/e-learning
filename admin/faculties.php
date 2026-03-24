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

// Delete Faculty
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Get name before delete
    $f_res = $conn->query("SELECT faculty_name FROM faculties WHERE id = $id");
    $f_name = ($f_res && $row = $f_res->fetch_assoc()) ? $row['faculty_name'] : "Unknown";

    if ($conn->query("DELETE FROM faculties WHERE id = $id")) {
        logEvent($conn, 'Delete Faculty', "Faculty deleted: $f_name (ID: $id)");
        $message = "ลบคณะเรียบร้อยแล้ว";
    } else {
        $message = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// Fetch all faculties
$faculties = $conn->query("SELECT * FROM faculties ORDER BY id DESC");

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">จัดการข้อมูลคณะ</h3>
                </div>
                <div class="panel-body">

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="row" style="margin-bottom: 20px;">
                <div class="col-md-12 text-right">
                    <a href="faculty_add.php" class="btn btn-primary">เพิ่มคณะใหม่</a>
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title" style="font-size: 16px;">รายการคณะทั้งหมด</h3>
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ชื่อคณะ</th>
                                <th class="text-center" style="width: 100px;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $faculties->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['faculty_name']); ?></td>
                                <td class="text-center">
                                    <a href="faculty_edit.php?id=<?php echo $row['id']; ?>" title="แก้ไข" style="margin-right: 10px; color: #333;">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                    <a href="faculties.php?delete=<?php echo $row['id']; ?>" title="ลบ" style="color: #333;" onclick="return confirm('ยืนยันการลบคณะ?')">
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
