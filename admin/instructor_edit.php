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

$id = (int)($_GET['id'] ?? 0);
$message = '';
$error = '';

// Fetch instructor data
$stmt = $conn->prepare("SELECT * FROM instructors WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$instructor = $stmt->get_result()->fetch_assoc();

if (!$instructor) {
    header("Location: instructors.php");
    exit();
}

// Update Instructor
if (isset($_POST['update_instructor'])) {
    $name = $conn->real_escape_string($_POST['instructor_name']);
    if ($conn->query("UPDATE instructors SET instructor_name = '$name' WHERE id = $id")) {
        $message = "แก้ไขชื่อผู้สอนเรียบร้อยแล้ว";
        // Update local data for display
        $instructor['instructor_name'] = $name;
    } else {
        $error = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">แก้ไขข้อมูลผู้สอน</h3>
                </div>
                <div class="panel-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form action="instructor_edit.php?id=<?php echo $id; ?>" method="POST" class="form-horizontal" style="margin-top: 20px;">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title" style="font-size: 16px;">ข้อมูลผู้สอน</h3>
                            </div>
                            <div class="panel-body">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">ชื่อ-นามสกุล ผู้สอน</label>
                                    <div class="col-sm-6">
                                        <input type="text" name="instructor_name" class="form-control" required value="<?php echo htmlspecialchars($instructor['instructor_name']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" name="update_instructor" class="btn btn-warning">บันทึกการแก้ไข</button>
                            <a href="instructors.php" class="btn btn-default">กลับไปยังรายการ</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
