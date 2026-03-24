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

// Fetch all settings
$res = $conn->query("SELECT * FROM site_settings");
$settings = [];
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">ตั้งค่าระบบ</h3>
                </div>
                <div class="panel-body">
                    <div class="row" style="margin-top: 20px;">
                        <!-- Left Column: Site Info -->
                        <div class="col-md-4">
                            <section style="margin-bottom: 30px;">
                                <h4 style="font-weight: 600; color: #333; margin-bottom: 15px;">ข้อมูลเว็บไซต์</h4>
                                <ul class="list-unstyled" style="line-height: 2;">
                                    <li><a href="edit_settings.php?section=general" class="text-primary">แก้ไขการตั้งค่าทั่วไป</a></li>
                                    <li style="margin-top: 10px;">
                                        <strong style="display: block; font-size: 13px; color: #777;">ชื่อเว็บไซต์</strong>
                                        <span><?php echo htmlspecialchars($settings['site_name'] ?? 'E-Learning Platform'); ?></span>
                                    </li>
                                    <li style="margin-top: 10px;">
                                        <strong style="display: block; font-size: 13px; color: #777;">อีเมลผู้ดูแลระบบ</strong>
                                        <span><?php echo htmlspecialchars($settings['site_email'] ?? 'admin@ubu.ac.th'); ?></span>
                                    </li>
                                    <li style="margin-top: 10px;">
                                        <strong style="display: block; font-size: 13px; color: #777;">เขตเวลาของระบบ</strong>
                                        <span><?php echo htmlspecialchars($settings['site_timezone'] ?? 'Asia/Bangkok'); ?></span>
                                    </li>
                                </ul>
                            </section>
                        </div>

                        <!-- Middle Column: Database Management -->
                        <div class="col-md-4">
                            <section style="margin-bottom: 30px;">
                                <h4 style="font-weight: 600; color: #333; margin-bottom: 15px;">ตั้งค่าข้อมูลระบบ</h4>
                                <ul class="list-unstyled" style="line-height: 2;">
                                    <li><a href="data_management.php" class="text-primary">จัดการข้อมูลคณะ สาขา และรายวิชา</a></li>
                                </ul>
                            </section>

                            <section style="margin-bottom: 30px;">
                                <h4 style="font-weight: 600; color: #333; margin-bottom: 15px;">การจัดการฐานข้อมูล</h4>
                                <ul class="list-unstyled" style="line-height: 2;">
                                    <li><a href="db_actions.php?action=export" class="text-primary">สำรองข้อมูล (Export SQL)</a></li>
                                    <li><a href="edit_settings.php?section=database" class="text-primary">คืนค่าข้อมูล (Import SQL)</a></li>
                                    <li style="margin-top: 15px;"><a href="#" onclick="confirmReset()" class="text-danger">ล้างข้อมูลและรีเซ็ตระบบทั้งหมด</a></li>
                                </ul>
                            </section>

                            <section style="margin-bottom: 30px;">
                                <h4 style="font-weight: 600; color: #333; margin-bottom: 15px;">รายงานระบบ</h4>
                                <ul class="list-unstyled" style="line-height: 2;">
                                    <li><a href="sessions_all.php" class="text-primary">ประวัติการเข้าใช้งานทั้งหมด</a></li>
                                    <li><a href="logs.php" class="text-primary">บันทึกประวัติการใช้งานระบบ (Logs)</a></li>
                                    <li><a href="#" class="text-primary">สถิติการลงทะเบียนรายวิชา</a></li>
                                </ul>
                            </section>
                        </div>

                        <!-- Right Column: Quick Links / Info -->
                        <div class="col-md-4">
                            <section style="margin-bottom: 30px; padding: 15px; background: #f9f9f9; border-radius: 4px; border: 1px solid #eee;">
                                <h4 style="font-weight: 600; color: #333; margin-top: 0; margin-bottom: 15px;">ข้อมูลเซิร์ฟเวอร์</h4>
                                <ul class="list-unstyled" style="line-height: 1.8; font-size: 13px;">
                                    <li><strong>PHP Version:</strong> <?php echo phpversion(); ?></li>
                                    <li><strong>MySQL Version:</strong> <?php echo $conn->server_info; ?></li>
                                    <li><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></li>
                                    <li><strong>Database Name:</strong> <?php echo $dbname; ?></li>
                                </ul>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmReset() {
    if (confirm("คุณแน่ใจหรือไม่ว่าต้องการล้างข้อมูลเว็บไซต์ทั้งหมด?\nการดำเนินการนี้จะลบข้อมูลทุกอย่างรวมถึงบัญชีแอดมินและบังคับให้ติดตั้งระบบใหม่!")) {
        window.location.href = "db_actions.php?action=reset";
    }
}
</script>

<?php include $path . 'includes/footer.php'; ?>
