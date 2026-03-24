<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$path = '../';
require_once $path . 'includes/db.php';

$subject_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($subject_id <= 0) {
    header("Location: " . $path . "my/index.php");
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $path . "login/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch subject info
$stmt = $conn->prepare("
    SELECT s.*, b.branch_name, f.faculty_name 
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

// Fetch real attendance
$att_stmt = $conn->prepare("
    SELECT * FROM course_attendance 
    WHERE subject_id = ? AND user_id = ? 
    ORDER BY check_date DESC
");
$att_stmt->bind_param("ii", $subject_id, $user_id);
$att_stmt->execute();
$attendance_result = $att_stmt->get_result();

// Calculate stats
$total_sessions = $attendance_result->num_rows;
$present = 0;
$late = 0;
$absent = 0;

$history = [];
while($row = $attendance_result->fetch_assoc()) {
    $history[] = $row;
    if ($row['status'] == 'present') $present++;
    elseif ($row['status'] == 'late') $late++;
    elseif ($row['status'] == 'absent') $absent++;
}

$percentage = ($total_sessions > 0) ? (($present + ($late * 0.5)) / $total_sessions) * 100 : 0;

include $path . 'includes/header.php';
include $path . 'includes/navbar.php';
?>

<style>
    .course-header-banner { background: #fff; border-bottom: 1px solid #eee; padding: 30px 0; margin-top: -20px; }
    .course-main-content { padding: 40px 0; background: #fcfcfc; min-height: calc(100vh - 200px); }
    .course-nav-tabs { margin-top: 20px; border-bottom: none; }
    .course-nav-tabs li a { border: none; color: #666; font-weight: 500; padding: 10px 20px; border-bottom: 3px solid transparent; }
    .course-nav-tabs li.active a { border: none; border-bottom: 3px solid #337ab7; color: #337ab7; background: transparent !important; }
    .course-nav-tabs li.dropdown.open a.dropdown-toggle,
    .course-nav-tabs li.dropdown.open a.dropdown-toggle:hover,
    .course-nav-tabs li.dropdown.open a.dropdown-toggle:focus {
        background: transparent !important;
        border: none;
        border-bottom: 3px solid #337ab7;
        color: #337ab7;
    }
    .content-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 30px; }
    .attendance-stat { text-align: center; padding: 20px; border-radius: 8px; background: #f9f9f9; border: 1px solid #eee; }
    .attendance-stat h4 { margin: 0; color: #777; font-size: 14px; text-transform: uppercase; }
    .attendance-stat .count { font-size: 32px; font-weight: bold; color: #333; margin: 10px 0; }
    
    @media (max-width: 767px) {
        .course-header-banner { padding: 20px 0; margin-top: 0; text-align: center; }
        .course-header-banner .row { display: block !important; }
        .course-header-banner h1 { margin-top: 15px !important; }
        .course-nav-tabs li a { padding: 10px 12px !important; font-size: 13px; }
        .course-main-content { padding: 20px 0; }
        .container { width: 100% !important; padding-left: 15px !important; padding-right: 15px !important; }
    }
</style>

<!-- Course Header -->
<div class="course-header-banner">
    <div class="container">
        <div class="row" style="display: flex; align-items: center; flex-wrap: wrap;">
            <div class="col-md-2 col-sm-3 text-center">
                <img src="<?php echo $path . htmlspecialchars($subject['cover_image']); ?>" class="img-rounded" style="width: 100%; max-width: 140px; height: auto; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            </div>
            <div class="col-md-10 col-sm-9">
                <h1 style="margin: 0; font-weight: bold; color: #333; font-size: 20px;">
                    <span class="text-primary"><?php echo htmlspecialchars($subject['subject_code']); ?></span>
                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                </h1>                <p class="text-muted" style="margin-top: 8px; font-size: 16px;">
                    <?php echo htmlspecialchars($subject['faculty_name']); ?> / <?php echo htmlspecialchars($subject['branch_name']); ?>
                </p>
                <ul class="nav nav-tabs course-nav-tabs hidden-xs">
                    <li><a href="course.php?id=<?php echo $subject_id; ?>">หน้าหลักวิชา</a></li>
                    <li><a href="grades.php?id=<?php echo $subject_id; ?>">คะแนน</a></li>
                    <li><a href="learners.php?id=<?php echo $subject_id; ?>">ผู้เรียน</a></li>
                    <li class="active"><a href="attendance.php?id=<?php echo $subject_id; ?>">เช็คชื่อเข้าเรียน</a></li>
                </ul>
                
                <ul class="nav nav-tabs course-nav-tabs visible-xs">
                    <li><a href="course.php?id=<?php echo $subject_id; ?>">หน้าหลักวิชา</a></li>
                    <li class="dropdown active">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                            อื่นๆ <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="grades.php?id=<?php echo $subject_id; ?>">คะแนน</a></li>
                            <li><a href="learners.php?id=<?php echo $subject_id; ?>">ผู้เรียน</a></li>
                            <li class="active"><a href="attendance.php?id=<?php echo $subject_id; ?>">เช็คชื่อเข้าเรียน</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="course-main-content">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="content-card">
                    <h3 style="margin-top: 0; margin-bottom: 25px; font-weight: bold; color: #333;">ประวัติการเข้าเรียน</h3>

                    <?php if ($total_sessions > 0): ?>
                        <!-- Stats Row -->
                        <div class="row" style="margin-bottom: 30px;">
                            <div class="col-md-3 col-sm-6">
                                <div class="attendance-stat">
                                    <h4>มาเรียน</h4>
                                    <div class="count text-success"><?php echo $present; ?></div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="attendance-stat">
                                    <h4>สาย</h4>
                                    <div class="count text-warning"><?php echo $late; ?></div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="attendance-stat">
                                    <h4>ขาด</h4>
                                    <div class="count text-danger"><?php echo $absent; ?></div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="attendance-stat">
                                    <h4>คิดเป็นร้อยละ</h4>
                                    <div class="count text-primary"><?php echo number_format($percentage, 2); ?>%</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr style="background: #f8f9fa;">
                                        <th>วันที่</th>
                                        <th class="text-center">สถานะ</th>
                                        <th>หมายเหตุ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $att): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($att['check_date'])); ?></td>
                                        <td class="text-center">
                                            <?php if ($att['status'] == 'present'): ?>
                                                <span class="label label-success">มาเรียน</span>
                                            <?php elseif ($att['status'] == 'late'): ?>
                                                <span class="label label-warning">สาย</span>
                                            <?php else: ?>
                                                <span class="label label-danger">ขาด</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted"><?php echo htmlspecialchars($att['remarks'] ?? '-'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center" style="padding: 60px 0;">
                            <i class="fa fa-calendar-check-o fa-5x text-muted" style="margin-bottom: 20px;"></i>
                            <h4 class="text-muted">ยังไม่มีข้อมูลการเข้าเรียนในวิชานี้</h4>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
