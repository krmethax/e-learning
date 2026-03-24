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

// Add Instructor
if (isset($_POST['add_instructor'])) {
    $name = $conn->real_escape_string($_POST['instructor_name']);
    $conn->query("INSERT INTO instructors (instructor_name) VALUES ('$name')");
    $message = "เพิ่มข้อมูลผู้สอนเรียบร้อยแล้ว";
}

// Assign Instructor to Subject
if (isset($_POST['assign_instructor'])) {
    $instructor_id = (int)$_POST['instructor_id'];
    $subject_id = (int)$_POST['subject_id'];
    $conn->query("INSERT IGNORE INTO subject_instructors (subject_id, instructor_id) VALUES ($subject_id, $instructor_id)");
    $message = "มอบหมายวิชาให้ผู้สอนเรียบร้อยแล้ว";
}

// Delete Instructor assignment
if (isset($_GET['delete_assign'])) {
    $sid = (int)$_GET['subject_id'];
    $iid = (int)$_GET['instructor_id'];
    $conn->query("DELETE FROM subject_instructors WHERE subject_id = $sid AND instructor_id = $iid");
    $message = "ยกเลิกการมอบหมายวิชาเรียบร้อยแล้ว";
}

// Delete Instructor (Entirely)
if (isset($_GET['delete_instructor'])) {
    $id = (int)$_GET['delete_instructor'];
    $conn->query("DELETE FROM instructors WHERE id = $id");
    $message = "ลบข้อมูลผู้สอนเรียบร้อยแล้ว";
}

// Fetch all instructors
$instructors = $conn->query("SELECT * FROM instructors ORDER BY instructor_name");

// Fetch instructors with their subjects
$assignments = $conn->query("
    SELECT i.instructor_name, s.subject_name, s.subject_code, si.subject_id, si.instructor_id
    FROM subject_instructors si
    JOIN instructors i ON si.instructor_id = i.id
    JOIN subjects s ON si.subject_id = s.id
    ORDER BY i.instructor_name, s.subject_code
");

// Fetch subjects for select box
$subjects_list = $conn->query("SELECT * FROM subjects ORDER BY subject_code");

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px; margin-bottom: 40px;">
            <div class="page-header">
                <h2>จัดการข้อมูลผู้สอน</h2>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="row">
                <!-- Add & Assign Panel -->
                <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-heading"><h3 class="panel-title">เพิ่มผู้สอน</h3></div>
                        <div class="panel-body">
                            <form action="instructors.php" method="POST">
                                <div class="form-group">
                                    <label>ชื่อผู้สอน</label>
                                    <input type="text" name="instructor_name" class="form-control" required>
                                </div>
                                <button type="submit" name="add_instructor" class="btn btn-primary btn-block">เพิ่มข้อมูลผู้สอน</button>
                            </form>
                        </div>
                    </div>

                    <div class="panel panel-default" style="margin-top: 20px;">
                        <div class="panel-heading"><h3 class="panel-title">มอบหมายวิชา</h3></div>
                        <div class="panel-body">
                            <form action="instructors.php" method="POST">
                                <div class="form-group">
                                    <label>ผู้สอน</label>
                                    <select name="instructor_id" class="form-control" required>
                                        <option value="">เลือกผู้สอน...</option>
                                        <?php 
                                        $instructors->data_seek(0);
                                        while($i = $instructors->fetch_assoc()): ?>
                                            <option value="<?php echo $i['id']; ?>"><?php echo htmlspecialchars($i['instructor_name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>วิชาที่สอน</label>
                                    <select name="subject_id" class="form-control" required>
                                        <option value="">เลือกวิชา...</option>
                                        <?php while($s = $subjects_list->fetch_assoc()): ?>
                                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['subject_code'] . " " . $s['subject_name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <button type="submit" name="assign_instructor" class="btn btn-success btn-block">มอบหมายวิชา</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Assignment List -->
                <div class="col-md-8">
                    <div class="panel panel-default">
                        <div class="panel-heading"><h3 class="panel-title">รายการการสอน</h3></div>
                        <div class="panel-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ชื่อผู้สอน</th>
                                        <th>วิชาที่สอน</th>
                                        <th width="80">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $assignments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['instructor_name']); ?></td>
                                        <td>
                                            <small><?php echo htmlspecialchars($row['subject_code']); ?></small><br>
                                            <?php echo htmlspecialchars($row['subject_name']); ?>
                                        </td>
                                        <td>
                                            <a href="instructors.php?delete_assign=1&subject_id=<?php echo $row['subject_id']; ?>&instructor_id=<?php echo $row['instructor_id']; ?>" class="btn btn-warning btn-xs" title="ยกเลิกการสอน">ยกเลิก</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="panel panel-default" style="margin-top: 20px;">
                        <div class="panel-heading"><h3 class="panel-title">รายชื่อผู้สอนทั้งหมด</h3></div>
                        <div class="panel-body">
                            <ul class="list-group">
                                <?php 
                                $instructors->data_seek(0);
                                while($i = $instructors->fetch_assoc()): ?>
                                    <li class="list-group-item">
                                        <?php echo htmlspecialchars($i['instructor_name']); ?>
                                        <a href="instructors.php?delete_instructor=<?php echo $i['id']; ?>" class="pull-right text-danger" onclick="return confirm('ลบผู้สอนท่านนี้?')"><span class="glyphicon glyphicon-trash"></span></a>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-left">
                <a href="settings.php" class="btn btn-default"><span class="glyphicon glyphicon-arrow-left"></span> กลับไปหน้าตั้งค่า</a>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>