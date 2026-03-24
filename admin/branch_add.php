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
    if ($conn->query("INSERT INTO branches (faculty_id, branch_name) VALUES ($faculty_id, '$name')")) {
        $message = "เพิ่มสาขาวิชาเรียบร้อยแล้ว";
    } else {
        $message = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// Fetch faculties for select box
$faculties_list = $conn->query("SELECT * FROM faculties ORDER BY faculty_name");

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">เพิ่มสาขาวิชาใหม่</h3>
                </div>
                <div class="panel-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <form action="branch_add.php" method="POST" class="form-horizontal" style="margin-top: 20px;">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title" style="font-size: 16px;">ข้อมูลทั่วไป</h3>
                            </div>
                            <div class="panel-body">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">เลือกคณะ</label>
                                    <div class="col-sm-6">
                                        <select name="faculty_id" class="form-control" required>
                                            <option value="">เลือกคณะ...</option>
                                            <?php while($f = $faculties_list->fetch_assoc()): ?>
                                                <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['faculty_name']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">ชื่อสาขาวิชา</label>
                                    <div class="col-sm-6">
                                        <input type="text" name="branch_name" class="form-control" required placeholder="ระบุชื่อสาขาวิชา">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" name="add_branch" class="btn btn-primary">เพิ่มสาขาวิชา</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
