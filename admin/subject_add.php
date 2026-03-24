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
    if ($conn->query("INSERT INTO subjects (branch_id, subject_code, subject_name) VALUES ($branch_id, '$code', '$name')")) {
        $message = "เพิ่มรายวิชาเรียบร้อยแล้ว";
    } else {
        $message = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

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
        <div class="col-md-12" style="margin-top: 20px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">เพิ่มรายวิชาใหม่</h3>
                </div>
                <div class="panel-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <form action="subject_add.php" method="POST" class="form-horizontal" style="margin-top: 20px;">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title" style="font-size: 16px;">ข้อมูลทั่วไป</h3>
                            </div>
                            <div class="panel-body">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">เลือกสาขาวิชา</label>
                                    <div class="col-sm-6">
                                        <select name="branch_id" class="form-control" required>
                                            <option value="">เลือกสาขาวิชา...</option>
                                            <?php while($b = $branches_list->fetch_assoc()): ?>
                                                <option value="<?php echo $b['id']; ?>">
                                                    <?php echo htmlspecialchars($b['faculty_name'] . " - " . $b['branch_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">รหัสวิชา</label>
                                    <div class="col-sm-6">
                                        <input type="text" name="subject_code" class="form-control" required placeholder="เช่น 1101 101">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">ชื่อวิชา</label>
                                    <div class="col-sm-6">
                                        <input type="text" name="subject_name" class="form-control" required placeholder="ชื่อวิชา">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" name="add_subject" class="btn btn-primary">เพิ่มรายวิชา</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
