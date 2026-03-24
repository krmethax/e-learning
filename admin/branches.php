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

// Delete Branch
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Get info before delete
    $b_res = $conn->query("SELECT branch_name FROM branches WHERE id = $id");
    $b_name = ($b_res && $row = $b_res->fetch_assoc()) ? $row['branch_name'] : "Unknown";

    if ($conn->query("DELETE FROM branches WHERE id = $id")) {
        logEvent($conn, 'Delete Branch', "Branch deleted: $b_name (ID: $id)");
        $message = "ลบสาขาวิชาเรียบร้อยแล้ว";
    } else {
        $message = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// Fetch all branches with faculty names
$branches = $conn->query("
    SELECT b.*, f.faculty_name 
    FROM branches b 
    JOIN faculties f ON b.faculty_id = f.id 
    ORDER BY f.faculty_name, b.branch_name
");

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">จัดการข้อมูลสาขาวิชา</h3>
                </div>
                <div class="panel-body">

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="row" style="margin-bottom: 20px;">
                <div class="col-md-12 text-right">
                    <a href="branch_add.php" class="btn btn-primary">เพิ่มสาขาวิชาใหม่</a>
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title" style="font-size: 16px;">รายการสาขาวิชาทั้งหมด</h3>
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>คณะ</th>
                                <th>ชื่อสาขาวิชา</th>
                                <th class="text-center" style="width: 100px;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $branches->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['faculty_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['branch_name']); ?></td>
                                <td class="text-center">
                                    <a href="branch_edit.php?id=<?php echo $row['id']; ?>" title="แก้ไข" style="margin-right: 10px; color: #333;">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                    <a href="branches.php?delete=<?php echo $row['id']; ?>" title="ลบ" style="color: #333;" onclick="return confirm('ยืนยันการลบสาขาวิชา?')">
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
