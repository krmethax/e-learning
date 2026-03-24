<?php $path = '../'; ?>
<?php include $path . 'includes/header.php'; ?>
<?php include $path . 'includes/navbar.php'; ?>
<?php require_once $path . 'includes/db.php'; ?>

<?php
// Get parameters
$f_id = isset($_GET['f_id']) ? (int)$_GET['f_id'] : null;
$b_id = isset($_GET['b_id']) ? (int)$_GET['b_id'] : null;

// Fetch breadcrumb/header info
$current_faculty_name = "";
if ($f_id) {
    $stmt = $conn->prepare("SELECT faculty_name FROM faculties WHERE id = ?");
    $stmt->bind_param("i", $f_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $current_faculty_name = $row['faculty_name'];
    }
}

$current_branch_name = "";
if ($b_id) {
    $stmt = $conn->prepare("SELECT branch_name FROM branches WHERE id = ?");
    $stmt->bind_param("i", $b_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $current_branch_name = $row['branch_name'];
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">รายวิชาออนไลน์</h3>
                </div>
                <div class="panel-body">
            
            <div class="list-group">
                <?php
                if ($b_id) {
                    // Level 3: Subjects
                    $stmt = $conn->prepare("SELECT * FROM subjects WHERE branch_id = ?");
                    $stmt->bind_param("i", $b_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $subject_id = $row['id'];
                            echo '<div class="list-group-item">';
                            echo '<div class="row">';
                            echo '<div class="col-md-9" style="color: #007bff; font-weight: 400;">';
                            echo htmlspecialchars($row['subject_code']) . ' ' . htmlspecialchars($row['subject_name']);
                            
                            // Info text for instructor toggle
                            echo ' <span class="instructor-toggle" style="cursor: pointer; margin-left: 5px; color: #777; font-weight: 300; font-size: 12px;" data-target="instr-'.$subject_id.'">[รายละเอียด]</span>';
                            echo '</div>';
                            echo '<div class="col-md-3 text-right">';
                            if (isset($_SESSION['user_id'])) {
                                echo '<a href="enroll.php?subject_id='.$subject_id.'" class="btn btn-success btn-xs">ลงทะเบียน</a>';
                            } else {
                                echo '<a href="../login/index.php" class="btn btn-default btn-xs" title="เข้าสู่ระบบเพื่อลงทะเบียน">เข้าสู่ระบบ</a>';
                            }
                            echo '</div>';
                            echo '</div>';
                            
                            // Fetch multiple instructors for this subject
                            $inst_stmt = $conn->prepare("
                                SELECT i.instructor_name 
                                FROM instructors i
                                JOIN subject_instructors si ON i.id = si.instructor_id
                                WHERE si.subject_id = ?
                            ");
                            $inst_stmt->bind_param("i", $subject_id);
                            $inst_stmt->execute();
                            $inst_result = $inst_stmt->get_result();
                            
                            $instructors = [];
                            while($inst_row = $inst_result->fetch_assoc()) {
                                $instructors[] = $inst_row['instructor_name'];
                            }
                            $instructor_text = !empty($instructors) ? implode(', ', $instructors) : 'ยังไม่ระบุผู้สอน';

                            echo '<div id="instr-'.$subject_id.'" class="instructor-info">';
                            echo 'ผู้สอน: ' . htmlspecialchars($instructor_text);
                            echo '</div>';
                            
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="list-group-item text-muted">ไม่มีข้อมูลรายวิชา</div>';
                    }
                } elseif ($f_id) {
                    // Level 2: Branches
                    $stmt = $conn->prepare("SELECT * FROM branches WHERE faculty_id = ?");
                    $stmt->bind_param("i", $f_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo '<a href="index.php?f_id=' . $f_id . '&b_id=' . $row['id'] . '" class="list-group-item" style="color: #007bff; font-weight: 400;">';
                            echo '<i class="fa fa-angle-right" style="margin-right: 10px;"></i> ' . htmlspecialchars($row['branch_name']);
                            echo '</a>';
                        }
                    } else {
                        echo '<div class="list-group-item text-muted">ไม่มีข้อมูลสาขาวิชา</div>';
                    }
                } else {
                    // Level 1: All Faculties
                    $sql = "SELECT * FROM faculties";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo '<a href="index.php?f_id=' . $row['id'] . '" class="list-group-item" style="color: #007bff; font-weight: 400;">';
                            echo '<i class="fa fa-angle-right" style="margin-right: 10px;"></i> ' . htmlspecialchars($row['faculty_name']);
                            echo '</a>';
                        }
                    } else {
                        echo '<div class="list-group-item text-muted">ไม่มีข้อมูล</div>';
                    }
                }
                ?>
            </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('.instructor-toggle').on('click', function(e) {
        e.preventDefault();
        var targetId = $(this).data('target');
        $('#' + targetId).slideToggle('fast');
    });
});
</script>

<?php include $path . 'includes/footer.php'; ?>
