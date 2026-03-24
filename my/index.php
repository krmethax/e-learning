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

// Ensure user_subjects table exists
$sql_create = "CREATE TABLE IF NOT EXISTS user_subjects (
    user_id INT(11) NOT NULL,
    subject_id INT(11) NOT NULL,
    PRIMARY KEY (user_id, subject_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
$conn->query($sql_create);

// Fetch enrolled subjects
$stmt = $conn->prepare("
    SELECT s.*, b.branch_name, f.faculty_name 
    FROM subjects s
    JOIN user_subjects us ON s.id = us.subject_id
    JOIN branches b ON s.branch_id = b.id
    JOIN faculties f ON b.faculty_id = f.id
    WHERE us.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include $path . 'includes/header.php'; ?>
<?php include $path . 'includes/navbar.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">รายวิชาของฉัน</h3>
                </div>
                <div class="panel-body">
                    <?php if ($result->num_rows > 0): ?>
                        <div class="list-group">
                            <?php while($row = $result->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <a href="<?php echo $path; ?>courses/course.php?id=<?php echo $row['id']; ?>">
                                                <?php if (!empty($row['cover_image'])): ?>
                                                    <img src="<?php echo $path . htmlspecialchars($row['cover_image']); ?>" alt="Cover" class="img-responsive img-rounded" style="width: 100%; height: auto; margin-bottom: 10px;">
                                                <?php else: ?>
                                                    <div style="width: 100%; height: 120px; background: #eee; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #999; margin-bottom: 10px;">
                                                        <i class="fa fa-book fa-3x"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </a>
                                        </div>
                                        <div class="col-md-9">
                                            <h4 class="list-group-item-heading" style="margin-top: 5px;">
                                                <a href="<?php echo $path; ?>courses/course.php?id=<?php echo $row['id']; ?>" style="text-decoration: none; color: #337ab7; font-weight: bold;">
                                                    <?php echo htmlspecialchars($row['subject_code']) . ' ' . htmlspecialchars($row['subject_name']); ?>
                                                </a>
                                            </h4>
                                            <p class="list-group-item-text text-muted">
                                                <?php echo htmlspecialchars($row['faculty_name']) . ' / ' . htmlspecialchars($row['branch_name']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            คุณยังไม่ได้ลงทะเบียนรายวิชาใดๆ <a href="<?php echo $path; ?>courses/index.php" class="alert-link">คลิกที่นี่เพื่อดูรายวิชาทั้งหมด</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
