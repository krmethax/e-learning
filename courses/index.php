<?php 
$path = '../'; 
require_once $path . 'includes/db.php';

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

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
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
                            $now = date('Y-m-d H:i:s');
                            $is_open = true;
                            $lock_reason = "";

                            if (!empty($row['start_date']) && $now < $row['start_date']) {
                                $is_open = false;
                                $lock_reason = "วิชานี้จะเปิดในวันที่ " . date('d/m/Y H:i', strtotime($row['start_date']));
                            }
                            if (!empty($row['end_date']) && $now > $row['end_date']) {
                                $is_open = false;
                                $lock_reason = "วิชานี้ปิดรับลงทะเบียนแล้ว";
                            }

                            echo '<div class="list-group-item">';
                            echo '<div class="row" style="display: flex; align-items: center;">';
                            
                            // Cover Image
                            echo '<div class="col-md-3">';
                            $cover = !empty($row['cover_image']) ? $path . $row['cover_image'] : "https://via.placeholder.com/260x160?text=No+Image";
                            echo '<a href="view.php?id='.$subject_id.'"><img src="'.$cover.'" class="img-responsive img-rounded" style="width: 260px; height: 160px; object-fit: cover;"></a>';
                            echo '</div>';

                            echo '<div class="col-md-9" style="color: #007bff; font-weight: 400;">';
                            echo '<a href="view.php?id='.$subject_id.'" style="text-decoration: none;">';
                            echo '<strong>' . htmlspecialchars($row['subject_code']) . '</strong> ' . htmlspecialchars($row['subject_name']);
                            echo '</a>';
                            
                            if ($row['enrollment_type'] === 'password') {
                                echo ' <i class="fa fa-lock text-muted" title="ต้องใช้รหัสผ่านในการเข้าถึง"></i>';
                            }
                            
                            // Info text for instructor toggle
                            echo ' <span class="instructor-toggle" style="cursor: pointer; margin-left: 5px; color: #777; font-weight: 300; font-size: 12px;" data-target="instr-'.$subject_id.'">[รายละเอียด]</span>';
                            
                            if (!$is_open) {
                                echo '<br><small class="text-danger"><i class="fa fa-clock-o"></i> ' . $lock_reason . '</small>';
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

                            echo '<div id="instr-'.$subject_id.'" class="instructor-info" style="display:none; padding: 10px; background: #fafafa; border-radius: 4px; margin-top: 5px; border: 1px solid #eee;">';
                            echo '<div style="margin-bottom: 5px; border-bottom: 1px solid #eee; padding-bottom: 5px;">';
                            echo '<strong>' . htmlspecialchars($row['subject_code']) . '</strong> ' . htmlspecialchars($row['subject_name_en'] ?? '');
                            echo '<br><span style="font-weight: 300;">' . htmlspecialchars($row['subject_name']) . '</span>';
                            echo '</div>';
                            
                            echo '<div style="margin-bottom: 10px;">';
                            echo '<strong>หน่วยกิต:</strong> ' . htmlspecialchars($row['credits'] ?? '-');
                            echo '</div>';

                            echo '<div style="margin-bottom: 10px;">';
                            echo '<strong>ผู้สอน:</strong> ' . htmlspecialchars($instructor_text);
                            echo '</div>';

                            if (!empty($row['description_th'])) {
                                echo '<div style="margin-bottom: 15px;">';
                                echo '<strong>คำอธิบายรายวิชา:</strong><br>';
                                echo nl2br(htmlspecialchars($row['description_th']));
                                echo '</div>';
                            }

                            if (!empty($row['description_en'])) {
                                echo '<div style="margin-bottom: 10px;">';
                                echo '<strong>Course Description:</strong><br>';
                                echo nl2br(htmlspecialchars($row['description_en']));
                                echo '</div>';
                            }
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
