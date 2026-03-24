<?php $path = '../'; ?>
<?php include $path . 'includes/header.php'; ?>
<?php include $path . 'includes/navbar.php'; ?>
<?php include $path . 'includes/db.php'; ?>

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
        <div class="col-md-12">
            <div class="page-header">
                <h1>คอร์สเรียนออนไลน์</h1>
            </div>

            <ol class="breadcrumb">
                <li><a href="index.php">คอร์สเรียน</a></li>
                <?php if ($f_id): ?>
                    <li class="<?php echo !$b_id ? 'active' : ''; ?>">
                        <?php if ($b_id): ?><a href="index.php?f_id=<?php echo $f_id; ?>"><?php echo $current_faculty_name; ?></a><?php else: ?><?php echo $current_faculty_name; ?><?php endif; ?>
                    </li>
                <?php endif; ?>
                <?php if ($b_id): ?>
                    <li class="active"><?php echo $current_branch_name; ?></li>
                <?php endif; ?>
            </ol>

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
                            echo '<a href="../login/index.php" title="เข้าสู่ระบบเพื่อเข้าเรียน">';
                            echo '<span class="glyphicon glyphicon-chevron-right text-muted" style="margin-right: 10px;"></span>';
                            echo htmlspecialchars($row['subject_code']) . ' ' . htmlspecialchars($row['subject_name']);
                            echo '</a>';
                            
                            // Info icon for instructor toggle
                            echo ' <span class="glyphicon glyphicon-info-sign text-primary instructor-toggle" style="cursor: pointer; margin-left: 5px;" data-target="instr-'.$subject_id.'"></span>';
                            
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
                            echo '<a href="index.php?f_id=' . $f_id . '&b_id=' . $row['id'] . '" class="list-group-item">';
                            echo '<span class="glyphicon glyphicon-chevron-right text-muted" style="margin-right: 10px;"></span>';
                            echo htmlspecialchars($row['branch_name']);
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
                            echo '<a href="index.php?f_id=' . $row['id'] . '" class="list-group-item">';
                            echo '<span class="glyphicon glyphicon-chevron-right text-muted" style="margin-right: 10px;"></span>';
                            echo htmlspecialchars($row['faculty_name']);
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
