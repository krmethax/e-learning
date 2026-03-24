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

$message = '';

// Handle session termination
if (isset($_GET['terminate'])) {
    $session_id = (int)$_GET['terminate'];
    if ($conn->query("DELETE FROM browser_sessions WHERE id = $session_id")) {
        $message = "ยกเลิกเซสชันเรียบร้อยแล้ว";
    }
}

// Fetch all sessions with user info
$sessions = $conn->query("
    SELECT s.*, u.full_name, u.username 
    FROM browser_sessions s
    JOIN users u ON s.user_id = u.id
    ORDER BY s.last_activity DESC
");

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px; margin-bottom: 40px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">ประวัติการเข้าใช้งานทั้งหมด</h3>
                </div>
                <div class="panel-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ผู้ใช้งาน</th>
                                    <th>เบราว์เซอร์ / อุปกรณ์</th>
                                    <th>ที่อยู่ IP</th>
                                    <th>กิจกรรมล่าสุด</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($sessions->num_rows > 0): ?>
                                    <?php while($row = $sessions->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($row['username']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['browser']); ?></td>
                                            <td>
                                                <a href="<?php echo $path; ?>plookup/index.php?ip=<?php echo urlencode($row['ip_address']); ?>">
                                                    <?php echo htmlspecialchars($row['ip_address'] ?? 'Unknown'); ?>
                                                </a>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($row['last_activity'])); ?></td>
                                            <td>
                                                <a href="sessions_all.php?terminate=<?php echo $row['id']; ?>" class="btn btn-danger btn-xs" onclick="return confirm('ยืนยันการยกเลิกเซสชันนี้?')">ยกเลิก</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">ไม่พบข้อมูลเซสชัน</td>
                                    </tr>
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
