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

// Add Branch
if (isset($_POST['add_branch'])) {
    $faculty_id = (int)$_POST['faculty_id'];
    $name = $conn->real_escape_string($_POST['branch_name']);
    $conn->query("INSERT INTO branches (faculty_id, branch_name) VALUES ($faculty_id, '$name')");
    $message = "เพิ่มสาขาวิชาเรียบร้อยแล้ว";
}

// Delete Branch
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM branches WHERE id = $id");
    $message = "ลบสาขาวิชาเรียบร้อยแล้ว";
}

// Fetch all branches with faculty names
$branches = $conn->query("
    SELECT b.*, f.faculty_name 
    FROM branches b 
    JOIN faculties f ON b.faculty_id = f.id 
    ORDER BY f.faculty_name, b.branch_name
");

// Fetch faculties for select box
$faculties_list = $conn->query("SELECT * FROM faculties ORDER BY faculty_name");

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px; margin-bottom: 40px;">
            <div class="page-header">
                <h2>จัดการข้อมูลสาขาวิชา</h2>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">เพิ่มสาขาวิชาใหม่</h3>
                        </div>
                        <div class="panel-body">
                            <form action="branches.php" method="POST">
                                <div class="form-group">
                                    <label>เลือกคณะ</label>
                                    <select name="faculty_id" class="form-control" required>
                                        <option value="">เลือกคณะ...</option>
                                        <?php while($f = $faculties_list->fetch_assoc()): ?>
                                            <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['faculty_name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>ชื่อสาขาวิชา</label>
                                    <input type="text" name="branch_name" class="form-control" required>
                                </div>
                                <button type="submit" name="add_branch" class="btn btn-primary btn-block">เพิ่มข้อมูล</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">รายการสาขาวิชาทั้งหมด</h3>
                        </div>
                        <div class="panel-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>คณะ</th>
                                        <th>ชื่อสาขาวิชา</th>
                                        <th width="100">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $branches->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><span class="label label-info"><?php echo htmlspecialchars($row['faculty_name']); ?></span></td>
                                        <td><?php echo htmlspecialchars($row['branch_name']); ?></td>
                                        <td>
                                            <a href="branches.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-xs" onclick="return confirm('ลบสาขานี้?')">ลบ</a>
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