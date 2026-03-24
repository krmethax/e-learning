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

// Fetch some stats for display
$fac_count = $conn->query("SELECT COUNT(*) as total FROM faculties")->fetch_assoc()['total'];
$br_count = $conn->query("SELECT COUNT(*) as total FROM branches")->fetch_assoc()['total'];
$sub_count = $conn->query("SELECT COUNT(*) as total FROM subjects")->fetch_assoc()['total'];
$inst_count = $conn->query("SELECT COUNT(*) as total FROM instructors")->fetch_assoc()['total'];

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">จัดการข้อมูลระบบ</h3>
                </div>
                <div class="panel-body">
                    <div class="row" style="margin-top: 20px;">
                        <!-- Left Column: Organizational Structure -->
                        <div class="col-md-4">
                            <section style="margin-bottom: 30px;">
                                <h4 style="font-weight: 600; color: #333; margin-bottom: 15px;">โครงสร้างองค์กร</h4>
                                <ul class="list-unstyled" style="line-height: 2;">
                                    <li><a href="faculties.php" class="text-primary">จัดการข้อมูลคณะ</a></li>
                                    <li style="margin-top: 5px;">
                                        <strong style="display: block; font-size: 13px; color: #777;">จำนวนคณะทั้งหมด</strong>
                                        <span><?php echo $fac_count; ?> คณะ</span>
                                    </li>
                                    
                                    <li style="margin-top: 15px;"><a href="branches.php" class="text-primary">จัดการข้อมูลสาขาวิชา</a></li>
                                    <li style="margin-top: 5px;">
                                        <strong style="display: block; font-size: 13px; color: #777;">จำนวนสาขาวิชาทั้งหมด</strong>
                                        <span><?php echo $br_count; ?> สาขาวิชา</span>
                                    </li>
                                </ul>
                            </section>
                        </div>

                        <!-- Middle Column: Courses and Staff -->
                        <div class="col-md-4">
                            <section style="margin-bottom: 30px;">
                                <h4 style="font-weight: 600; color: #333; margin-bottom: 15px;">รายวิชาและบุคลากร</h4>
                                <ul class="list-unstyled" style="line-height: 2;">
                                    <li><a href="subjects.php" class="text-primary">จัดการข้อมูลรายวิชา</a></li>
                                    <li style="margin-top: 5px;">
                                        <strong style="display: block; font-size: 13px; color: #777;">จำนวนรายวิชาออนไลน์</strong>
                                        <span><?php echo $sub_count; ?> รายวิชา</span>
                                    </li>

                                    <li style="margin-top: 15px;"><a href="instructors.php" class="text-primary">จัดการข้อมูลผู้สอน</a></li>
                                    <li style="margin-top: 5px;">
                                        <strong style="display: block; font-size: 13px; color: #777;">จำนวนอาจารย์ผู้สอน</strong>
                                        <span><?php echo $inst_count; ?> ท่าน</span>
                                    </li>
                                </ul>
                            </section>
                        </div>

                        <!-- Right Column: Quick Stats -->
                        <div class="col-md-4">
                            <section style="margin-bottom: 30px; padding: 15px; background: #f9f9f9; border-radius: 4px; border: 1px solid #eee;">
                                <h4 style="font-weight: 600; color: #333; margin-top: 0; margin-bottom: 15px;">สรุปข้อมูลระบบ</h4>
                                <ul class="list-group" style="margin-bottom: 0;">
                                    <li class="list-group-item d-flex justify-content-between align-items-center" style="background: transparent; border: none; padding: 5px 0;">
                                        คณะ <span class="badge"><?php echo $fac_count; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center" style="background: transparent; border: none; padding: 5px 0;">
                                        สาขาวิชา <span class="badge"><?php echo $br_count; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center" style="background: transparent; border: none; padding: 5px 0;">
                                        รายวิชา <span class="badge"><?php echo $sub_count; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center" style="background: transparent; border: none; padding: 5px 0;">
                                        ผู้สอน <span class="badge"><?php echo $inst_count; ?></span>
                                    </li>
                                </ul>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
