<nav class="navbar navbar-default">
  <div class="container">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse-1">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="<?php echo isset($_SESSION['user_id']) ? $path . 'my/index.php' : $path . 'index.php'; ?>"><?php echo htmlspecialchars($site_name ?? 'E-Learning'); ?></a>
    </div>

    <div class="collapse navbar-collapse" id="navbar-collapse-1">
      <ul class="nav navbar-nav">
        <li><a href="<?php echo isset($_SESSION['user_id']) ? $path . 'my/index.php' : $path . 'index.php'; ?>">หน้าแรก</a></li>
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">รายวิชา <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="<?php echo $path; ?>courses/index.php">ดูทั้งหมด</a></li>
            <li role="separator" class="divider"></li>
            <li class="dropdown-header">แยกตามคณะ</li>
            <?php
              $nav_fac_res = $conn->query("SELECT * FROM faculties ORDER BY faculty_name ASC");
              if ($nav_fac_res && $nav_fac_res->num_rows > 0):
                while($nav_fac = $nav_fac_res->fetch_assoc()):
            ?>
              <li><a href="<?php echo $path; ?>courses/index.php?f_id=<?php echo $nav_fac['id']; ?>"><?php echo htmlspecialchars($nav_fac['faculty_name']); ?></a></li>
            <?php 
                endwhile;
              endif; 
            ?>
          </ul>
        </li>
        <?php if (isset($_SESSION['user_id'])): ?>
          <li><a href="<?php echo $path; ?>import/index.php">นำเข้ารายชื่อผู้เรียน</a></li>
        <?php endif; ?>
      </ul>
      <ul class="nav navbar-nav navbar-right">
        <?php if (isset($_SESSION['user_id'])): ?>
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" style="padding-top: 10px; padding-bottom: 10px;">
              <?php 
                $user_img = isset($_SESSION['profile_image']) && !empty($_SESSION['profile_image']) ? $path . $_SESSION['profile_image'] : "https://ui-avatars.com/api/?name=" . urlencode($_SESSION['full_name']) . "&background=transparent&color=333&bold=true";
              ?>
              <img src="<?php echo $user_img; ?>" class="navbar-profile-img" alt="Profile">
              <span class="caret"></span>
            </a>
            <ul class="dropdown-menu">
              <li><a href="<?php echo $path; ?>profile/index.php">ประวัติส่วนตัว</a></li>
              <li><a href="#">คะแนน</a></li>
              <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li role="separator" class="divider"></li>
                <li class="dropdown-header">ส่วนผู้ดูแลระบบ</li>
                <li><a href="<?php echo $path; ?>admin/data_management.php">จัดการข้อมูลระบบ</a></li>
                <li><a href="<?php echo $path; ?>admin/settings.php">ตั้งค่าระบบ</a></li>
              <?php endif; ?>
              <li role="separator" class="divider"></li>
              <li><a href="<?php echo $path; ?>login/logout.php">ออกจากระบบ</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li><a href="<?php echo $path; ?>login/index.php">เข้าสู่ระบบ</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<?php include __DIR__ . '/breadcrumb.php'; ?>
