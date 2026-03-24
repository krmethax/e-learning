<nav class="navbar navbar-default">
  <div class="container">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse-1">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="<?php echo isset($_SESSION['user_id']) ? $path . 'my/index.php' : $path . 'index.php'; ?>">E-Learning</a>
    </div>

    <div class="collapse navbar-collapse" id="navbar-collapse-1">
      <ul class="nav navbar-nav">
        <li><a href="<?php echo isset($_SESSION['user_id']) ? $path . 'my/index.php' : $path . 'index.php'; ?>">หน้าแรก</a></li>
        <li><a href="<?php echo $path; ?>courses/index.php">คอร์สเรียน</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
          <li><a href="<?php echo $path; ?>import/index.php">นำเข้ารายชื่อผู้เรียน</a></li>
        <?php endif; ?>
      </ul>
      <ul class="nav navbar-nav navbar-right">
        <?php if (isset($_SESSION['user_id'])): ?>
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
              ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['username']); ?> <span class="caret"></span>
            </a>
            <ul class="dropdown-menu">
              <li><a href="<?php echo $path; ?>my/index.php">หน้าส่วนตัวของฉัน</a></li>
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
