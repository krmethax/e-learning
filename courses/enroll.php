<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$path = '../';
require_once $path . 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $path . "login/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$subject_id = isset($_REQUEST['subject_id']) ? (int)$_REQUEST['subject_id'] : 0;

if ($subject_id <= 0) {
    header("Location: index.php");
    exit();
}

// Fetch subject info
$stmt = $conn->prepare("SELECT * FROM subjects WHERE id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();

if (!$subject) {
    die("ไม่พบรายวิชานี้");
}

// Check if already enrolled
$check = $conn->prepare("SELECT * FROM user_subjects WHERE user_id = ? AND subject_id = ?");
$check->bind_param("ii", $user_id, $subject_id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    header("Location: " . $path . "my/index.php");
    exit();
}

// Check if within dates
$now = date('Y-m-d H:i:s');
if (!empty($subject['start_date']) && $now < $subject['start_date']) {
    die("รายวิชานี้จะเปิดให้ลงทะเบียนในวันที่ " . date('d/m/Y H:i', strtotime($subject['start_date'])));
}
if (!empty($subject['end_date']) && $now > $subject['end_date']) {
    die("รายวิชานี้ปิดรับลงทะเบียนแล้ว");
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $can_enroll = false;
    
    if ($subject['enrollment_type'] === 'open') {
        $can_enroll = true;
    } elseif ($subject['enrollment_type'] === 'password') {
        $entered_key = $_POST['enrollment_key'] ?? '';
        if ($entered_key === $subject['enrollment_key']) {
            $can_enroll = true;
        } else {
            $error = "รหัสผ่านสำหรับลงทะเบียน (Enrollment Key) ไม่ถูกต้อง";
        }
    }

    if ($can_enroll) {
        $stmt_enroll = $conn->prepare("INSERT IGNORE INTO user_subjects (user_id, subject_id) VALUES (?, ?)");
        $stmt_enroll->bind_param("ii", $user_id, $subject_id);
        if ($stmt_enroll->execute()) {
            logEvent($conn, 'Enroll Subject', "User enrolled in subject: " . $subject['subject_code']);
            header("Location: " . $path . "my/index.php");
            exit();
        } else {
            $error = "เกิดข้อผิดพลาดในการลงทะเบียน: " . $conn->error;
        }
    }
}

// If open and GET request, just enroll directly (to keep it smooth)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $subject['enrollment_type'] === 'open') {
    $stmt_enroll = $conn->prepare("INSERT IGNORE INTO user_subjects (user_id, subject_id) VALUES (?, ?)");
    $stmt_enroll->bind_param("ii", $user_id, $subject_id);
    if ($stmt_enroll->execute()) {
        logEvent($conn, 'Enroll Subject', "User enrolled in subject: " . $subject['subject_code']);
        header("Location: " . $path . "my/index.php");
        exit();
    }
}

include $path . 'includes/header.php';
include $path . 'includes/navbar.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-6 col-md-offset-3" style="margin-top: 50px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">ยืนยันการลงทะเบียน</h3>
                </div>
                <div class="panel-body">
                    <div class="text-center" style="margin-bottom: 20px;">
                        <?php if (!empty($subject['cover_image'])): ?>
                            <img src="<?php echo $path . $subject['cover_image']; ?>" class="img-responsive img-rounded" style="max-height: 200px; margin: 0 auto 15px;">
                        <?php endif; ?>
                        <h4><?php echo htmlspecialchars($subject['subject_code'] . ' ' . $subject['subject_name']); ?></h4>
                        <p class="text-muted"><?php echo htmlspecialchars($subject['subject_name_en']); ?></p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form action="enroll.php" method="POST">
                        <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
                        
                        <?php if ($subject['enrollment_type'] === 'password'): ?>
                            <div class="form-group">
                                <label>กรุณาระบุรหัสผ่านสำหรับการลงทะเบียน (Enrollment Key)</label>
                                <input type="password" name="enrollment_key" class="form-control" placeholder="Enrollment Key" required autofocus>
                            </div>
                        <?php else: ?>
                            <p class="text-center">ท่านแน่ใจหรือไม่ที่จะลงทะเบียนในวิชานี้?</p>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-xs-6">
                                <button type="submit" class="btn btn-primary btn-block">ยืนยันการลงทะเบียน</button>
                            </div>
                            <div class="col-xs-6">
                                <a href="view.php?id=<?php echo $subject_id; ?>" class="btn btn-default btn-block">ยกเลิก</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
