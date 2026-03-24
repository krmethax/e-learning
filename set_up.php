<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$path = '';
require_once 'includes/db.php';

$message = '';
$setup_done = false;

// Check if admin user already exists
$check_admin = $conn->query("SELECT id FROM users LIMIT 1");
if ($check_admin && $check_admin->num_rows > 0) {
    $setup_done = true;
    $message = "ระบบถูกตั้งค่าเรียบร้อยแล้ว กรุณาลบไฟล์ set_up.php เพื่อความปลอดภัย";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$setup_done) {
    $admin_user = $conn->real_escape_string($_POST['username']);
    $admin_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $admin_name = $conn->real_escape_string($_POST['full_name']);

    // 1. Execute SQL Setup from file
    $sql_content = file_get_contents('sql/setup.sql');
    try {
        if ($conn->multi_query($sql_content)) {
            // Clear results from multi_query
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->next_result());

            // 2. Insert Admin User
            $stmt = $conn->prepare("INSERT INTO users (username, password, full_name) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $admin_user, $admin_pass, $admin_name);
            
            if ($stmt->execute()) {
                $message = "ตั้งค่าระบบและสร้างบัญชีผู้ดูแลระบบสำเร็จแล้ว!";
                $setup_done = true;
            } else {
                $message = "เกิดข้อผิดพลาดในการสร้างบัญชีผู้ดูแลระบบ: " . $conn->error;
            }
        } else {
            $message = "เกิดข้อผิดพลาดในการรัน SQL Setup: " . $conn->error;
        }
    } catch (mysqli_sql_exception $e) {
        $message = "เกิดข้อผิดพลาดทาง SQL: " . $e->getMessage();
    }
}

include 'includes/header.php';
include 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-6 col-md-offset-3" style="margin-top: 50px;">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title text-center">ตั้งค่าระบบเริ่มต้น (Initial Setup)</h3>
                </div>
                <div class="panel-body">
                    <?php if ($message): ?>
                        <div class="alert <?php echo $setup_done ? 'alert-success' : 'alert-danger'; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!$setup_done): ?>
                        <form action="set_up.php" method="POST">
                            <p class="text-muted">กรุณากำหนดข้อมูลสำหรับบัญชีผู้ดูแลระบบคนแรก:</p>
                            <div class="form-group">
                                <label>ชื่อผู้ใช้งาน (Admin Username)</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>รหัสผ่าน (Admin Password)</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>ชื่อ-นามสกุล (Full Name)</label>
                                <input type="text" name="full_name" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">ยืนยันการตั้งค่า</button>
                        </form>
                    <?php else: ?>
                        <div class="text-center">
                            <a href="login/index.php" class="btn btn-success">ไปที่หน้าเข้าสู่ระบบ</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
