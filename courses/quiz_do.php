<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$path = '../';
require_once $path . 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . $path . "login/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$subject_id = (int)($_GET['id'] ?? 0);
$item_id = (int)($_GET['item_id'] ?? 0);

if (!$subject_id || !$item_id) {
    die("Invalid request");
}

// Fetch quiz info
$res = $conn->query("SELECT * FROM course_items WHERE id = $item_id");
$quiz = $res->fetch_assoc();

if (!$quiz) die("Quiz not found");

// Fetch questions and options
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

// Handle Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score = 0;
    foreach ($questions as $q) {
        $q_id = $q['id'];
        if (isset($_POST['question_' . $q_id])) {
            $selected_opt_id = (int)$_POST['question_' . $q_id];
            // Check if correct
            $check = $conn->query("SELECT is_correct FROM quiz_options WHERE id = $selected_opt_id AND question_id = $q_id");
            if ($check && $check->fetch_assoc()['is_correct'] == 1) {
                $score += $q['points'];
            }
        }
    }

    // Save attempt
    $conn->query("INSERT INTO quiz_attempts (item_id, user_id, score, finished_at) VALUES ($item_id, $user_id, $score, CURRENT_TIMESTAMP)");
    
    // Save to course_grades
    $conn->query("DELETE FROM course_grades WHERE item_id = $item_id AND user_id = $user_id");
    $conn->query("INSERT INTO course_grades (item_id, user_id, score) VALUES ($item_id, $user_id, $score)");

    header("Location: lesson.php?id=$subject_id&item_id=$item_id&finished=1");
    exit();
}

include $path . 'includes/header.php';
include $path . 'includes/navbar.php';
?>

<div class="container" style="margin-top: 20px;">
    <div class="row">
        <div class="col-md-9">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 style="margin: 0; font-weight: bold;"><?php echo htmlspecialchars($quiz['item_name']); ?></h3>
                </div>
                <div class="panel-body">
                    <form method="POST">
                        <?php foreach($questions as $idx => $q): ?>
                            <div class="well well-sm" style="background: #fff; margin-bottom: 25px; border-radius: 8px;">
                                <p style="font-size: 16px; font-weight: bold; margin-bottom: 15px;">ข้อที่ <?php echo $idx+1; ?>: <?php echo htmlspecialchars($q['question_text']); ?></p>
                                <?php foreach($q['options'] as $o): ?>
                                    <div class="radio" style="margin-left: 20px; padding: 10px; border-radius: 4px; border: 1px solid #f0f0f0; margin-bottom: 8px;">
                                        <label style="display: block; width: 100%; cursor: pointer;">
                                            <input type="radio" name="question_<?php echo $q['id']; ?>" value="<?php echo $o['id']; ?>" required>
                                            <?php echo htmlspecialchars($o['option_text']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="text-center" style="margin-top: 30px;">
                            <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('ยืนยันการส่งคำตอบ?')">ส่งแบบทดสอบ</button>
                            <a href="lesson.php?id=<?php echo $subject_id; ?>&item_id=<?php echo $item_id; ?>" class="btn btn-link">ยกเลิก</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="panel panel-info">
                <div class="panel-heading">Quiz Navigation</div>
                <div class="panel-body">
                    <p class="text-muted">โปรดทำคำถามให้ครบทุกข้อก่อนกดส่ง</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                        <?php foreach($questions as $idx => $q): ?>
                            <div style="width: 30px; height: 30px; line-height: 30px; text-align: center; border: 1px solid #ccc; border-radius: 4px;"><?php echo $idx+1; ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
