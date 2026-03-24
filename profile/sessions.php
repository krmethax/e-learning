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

// Handle session termination (optional but good practice)
if (isset($_GET['terminate'])) {
    $session_id = (int)$_GET['terminate'];
    if ($conn->query("DELETE FROM browser_sessions WHERE id = $session_id AND user_id = $user_id")) {
        $message = "ยกเลิกเซสชันเรียบร้อยแล้ว";
    }
}

// Fetch sessions
$stmt = $conn->prepare("SELECT * FROM browser_sessions WHERE user_id = ? ORDER BY last_activity DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$sessions = $stmt->get_result();

// Helper function for Thai Date
function thai_date_full($timestamp) {
    $days = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
    $months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    
    $time = strtotime($timestamp);
    $day_of_week = $days[date('w', $time)];
    $day = date('j', $time);
    $month = $months[(int)date('n', $time)];
    $year = date('Y', $time);
    $clock = date('g:iA', $time); // 8:53PM format
    
    return "วัน$day_of_week, $day $month $year, $clock";
}

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px; margin-bottom: 40px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Browser sessions</h3>
                </div>
                <div class="panel-body">
                    <p class="text-muted" style="margin-bottom: 20px;">
                        รายการเซสชันที่ใช้งานอยู่ในปัจจุบันของคุณในเบราว์เซอร์และอุปกรณ์ต่างๆ
                    </p>

                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>เข้าสู่ระบบ</th>
                                    <th>เข้ามาครั้งสุดท้ายเมื่อ</th>
                                    <th>หมายเลขไอพีที่ใช้ครั้งสุดท้าย</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($sessions->num_rows > 0): ?>
                                    <?php while($row = $sessions->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight: 400;"><?php echo thai_date_full($row['last_activity']); ?></div>
                                            </td>
                                            <td>
                                                <?php echo date('d/m/Y H:i', strtotime($row['last_activity'])); ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo $path; ?>plookup/index.php?ip=<?php echo urlencode($row['ip_address']); ?>" class="text-primary">
                                                    <?php echo htmlspecialchars($row['ip_address'] ?? 'Unknown'); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">ไม่พบข้อมูลเซสชัน</td>
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
