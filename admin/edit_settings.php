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

$section = $_GET['section'] ?? 'general';
$message = '';

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $conn->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $value, $key);
        $stmt->execute();
    }
    $message = "บันทึกการตั้งค่าระบบเรียบร้อยแล้ว";
}

// Handle DB Import
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['import_db'])) {
    if (isset($_FILES['db_file']) && $_FILES['db_file']['error'] == 0) {
        $sql_content = file_get_contents($_FILES['db_file']['tmp_name']);
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        if ($conn->multi_query($sql_content)) {
            do { if ($res = $conn->store_result()) $res->free(); } while ($conn->next_result());
            $message = "นำเข้าข้อมูลฐานข้อมูลเรียบร้อยแล้ว";
        } else {
            $message = "เกิดข้อผิดพลาดในการนำเข้าข้อมูล: " . $conn->error;
        }
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    }
}

// Fetch settings
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
        <div class="col-md-8 col-md-offset-2" style="margin-top: 20px; margin-bottom: 40px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <?php echo $section === 'general' ? 'แก้ไขการตั้งค่าทั่วไป' : 'จัดการฐานข้อมูล (Import)'; ?>
                    </h3>
                </div>
                <div class="panel-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <?php if ($section === 'general'): ?>
                        <form action="edit_settings.php?section=general" method="POST" class="form-horizontal">
                            <div class="form-group">
                                <label class="col-sm-4 control-label">ชื่อเว็บไซต์</label>
                                <div class="col-sm-8">
                                    <input type="text" name="settings[site_name]" class="form-control" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-4 control-label">อีเมลเว็บไซต์</label>
                                <div class="col-sm-8">
                                    <input type="email" name="settings[site_email]" class="form-control" value="<?php echo htmlspecialchars($settings['site_email'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="form-group" style="margin-top: 30px;">
                                <div class="col-sm-offset-4 col-sm-8">
                                    <button type="submit" name="update_settings" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                                    <a href="settings.php" class="btn btn-default">ยกเลิก</a>
                                </div>
                            </div>
                        </form>

                    <?php elseif ($section === 'database'): ?>
                        <form action="edit_settings.php?section=database" method="POST" enctype="multipart/form-data" class="form-horizontal">
                            <div class="alert alert-warning">
                                <span class="glyphicon glyphicon-warning-sign"></span> 
                                <strong>คำเตือน:</strong> การนำเข้าไฟล์ SQL จะทำการเขียนทับข้อมูลปัจจุบันทั้งหมดในฐานข้อมูล
                            </div>
                            
                            <div class="form-group">
                                <label class="col-sm-4 control-label">เลือกไฟล์ SQL (.sql)</label>
                                <div class="col-sm-8">
                                    <input type="file" name="db_file" class="form-control" accept=".sql" required>
                                </div>
                            </div>

                            <div class="form-group" style="margin-top: 30px;">
                                <div class="col-sm-offset-4 col-sm-8">
                                    <button type="submit" name="import_db" class="btn btn-warning">เริ่มการนำเข้าข้อมูล</button>
                                    <a href="settings.php" class="btn btn-default">ยกเลิก</a>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>