<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';

if (ssm_has_admin()) {
    header('Location: login.php');
    exit;
}

$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if (!ssm_verify_csrf($_POST['csrf'] ?? null)) {
        $error = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
    } elseif (!preg_match('/^[A-Za-z0-9._-]{3,40}$/', $username)) {
        $error = 'ชื่อผู้ใช้ต้องยาว 3–40 ตัว และใช้ A-Z, a-z, 0-9, จุด, ขีดกลาง หรือขีดล่าง';
    } elseif (strlen($password) < 10) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 10 ตัวอักษร';
    } elseif ($password !== $confirm) {
        $error = 'ยืนยันรหัสผ่านไม่ตรงกัน';
    } else {
        try {
            ssm_storage_update('users', ['users' => []], static function (array $data) use ($username, $password): array {
                if (!empty($data['users'])) {
                    return $data;
                }
                $data['users'] = [[
                    'id' => bin2hex(random_bytes(8)),
                    'username' => $username,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'created_at' => date(DATE_ATOM),
                ]];
                return $data;
            });
            ssm_login($username, $password);
            header('Location: index.php?welcome=1');
            exit;
        } catch (Throwable $e) {
            $error = 'สร้างบัญชีไม่สำเร็จ: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>ตั้งค่า Admin | SuperSmile</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Mali:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css">
</head>
<body class="auth-page">
<main class="auth-card">
<a class="admin-brand" href="../index.html"><img src="../images/LogoSuperSmile.webp" alt="" width="56" height="56"><span><strong>SuperSmile</strong><small>Admin</small></span></a>
<div class="auth-heading"><span>FIRST SETUP</span><h1>สร้างบัญชีผู้ดูแล</h1><p>หน้านี้เปิดใช้ได้เฉพาะก่อนสร้าง Admin คนแรกเท่านั้น</p></div>
<?php if ($error !== ''): ?><div class="alert alert-error" role="alert"><?= ssm_e($error) ?></div><?php endif; ?>
<form method="post" class="admin-form" autocomplete="off">
<input type="hidden" name="csrf" value="<?= ssm_e(ssm_csrf_token()) ?>">
<label>ชื่อผู้ใช้<input name="username" value="<?= ssm_e($username) ?>" required minlength="3" maxlength="40" autocomplete="username" placeholder="เช่น supersmile_admin"></label>
<label>รหัสผ่าน<input type="password" name="password" required minlength="10" autocomplete="new-password" placeholder="อย่างน้อย 10 ตัวอักษร"></label>
<label>ยืนยันรหัสผ่าน<input type="password" name="confirm_password" required minlength="10" autocomplete="new-password"></label>
<button class="btn btn-primary" type="submit">สร้าง Admin และเข้าสู่ระบบ</button>
</form>
<p class="auth-note">ควรใช้รหัสผ่านที่ไม่ซ้ำกับบัญชีอื่น และเก็บไว้ในตัวจัดการรหัสผ่าน</p>
</main>
</body>
</html>
