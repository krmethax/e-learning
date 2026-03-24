<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$path = '../';
require_once $path . 'includes/db.php';

// Redirect if not logged in or doesn't have proper role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'instructor'])) {
    header("Location: " . $path . "login/index.php");
    exit();
}

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch subjects for the current user
if ($role === 'admin') {
    // Admins can see all subjects
    $subjects_query = "SELECT id, subject_code, subject_name FROM subjects ORDER BY subject_code";
} else {
    // Instructors see only their assigned subjects
    // Note: This assumes instructors table is linked to users table via some logic 
    // or we use the user's name to match the instructor_name.
    // Given the current schema, let's try to match by full_name as a fallback 
    // or look for an 'instructor_id' in users table if we were to add it.
    // For now, let's match instructors.instructor_name with users.full_name
    $full_name = $conn->real_escape_string($_SESSION['full_name']);
    $subjects_query = "
        SELECT s.id, s.subject_code, s.subject_name 
        FROM subjects s
        JOIN subject_instructors si ON s.id = si.subject_id
        JOIN instructors i ON si.instructor_id = i.id
        WHERE i.instructor_name = '$full_name'
        ORDER BY s.subject_code";
}
$subjects_list = $conn->query($subjects_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];
    $subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
    
    if ($subject_id <= 0) {
        $error = "กรุณาเลือกรายวิชาที่ต้องการนำเข้า";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
    } else {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if ($ext !== 'csv') {
            $error = "กรุณาเลือกไฟล์ .csv เท่านั้น";
        } else {
            if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
                // Skip header
                fgetcsv($handle, 1000, ",");
                
                $count = 0;
                $conn->begin_transaction();
                try {
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        // Expected CSV columns: Full Name (Firstname Lastname), Student ID, Status
                        if (count($data) < 2) continue;
                        
                        $raw_name = trim($data[0]);
                        $student_id = $conn->real_escape_string(trim($data[1]));
                        $status = isset($data[2]) ? trim($data[2]) : '';
                        
                        // Check status - only allow student or นักเรียน
                        if (!in_array($status, ['student', 'นักเรียน'])) {
                            continue;
                        }

                        // Split name into firstname and lastname
                        $name_parts = explode(' ', $raw_name, 2);
                        $firstname = $conn->real_escape_string($name_parts[0]);
                        $lastname = isset($name_parts[1]) ? $conn->real_escape_string($name_parts[1]) : '';
                        
                        // Password defaults to student ID
                        $password = password_hash($student_id, PASSWORD_DEFAULT);
                        $full_name = $conn->real_escape_string($raw_name);
                        
                        // 1. Check/Create User
                        $user_id_to_enroll = 0;
                        $check = $conn->query("SELECT id FROM users WHERE username = '$student_id'");
                        if ($check->num_rows > 0) {
                            $user_row = $check->fetch_assoc();
                            $user_id_to_enroll = $user_row['id'];
                        } else {
                            $sql = "INSERT INTO users (username, password, firstname, lastname, full_name, role) 
                                    VALUES ('$student_id', '$password', '$firstname', '$lastname', '$full_name', 'user')";
                            if ($conn->query($sql)) {
                                $user_id_to_enroll = $conn->insert_id;
                            }
                        }
                        
                        // 2. Enroll in Subject
                        if ($user_id_to_enroll > 0) {
                            $enroll_sql = "INSERT IGNORE INTO user_subjects (user_id, subject_id) VALUES ($user_id_to_enroll, $subject_id)";
                            if ($conn->query($enroll_sql)) {
                                $count++;
                            }
                        }
                    }
                    $conn->commit();
                    $success = "นำเข้าและลงทะเบียนสำเร็จ $count รายการ";
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
                }
                fclose($handle);
            } else {
                $error = "ไม่สามารถเปิดไฟล์ได้";
            }
        }
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
                    <h3 class="panel-title">นำเข้ารายชื่อผู้เรียนและลงทะเบียนวิชา</h3>
                </div>
                <div class="panel-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <div class="well">
                        <h4>คำแนะนำในการนำเข้าข้อมูล</h4>
                        <p>ไฟล์ที่นำเข้าต้องเป็นรูปแบบ CSV (.csv) โดยมีลำดับคอลัมน์ดังนี้:</p>
                        <code>ชื่อ-นามสกุล, รหัสนักศึกษา, สถานะ</code>
                        <p><strong>ตัวอย่าง:</strong> สมชาย ใจดี, 65000001, นักเรียน</p>
                        <p style="margin-top: 10px;">
                            <a href="sample.csv" class="btn btn-default btn-sm">ดาวน์โหลดไฟล์ตัวอย่างใหม่</a>
                        </p>
                    </div>

                    <form action="index.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>เลือกรายวิชาที่ต้องการนำเข้า</label>
                            <select name="subject_id" class="form-control" required>
                                <option value="">--- เลือกรายวิชา ---</option>
                                <?php if ($subjects_list && $subjects_list->num_rows > 0): ?>
                                    <?php while($s = $subjects_list->fetch_assoc()): ?>
                                        <option value="<?php echo $s['id']; ?>">
                                            <?php echo htmlspecialchars($s['subject_code'] . " " . $s['subject_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option value="" disabled>ไม่พบรายวิชาที่ท่านดูแล</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>เลือกไฟล์ CSV</label>
                            <input type="file" name="import_file" class="form-control" accept=".csv" required>
                        </div>
                        <button type="submit" class="btn btn-primary" <?php echo ($subjects_list && $subjects_list->num_rows > 0) ? '' : 'disabled'; ?>>เริ่มนำเข้าข้อมูล</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
