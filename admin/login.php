<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';

if (!ssm_has_admin()) {
    header('Location: setup.php');
    exit;
}
if (ssm_is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $limit = ssm_login_limit_status($username);
    if (!ssm_verify_csrf($_POST['csrf'] ?? null)) {
        $error = 'เซสชันหมดอายุ กรุณาลองใหม่';
    } elseif ($limit['blocked']) {
        $minutes = max(1, (int) ceil($limit['retry_after'] / 60));
        $error = 'ลองเข้าสู่ระบบหลายครั้งเกินไป กรุณารอประมาณ ' . $minutes . ' นาทีแล้วลองใหม่';
    } elseif (!ssm_login($username, $password)) {
        ssm_record_failed_login($username);
        usleep(350000);
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    } else {
        header('Location: index.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>เข้าสู่ระบบ Admin | SuperSmile</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Mali:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css">
</head>
<body class="auth-page">
<main class="auth-card">
<a class="admin-brand" href="../index.html"><img src="../images/LogoSuperSmile.webp" alt="" width="56" height="56"><span><strong>SuperSmile</strong><small>Admin</small></span></a>
<div class="auth-heading"><span>ADMIN PANEL</span><h1>เข้าสู่ระบบหลังบ้าน</h1><p>สำหรับเจ้าหน้าที่ที่ได้รับสิทธิ์เท่านั้น</p></div>
<?php if ($error !== ''): ?><div class="alert alert-error" role="alert"><?= ssm_e($error) ?></div><?php endif; ?>
<form method="post" class="admin-form">
<input type="hidden" name="csrf" value="<?= ssm_e(ssm_csrf_token()) ?>">
<label>ชื่อผู้ใช้<input name="username" value="<?= ssm_e($username) ?>" required autocomplete="username"></label>
<label>รหัสผ่าน<input type="password" name="password" required autocomplete="current-password"></label>
<button class="btn btn-primary" type="submit">เข้าสู่ระบบ</button>
</form>
<a class="auth-back" href="../index.html">← กลับหน้าเว็บไซต์</a>
</main>
</body>
</html>
