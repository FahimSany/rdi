<?php
header('Content-Type: text/html; charset=utf-8');
$config = require __DIR__ . '/../config/database.php';
$remote = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
$isLocal = in_array($remote, array('127.0.0.1','::1'), true) || PHP_SAPI === 'cli-server' || PHP_SAPI === 'cli';
if (!$isLocal) {
    http_response_code(403);
    echo '<h2>Admin repair is disabled for remote requests.</h2><p>For security, run this page from localhost/XAMPP only, then delete it.</p>';
    exit;
}

$message='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        $dsn='mysql:host='.$config['host'].';port='.$config['port'].';dbname='.$config['name'].';charset='.$config['charset'];
        $pdo=new PDO($dsn,$config['user'],$config['pass'],array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
        $adminEmail=rdi_env('RDI_SEED_ADMIN_EMAIL');$adminPass=rdi_env('RDI_SEED_ADMIN_PASSWORD');if(!$adminEmail||!$adminPass)throw new RuntimeException('Admin repair credentials are missing from .env.');$hash=password_hash($adminPass,PASSWORD_DEFAULT);
        $st=$pdo->prepare("INSERT INTO users(id,rdi_member_id,name,email,password_hash,role,status,join_date) VALUES(?,?,?,?,?,?,'Active',CURDATE()) ON DUPLICATE KEY UPDATE rdi_member_id=VALUES(rdi_member_id), name=VALUES(name), email=VALUES(email), password_hash=VALUES(password_hash), role=VALUES(role), status='Active', deleted_at=NULL, failed_login_count=0, locked_at=NULL");
        $st->execute(array('u-admin','RDI-ADMIN-0001','RDI Super Admin',$adminEmail,$hash,'super_admin'));
        $message='<div style="padding:12px;background:#d1fae5;color:#065f46;border-radius:8px">Super Admin account repaired from the credentials configured in <code>.env</code>.</div>';
    } catch (Throwable $e) {
        http_response_code(500);
        $message='<div style="padding:12px;background:#fee2e2;color:#991b1b;border-radius:8px">'.htmlspecialchars($e->getMessage()).'</div>';
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>RDI Admin Repair</title><style>body{font-family:Arial,sans-serif;max-width:680px;margin:60px auto;padding:0 20px;line-height:1.6}button{padding:12px 18px;border:0;border-radius:8px;background:#ff5e1a;color:#fff;font-weight:700;cursor:pointer}code{background:#eee;padding:2px 6px;border-radius:4px}</style></head><body>
<h1>RDI Admin Repair</h1>
<p>This localhost-only utility recreates/resets the default Super Admin account without deleting project data.</p>
<?php echo $message; ?>
<form method="post" style="margin-top:20px"><button type="submit">Repair Default Admin</button></form>
<p><b>After success:</b> delete or rename <code>database/repair-admin.php</code>.</p>
</body></html>
