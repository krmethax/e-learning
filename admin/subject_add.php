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
    $cover_image = null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . rand(1000, 9999) . '.' . $ext;
        $upload_path = '../assets/img/subjects/' . $filename;
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
            $cover_image = 'assets/img/subjects/' . $filename;
        }
    }

    $sql = "INSERT INTO subjects (branch_id, subject_code, subject_name, subject_name_en, credits, description_th, description_en, cover_image, start_date, end_date, course_start, course_end, is_visible, enrollment_type, enrollment_key) 
            VALUES ($branch_id, '$code', '$name', '$name_en', '$credits', '$desc_th', '$desc_en', " . ($cover_image ? "'$cover_image'" : "NULL") . ", " . ($start_date ? "'$start_date'" : "NULL") . ", " . ($end_date ? "'$end_date'" : "NULL") . ", " . ($course_start ? "'$course_start'" : "NULL") . ", " . ($course_end ? "'$course_end'" : "NULL") . ", $is_visible, '$enrollment_type', " . ($enrollment_key ? "'$enrollment_key'" : "NULL") . ")";
    
    if ($conn->query($sql)) {
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

                    <form action="subject_add.php" method="POST" class="form-horizontal" style="margin-top: 20px;" enctype="multipart/form-data">
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
                                    <label class="col-sm-3 control-label">รูปภาพปกวิชา</label>
                                    <div class="col-sm-6">
                                        <input type="file" name="cover_image" class="form-control" accept="image/*">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">รหัสวิชา</label>
                                    <div class="col-sm-6">
                                        <input type="text" name="subject_code" class="form-control" required placeholder="เช่น 1306444-65">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">ชื่อวิชา (ภาษาไทย)</label>
                                    <div class="col-sm-6">
                                        <input type="text" name="subject_name" class="form-control" required placeholder="เช่น การเรียนรู้ของเครื่อง">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">ชื่อวิชา (ภาษาอังกฤษ)</label>
                                    <div class="col-sm-6">
                                        <input type="text" name="subject_name_en" class="form-control" placeholder="เช่น Machine Learning">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">หน่วยกิต</label>
                                    <div class="col-sm-6">
                                        <input type="text" name="credits" class="form-control" placeholder="เช่น 3(3-0-6)">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">คำอธิบายรายวิชา (ภาษาไทย)</label>
                                    <div class="col-sm-6">
                                        <textarea name="description_th" class="form-control" rows="4"></textarea>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Course Description (English)</label>
                                    <div class="col-sm-6">
                                        <textarea name="description_en" class="form-control" rows="4"></textarea>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">การแสดงผล</label>
                                    <div class="col-sm-6">
                                        <select name="is_visible" class="form-control">
                                            <option value="1">แสดงรายวิชา (Show)</option>
                                            <option value="0">ซ่อนรายวิชา (Hide)</option>
                                        </select>
                                    </div>
                                </div>
                                <hr>
                                <div class="panel panel-info">
                                    <div class="panel-heading">ตั้งค่าการรับสมัคร (Enrollment)</div>
                                    <div class="panel-body">
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">วันที่เริ่มรับสมัคร</label>
                                            <div class="col-sm-6">
                                                <input type="text" name="start_date" id="start_date" class="form-control" placeholder="เลือกวันที่และเวลาเริ่มรับสมัคร">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">วันที่ปิดรับสมัคร</label>
                                            <div class="col-sm-6">
                                                <input type="text" name="end_date" id="end_date" class="form-control" placeholder="เลือกวันที่และเวลาปิดรับสมัคร">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="panel panel-success">
                                    <div class="panel-heading">ตั้งค่าการเข้าถึงเนื้อหา (Course Access)</div>
                                    <div class="panel-body">
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">วันที่เริ่มเปิดเรียน</label>
                                            <div class="col-sm-6">
                                                <input type="text" name="course_start" id="course_start" class="form-control" placeholder="เลือกวันที่และเวลาเริ่มเปิดเรียน">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">วันที่ปิดเรียน</label>
                                            <div class="col-sm-6">
                                                <input type="text" name="course_end" id="course_end" class="form-control" placeholder="เลือกวันที่และเวลาปิดเรียน">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">วิธีการลงทะเบียน</label>
                                    <div class="col-sm-6">
                                        <select name="enrollment_type" class="form-control" id="enrollment_type">
                                            <option value="open">เปิดให้เข้าได้เอง (Open)</option>
                                            <option value="password">ล็อกแบบใช้รหัสผ่าน (Key)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group" id="key_field" style="display:none;">
                                    <label class="col-sm-3 control-label">รหัสผ่านสำหรับลงทะเบียน</label>
                                    <div class="col-sm-6">
                                        <input type="text" name="enrollment_key" class="form-control" placeholder="ระบุ Enrollment Key">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" name="add_subject" class="btn btn-primary">เพิ่มรายวิชา</button>
                            <a href="subjects.php" class="btn btn-default">ยกเลิก</a>
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
    flatpickr("#course_start", config);
    flatpickr("#course_end", config);
});
</script>

<?php include $path . 'includes/footer.php'; ?>
