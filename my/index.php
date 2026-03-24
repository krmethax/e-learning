<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$path = '../';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $path . "login/index.php");
    exit();
}
?>

<?php include $path . 'includes/header.php'; ?>
<?php include $path . 'includes/navbar.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 50px;">
           
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
