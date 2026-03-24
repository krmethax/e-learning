<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$path = '../';
require_once $path . 'includes/db.php';

// Check if any user exists, if not, redirect to setup
$check_users = $conn->query("SELECT id FROM users LIMIT 1");
if (!$check_users || $check_users->num_rows == 0) {
    header("Location: " . $path . "set_up.php");
    exit();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . $path . "my/index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['profile_image'] = $user['profile_image'];
            $_SESSION['role'] = $user['role'];

            // Log activity
            logEvent($conn, 'Login', 'User logged in: ' . $user['username']);

            // 1. Update last_access
            $uid = $user['id'];
            $conn->query("UPDATE users SET last_access = CURRENT_TIMESTAMP WHERE id = $uid");

            // 2. Insert browser session
            $browser = $_SERVER['HTTP_USER_AGENT'];
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt_sess = $conn->prepare("INSERT INTO browser_sessions (user_id, browser, ip_address) VALUES (?, ?, ?)");
            $stmt_sess->bind_param("iss", $uid, $browser, $ip);
            $stmt_sess->execute();
            
            header("Location: " . $path . "my/index.php");
            exit();
        } else {
            $error = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $error = "ไม่พบชื่อผู้ใช้งานนี้";
    }
}

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-4 col-md-offset-4" style="margin-top: 50px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title text-center">เข้าสู่ระบบ</h3>
                </div>
                <div class="panel-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form action="index.php" method="POST">
                        <div class="form-group">
                            <label for="username">ชื่อผู้ใช้งาน</label>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">รหัสผ่าน</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">เข้าสู่ระบบ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
