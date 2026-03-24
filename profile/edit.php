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
$message = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = $conn->real_escape_string($_POST['firstname'] ?? '');
    $lastname = $conn->real_escape_string($_POST['lastname'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    $email_display = (int)($_POST['email_display'] ?? 1);
    $city = $conn->real_escape_string($_POST['city'] ?? '');
    $timezone = $conn->real_escape_string($_POST['timezone'] ?? 'Asia/Bangkok');
    $description = $conn->real_escape_string($_POST['description'] ?? '');
    $full_name = trim($firstname . " " . $lastname);

    // Profile Image Upload
    $profile_image_path = null;
    if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] == 0) {
        $target_dir = $path . "assets/img/profiles/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES['new_image']['name'], PATHINFO_EXTENSION);
        $file_name = $user_id . "_" . time() . "." . $file_ext;
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES['new_image']['tmp_name'], $target_file)) {
            $profile_image_path = "assets/img/profiles/" . $file_name;
        }
    }

    // Update Query (Only include fields that were in the form)
    $sql = "UPDATE users SET 
            firstname = '$firstname', 
            lastname = '$lastname', 
            full_name = '$full_name',
            email = '$email', 
            email_display = $email_display, 
            city = '$city', 
            timezone = '$timezone', 
            description = '$description'";
    
    if ($profile_image_path) {
        $sql .= ", profile_image = '$profile_image_path'";
        $_SESSION['profile_image'] = $profile_image_path;
    }
    
    $sql .= " WHERE id = $user_id";

    if ($conn->query($sql)) {
        $message = "อัปเดตข้อมูลบัญชีเรียบร้อยแล้ว";
        $_SESSION['full_name'] = $full_name;
    } else {
        $message = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// Fetch current user data
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px; margin-bottom: 40px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">แก้ไขบัญชี <?php echo htmlspecialchars($user['full_name'] ?? ''); ?></h3>
                </div>
                <div class="panel-body">

                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <form action="edit.php" method="POST" enctype="multipart/form-data" class="form-horizontal" style="margin-top: 20px;">
                        <!-- General Section -->
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title" style="font-size: 16px;">ทั่วไป</h3>
                            </div>
                            <div class="panel-body">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">ชื่อ</label>
                                    <div class="col-sm-6">
                                        <input type="text" name="firstname" class="form-control" value="<?php echo htmlspecialchars($user['firstname'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">นามสกุล</label>
                                    <div class="col-sm-6">
                                        <input type="text" name="lastname" class="form-control" value="<?php echo htmlspecialchars($user['lastname'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">อีเมล</label>
                                    <div class="col-sm-6">
                                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars(($user['email'] ?? '') ?: ($user['username'] ?? '').'@ubu.ac.th'); ?>" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">แสดงอีเมล</label>
                                    <div class="col-sm-6">
                                        <select name="email_display" class="form-control">
                                            <option value="0" <?php echo ($user['email_display'] ?? 1) == 0 ? 'selected' : ''; ?>>ซ่อนอีเมลของฉันจากทุกคน</option>
                                            <option value="1" <?php echo ($user['email_display'] ?? 1) == 1 ? 'selected' : ''; ?>>สมาชิกในวิชาที่เรียนเท่านั้นที่จะเห็นอีเมล</option>
                                            <option value="2" <?php echo ($user['email_display'] ?? 1) == 2 ? 'selected' : ''; ?>>แสดงอีเมลของฉันให้ทุกคนเห็น</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">จังหวัด</label>
                                    <div class="col-sm-6">
                                        <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">โซนเวลา</label>
                                    <div class="col-sm-6">
                                        <select name="timezone" class="form-control">
                                            <option value="Asia/Bangkok" <?php echo ($user['timezone'] ?? 'Asia/Bangkok') == 'Asia/Bangkok' ? 'selected' : ''; ?>>เขตเวลาของเซิร์ฟเวอร์ (Asia/Bangkok)</option>
                                            <option value="UTC" <?php echo ($user['timezone'] ?? '') == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">รายละเอียด</label>
                                    <div class="col-sm-9">
                                        <textarea name="description" class="form-control" rows="10"><?php echo htmlspecialchars($user['description'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Picture Section -->
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title" style="font-size: 16px;">รูปภาพส่วนตัว</h3>
                            </div>
                            <div class="panel-body">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">รูปปัจจุบัน</label>
                                    <div class="col-sm-6">
                                        <?php 
                                            $user_img = !empty($user['profile_image'] ?? '') ? $path . $user['profile_image'] : "https://ui-avatars.com/api/?name=" . urlencode($user['full_name'] ?? 'User') . "&background=transparent&color=333&bold=true&size=100";
                                        ?>
                                        <img src="<?php echo $user_img; ?>" class="img-thumbnail" style="width: 100px;">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">ภาพใหม่</label>
                                    <div class="col-sm-6">
                                        <input type="file" name="new_image" class="form-control" accept="image/*">
                                        <p class="help-block">Accepted file types: .gif .jpe .jpeg .jpg .png .webp</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">อัปเดตข้อมูลบัญชี</button>
                            <a href="index.php" class="btn btn-default">ยกเลิก</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
