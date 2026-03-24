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

$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

if ($subject_id <= 0) {
    die("ไม่พบรายวิชา");
}

// Fetch subject info
$stmt = $conn->prepare("SELECT * FROM subjects WHERE id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();

if (!$subject) {
    die("ไม่พบรายวิชา");
}

$message = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_section') {
        $title = $conn->real_escape_string($_POST['title']);
        $sql = "INSERT INTO course_sections (subject_id, title, sort_order) 
                SELECT $subject_id, '$title', IFNULL(MAX(sort_order), 0) + 1 FROM course_sections WHERE subject_id = $subject_id";
        if ($conn->query($sql)) {
            $message = "เพิ่มหัวข้อเรียบร้อยแล้ว";
        }
    } elseif ($action === 'edit_section') {
        $id = (int)$_POST['id'];
        $title = $conn->real_escape_string($_POST['title']);
        $sql = "UPDATE course_sections SET title = '$title' WHERE id = $id AND subject_id = $subject_id";
        if ($conn->query($sql)) {
            $message = "แก้ไขหัวข้อเรียบร้อยแล้ว";
        }
    } elseif ($action === 'add_item') {
        $section_id = (int)$_POST['section_id'];
        $name = $conn->real_escape_string($_POST['name']);
        $type = $conn->real_escape_string($_POST['type']);
        $content = $conn->real_escape_string($_POST['content']);
        $weight = (float)($_POST['weight'] ?? 0);
        $max_score = (float)($_POST['max_score'] ?? 0);
        
        $sql = "INSERT INTO course_items (section_id, item_name, item_type, item_content, weight, max_score, sort_order) 
                SELECT $section_id, '$name', '$type', '$content', $weight, $max_score, IFNULL(MAX(sort_order), 0) + 1 FROM course_items WHERE section_id = $section_id";
        if ($conn->query($sql)) {
            $message = "เพิ่มหัวข้อย่อยเรียบร้อยแล้ว";
        }
    } elseif ($action === 'edit_item') {
        $id = (int)$_POST['id'];
        $name = $conn->real_escape_string($_POST['name']);
        $type = $conn->real_escape_string($_POST['type']);
        $content = $conn->real_escape_string($_POST['content']);
        $weight = (float)($_POST['weight'] ?? 0);
        $max_score = (float)($_POST['max_score'] ?? 0);
        
        $sql = "UPDATE course_items SET 
                item_name = '$name', 
                item_type = '$type', 
                item_content = '$content', 
                weight = $weight, 
                max_score = $max_score 
                WHERE id = $id";
        if ($conn->query($sql)) {
            $message = "แก้ไขหัวข้อย่อยเรียบร้อยแล้ว";
        }
    } elseif ($action === 'delete_section') {
        $id = (int)$_POST['id'];
        if ($conn->query("DELETE FROM course_sections WHERE id = $id")) {
            $message = "ลบหัวข้อเรียบร้อยแล้ว";
        }
    } elseif ($action === 'delete_item') {
        $id = (int)$_POST['id'];
        if ($conn->query("DELETE FROM course_items WHERE id = $id")) {
            $message = "ลบหัวข้อย่อยเรียบร้อยแล้ว";
        }
    }
}

// Fetch all sections and items
$sections = [];
$res = $conn->query("SELECT * FROM course_sections WHERE subject_id = $subject_id ORDER BY sort_order ASC");
while ($row = $res->fetch_assoc()) {
    $section_id = $row['id'];
    $row['items'] = [];
    $item_res = $conn->query("SELECT * FROM course_items WHERE section_id = $section_id ORDER BY sort_order ASC");
    while ($item = $item_res->fetch_assoc()) {
        $row['items'][] = $item;
    }
    $sections[] = $row;
}

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row" style="margin-top: 20px;">
        <div class="col-md-12">
            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                <a href="course.php?id=<?php echo $subject_id; ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> กลับไปหน้าหลักวิชา</a>
                <a href="course.php?id=<?php echo $subject_id; ?>" target="_blank" class="btn btn-info"><i class="fa fa-external-link"></i> ดูหน้าตัวอย่างรายวิชา</a>
            </div>

            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">จัดการเนื้อหารายวิชา: <?php echo htmlspecialchars($subject['subject_code'] . ' ' . $subject['subject_name']); ?></h3>
                </div>
                <div class="panel-body">
                    
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <!-- Add Section -->
                    <div class="well">
                        <form method="POST" class="form-inline">
                            <input type="hidden" name="action" value="add_section">
                            <div class="form-group">
                                <label>เพิ่มหัวข้อใหม่ (Chapter/Section):</label>
                                <input type="text" name="title" class="form-control" placeholder="ชื่อหัวข้อ" required style="min-width: 300px;">
                            </div>
                            <button type="submit" class="btn btn-primary">เพิ่มหัวข้อ</button>
                        </form>
                    </div>

                    <hr>

                    <!-- List Sections and Items -->
                    <?php if (empty($sections)): ?>
                        <p class="text-center text-muted">ยังไม่มีเนื้อหาในรายวิชานี้</p>
                    <?php else: ?>
                        <?php foreach ($sections as $sec): ?>
                            <div class="panel panel-default">
                                <div class="panel-heading" style="background: #fcfcfc;">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h4 style="margin: 0; font-weight: bold;">
                                                <?php echo htmlspecialchars($sec['title']); ?>
                                                <button type="button" class="btn btn-link btn-xs" data-toggle="modal" data-target="#editSectionModal<?php echo $sec['id']; ?>"><i class="fa fa-pencil"></i> แก้ไข</button>
                                            </h4>
                                        </div>
                                        <div class="col-md-4 text-right">
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('ยืนยันการลบหัวข้อและเนื้อหาทั้งหมดในหัวข้อนี้?')">
                                                <input type="hidden" name="action" value="delete_section">
                                                <input type="hidden" name="id" value="<?php echo $sec['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i> ลบหัวข้อ</button>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Edit Section Modal -->
                                    <div class="modal fade" id="editSectionModal<?php echo $sec['id']; ?>" tabindex="-1" role="dialog">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                        <h4 class="modal-title">แก้ไขหัวข้อ</h4>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="edit_section">
                                                        <input type="hidden" name="id" value="<?php echo $sec['id']; ?>">
                                                        <div class="form-group">
                                                            <label>ชื่อหัวข้อ:</label>
                                                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($sec['title']); ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-default" data-dismiss="modal">ยกเลิก</button>
                                                        <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="panel-body">
                                    <table class="table table-hover" style="table-layout: fixed; width: 100%;">
                                        <thead>
                                            <tr>
                                                <th style="width: 40px;"></th>
                                                <th style="width: 25%;">ชื่อหัวข้อย่อย / กิจกรรม</th>
                                                <th style="width: 15%;">ประเภท</th>
                                                <th>รายละเอียด/ลิงก์</th>
                                                <th class="text-right" style="width: 120px;">จัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($sec['items'] as $it): ?>
                                                <tr>
                                                    <td><i class="fa fa-circle-o text-muted"></i></td>
                                                    <td style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($it['item_name']); ?></td>
                                                    <td><span class="label label-default"><?php echo htmlspecialchars($it['item_type']); ?></span></td>
                                                    <td style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #777; font-size: 12px;">
                                                        <?php echo htmlspecialchars($it['item_content']); ?>
                                                    </td>
                                                    <td class="text-right">
                                                        <?php if ($it['item_type'] === 'quiz'): ?>
                                                            <a href="quiz_manage.php?subject_id=<?php echo $subject_id; ?>&item_id=<?php echo $it['id']; ?>" class="btn btn-link btn-xs" style="padding:0; margin-right: 5px;"><i class="fa fa-question-circle"></i> คำถาม</a>
                                                        <?php endif; ?>
                                                        <button type="button" class="btn btn-link btn-xs" data-toggle="modal" data-target="#editItemModal<?php echo $it['id']; ?>" style="padding:0; margin-right: 5px;"><i class="fa fa-pencil"></i> แก้ไข</button>
                                                        <form method="POST" style="display:inline;" onsubmit="return confirm('ยืนยันการลบ?')">
                                                            <input type="hidden" name="action" value="delete_item">
                                                            <input type="hidden" name="id" value="<?php echo $it['id']; ?>">
                                                            <button type="submit" class="btn btn-link btn-xs text-danger" style="padding:0;"><i class="fa fa-trash"></i> ลบ</button>
                                                        </form>

                                                        <!-- Edit Item Modal -->
                                                        <div class="modal fade text-left" id="editItemModal<?php echo $it['id']; ?>" tabindex="-1" role="dialog">
                                                            <div class="modal-dialog" role="document">
                                                                <div class="modal-content">
                                                                    <form method="POST">
                                                                        <div class="modal-header">
                                                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                                            <h4 class="modal-title">แก้ไขหัวข้อย่อย</h4>
                                                                        </div>
                                                                        <div class="modal-body" style="white-space: normal;">
                                                                            <input type="hidden" name="action" value="edit_item">
                                                                            <input type="hidden" name="id" value="<?php echo $it['id']; ?>">
                                                                            <div class="form-group">
                                                                                <label>ชื่อหัวข้อย่อย:</label>
                                                                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($it['item_name']); ?>" required>
                                                                            </div>
                                                                            <div class="form-group">
                                                                                <label>ประเภท:</label>
                                                                                <select name="type" class="form-control" required>
                                                                                    <option value="video" <?php if($it['item_type'] == 'video') echo 'selected'; ?>>วิดีโอ (Video)</option>
                                                                                    <option value="file" <?php if($it['item_type'] == 'file') echo 'selected'; ?>>ไฟล์เอกสาร (File/PDF)</option>
                                                                                    <option value="quiz" <?php if($it['item_type'] == 'quiz') echo 'selected'; ?>>แบบทดสอบ (Quiz)</option>
                                                                                    <option value="assignment" <?php if($it['item_type'] == 'assignment') echo 'selected'; ?>>งาน/การบ้าน (Assignment)</option>
                                                                                    <option value="forum" <?php if($it['item_type'] == 'forum') echo 'selected'; ?>>กระดานเสวนา (Forum)</option>
                                                                                    <option value="choice" <?php if($it['item_type'] == 'choice') echo 'selected'; ?>>โพล/ตัวเลือก (Choice)</option>
                                                                                    <option value="link" <?php if($it['item_type'] == 'link') echo 'selected'; ?>>ลิงก์ภายนอก (Link)</option>
                                                                                    <option value="info" <?php if($it['item_type'] == 'info') echo 'selected'; ?>>ข้อมูลทั่วไป (Information)</option>
                                                                                    <option value="news" <?php if($it['item_type'] == 'news') echo 'selected'; ?>>ข่าวสาร (News)</option>
                                                                                </select>
                                                                            </div>
                                                                            <div class="form-group">
                                                                                <label>ลิงก์ หรือ เนื้อหา:</label>
                                                                                <textarea name="content" class="form-control" rows="3"><?php echo htmlspecialchars($it['item_content']); ?></textarea>
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label>น้ำหนัก (%):</label>
                                                                                        <input type="number" name="weight" class="form-control" value="<?php echo htmlspecialchars($it['weight']); ?>" step="0.1">
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label>คะแนนเต็ม:</label>
                                                                                        <input type="number" name="max_score" class="form-control" value="<?php echo htmlspecialchars($it['max_score']); ?>" step="0.1">
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-default" data-dismiss="modal">ยกเลิก</button>
                                                                            <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($sec['items'])): ?>
                                                <tr><td colspan="5" class="text-center text-muted">ไม่มีข้อมูล</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>

                                    <!-- Add Item to this Section -->
                                    <div style="background: #f9f9f9; padding: 15px; border-radius: 4px; margin-top: 10px;">
                                        <h5 style="margin-top: 0; font-weight: bold; margin-bottom: 10px;">เพิ่มหัวข้อย่อยใน "<?php echo htmlspecialchars($sec['title']); ?>"</h5>
                                        <form method="POST" class="form-inline">
                                            <input type="hidden" name="action" value="add_item">
                                            <input type="hidden" name="section_id" value="<?php echo $sec['id']; ?>">
                                            <div class="form-group" style="margin-bottom: 5px;">
                                                <input type="text" name="name" class="form-control" placeholder="ชื่อหัวข้อย่อย" required>
                                            </div>
                                            <div class="form-group" style="margin-bottom: 5px;">
                                                <select name="type" class="form-control" required>
                                                    <option value="video">วิดีโอ (Video)</option>
                                                    <option value="file">ไฟล์เอกสาร (File/PDF)</option>
                                                    <option value="quiz">แบบทดสอบ (Quiz)</option>
                                                    <option value="assignment">งาน/การบ้าน (Assignment)</option>
                                                    <option value="forum">กระดานเสวนา (Forum)</option>
                                                    <option value="choice">โพล/ตัวเลือก (Choice)</option>
                                                    <option value="link">ลิงก์ภายนอก (Link)</option>
                                                    <option value="info">ข้อมูลทั่วไป (Information)</option>
                                                    <option value="news">ข่าวสาร (News)</option>
                                                </select>
                                            </div>
                                            <div class="form-group" style="margin-bottom: 5px;">
                                                <input type="text" name="content" class="form-control" placeholder="ลิงก์ หรือ ข้อมูลเบื้องต้น">
                                            </div>
                                            <div class="form-group" style="margin-bottom: 5px;">
                                                <input type="number" name="weight" class="form-control" placeholder="น้ำหนัก (%)" step="0.1" style="width: 100px;">
                                            </div>
                                            <div class="form-group" style="margin-bottom: 5px;">
                                                <input type="number" name="max_score" class="form-control" placeholder="คะแนนเต็ม" step="0.1" style="width: 100px;">
                                            </div>
                                            <button type="submit" class="btn btn-success">เพิ่ม</button>
                                        </form>
                                    </div>
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
