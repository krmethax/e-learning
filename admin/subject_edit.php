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
    $name_en = $conn->real_escape_string($_POST['subject_name_en']);
    $credits = $conn->real_escape_string($_POST['credits']);
    $desc_th = $conn->real_escape_string($_POST['description_th']);
    $desc_en = $conn->real_escape_string($_POST['description_en']);
    $start_date = $_POST['start_date'] ?: null;
    $end_date = $_POST['end_date'] ?: null;
    $course_start = $_POST['course_start'] ?: null;
    $course_end = $_POST['course_end'] ?: null;
    $is_visible = isset($_POST['is_visible']) ? (int)$_POST['is_visible'] : 1;
    $enrollment_type = $conn->real_escape_string($_POST['enrollment_type']);
    $enrollment_key = $conn->real_escape_string($_POST['enrollment_key']);
    
    // Handle Cover Image Upload
    $cover_image_sql = "";
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . rand(1000, 9999) . '.' . $ext;
        $upload_path = '../assets/img/subjects/' . $filename;
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
            $new_cover_image = 'assets/img/subjects/' . $filename;
            $cover_image_sql = ", cover_image = '$new_cover_image'";
            $subject['cover_image'] = $new_cover_image; // Update local for display
        }
    }

    $sql = "UPDATE subjects SET 
            branch_id = $branch_id, 
            subject_code = '$code', 
            subject_name = '$name',
            subject_name_en = '$name_en',
            credits = '$credits',
            description_th = '$desc_th',
            description_en = '$desc_en',
            start_date = " . ($start_date ? "'$start_date'" : "NULL") . ",
            end_date = " . ($end_date ? "'$end_date'" : "NULL") . ",
            course_start = " . ($course_start ? "'$course_start'" : "NULL") . ",
            course_end = " . ($course_end ? "'$course_end'" : "NULL") . ",
            is_visible = $is_visible,
            enrollment_type = '$enrollment_type',
            enrollment_key = " . ($enrollment_key ? "'$enrollment_key'" : "NULL") . "
            $cover_image_sql
            WHERE id = $id";
            
    if ($conn->query($sql)) {
        $message = "แก้ไขรายวิชาเรียบร้อยแล้ว";
        // Update local data for display
        $subject['branch_id'] = $branch_id;
        $subject['subject_code'] = $code;
        $subject['subject_name'] = $name;
        $subject['subject_name_en'] = $name_en;
        $subject['credits'] = $credits;
        $subject['description_th'] = $desc_th;
        $subject['description_en'] = $desc_en;
        $subject['start_date'] = $start_date;
        $subject['end_date'] = $end_date;
        $subject['course_start'] = $course_start;
        $subject['course_end'] = $course_end;
        $subject['is_visible'] = $is_visible;
        $subject['enrollment_type'] = $enrollment_type;
        $subject['enrollment_key'] = $enrollment_key;
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

                    <form action="subject_edit.php?id=<?php echo $id; ?>" method="POST" class="form-horizontal" style="margin-top: 20px;" enctype="multipart/form-data">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title" style="font-size: 16px;">ข้อมูลรายวิชา</h3>
                            </div>
                            <div class="panel-body">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">รูปภาพปกวิชา</label>
                                    <div class="col-sm-6">
                                        <?php if (!empty($subject['cover_image'])): ?>
                                            <img src="<?php echo $path . $subject['cover_image']; ?>" style="max-width: 200px; margin-bottom: 10px; display: block;">
                                        <?php endif; ?>
                                        <input type="file" name="cover_image" class="form-control" accept="image/*">
                                        <small class="text-muted">หากไม่อยากเปลี่ยน ให้เว้นว่างไว้</small>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">รหัสวิชา</label>
                                    <div class="col-sm-6">
                                        <input type="text" name="subject_code" class="form-control" required value="<?php echo htmlspecialchars($subject['subject_code']); ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">ชื่อวิชา (ภาษาไทย)</label>
                                    <div class="col-sm-6">
                                        <input type="text" name="subject_name" class="form-control" required value="<?php echo htmlspecialchars($subject['subject_name']); ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">ชื่อวิชา (ภาษาอังกฤษ)</label>
                                    <div class="col-sm-6">
                                        <input type="text" name="subject_name_en" class="form-control" value="<?php echo htmlspecialchars($subject['subject_name_en'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">หน่วยกิต</label>
                                    <div class="col-sm-6">
                                        <input type="text" name="credits" class="form-control" value="<?php echo htmlspecialchars($subject['credits'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">คำอธิบายรายวิชา (ภาษาไทย)</label>
                                    <div class="col-sm-6">
                                        <textarea name="description_th" class="form-control" rows="4"><?php echo htmlspecialchars($subject['description_th'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Course Description (English)</label>
                                    <div class="col-sm-6">
                                        <textarea name="description_en" class="form-control" rows="4"><?php echo htmlspecialchars($subject['description_en'] ?? ''); ?></textarea>
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
                                <hr>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">วันที่เริ่มเปิดวิชา</label>
                                    <div class="col-sm-6">
                                        <?php 
                                            $s_date = !empty($subject['start_date']) ? date('Y-m-d H:i', strtotime($subject['start_date'])) : '';
                                        ?>
                                        <input type="text" name="start_date" id="start_date" class="form-control" value="<?php echo $s_date; ?>" placeholder="เลือกวันที่และเวลาเริ่ม">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">วันที่ปิดวิชา</label>
                                    <div class="col-sm-6">
                                        <?php 
                                            $e_date = !empty($subject['end_date']) ? date('Y-m-d H:i', strtotime($subject['end_date'])) : '';
                                        ?>
                                        <input type="text" name="end_date" id="end_date" class="form-control" value="<?php echo $e_date; ?>" placeholder="เลือกวันที่และเวลาปิด">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">วิธีการลงทะเบียน</label>
                                    <div class="col-sm-6">
                                        <select name="enrollment_type" class="form-control" id="enrollment_type">
                                            <option value="open" <?php echo ($subject['enrollment_type'] === 'open') ? 'selected' : ''; ?>>เปิดให้เข้าได้เอง (Open)</option>
                                            <option value="password" <?php echo ($subject['enrollment_type'] === 'password') ? 'selected' : ''; ?>>ล็อกแบบใช้รหัสผ่าน (Key)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group" id="key_field" style="<?php echo ($subject['enrollment_type'] === 'password') ? '' : 'display:none;'; ?>">
                                    <label class="col-sm-3 control-label">รหัสผ่านสำหรับลงทะเบียน</label>
                                    <div class="col-sm-6">
                                        <input type="text" name="enrollment_key" class="form-control" placeholder="ระบุ Enrollment Key" value="<?php echo htmlspecialchars($subject['enrollment_key'] ?? ''); ?>">
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

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enrollment Key toggle
    var typeSelect = document.getElementById('enrollment_type');
    var keyField = document.getElementById('key_field');
    
    typeSelect.addEventListener('change', function() {
        if (this.value === 'password') {
            keyField.style.display = 'block';
        } else {
            keyField.style.display = 'none';
        }
    });

    // Flatpickr initialization
    const config = {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        time_24hr: true,
        locale: "th",
        altInput: true,
        altFormat: "j F Y (H:i น.)",
    };
    
    flatpickr("#start_date", config);
    flatpickr("#end_date", config);
});
</script>

<?php include $path . 'includes/footer.php'; ?>
