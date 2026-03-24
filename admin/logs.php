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

// Fetch logs with user info
$logs = $conn->query("
    SELECT l.*, u.username, u.full_name, u.role 
    FROM system_logs l
    LEFT JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
    LIMIT 500
");

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px; margin-bottom: 40px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">บันทึกประวัติการใช้งานระบบ (System Logs)</h3>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>วัน-เวลา</th>
                                    <th>ผู้ใช้งาน</th>
                                    <th>สถานะ</th>
                                    <th>การดำเนินการ</th>
                                    <th>รายละเอียด</th>
                                    <th>ที่อยู่ IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($logs && $logs->num_rows > 0): ?>
                                    <?php while($row = $logs->fetch_assoc()): ?>
                                        <tr>
                                            <td style="white-space: nowrap;">
                                                <small><?php echo date('d/m/Y H:i:s', strtotime($row['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($row['user_id']): ?>
                                                    <?php echo htmlspecialchars($row['full_name']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">System / Guest</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    $role_label = '-';
                                                    if ($row['role'] === 'admin') $role_label = 'แอดมิน';
                                                    elseif ($row['role'] === 'user') $role_label = 'นักศึกษา';
                                                    elseif ($row['role'] === 'instructor') $role_label = 'ผู้สอน';
                                                    else $role_label = htmlspecialchars($row['role'] ?? '-');
                                                    
                                                    echo $role_label;
                                                ?>
                                            </td>
                                            <td>
                                                <span class="text-primary" style="font-weight: 500;">
                                                    <?php echo htmlspecialchars($row['action']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['details']); ?></td>
                                            <td>
                                                <a href="<?php echo $path; ?>plookup/index.php?ip=<?php echo urlencode($row['ip_address']); ?>">
                                                    <?php echo htmlspecialchars($row['ip_address']); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">ไม่พบข้อมูลบันทึกประวัติ</td>
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
