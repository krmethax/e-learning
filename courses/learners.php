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

// Fetch enrolled students
$enroll_stmt = $conn->prepare("
    SELECT u.full_name, u.email, u.profile_image, u.last_access
    FROM users u
    JOIN user_subjects us ON u.id = us.user_id
    WHERE us.subject_id = ?
    ORDER BY u.full_name ASC
");
$enroll_stmt->bind_param("i", $subject_id);
$enroll_stmt->execute();
$learners = $enroll_stmt->get_result();

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
    .learner-img { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; margin-right: 10px; border: 1px solid #eee; }
    
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
                    <li class="active"><a href="learners.php?id=<?php echo $subject_id; ?>">ผู้เรียน</a></li>
                    <li><a href="attendance.php?id=<?php echo $subject_id; ?>">เช็คชื่อเข้าเรียน</a></li>
                </ul>
                
                <ul class="nav nav-tabs course-nav-tabs visible-xs">
                    <li><a href="course.php?id=<?php echo $subject_id; ?>">หน้าหลักวิชา</a></li>
                    <li class="dropdown active">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                            อื่นๆ <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="grades.php?id=<?php echo $subject_id; ?>">คะแนน</a></li>
                            <li class="active"><a href="learners.php?id=<?php echo $subject_id; ?>">ผู้เรียน</a></li>
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
                    <div class="row" style="margin-bottom: 25px;">
                        <div class="col-md-6">
                            <h3 style="margin: 0; font-weight: bold; color: #333;">รายชื่อผู้เรียน (<?php echo $learners->num_rows; ?> คน)</h3>
                        </div>
                        <div class="col-md-6 text-right">
                            <div class="input-group" style="max-width: 300px; display: inline-table;">
                                <input type="text" class="form-control" placeholder="ค้นหาชื่อผู้เรียน...">
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="button"><i class="fa fa-search"></i></button>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>อีเมล</th>
                                    <th>การเข้าใช้งานล่าสุด</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($user = $learners->fetch_assoc()): ?>
                                <tr>
                                    <td style="display: flex; align-items: center;">
                                        <?php 
                                            $u_img = !empty($user['profile_image']) ? $path . $user['profile_image'] : "https://ui-avatars.com/api/?name=" . urlencode($user['full_name']) . "&background=random";
                                        ?>
                                        <img src="<?php echo $u_img; ?>" class="learner-img" alt="User">
                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                    </td>
                                    <td class="text-muted"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="text-muted">
                                        <?php echo $user['last_access'] ? date('d/m/Y H:i', strtotime($user['last_access'])) : 'ยังไม่เคยเข้าใช้'; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if ($learners->num_rows == 0): ?>
                                    <tr><td colspan="3" class="text-center text-muted" style="padding: 40px;">ไม่มีผู้เรียนในวิชานี้</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
