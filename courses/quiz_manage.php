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

$subject_id = (int)($_GET['subject_id'] ?? 0);
$item_id = (int)($_GET['item_id'] ?? 0);

if (!$subject_id || !$item_id) {
    die("Invalid request");
}

// Fetch quiz info
$res = $conn->query("SELECT * FROM course_items WHERE id = $item_id");
$quiz = $res->fetch_assoc();

if (!$quiz) die("Quiz not found");

$message = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_question') {
        $text = $conn->real_escape_string($_POST['question_text']);
        $sql = "INSERT INTO quiz_questions (item_id, question_text) VALUES ($item_id, '$text')";
        if ($conn->query($sql)) {
            $q_id = $conn->insert_id;
            // Add options
            foreach ($_POST['options'] as $idx => $opt_text) {
                if (trim($opt_text) === '') continue;
                $is_correct = ($idx == $_POST['correct_idx']) ? 1 : 0;
                $opt_text = $conn->real_escape_string($opt_text);
                $conn->query("INSERT INTO quiz_options (question_id, option_text, is_correct) VALUES ($q_id, '$opt_text', $is_correct)");
            }
            $message = "เพิ่มคำถามเรียบร้อยแล้ว";
        }
    } elseif ($action === 'delete_question') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM quiz_questions WHERE id = $id");
        $message = "ลบคำถามเรียบร้อยแล้ว";
    }
}

// Fetch questions
$questions = [];
$q_res = $conn->query("SELECT * FROM quiz_questions WHERE item_id = $item_id ORDER BY id ASC");
while ($q = $q_res->fetch_assoc()) {
    $q['options'] = [];
    $o_res = $conn->query("SELECT * FROM quiz_options WHERE question_id = " . $q['id']);
    while ($o = $o_res->fetch_assoc()) {
        $q['options'][] = $o;
    }
    $questions[] = $q;
}

include $path . 'includes/header.php';
include $path . 'includes/navbar.php';
?>

<div class="container" style="margin-top: 20px;">
    <div class="row">
        <div class="col-md-12">
            <div style="margin-bottom: 20px;">
                <a href="manage.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> กลับไปหน้าจัดการเนื้อหา</a>
            </div>

            <div class="panel panel-primary">
                <div class="panel-heading">จัดการคำถาม: <?php echo htmlspecialchars($quiz['item_name']); ?></div>
                <div class="panel-body">
                    
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <!-- Add Question Form -->
                    <div class="well">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_question">
                            <div class="form-group">
                                <label>โจทย์คำถาม:</label>
                                <textarea name="question_text" class="form-control" rows="2" required></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-12"><label>ตัวเลือก (เลือกปุ่มวิทยุหน้าข้อที่ถูกต้อง):</label></div>
                                <?php for($i=0; $i<4; $i++): ?>
                                    <div class="col-md-6">
                                        <div class="input-group" style="margin-bottom: 10px;">
                                            <span class="input-group-addon">
                                                <input type="radio" name="correct_idx" value="<?php echo $i; ?>" <?php if($i==0) echo 'checked'; ?>>
                                            </span>
                                            <input type="text" name="options[]" class="form-control" placeholder="ตัวเลือกที่ <?php echo $i+1; ?>">
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <button type="submit" class="btn btn-success">เพิ่มคำถาม</button>
                        </form>
                    </div>

                    <hr>

                    <!-- List Questions -->
                    <?php if (empty($questions)): ?>
                        <p class="text-center text-muted">ยังไม่มีคำถามในแบบทดสอบนี้</p>
                    <?php else: ?>
                        <?php foreach($questions as $idx => $q): ?>
                            <div class="panel panel-default">
                                <div class="panel-body">
                                    <div style="display: flex; justify-content: space-between;">
                                        <strong>ข้อที่ <?php echo $idx+1; ?>: <?php echo htmlspecialchars($q['question_text']); ?></strong>
                                        <form method="POST" onsubmit="return confirm('ลบคำถามนี้?')">
                                            <input type="hidden" name="action" value="delete_question">
                                            <input type="hidden" name="id" value="<?php echo $q['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-xs">ลบ</button>
                                        </form>
                                    </div>
                                    <ul style="margin-top: 10px; list-style: none; padding-left: 20px;">
                                        <?php foreach($q['options'] as $o): ?>
                                            <li style="<?php if($o['is_correct']) echo 'color: green; font-weight: bold;'; ?>">
                                                <?php if($o['is_correct']) echo '<i class="fa fa-check-circle"></i>'; else echo '<i class="fa fa-circle-o"></i>'; ?>
                                                <?php echo htmlspecialchars($o['option_text']); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
