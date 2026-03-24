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

// Fetch subject data
$stmt = $conn->prepare("SELECT * FROM subjects WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();

if (!$subject) {
    header("Location: subjects.php");
    exit();
}

// Update Subject
if (isset($_POST['update_subject'])) {
    $branch_id = (int)$_POST['branch_id'];
    $code = $conn->real_escape_string($_POST['subject_code']);
    $name = $conn->real_escape_string($_POST['subject_name']);
    if ($conn->query("UPDATE subjects SET branch_id = $branch_id, subject_code = '$code', subject_name = '$name' WHERE id = $id")) {
        $message = "แก้ไขรายวิชาเรียบร้อยแล้ว";
        // Update local data for display
        $subject['branch_id'] = $branch_id;
        $subject['subject_code'] = $code;
        $subject['subject_name'] = $name;
    } else {
        $error = "เกิดข้อผิดพลาด: " . $conn->error;
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
                    <h3 class="panel-title">แก้ไขข้อมูลรายวิชา</h3>
                </div>
                <div class="panel-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form action="subject_edit.php?id=<?php echo $id; ?>" method="POST" class="form-horizontal" style="margin-top: 20px;">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title" style="font-size: 16px;">ข้อมูลรายวิชา</h3>
                            </div>
                            <div class="panel-body">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">รหัสวิชา</label>
                                    <div class="col-sm-6">
                                        <input type="text" name="subject_code" class="form-control" required value="<?php echo htmlspecialchars($subject['subject_code']); ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">ชื่อวิชา</label>
                                    <div class="col-sm-6">
                                        <input type="text" name="subject_name" class="form-control" required value="<?php echo htmlspecialchars($subject['subject_name']); ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">สาขาวิชา</label>
                                    <div class="col-sm-6">
                                        <select name="branch_id" class="form-control" required>
                                            <option value="">เลือกสาขาวิชา...</option>
                                            <?php while($b = $branches_list->fetch_assoc()): ?>
                                                <option value="<?php echo $b['id']; ?>" <?php echo ($subject['branch_id'] == $b['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($b['faculty_name'] . ' - ' . $b['branch_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" name="update_subject" class="btn btn-warning">บันทึกการแก้ไข</button>
                            <a href="subjects.php" class="btn btn-default">กลับไปยังรายการ</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
