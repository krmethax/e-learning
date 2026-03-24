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

// Add Subject
if (isset($_POST['add_subject'])) {
    $branch_id = (int)$_POST['branch_id'];
    $code = $conn->real_escape_string($_POST['subject_code']);
    $name = $conn->real_escape_string($_POST['subject_name']);
    $conn->query("INSERT INTO subjects (branch_id, subject_code, subject_name) VALUES ($branch_id, '$code', '$name')");
    $message = "เพิ่มรายวิชาเรียบร้อยแล้ว";
}

// Delete Subject
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM subjects WHERE id = $id");
    $message = "ลบรายวิชาเรียบร้อยแล้ว";
}

// Fetch all subjects with branch and faculty names
$subjects = $conn->query("
    SELECT s.*, b.branch_name, f.faculty_name 
    FROM subjects s 
    JOIN branches b ON s.branch_id = b.id 
    JOIN faculties f ON b.faculty_id = f.id 
    ORDER BY f.faculty_name, b.branch_name, s.subject_code
");

// Fetch branches for select box
$branches_list = $conn->query("
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
        <div class="col-md-12" style="margin-top: 20px; margin-bottom: 40px;">
            <div class="page-header">
                <h2>จัดการข้อมูลรายวิชา</h2>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">เพิ่มรายวิชาใหม่</h3>
                        </div>
                        <div class="panel-body">
                            <form action="subjects.php" method="POST">
                                <div class="form-group">
                                    <label>เลือกสาขาวิชา (แยกตามคณะ)</label>
                                    <select name="branch_id" class="form-control" required>
                                        <option value="">เลือกสาขาวิชา...</option>
                                        <?php while($b = $branches_list->fetch_assoc()): ?>
                                            <option value="<?php echo $b['id']; ?>">
                                                <?php echo htmlspecialchars($b['faculty_name'] . " - " . $b['branch_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>รหัสวิชา</label>
                                    <input type="text" name="subject_code" class="form-control" required placeholder="เช่น 1101 101">
                                </div>
                                <div class="form-group">
                                    <label>ชื่อวิชา</label>
                                    <input type="text" name="subject_name" class="form-control" required>
                                </div>
                                <button type="submit" name="add_subject" class="btn btn-primary btn-block">เพิ่มข้อมูล</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">รายการวิชาทั้งหมด</h3>
                        </div>
                        <div class="panel-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>รหัส</th>
                                        <th>ชื่อวิชา</th>
                                        <th>สาขา/คณะ</th>
                                        <th width="100">จัดการ</th>
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
                                        <td>
                                            <a href="subjects.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-xs" onclick="return confirm('ลบรายวิชานี้?')">ลบ</a>
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