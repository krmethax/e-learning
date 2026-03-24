<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$path = '../';
require_once $path . 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $path . "login/index.php");
    exit();
}

$ip = isset($_GET['ip']) ? $_GET['ip'] : '';

include $path . 'includes/header.php';
include $path . 'includes/navbar.php'; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-12" style="margin-top: 20px; margin-bottom: 40px;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">ข้อมูลที่อยู่ IP: <?php echo htmlspecialchars($ip); ?></h3>
                </div>
                <div class="panel-body">
                    <?php if (empty($ip)): ?>
                        <div class="alert alert-warning">กรุณาระบุที่อยู่ IP</div>
                    <?php else: ?>
                        <div class="row">
                            <div class="col-md-12">
                                <p><strong>IP:</strong> <?php echo htmlspecialchars($ip); ?></p>
                                <hr>
                                <h4>IP Geolocation (External Service)</h4>
                                <div id="ip-info" class="well">
                                    กำลังดึงข้อมูล...
                                </div>
                            </div>
                        </div>

                        <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const ip = '<?php echo $ip; ?>';
                            
                            // Check for local/reserved IP addresses
                            const isReserved = ip === '127.0.0.1' || ip === '::1' || ip.startsWith('192.168.') || ip.startsWith('10.') || ip.startsWith('172.16.');
                            
                            if (isReserved) {
                                document.getElementById('ip-info').innerHTML = '<ul class="list-unstyled" style="line-height: 2;"><li><strong>สถานะ:</strong> ที่อยู่ IP ภายในเครือข่าย หรือ Localhost (Reserved IP)</li><li><strong>ข้อมูล:</strong> ไม่สามารถระบุพิกัดทางภูมิศาสตร์ได้</li></ul>';
                                return;
                            }

                            fetch(`https://ipapi.co/${ip}/json/`)
                                .then(response => response.json())
                                .then(data => {
                                    let html = '<ul class="list-unstyled" style="line-height: 2;">';
                                    if (data.error) {
                                        if (data.reason === 'Reserved IP Address') {
                                            html += '<li><strong>สถานะ:</strong> ที่อยู่ IP ภายในเครือข่าย หรือ Localhost (Reserved IP)</li>';
                                            html += '<li><strong>ข้อมูล:</strong> ไม่สามารถระบุพิกัดทางภูมิศาสตร์ได้</li>';
                                        } else {
                                            html += `<li><strong>Error:</strong> ${data.reason}</li>`;
                                        }
                                    } else {
                                        html += `<li><strong>City:</strong> ${data.city || 'N/A'}</li>`;
                                        html += `<li><strong>Region:</strong> ${data.region || 'N/A'}</li>`;
                                        html += `<li><strong>Country:</strong> ${data.country_name || 'N/A'}</li>`;
                                        html += `<li><strong>ISP:</strong> ${data.org || 'N/A'}</li>`;
                                        html += `<li><strong>Timezone:</strong> ${data.timezone || 'N/A'}</li>`;
                                    }
                                    html += '</ul>';
                                    document.getElementById('ip-info').innerHTML = html;
                                })
                                .catch(err => {
                                    document.getElementById('ip-info').innerHTML = '<span class="text-danger">ไม่สามารถดึงข้อมูลได้ในขณะนี้</span>';
                                });
                        });
                        </script>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $path . 'includes/footer.php'; ?>
