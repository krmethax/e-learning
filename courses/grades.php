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

// Fetch real grades for this user in this subject
$grades_query = "
    SELECT 
        i.item_name, 
        i.item_type,
        i.weight, 
        i.max_score, 
        g.score, 
        g.feedback
    FROM course_items i
    JOIN course_sections s ON i.section_id = s.id
    LEFT JOIN course_grades g ON i.id = g.item_id AND g.user_id = ?
    WHERE s.subject_id = ? AND (i.item_type = 'quiz' OR i.item_type = 'assignment')
    ORDER BY s.sort_order ASC, i.sort_order ASC
";
$g_stmt = $conn->prepare($grades_query);
$g_stmt->bind_param("ii", $user_id, $subject_id);
$g_stmt->execute();
$grades_result = $g_stmt->get_result();

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
                    <li class="active"><a href="grades.php?id=<?php echo $subject_id; ?>">คะแนน</a></li>
                    <li><a href="learners.php?id=<?php echo $subject_id; ?>">ผู้เรียน</a></li>
                    <li><a href="attendance.php?id=<?php echo $subject_id; ?>">เช็คชื่อเข้าเรียน</a></li>
                </ul>
                
                <ul class="nav nav-tabs course-nav-tabs visible-xs">
                    <li><a href="course.php?id=<?php echo $subject_id; ?>">หน้าหลักวิชา</a></li>
                    <li class="dropdown active">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                            อื่นๆ <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="active"><a href="grades.php?id=<?php echo $subject_id; ?>">คะแนน</a></li>
                            <li><a href="learners.php?id=<?php echo $subject_id; ?>">ผู้เรียน</a></li>
                            <li><a href="attendance.php?id=<?php echo $subject_id; ?>">เช็คชื่อเข้าเรียน</a></li>
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
                    <h3 style="margin-top: 0; margin-bottom: 25px; font-weight: bold; color: #333;">รายงานคะแนนสะสม</h3>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th>ชิ้นงาน</th>
                                    <th class="text-center">Calculated weight</th>
                                    <th class="text-center">คะแนนที่ได้</th>
                                    <th class="text-center">Range</th>
                                    <th class="text-center">Percentage</th>
                                    <th>Feedback</th>
                                    <th class="text-center">Contribution to course total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_contribution = 0;
                                if ($grades_result->num_rows > 0): 
                                    while($row = $grades_result->fetch_assoc()): 
                                        $has_grade = ($row['score'] !== null);
                                        $percentage = ($has_grade && $row['max_score'] > 0) ? ($row['score'] / $row['max_score']) * 100 : 0;
                                        $contribution = ($has_grade && $row['max_score'] > 0) ? ($row['score'] / $row['max_score']) * $row['weight'] : 0;
                                        $total_contribution += $contribution;
                                        $icon = ($row['item_type'] == 'quiz') ? 'fa-check-square-o text-success' : 'fa-tasks text-warning';
                                ?>
                                    <tr>
                                        <td><i class="fa <?php echo $icon; ?>"></i> <?php echo htmlspecialchars($row['item_name']); ?></td>
                                        <td class="text-center"><?php echo number_format($row['weight'], 1); ?> %</td>
                                        <td class="text-center">
                                            <?php if ($has_grade): ?>
                                                <?php echo number_format($row['score'], 2); ?>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size: 12px; font-style: italic;">รอตรวจ</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">0–<?php echo number_format($row['max_score'], 0); ?></td>
                                        <td class="text-center">
                                            <?php if ($has_grade): ?>
                                                <?php echo number_format($percentage, 2) . ' %'; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted"><?php echo htmlspecialchars($row['feedback'] ?? '-'); ?></td>
                                        <td class="text-center">
                                            <?php if ($has_grade): ?>
                                                <?php echo number_format($contribution, 2) . ' %'; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center text-muted" style="padding: 30px;">ยังไม่มีรายการคะแนนในวิชานี้</td></tr>
                                <?php endif; ?>
                                
                                <tr style="background: #fcfcfc; font-weight: bold;">
                                    <td colspan="6" class="text-right">Course total</td>
                                    <td class="text-center text-primary" style="font-size: 16px;"><?php echo number_format($total_contribution, 2); ?> %</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-info" style="margin-top: 20px;">
                        <i class="fa fa-info-circle"></i> ข้อมูลคะแนนจะอัปเดตหลังจากผู้สอนทำการตรวจและประกาศคะแนนในระบบเรียบร้อยแล้ว
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
