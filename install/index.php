<?php
session_start();

$config_file = '../includes/config.php';

// If already installed, don't allow re-install unless a specific flag is set
if (file_exists($config_file) && !isset($_GET['force'])) {
    // Check if we actually have users. If not, we might be in a half-installed state
    require_once '../includes/db.php';
    $check_users = $conn->query("SELECT id FROM users LIMIT 1");
    if ($check_users && $check_users->num_rows > 0) {
        header("Location: ../index.php");
        exit;
    }
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1) {
        // Step 1: Database Connection
        $db_host = $_POST['db_host'];
        $db_user = $_POST['db_user'];
        $db_pass = $_POST['db_pass'];
        $db_name = $_POST['db_name'];

        // Try connection without database first (to create it if missing)
        try {
            $conn = @new mysqli($db_host, $db_user, $db_pass);
            
            if ($conn->connect_error) {
                $error = "การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error;
            } else {
                // Create database if not exists
                if (!$conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8 COLLATE utf8_general_ci")) {
                    $error = "ไม่สามารถสร้างฐานข้อมูลได้: " . $conn->error;
                } else {
                    $conn->select_db($db_name);
                    
                    // Read and execute setup.sql
                    $sql_content = file_get_contents('../sql/setup.sql');
                    // Remove CREATE DATABASE and USE statements to use the database name provided by user
                    $sql_content = preg_replace('/CREATE DATABASE IF NOT EXISTS `?elearning_db`?[^;]*;/i', '', $sql_content);
                    $sql_content = preg_replace('/USE `?elearning_db`?;/i', '', $sql_content);
                    
                    if ($conn->multi_query($sql_content)) {
                        do {
                            if ($result = $conn->store_result()) { $result->free(); }
                        } while ($conn->next_result());
                        
                        // Save to session for next step
                        $_SESSION['install_db'] = [
                            'host' => $db_host,
                            'user' => $db_user,
                            'pass' => $db_pass,
                            'name' => $db_name
                        ];
                        header("Location: index.php?step=2");
                        exit;
                    } else {
                        $error = "ไม่สามารถรัน SQL Setup ได้: " . $conn->error;
                    }
                }
                $conn->close();
            }
        } catch (mysqli_sql_exception $e) {
            $error = "การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage();
        }
    } elseif ($step === 2) {
        // Step 2: Admin Account
        if (!isset($_SESSION['install_db'])) {
            header("Location: index.php?step=1");
            exit;
        }

        try {
            $db = $_SESSION['install_db'];
            $conn = new mysqli($db['host'], $db['user'], $db['pass'], $db['name']);
            
            $admin_user = $conn->real_escape_string($_POST['admin_user']);
            $admin_pass = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);
            $admin_fullname = $conn->real_escape_string($_POST['admin_fullname']);
            $admin_email = $conn->real_escape_string($_POST['admin_email']);
            $site_name = $conn->real_escape_string($_POST['site_name']);

            // Check if admin exists
            $conn->query("DELETE FROM users WHERE username = '$admin_user'");
            
            $sql = "INSERT INTO users (username, password, full_name, email, role) VALUES ('$admin_user', '$admin_pass', '$admin_fullname', '$admin_email', 'admin')";
            
            if ($conn->query($sql)) {
                // Update Site Name in site_settings if table exists
                $conn->query("UPDATE site_settings SET setting_value = '$site_name' WHERE setting_key = 'site_name'");
                $conn->query("UPDATE site_settings SET setting_value = '$admin_email' WHERE setting_key = 'site_email'");

                // Generate config.php
                $config_content = "<?php\n";
                $config_content .= "define('DB_HOST', '" . addslashes($db['host']) . "');\n";
                $config_content .= "define('DB_USER', '" . addslashes($db['user']) . "');\n";
                $config_content .= "define('DB_PASS', '" . addslashes($db['pass']) . "');\n";
                $config_content .= "define('DB_NAME', '" . addslashes($db['name']) . "');\n";
                $config_content .= "?>";

                if (file_put_contents($config_file, $config_content)) {
                    $success = "ติดตั้งระบบเรียบร้อยแล้ว!";
                    session_destroy();
                    $step = 3;
                } else {
                    $error = "ไม่สามารถเขียนไฟล์ includes/config.php ได้ กรุณาตรวจสอบสิทธิ์ (Permission)";
                }
            } else {
                $error = "ไม่สามารถสร้างบัญชีผู้ดูแลระบบได้: " . $conn->error;
            }
            $conn->close();
        } catch (mysqli_sql_exception $e) {
            $error = "การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>E-Learning Installation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <style>
        body { background: #f4f7f6; padding-top: 50px; }
        .install-box { background: #fff; padding: 30px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .step-indicator { margin-bottom: 30px; }
        .step { display: inline-block; width: 30px; height: 30px; line-height: 30px; border-radius: 50%; background: #ddd; text-align: center; margin-right: 10px; font-weight: bold; }
        .step.active { background: #337ab7; color: #fff; }
    </style>
</head>
<body>

<div class="container">
    <div class="row">
        <div class="col-md-6 col-md-offset-3">
            <div class="install-box">
                <h2 class="text-center" style="margin-bottom: 30px;">ติดตั้งระบบ E-Learning</h2>
                
                <div class="step-indicator text-center">
                    <span class="step <?php echo $step === 1 ? 'active' : ''; ?>">1</span>
                    <span class="step <?php echo $step === 2 ? 'active' : ''; ?>">2</span>
                    <span class="step <?php echo $step === 3 ? 'active' : ''; ?>">3</span>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($step === 1): ?>
                    <form method="POST">
                        <h4>ตั้งค่าฐานข้อมูล</h4>
                        <div class="form-group">
                            <label>Host</label>
                            <input type="text" name="db_host" class="form-control" value="<?php echo getenv('DB_HOST') ?: 'localhost'; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="db_user" class="form-control" value="<?php echo getenv('DB_USER') ?: 'root'; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="db_pass" class="form-control" value="<?php echo getenv('DB_PASS') ?: ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Database Name</label>
                            <input type="text" name="db_name" class="form-control" value="<?php echo getenv('DB_NAME') ?: 'elearning_db'; ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">ถัดไป (ตรวจสอบการเชื่อมต่อ)</button>
                    </form>

                <?php elseif ($step === 2): ?>
                    <form method="POST">
                        <h4>ข้อมูลผู้ดูแลระบบ & เว็บไซต์</h4>
                        <div class="form-group">
                            <label>ชื่อเว็บไซต์</label>
                            <input type="text" name="site_name" class="form-control" value="E-Learning Platform" required>
                        </div>
                        <hr>
                        <div class="form-group">
                            <label>ชื่อผู้ใช้งาน Admin</label>
                            <input type="text" name="admin_user" class="form-control" placeholder="admin" required>
                        </div>
                        <div class="form-group">
                            <label>รหัสผ่าน Admin</label>
                            <input type="password" name="admin_pass" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>ชื่อ-นามสกุล</label>
                            <input type="text" name="admin_fullname" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>อีเมล</label>
                            <input type="email" name="admin_email" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">ติดตั้งเสร็จสมบูรณ์</button>
                        <a href="index.php?step=1" class="btn btn-default btn-block">ย้อนกลับ</a>
                    </form>

                <?php elseif ($step === 3): ?>
                    <div class="text-center">
                        <div class="alert alert-success">
                            <h4><i class="glyphicon glyphicon-ok"></i> ติดตั้งสำเร็จ!</h4>
                            <p>ขณะนี้ระบบพร้อมใช้งานแล้ว</p>
                        </div>
                        <p class="text-danger"><b>คำเตือน:</b> กรุณาลบโฟลเดอร์ <code>install/</code> เพื่อความปลอดภัย</p>
                        <hr>
                        <a href="../login/index.php" class="btn btn-success btn-block">ไปที่หน้าเข้าสู่ระบบ</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>
