<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$path = '../';
require_once $path . 'includes/db.php';

$subject_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

if ($subject_id <= 0 || $item_id <= 0) {
    header("Location: " . $path . "my/index.php");
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $path . "login/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch subject info
$stmt = $conn->prepare("
    SELECT s.*, b.branch_name, f.faculty_name 
    FROM subjects s 
    JOIN branches b ON s.branch_id = b.id 
    JOIN faculties f ON b.faculty_id = f.id 
    WHERE s.id = ?
");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();

if (!$subject) {
    die("ไม่พบรายวิชานี้");
}

// Fetch Item and Section info from DB
$item_stmt = $conn->prepare("
    SELECT i.*, s.title as section_title 
    FROM course_items i
    JOIN course_sections s ON i.section_id = s.id
    WHERE i.id = ? AND s.subject_id = ?
");
$item_stmt->bind_param("ii", $item_id, $subject_id);
$item_stmt->execute();
$item = $item_stmt->get_result()->fetch_assoc();

if (!$item) {
    die("ไม่พบข้อมูลหัวข้อย่อยนี้");
}

include $path . 'includes/header.php';
include $path . 'includes/navbar.php';
?>

<style>
    .lesson-header-banner {
        background: #fff;
        border-bottom: 1px solid #eee;
        padding: 20px 0;
        margin-top: -20px;
    }
    .lesson-main-content {
        padding: 30px 0;
        background: #fcfcfc;
        min-height: calc(100vh - 150px);
    }
    .content-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        padding: 30px;
        margin-bottom: 30px;
    }
    .video-container {
        position: relative;
        padding-bottom: 56.25%; /* 16:9 */
        height: 0;
        overflow: hidden;
        background: #000;
        border-radius: 8px;
        margin-bottom: 25px;
    }
    .video-container iframe, .video-container object, .video-container embed {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
    .file-preview {
        height: 400px;
        border: 1px solid #eee;
        border-radius: 8px;
        margin-bottom: 25px;
        background: #f9f9f9;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }
</style>

<!-- Lesson Header -->
<div class="lesson-header-banner">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div style="margin-bottom: 10px;">
                    <a href="course.php?id=<?php echo $subject_id; ?>" class="text-muted" style="text-decoration: none;">
                        <i class="fa fa-arrow-left"></i> กลับสู่หน้าหลักวิชา <strong><?php echo htmlspecialchars($subject['subject_code']); ?></strong>
                    </a>
                </div>
                <h2 style="margin: 0; font-weight: bold; color: #333;">
                    <?php echo htmlspecialchars($item['item_name']); ?>
                </h2>
                <p class="text-muted" style="margin-top: 5px;">
                    <i class="fa fa-folder-open-o"></i> <?php echo htmlspecialchars($item['section_title']); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="lesson-main-content">
    <div class="container">
        <div class="row">
            <div class="col-md-9">
                <div class="content-card">
                    
                    <?php if ($item['item_type'] === 'video'): ?>
                        <div class="video-container">
                            <?php if (strpos($item['item_content'], 'youtube.com') !== false || strpos($item['item_content'], 'youtu.be') !== false): ?>
                                <?php 
                                    // Basic YouTube embed logic
                                    $video_id = "";
                                    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $item['item_content'], $match)) {
                                        $video_id = $match[1];
                                    }
                                ?>
                                <iframe src="https://www.youtube.com/embed/<?php echo $video_id; ?>" frameborder="0" allowfullscreen></iframe>
                            <?php else: ?>
                                <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #fff; flex-direction: column;">
                                    <i class="fa fa-play-circle-o fa-5x"></i>
                                    <p style="margin-top: 15px; font-size: 18px;">Video: <?php echo htmlspecialchars($item['item_content']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($item['item_type'] === 'file'): ?>
                        <div class="file-preview">
                            <i class="fa fa-file-pdf-o fa-5x text-danger"></i>
                            <h4 style="margin-top: 20px;">ดาวน์โหลดไฟล์เอกสาร</h4>
                            <p class="text-muted"><?php echo htmlspecialchars($item['item_name']); ?></p>
                            <a href="<?php echo htmlspecialchars($item['item_content']); ?>" class="btn btn-primary" style="margin-top: 10px;" target="_blank"><i class="fa fa-download"></i> ดาวน์โหลด / เปิดไฟล์</a>
                        </div>
                    <?php elseif ($item['item_type'] === 'quiz'): ?>
                        <div class="text-center" style="padding: 40px 0;">
                            <i class="fa fa-check-square-o fa-5x text-success"></i>
                            <h3 style="margin-top: 25px; font-weight: bold;">แบบทดสอบ: <?php echo htmlspecialchars($item['item_name']); ?></h3>
                            <p class="text-muted" style="font-size: 16px;">กรุณาอ่านคำแนะนำก่อนเริ่มทำแบบทดสอบ</p>
                            <?php 
                                $q_count = $conn->query("SELECT COUNT(*) as count FROM quiz_questions WHERE item_id = $item_id")->fetch_assoc()['count'];
                                $attempt = $conn->query("SELECT * FROM quiz_attempts WHERE item_id = $item_id AND user_id = $user_id ORDER BY finished_at DESC LIMIT 1")->fetch_assoc();
                            ?>
                            <div style="margin: 20px 0;">
                                <span class="label label-info">จำนวนคำถาม: <?php echo $q_count; ?> ข้อ</span>
                                <?php if($attempt): ?>
                                    <span class="label label-success">คะแนนล่าสุด: <?php echo number_format($attempt['score'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                            <hr style="width: 200px; margin: 30px auto;">
                            <?php if ($q_count > 0): ?>
                                <a href="quiz_do.php?id=<?php echo $subject_id; ?>&item_id=<?php echo $item_id; ?>" class="btn btn-success btn-lg" style="padding: 12px 40px;">เริ่มทำแบบทดสอบ</a>
                            <?php else: ?>
                                <button class="btn btn-default btn-lg" disabled>ยังไม่มีคำถาม</button>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($item['item_type'] === 'forum'): ?>
                        <div class="forum-container">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <h4 style="font-weight: bold; margin: 0;">หัวข้อเสวนา</h4>
                                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#newTopicModal"><i class="fa fa-plus"></i> เริ่มหัวข้อใหม่</button>
                            </div>
                            
                            <div class="list-group">
                                <?php 
                                $discs = $conn->query("SELECT d.*, u.full_name FROM course_forum_discussions d JOIN users u ON d.user_id = u.id WHERE d.item_id = $item_id ORDER BY d.created_at DESC");
                                if ($discs && $discs->num_rows > 0):
                                    while($d = $discs->fetch_assoc()):
                                ?>
                                    <div class="list-group-item" style="padding: 15px;">
                                        <div style="display: flex; justify-content: space-between;">
                                            <h5 style="margin: 0; font-weight: bold;"><a href="forum_topic.php?id=<?php echo $subject_id; ?>&item_id=<?php echo $item_id; ?>&topic_id=<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['title']); ?></a></h5>
                                            <small class="text-muted"><?php echo $d['created_at']; ?></small>
                                        </div>
                                        <div style="font-size: 13px; color: #777; margin-top: 5px;">โดย <?php echo htmlspecialchars($d['full_name']); ?></div>
                                    </div>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                    <div class="text-center text-muted" style="padding: 30px; border: 1px dashed #ddd; border-radius: 4px;">ยังไม่มีหัวข้อเสวนา</div>
                                <?php endif; ?>
                            </div>

                            <!-- New Topic Modal -->
                            <div class="modal fade" id="newTopicModal" tabindex="-1" role="dialog">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <form method="POST" action="forum_actions.php">
                                            <div class="modal-header">
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                <h4 class="modal-title">เริ่มหัวข้อเสวนาใหม่</h4>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
                                                <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                                                <input type="hidden" name="action" value="new_topic">
                                                <div class="form-group">
                                                    <label>หัวข้อ:</label>
                                                    <input type="text" name="title" class="form-control" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>ข้อความ:</label>
                                                    <textarea name="message" class="form-control" rows="5" required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-default" data-dismiss="modal">ยกเลิก</button>
                                                <button type="submit" class="btn btn-primary">โพสต์หัวข้อ</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($item['item_type'] === 'choice'): ?>
                        <div class="choice-container">
                            <h4 style="font-weight: bold; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">โพล / ตัวเลือก</h4>
                            <p><?php echo nl2br(htmlspecialchars($item['item_content'])); ?></p>
                            <hr>
                            <div class="text-center text-muted" style="padding: 20px; border: 1px dashed #ddd; border-radius: 4px;">ฟีเจอร์ตัวเลือกโพลกำลังอยู่ระหว่างการพัฒนา</div>
                        </div>
                    <?php else: ?>
                        <div style="font-size: 16px; line-height: 1.8; color: #444;">
                            <h4 style="font-weight: bold; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">รายละเอียดเนื้อหา</h4>
                            <div class="item-content-text" style="word-break: break-all;">
                                <?php if ($item['item_type'] === 'link'): ?>
                                    <a href="<?php echo htmlspecialchars($item['item_content']); ?>" target="_blank" style="display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #337ab7; text-decoration: underline;">
                                        <?php echo htmlspecialchars($item['item_content']); ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo nl2br(htmlspecialchars($item['item_content'])); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Navigation Buttons -->
                    <hr style="margin: 40px 0 20px 0;">
                    <div class="row">
                        <div class="col-xs-6 text-left">
                            <a href="course.php?id=<?php echo $subject_id; ?>" class="btn btn-default"><i class="fa fa-chevron-left"></i> กลับไปหน้ารายวิชา</a>
                        </div>
                        <div class="col-xs-6 text-right">
                            <!-- In a real app, find next item ID -->
                            <button class="btn btn-primary" disabled>หัวข้อถัดไป <i class="fa fa-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="panel panel-default" style="border-radius: 8px;">
                    <div class="panel-heading" style="background: #fff; border-bottom: 1px solid #eee; font-weight: bold;">เกี่ยวกับรายวิชา</div>
                    <div class="panel-body">
                        <div class="text-center" style="margin-bottom: 15px;">
                            <img src="<?php echo $path . htmlspecialchars($subject['cover_image']); ?>" class="img-rounded" style="width: 100%; height: auto; border: 1px solid #eee;">
                        </div>
                        <p style="font-size: 13px; line-height: 1.6; color: #666;">
                            <strong><?php echo htmlspecialchars($subject['subject_code']); ?></strong><br>
                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
