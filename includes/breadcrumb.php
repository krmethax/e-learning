<div class="container" style="margin-top: 15px;">
    <ol class="breadcrumb" style="background-color: transparent; padding: 0; margin-bottom: 10px;">
        <li><a href="<?php echo $path; ?>index.php">หน้าแรก</a></li>
        <?php
            // Simple logic to determine breadcrumb segments
            $current_file = basename($_SERVER['PHP_SELF']);
            $current_dir = basename(dirname($_SERVER['PHP_SELF']));
            
            // Get faculty/branch info if we are in courses/index.php
            if ($current_dir == 'courses' && $current_file == 'index.php') {
                echo '<li><a href="'.$path.'courses/index.php">รายวิชา</a></li>';
                if (isset($current_faculty_name) && !empty($current_faculty_name)) {
                    if (isset($current_branch_name) && !empty($current_branch_name)) {
                        $f_id_val = isset($f_id) ? (int)$f_id : (isset($_GET['f_id']) ? (int)$_GET['f_id'] : 0);
                        echo '<li><a href="'.$path.'courses/index.php?f_id='.$f_id_val.'">'.htmlspecialchars($current_faculty_name).'</a></li>';
                    } else {
                        echo '<li class="active">'.htmlspecialchars($current_faculty_name).'</li>';
                    }
                }
                if (isset($current_branch_name) && !empty($current_branch_name)) {
                    echo '<li class="active">'.htmlspecialchars($current_branch_name).'</li>';
                }
            } elseif ($current_dir == 'courses' && $current_file == 'view.php') {
                echo '<li><a href="'.$path.'courses/index.php">รายวิชา</a></li>';
                if (isset($subject['faculty_name'])) {
                    echo '<li><a href="'.$path.'courses/index.php?f_id='.$subject['f_id'].'">'.htmlspecialchars($subject['faculty_name']).'</a></li>';
                }
                if (isset($subject['branch_name'])) {
                    echo '<li><a href="'.$path.'courses/index.php?f_id='.$subject['f_id'].'&b_id='.$subject['b_id'].'">'.htmlspecialchars($subject['branch_name']).'</a></li>';
                }
                if (isset($subject['subject_code'])) {
                    echo '<li class="active">'.htmlspecialchars($subject['subject_code']).'</li>';
                }
            } elseif ($current_dir == 'courses' && $current_file == 'enroll.php') {
                echo '<li><a href="'.$path.'courses/index.php">รายวิชา</a></li>';
                echo '<li class="active">ลงทะเบียน</li>';
            } elseif ($current_dir == 'my') {
                echo '<li class="active">รายวิชาของฉัน</li>';
            } elseif ($current_dir == 'admin') {
                echo '<li><a href="'.$path.'admin/data_management.php">จัดการข้อมูลระบบ</a></li>';
                if ($current_file == 'faculties.php' || $current_file == 'faculty_add.php') echo '<li class="active">จัดการข้อมูลคณะ</li>';
                elseif ($current_file == 'branches.php' || $current_file == 'branch_add.php') echo '<li class="active">จัดการข้อมูลสาขาวิชา</li>';
                elseif ($current_file == 'subjects.php' || $current_file == 'subject_add.php') echo '<li class="active">จัดการข้อมูลรายวิชา</li>';
                elseif ($current_file == 'instructors.php' || $current_file == 'instructor_add.php') echo '<li class="active">จัดการข้อมูลผู้สอน</li>';
                elseif ($current_file == 'settings.php') echo '<li class="active">ตั้งค่าระบบ</li>';
                elseif ($current_file == 'edit_settings.php') echo '<li class="active">แก้ไขการตั้งค่า</li>';
            } elseif ($current_dir == 'profile') {
                echo '<li><a href="'.$path.'profile/index.php">โปรไฟล์</a></li>';
                if ($current_file == 'edit.php') echo '<li class="active">แก้ไขข้อมูลส่วนตัว</li>';
                elseif ($current_file == 'sessions.php') echo '<li class="active">Browser sessions</li>';
            } elseif ($current_dir == 'import') {
                echo '<li class="active">นำเข้ารายชื่อผู้เรียน</li>';
            }
        ?>
    </ol>
    <hr style="margin-top: 0; margin-bottom: 20px; border-top: 1px solid #f0f0f0;">
</div>
