<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$path = '../';
require_once $path . 'includes/db.php';

$subject_id = (int)($_GET['id'] ?? 0);
$item_id = (int)($_GET['item_id'] ?? 0);
$topic_id = (int)($_GET['topic_id'] ?? 0);

if (!$subject_id || !$item_id || !$topic_id) {
    header("Location: " . $path . "index.php");
    exit();
}

// Fetch topic
$topic_res = $conn->query("SELECT d.*, u.full_name, u.profile_image FROM course_forum_discussions d JOIN users u ON d.user_id = u.id WHERE d.id = $topic_id");
$topic = $topic_res->fetch_assoc();

if (!$topic) die("Topic not found");

// Fetch replies
$replies = $conn->query("SELECT r.*, u.full_name, u.profile_image FROM course_forum_replies r JOIN users u ON r.user_id = u.id WHERE r.discussion_id = $topic_id ORDER BY r.created_at ASC");

include $path . 'includes/header.php';
include $path . 'includes/navbar.php';
?>

<div class="container" style="margin-top: 20px;">
    <div class="row">
        <div class="col-md-12">
            <div style="margin-bottom: 20px;">
                <a href="lesson.php?id=<?php echo $subject_id; ?>&item_id=<?php echo $item_id; ?>" class="text-muted"><i class="fa fa-arrow-left"></i> กลับไปที่กระดานเสวนา</a>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading" style="background: #f8f9fa; padding: 20px;">
                    <h3 style="margin: 0; font-weight: bold;"><?php echo htmlspecialchars($topic['title']); ?></h3>
                    <div style="margin-top: 10px; color: #666; font-size: 13px;">
                        โดย <strong><?php echo htmlspecialchars($topic['full_name']); ?></strong> เมื่อ <?php echo $topic['created_at']; ?>
                    </div>
                </div>
                <div class="panel-body" style="padding: 25px; font-size: 15px; line-height: 1.6;">
                    <?php echo nl2br(htmlspecialchars($topic['message'])); ?>
                </div>
            </div>

            <h4 style="margin: 30px 0 20px; font-weight: bold; color: #555;">การตอบกลับ (<?php echo $replies->num_rows; ?>)</h4>

            <?php while($r = $replies->fetch_assoc()): ?>
                <div class="panel panel-default" style="border-left: 4px solid #337ab7;">
                    <div class="panel-body" style="padding: 20px;">
                        <div style="margin-bottom: 10px; display: flex; justify-content: space-between;">
                            <strong style="color: #337ab7;"><?php echo htmlspecialchars($r['full_name']); ?></strong>
                            <small class="text-muted"><?php echo $r['created_at']; ?></small>
                        </div>
                        <div style="line-height: 1.6;">
                            <?php echo nl2br(htmlspecialchars($r['message'])); ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <div class="panel panel-default" style="margin-top: 30px;">
                <div class="panel-heading">ตอบกลับหัวข้อนี้</div>
                <div class="panel-body">
                    <form method="POST" action="forum_actions.php">
                        <input type="hidden" name="action" value="reply">
                        <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
                        <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                        <input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
                        <div class="form-group">
                            <textarea name="message" class="form-control" rows="4" placeholder="พิมพ์ข้อความตอบกลับที่นี่..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">ส่งข้อความ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
