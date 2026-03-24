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

// Add Faculty
if (isset($_POST['add_faculty'])) {
    $name = $conn->real_escape_string($_POST['faculty_name']);
    $conn->query("INSERT INTO faculties (faculty_name) VALUES ('$name')");
    $message = "เพิ่มคณะเรียบร้อยแล้ว";
}

// Delete Faculty
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM faculties WHERE id = $id");
    $message = "ลบคณะเรียบร้อยแล้ว";
}

// Fetch all faculties
$faculties = $conn->query("SELECT * FROM faculties ORDER BY id DESC");

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px; margin-bottom: 40px;">
            <div class="page-header">
                <h2>จัดการข้อมูลคณะ</h2>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">เพิ่มคณะใหม่</h3>
                        </div>
                        <div class="panel-body">
                            <form action="faculties.php" method="POST">
                                <div class="form-group">
                                    <label>ชื่อคณะ</label>
                                    <input type="text" name="faculty_name" class="form-control" required>
                                </div>
                                <button type="submit" name="add_faculty" class="btn btn-primary btn-block">เพิ่มข้อมูล</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">รายการคณะทั้งหมด</h3>
                        </div>
                        <div class="panel-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>ชื่อคณะ</th>
                                        <th width="100">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $faculties->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['faculty_name']); ?></td>
                                        <td>
                                            <a href="faculties.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-xs" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบคณะนี้? ข้อมูลสาขาและวิชาในคณะจะหายไปด้วย')">ลบ</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-left">
                <a href="settings.php" class="btn btn-default"><span class="glyphicon glyphicon-arrow-left"></span> กลับไปหน้าตั้งค่า</a>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>