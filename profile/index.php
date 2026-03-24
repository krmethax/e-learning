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

// Fetch latest user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch counts for various sections
$blog_count = $conn->query("SELECT COUNT(*) as total FROM user_blogs WHERE user_id = $user_id")->fetch_assoc()['total'];
$forum_count = $conn->query("SELECT COUNT(*) as total FROM forum_discussions WHERE user_id = $user_id")->fetch_assoc()['total'];
$plan_count = $conn->query("SELECT COUNT(*) as total FROM learning_plans WHERE user_id = $user_id")->fetch_assoc()['total'];
$session_count = $conn->query("SELECT COUNT(*) as total FROM browser_sessions WHERE user_id = $user_id")->fetch_assoc()['total'];

// Fetch enrolled subjects
$stmt_subs = $conn->prepare("
    SELECT s.subject_name 
    FROM subjects s
    JOIN user_subjects us ON s.id = us.subject_id
    WHERE us.user_id = ?
");
$stmt_subs->bind_param("i", $user_id);
$stmt_subs->execute();
$res_subs = $stmt_subs->get_result();
$subjects = [];
while($s = $res_subs->fetch_assoc()) {
    $subjects[] = $s['subject_name'];
}

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px; margin-bottom: 40px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                </div>
                <div class="panel-body">

            <div class="row" style="margin-top: 20px;">
                <!-- Left Column -->
                <div class="col-md-4">
                    <section style="margin-bottom: 30px;">
                        <h4 style="font-weight: 600; color: #333; margin-bottom: 15px;">รายละเอียดผู้ใช้งาน</h4>
                        <ul class="list-unstyled" style="line-height: 2;">
                            <li><a href="edit.php" class="text-primary">แก้ไขข้อมูลส่วนตัว</a></li>
                            <li style="margin-top: 10px;">
                                <strong style="display: block; font-size: 13px; color: #777;">อีเมล</strong>
                                <span><?php echo htmlspecialchars($user['email'] ?: $user['username'].'@ubu.ac.th'); ?></span>
                                <small class="text-muted" style="display: block; font-size: 11px;">(สมาชิกรายวิชาเท่านั้นที่เห็น)</small>
                            </li>
                            <li style="margin-top: 10px;">
                                <strong style="display: block; font-size: 13px; color: #777;">โซนเวลา</strong>
                                <span><?php echo htmlspecialchars($user['timezone'] ?: 'Asia/Bangkok'); ?></span>
                            </li>
                        </ul>
                    </section>
                </div>

                <!-- Middle Column -->
                <div class="col-md-4">
                    <section style="margin-bottom: 30px;">
                        <h4 style="font-weight: 600; color: #333; margin-bottom: 15px;">รายละเอียดของรายวิชา</h4>
                        <ul class="list-unstyled" style="line-height: 2;">
                            <?php if (!empty($subjects)): ?>
                                <?php foreach($subjects as $sub_name): ?>
                                    <li><a href="#" class="text-primary"><?php echo htmlspecialchars($sub_name); ?></a></li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="text-muted">ยังไม่มีรายวิชาที่ลงทะเบียน</li>
                            <?php endif; ?>
                        </ul>
                    </section>

                    <section style="margin-bottom: 30px;">
                        <h4 style="font-weight: 600; color: #333; margin-bottom: 15px;">ทั่วไป</h4>
                        <ul class="list-unstyled" style="line-height: 2;">
                            <li><a href="#" class="text-primary">บทความบล็อก (<?php echo $blog_count; ?>)</a></li>
                            <li><a href="#" class="text-primary">Forum discussions (<?php echo $forum_count; ?>)</a></li>
                            <li><a href="#" class="text-primary">Learning plans (<?php echo $plan_count; ?>)</a></li>
                        </ul>
                    </section>
                </div>

                <!-- Right Column -->
                <div class="col-md-4">
                    <section style="margin-bottom: 30px;">
                        <h4 style="font-weight: 600; color: #333; margin-bottom: 15px;">รายงาน</h4>
                        <ul class="list-unstyled" style="line-height: 2;">
                            <li><a href="sessions.php" class="text-primary">Browser sessions (<?php echo $session_count; ?>)</a></li>
                            <li><a href="<?php echo $path; ?>my/index.php" class="text-primary">ภาพรวมเกรด</a></li>
                        </ul>
                    </section>

                    <section style="margin-bottom: 30px;">
                        <h4 style="font-weight: 600; color: #333; margin-bottom: 15px;">กิจกรรมการเข้าสู่ระบบ</h4>
                        <ul class="list-unstyled" style="line-height: 2;">
                            <li>
                                <strong style="display: block; font-size: 13px; color: #777;">ครั้งแรกที่เข้ามายังเว็บไซต์</strong>
                                <span><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></span>
                            </li>
                            <li style="margin-top: 10px;">
                                <strong style="display: block; font-size: 13px; color: #777;">เข้ามายังเว็บไซต์ครั้งสุดท้าย เมื่อ</strong>
                                <span>
                                    <?php 
                                        if ($user['last_access']) {
                                            echo date('d/m/Y H:i', strtotime($user['last_access']));
                                        } else {
                                            echo "เพิ่งเข้าสู่ระบบครั้งแรก";
                                        }
                                    ?>
                                </span>
                            </li>
                        </ul>
                    </section>
                </div>
            </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
