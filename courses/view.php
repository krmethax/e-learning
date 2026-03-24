<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$path = '../';
require_once $path . 'includes/db.php';

$subject_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($subject_id <= 0) {
    header("Location: index.php");
    exit();
}

// Fetch subject info
$stmt = $conn->prepare("
    SELECT s.*, b.branch_name, f.faculty_name, f.id as f_id, b.id as b_id
    FROM subjects s 
    JOIN branches b ON s.branch_id = b.id 
    JOIN faculties f ON b.faculty_id = f.id 
    WHERE s.id = ?
");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();

if (!$subject) {
    die("ไม่พบรายวิชานี้");
}

// Fetch instructors
$inst_stmt = $conn->prepare("
    SELECT i.instructor_name 
    FROM instructors i
    JOIN subject_instructors si ON i.id = si.instructor_id
    WHERE si.subject_id = ?
");
$inst_stmt->bind_param("i", $subject_id);
$inst_stmt->execute();
$inst_result = $inst_stmt->get_result();
$instructors = [];
while($inst_row = $inst_result->fetch_assoc()) {
    $instructors[] = $inst_row['instructor_name'];
}
$instructor_text = !empty($instructors) ? implode(', ', $instructors) : 'ยังไม่ระบุผู้สอน';

// Check enrollment status
$is_enrolled = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $check = $conn->prepare("SELECT * FROM user_subjects WHERE user_id = ? AND subject_id = ?");
    $check->bind_param("ii", $user_id, $subject_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $is_enrolled = true;
    }
}

// Check open/close status
$now = date('Y-m-d H:i:s');
$is_open = true;
$lock_reason = "";
if (!empty($subject['start_date']) && $now < $subject['start_date']) {
    $is_open = false;
    $lock_reason = "วิชานี้จะเปิดในวันที่ " . date('d/m/Y H:i', strtotime($subject['start_date']));
}
if (!empty($subject['end_date']) && $now > $subject['end_date']) {
    $is_open = false;
    $lock_reason = "วิชานี้ปิดรับลงทะเบียนแล้ว";
}

include $path . 'includes/header.php';
include $path . 'includes/navbar.php';
?>

<div class="container">
    <div class="row" style="margin-top: 20px;">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-body">
                    <!-- 1. Cover Image -->
                    <div class="text-center" style="margin-bottom: 25px;">
                        <?php 
                            $cover = !empty($subject['cover_image']) ? $path . $subject['cover_image'] : "https://via.placeholder.com/260x160?text=No+Image";
                        ?>
                        <img src="<?php echo $cover; ?>" class="img-responsive img-rounded" style="width: 260px; height: 160px; object-fit: cover; margin: 0 auto; display: block;">
                    </div>

                    <!-- 2. Header Info -->
                    <div class="text-center">
                        <h2 style="margin-top: 0; color: #337ab7; font-weight: 500;">
                            <?php echo htmlspecialchars($subject['subject_code']); ?> 
                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                        </h2>
                        <h4 class="text-muted" style="font-weight: 300;"><?php echo htmlspecialchars($subject['subject_name_en'] ?? ''); ?></h4>
                        
                        <div style="margin: 20px 0;">
                            <span class="label label-info" style="font-size: 14px; padding: 5px 10px;"><?php echo htmlspecialchars($subject['faculty_name']); ?></span>
                            <span class="label label-default" style="font-size: 14px; padding: 5px 10px;"><?php echo htmlspecialchars($subject['branch_name']); ?></span>
                            <span class="label label-primary" style="font-size: 14px; padding: 5px 10px;">หน่วยกิต: <?php echo htmlspecialchars($subject['credits'] ?? '-'); ?></span>
                        </div>

                        <p style="font-size: 16px;"><strong>ผู้สอน:</strong> <?php echo htmlspecialchars($instructor_text); ?></p>
                    </div>

                    <hr>

                    <!-- 3. Enrollment Button -->
                    <div class="row" style="margin-bottom: 30px;">
                        <div class="col-md-4 col-md-offset-4">
                            <?php if ($is_enrolled): ?>
                                <a href="<?php echo $path; ?>my/index.php" class="btn btn-primary btn-block">เข้าสู่บทเรียน</a>
                            <?php elseif (isset($_SESSION['user_id'])): ?>
                                <?php if ($is_open): ?>
                                    <a href="enroll.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-success btn-block">ลงทะเบียน</a>
                                <?php else: ?>
                                    <button class="btn btn-default btn-block" disabled><?php echo $lock_reason; ?></button>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="<?php echo $path; ?>login/index.php" class="btn btn-default btn-block">เข้าสู่ระบบเพื่อลงทะเบียน</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 4. Descriptions -->
                    <div class="well" style="background-color: #fff; border: none; box-shadow: none;">
                        <h4 style="color: #337ab7; border-bottom: 2px solid #eee; padding-bottom: 10px;">คำอธิบายรายวิชา (Thai)</h4>
                        <p style="text-indent: 30px; line-height: 1.6; font-size: 15px;"><?php echo !empty($subject['description_th']) ? nl2br(htmlspecialchars($subject['description_th'])) : 'ไม่มีข้อมูล'; ?></p>
                        
                        <br>
                        <h4 style="color: #337ab7; border-bottom: 2px solid #eee; padding-bottom: 10px;">Course Description (English)</h4>
                        <p style="text-indent: 30px; line-height: 1.6; font-size: 15px; font-style: italic;"><?php echo !empty($subject['description_en']) ? nl2br(htmlspecialchars($subject['description_en'])) : 'ไม่มีข้อมูล'; ?></p>
                    </div>

                    <?php if (!$is_open && $lock_reason): ?>
                        <div class="alert alert-warning text-center" style="margin-top: 20px;">
                            <i class="fa fa-info-circle"></i> <?php echo $lock_reason; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
