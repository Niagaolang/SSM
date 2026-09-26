<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
ssm_require_admin();

$error = '';
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = (string) ($_POST['current_password'] ?? '');
    $new = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    if (!ssm_verify_csrf($_POST['csrf'] ?? null)) {
        $error = 'เซสชันหมดอายุ กรุณาลองใหม่';
    } elseif (strlen($new) < 10) {
        $error = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 10 ตัวอักษร';
    } elseif ($new !== $confirm) {
        $error = 'ยืนยันรหัสผ่านใหม่ไม่ตรงกัน';
    } elseif (!ssm_change_password((string) $_SESSION['admin_user'], $current, $new)) {
        $error = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
    } else {
        $notice = 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว';
    }
}
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>เปลี่ยนรหัสผ่าน | SuperSmile Admin</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Mali:wght@400;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="admin.css"></head><body class="auth-page"><main class="auth-card"><a class="admin-brand" href="index.php"><img src="../images/LogoSuperSmile.webp" alt="" width="56" height="56"><span><strong>SuperSmile</strong><small>Admin</small></span></a><div class="auth-heading"><span>SECURITY</span><h1>เปลี่ยนรหัสผ่าน</h1><p>แนะนำให้เปลี่ยนรหัสผ่านเป็นระยะและไม่ใช้ซ้ำกับบริการอื่น</p></div><?php if ($error): ?><div class="alert alert-error"><?= ssm_e($error) ?></div><?php endif; ?><?php if ($notice): ?><div class="alert alert-success"><?= ssm_e($notice) ?></div><?php endif; ?><form method="post" class="admin-form"><input type="hidden" name="csrf" value="<?= ssm_e(ssm_csrf_token()) ?>"><label>รหัสผ่านปัจจุบัน<input type="password" name="current_password" required autocomplete="current-password"></label><label>รหัสผ่านใหม่<input type="password" name="new_password" required minlength="10" autocomplete="new-password"></label><label>ยืนยันรหัสผ่านใหม่<input type="password" name="confirm_password" required minlength="10" autocomplete="new-password"></label><button class="btn btn-primary" type="submit">บันทึกรหัสผ่านใหม่</button></form><a class="auth-back" href="index.php">← กลับหน้าหลังบ้าน</a></main></body></html>
