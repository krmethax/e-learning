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

// Check enrollment status
$check = $conn->prepare("SELECT * FROM user_subjects WHERE user_id = ? AND subject_id = ?");
$check->bind_param("ii", $user_id, $subject_id);
$check->execute();
if ($check->get_result()->num_rows == 0 && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
    // If not enrolled and not admin, redirect to view page
    header("Location: view.php?id=" . $subject_id);
    exit();
}

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

// Fetch Course Structure from Database
$sections = [];
$res = $conn->query("SELECT * FROM course_sections WHERE subject_id = $subject_id ORDER BY sort_order ASC");
while ($row = $res->fetch_assoc()) {
    $section_id = $row['id'];
    $row['items'] = [];
    $item_res = $conn->query("SELECT * FROM course_items WHERE section_id = $section_id ORDER BY sort_order ASC");
    while ($item = $item_res->fetch_assoc()) {
        $row['items'][] = $item;
    }
    $sections[] = $row;
}

include $path . 'includes/header.php';
include $path . 'includes/navbar.php';
?>

<style>
    .course-header-banner {
        background: #fff;
        border-bottom: 1px solid #eee;
        padding: 30px 0;
        margin-top: -20px;
    }
    .course-main-content {
        padding: 40px 0;
        background: #fcfcfc;
        min-height: calc(100vh - 200px);
    }
    .panel-course {
        border: none;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        margin-bottom: 25px;
        border-radius: 8px;
        overflow: hidden;
    }
    .panel-course .panel-heading {
        background: #fff;
        border-bottom: 1px solid #f0f0f0;
        padding: 15px 25px;
    }
    .panel-course .panel-title {
        font-weight: bold;
        font-size: 18px;
        color: #333;
    }
    .list-group-item-course {
        border: none;
        border-bottom: 1px solid #f8f8f8;
        padding: 15px 25px;
        transition: all 0.2s;
        display: block;
        text-decoration: none !important;
    }
    .list-group-item-course:last-child {
        border-bottom: none;
    }
    .list-group-item-course:hover {
        background: #f1f8ff;
    }
    .list-group-item-course:hover .item-name {
        color: #337ab7;
        font-weight: 500;
    }
    .course-nav-tabs {
        margin-top: 20px;
        border-bottom: none;
    }
    .course-nav-tabs li a {
        border: none;
        color: #666;
        font-weight: 500;
        padding: 10px 20px;
        border-bottom: 3px solid transparent;
    }
    .course-nav-tabs li.active a {
        border: none;
        border-bottom: 3px solid #337ab7;
        color: #337ab7;
        background: transparent !important;
    }
    .course-nav-tabs li.dropdown.open a.dropdown-toggle,
    .course-nav-tabs li.dropdown.open a.dropdown-toggle:hover,
    .course-nav-tabs li.dropdown.open a.dropdown-toggle:focus {
        background: transparent !important;
        border: none;
        border-bottom: 3px solid #337ab7;
        color: #337ab7;
    }
    
    @media (max-width: 767px) {
        .course-header-banner { padding: 20px 0; margin-top: 0; text-align: center; }
        .course-header-banner .row { display: block !important; }
        .course-header-banner .pull-right { float: none !important; margin-bottom: 15px; }
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
                <div class="pull-right">
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="manage.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-primary btn-sm">
                            <i class="fa fa-edit"></i> จัดการเนื้อหา
                        </a>
                    <?php endif; ?>
                </div>
                <h1 style="margin: 0; font-weight: bold; color: #333; font-size: 20px;">
                    <span class="text-primary"><?php echo htmlspecialchars($subject['subject_code']); ?></span> 
                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                </h1>
                <p class="text-muted" style="margin-top: 8px; font-size: 16px;">
                    <?php echo htmlspecialchars($subject['faculty_name']); ?> / <?php echo htmlspecialchars($subject['branch_name']); ?>
                </p>
                
                <ul class="nav nav-tabs course-nav-tabs hidden-xs">
                    <li class="active"><a href="course.php?id=<?php echo $subject_id; ?>">หน้าหลักวิชา</a></li>
                    <li><a href="grades.php?id=<?php echo $subject_id; ?>">คะแนน</a></li>
                    <li><a href="learners.php?id=<?php echo $subject_id; ?>">ผู้เรียน</a></li>
                    <li><a href="attendance.php?id=<?php echo $subject_id; ?>">เช็คชื่อเข้าเรียน</a></li>
                </ul>
                
                <ul class="nav nav-tabs course-nav-tabs visible-xs">
                    <li class="active"><a href="course.php?id=<?php echo $subject_id; ?>">หน้าหลักวิชา</a></li>
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                            อื่นๆ <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="grades.php?id=<?php echo $subject_id; ?>">คะแนน</a></li>
                            <li><a href="learners.php?id=<?php echo $subject_id; ?>">ผู้เรียน</a></li>
                            <li><a href="attendance.php?id=<?php echo $subject_id; ?>">เช็คชื่อเข้าเรียน</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Course Content -->
<div class="course-main-content">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                
                <?php
                $icons = [
                    'news' => 'fa-bullhorn text-primary',
                    'link' => 'fa-external-link text-info',
                    'info' => 'fa-info-circle text-muted',
                    'file' => 'fa-file-pdf-o text-danger',
                    'video' => 'fa-play-circle text-primary',
                    'quiz' => 'fa-check-square-o text-success',
                    'assignment' => 'fa-tasks text-warning',
                    'forum' => 'fa-comments text-primary',
                    'choice' => 'fa-list-ul text-info'
                ];

                if (empty($sections)): ?>
                    <div class="text-center" style="padding: 100px 0;">
                        <i class="fa fa-folder-open-o fa-5x text-muted" style="margin-bottom: 20px;"></i>
                        <h3 class="text-muted">ยังไม่มีเนื้อหาในวิชานี้</h3>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <p style="margin-top: 15px;"><a href="manage.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-primary">คลิกเพื่อเพิ่มเนื้อหา</a></p>
                        <?php endif; ?>
                    </div>
                <?php else: 
                    foreach ($sections as $index => $section):
                    ?>
                        <div class="panel panel-default panel-course">
                            <div class="panel-heading">
                                <div class="row">
                                    <div class="col-xs-10">
                                        <h4 class="panel-title"><?php echo htmlspecialchars($section['title']); ?></h4>
                                    </div>
                                    <div class="col-xs-2 text-right">
                                        <a href="#section-<?php echo $section['id']; ?>" data-toggle="collapse" style="color: #bbb; font-size: 11px; text-decoration: none; text-transform: uppercase;">
                                            ย่อ <i class="fa fa-chevron-down"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div id="section-<?php echo $section['id']; ?>" class="panel-collapse collapse in">
                                <div class="panel-body" style="padding: 0;">
                                    <?php if (empty($section['items'])): ?>
                                        <div style="padding: 20px 25px; color: #999; font-style: italic; font-size: 14px;">ไม่มีกิจกรรมในส่วนนี้</div>
                                    <?php else: ?>
                                        <div class="list-group" style="margin-bottom: 0;">
                                            <?php foreach ($section['items'] as $it): ?>
                                                <a href="lesson.php?id=<?php echo $subject_id; ?>&item_id=<?php echo $it['id']; ?>" class="list-group-item list-group-item-course">
                                                    <div class="row" style="display: flex; align-items: center;">
                                                        <div class="col-xs-1 text-center" style="width: 45px;">
                                                            <i class="fa <?php echo $icons[$it['item_type']] ?? 'fa-circle-o'; ?> fa-lg"></i>
                                                        </div>
                                                        <div class="col-xs-10">
                                                            <span class="item-name" style="color: #444; font-size: 15px;"><?php echo htmlspecialchars($it['item_name']); ?></span>
                                                        </div>
                                                        <div class="col-xs-1 text-right">
                                                            <i class="fa fa-angle-right text-muted"></i>
                                                        </div>
                                                    </div>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
