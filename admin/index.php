<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
ssm_require_admin();

$weekdays = ssm_weekdays();
$error = '';
$notice = '';

if (isset($_GET['welcome'])) {
    $notice = 'สร้างระบบ Admin สำเร็จแล้ว เริ่มเพิ่มตารางหมอได้เลย';
}
if (isset($_GET['saved'])) $notice = 'บันทึกตารางหมอเรียบร้อยแล้ว';
if (isset($_GET['deleted'])) $notice = 'ลบรายการเรียบร้อยแล้ว';
if (isset($_GET['toggled'])) $notice = 'เปลี่ยนสถานะเรียบร้อยแล้ว';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!ssm_verify_csrf($_POST['csrf'] ?? null)) {
        $error = 'เซสชันหมดอายุ กรุณารีเฟรชหน้าแล้วลองใหม่';
    } else {
        $action = (string) ($_POST['action'] ?? 'save');
        try {
            if ($action === 'delete') {
                $id = (string) ($_POST['id'] ?? '');
                ssm_storage_update('schedules', ['items' => [], 'updated_at' => null], static function (array $data) use ($id): array {
                    $data['items'] = array_values(array_filter($data['items'] ?? [], static fn($item): bool => (string) ($item['id'] ?? '') !== $id));
                    $data['updated_at'] = date(DATE_ATOM);
                    return $data;
                });
                header('Location: index.php?deleted=1');
                exit;
            }

            if ($action === 'toggle') {
                $id = (string) ($_POST['id'] ?? '');
                ssm_storage_update('schedules', ['items' => [], 'updated_at' => null], static function (array $data) use ($id): array {
                    foreach ($data['items'] as &$item) {
                        if ((string) ($item['id'] ?? '') === $id) {
                            $item['active'] = !((bool) ($item['active'] ?? false));
                            $item['updated_at'] = date(DATE_ATOM);
                            break;
                        }
                    }
                    unset($item);
                    $data['updated_at'] = date(DATE_ATOM);
                    return $data;
                });
                header('Location: index.php?toggled=1');
                exit;
            }

            $id = trim((string) ($_POST['id'] ?? ''));
            $doctorName = trim((string) ($_POST['doctor_name'] ?? ''));
            $weekday = (int) ($_POST['weekday'] ?? 0);
            $startTime = trim((string) ($_POST['start_time'] ?? ''));
            $endTime = trim((string) ($_POST['end_time'] ?? ''));
            $note = trim((string) ($_POST['note'] ?? ''));
            $active = isset($_POST['active']);

            if ($doctorName === '' || ssm_strlen($doctorName) > 100) {
                throw new RuntimeException('กรุณาระบุชื่อหมอไม่เกิน 100 ตัวอักษร');
            }
            if (!isset($weekdays[$weekday])) {
                throw new RuntimeException('กรุณาเลือกวันทำงาน');
            }
            if (!ssm_valid_time($startTime) || !ssm_valid_time($endTime) || $startTime >= $endTime) {
                throw new RuntimeException('เวลาเริ่มและเวลาสิ้นสุดไม่ถูกต้อง');
            }
            if (ssm_strlen($note) > 200) {
                throw new RuntimeException('หมายเหตุต้องไม่เกิน 200 ตัวอักษร');
            }

            ssm_storage_update('schedules', ['items' => [], 'updated_at' => null], static function (array $data) use ($id, $doctorName, $weekday, $startTime, $endTime, $note, $active): array {
                $items = is_array($data['items'] ?? null) ? $data['items'] : [];
                $now = date(DATE_ATOM);
                $found = false;
                foreach ($items as &$item) {
                    if ($id !== '' && (string) ($item['id'] ?? '') === $id) {
                        $item['doctor_name'] = $doctorName;
                        $item['weekday'] = $weekday;
                        $item['start_time'] = $startTime;
                        $item['end_time'] = $endTime;
                        $item['note'] = $note;
                        $item['active'] = $active;
                        $item['updated_at'] = $now;
                        $found = true;
                        break;
                    }
                }
                unset($item);
                if (!$found) {
                    $items[] = [
                        'id' => bin2hex(random_bytes(8)),
                        'doctor_name' => $doctorName,
                        'weekday' => $weekday,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'note' => $note,
                        'active' => $active,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                ssm_sort_schedules($items);
                $data['items'] = $items;
                $data['updated_at'] = $now;
                return $data;
            });
            header('Location: index.php?saved=1');
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$data = ssm_schedule_data();
$items = is_array($data['items'] ?? null) ? $data['items'] : [];
ssm_sort_schedules($items);
$activeCount = count(array_filter($items, static fn(array $item): bool => (bool) ($item['active'] ?? false)));
$doctorNames = array_values(array_unique(array_filter(array_map(static fn(array $item): string => trim((string) ($item['doctor_name'] ?? '')), $items))));
sort($doctorNames, SORT_NATURAL | SORT_FLAG_CASE);

$editItem = null;
$editId = (string) ($_GET['edit'] ?? '');
if ($editId !== '') {
    foreach ($items as $item) {
        if ((string) ($item['id'] ?? '') === $editId) {
            $editItem = $item;
            break;
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
<title>Admin Dashboard | SuperSmile</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Mali:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css">
</head>
<body class="admin-page">
<header class="admin-topbar">
<a class="admin-brand" href="index.php"><img src="../images/LogoSuperSmile.webp" alt="" width="52" height="52"><span><strong>SuperSmile</strong><small>Admin Panel</small></span></a>
<div class="admin-top-actions"><a class="btn btn-light" href="../pages/dentists.html#schedule" target="_blank" rel="noopener">ดูหน้าตารางหมอ ↗</a><a class="btn btn-light" href="change-password.php">เปลี่ยนรหัสผ่าน</a><form method="post" action="backup.php"><input type="hidden" name="csrf" value="<?= ssm_e(ssm_csrf_token()) ?>"><button class="btn btn-light" type="submit">สำรองตาราง</button></form><form method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= ssm_e(ssm_csrf_token()) ?>"><button class="btn btn-danger-ghost" type="submit">ออกจากระบบ</button></form></div>
</header>

<main class="admin-shell">
<section class="admin-hero">
<div><span>ADMIN DASHBOARD</span><h1>จัดการตารางหมอ</h1><p>แก้ไขตารางประจำสัปดาห์ แล้วข้อมูลฝั่งเว็บไซต์จะอัปเดตจากระบบเดียวกัน</p></div>
<div class="stats-grid"><div class="stat-card"><strong><?= count($items) ?></strong><span>รายการทั้งหมด</span></div><div class="stat-card"><strong><?= $activeCount ?></strong><span>กำลังแสดง</span></div><div class="stat-card"><strong><?= count($doctorNames) ?></strong><span>ทันตแพทย์ในตาราง</span></div></div>
</section>

<?php if ($notice !== ''): ?><div class="alert alert-success" role="status"><?= ssm_e($notice) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-error" role="alert"><?= ssm_e($error) ?></div><?php endif; ?>

<div class="admin-layout">
<section class="panel schedule-list-panel">
<div class="panel-heading"><div><span>WEEKLY SCHEDULE</span><h2>ตารางปัจจุบัน</h2></div><a href="index.php#schedule-form" class="btn btn-primary btn-small">+ เพิ่มตาราง</a></div>
<?php if (!$items): ?>
<div class="empty-state"><strong>ยังไม่มีตารางหมอ</strong><p>เพิ่มรายการแรกจากแบบฟอร์มด้านข้างได้เลย</p></div>
<?php else: ?>
<div class="schedule-admin-list">
<?php foreach ($items as $item): ?>
<article class="schedule-admin-item <?= !($item['active'] ?? false) ? 'is-inactive' : '' ?>">
<div class="schedule-day-badge"><span><?= ssm_e($weekdays[(int) ($item['weekday'] ?? 0)] ?? '-') ?></span></div>
<div class="schedule-main"><h3><?= ssm_e((string) ($item['doctor_name'] ?? '')) ?></h3><p><strong><?= ssm_e((string) ($item['start_time'] ?? '')) ?>–<?= ssm_e((string) ($item['end_time'] ?? '')) ?> น.</strong><?php if (!empty($item['note'])): ?> · <?= ssm_e((string) $item['note']) ?><?php endif; ?></p><span class="status-pill <?= ($item['active'] ?? false) ? 'active' : 'inactive' ?>"><?= ($item['active'] ?? false) ? 'กำลังแสดง' : 'ซ่อนอยู่' ?></span></div>
<div class="item-actions"><a class="icon-btn" href="?edit=<?= urlencode((string) ($item['id'] ?? '')) ?>#schedule-form">แก้ไข</a><form method="post"><input type="hidden" name="csrf" value="<?= ssm_e(ssm_csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= ssm_e((string) ($item['id'] ?? '')) ?>"><button class="icon-btn" type="submit"><?= ($item['active'] ?? false) ? 'ซ่อน' : 'แสดง' ?></button></form><form method="post" onsubmit="return confirm('ลบรายการนี้หรือไม่?')"><input type="hidden" name="csrf" value="<?= ssm_e(ssm_csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= ssm_e((string) ($item['id'] ?? '')) ?>"><button class="icon-btn danger" type="submit">ลบ</button></form></div>
</article>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>

<aside class="panel form-panel" id="schedule-form">
<div class="panel-heading"><div><span><?= $editItem ? 'EDIT SCHEDULE' : 'NEW SCHEDULE' ?></span><h2><?= $editItem ? 'แก้ไขตารางหมอ' : 'เพิ่มตารางหมอ' ?></h2></div><?php if ($editItem): ?><a href="index.php#schedule-form" class="text-link">ยกเลิกแก้ไข</a><?php endif; ?></div>
<form method="post" class="admin-form schedule-form">
<input type="hidden" name="csrf" value="<?= ssm_e(ssm_csrf_token()) ?>">
<input type="hidden" name="action" value="save">
<input type="hidden" name="id" value="<?= ssm_e((string) ($editItem['id'] ?? '')) ?>">
<label>ชื่อทันตแพทย์<input name="doctor_name" list="doctor-list" required maxlength="100" value="<?= ssm_e((string) ($editItem['doctor_name'] ?? '')) ?>" placeholder="เช่น หมออาร์ท"><datalist id="doctor-list"><option value="หมออาร์ท"><?php foreach ($doctorNames as $name): ?><option value="<?= ssm_e($name) ?>"><?php endforeach; ?></datalist></label>
<label>วันทำงาน<select name="weekday" required><option value="">เลือกวัน</option><?php foreach ($weekdays as $number => $label): ?><option value="<?= $number ?>" <?= (int) ($editItem['weekday'] ?? 0) === $number ? 'selected' : '' ?>><?= ssm_e($label) ?></option><?php endforeach; ?></select></label>
<div class="form-row"><label>เวลาเริ่ม<input type="time" name="start_time" required value="<?= ssm_e((string) ($editItem['start_time'] ?? '10:00')) ?>"></label><label>เวลาสิ้นสุด<input type="time" name="end_time" required value="<?= ssm_e((string) ($editItem['end_time'] ?? '19:00')) ?>"></label></div>
<label>หมายเหตุ <small>(ไม่บังคับ)</small><input name="note" maxlength="200" value="<?= ssm_e((string) ($editItem['note'] ?? '')) ?>" placeholder="เช่น เฉพาะช่วงบ่าย / กรุณานัดล่วงหน้า"></label>
<label class="toggle-row"><input type="checkbox" name="active" <?= !isset($editItem['active']) || ($editItem['active'] ?? false) ? 'checked' : '' ?>><span><strong>แสดงบนหน้าเว็บไซต์</strong><small>ปิดไว้ได้ถ้ายังไม่ต้องการให้ลูกค้าเห็น</small></span></label>
<button class="btn btn-primary" type="submit"><?= $editItem ? 'บันทึกการแก้ไข' : 'เพิ่มตารางหมอ' ?></button>
</form>
</aside>
</div>

<section class="admin-help panel"><h2>วิธีใช้งาน</h2><ol><li>เพิ่มวันและเวลาที่หมอเข้าคลินิกเป็นรายวัน</li><li>ถ้าหมอคนเดิมเข้า 3 วัน ให้เพิ่ม 3 รายการ</li><li>กด “ซ่อน” เมื่อไม่ต้องการให้รายการนั้นขึ้นหน้าเว็บ โดยข้อมูลยังไม่ถูกลบ</li><li>ตารางฝั่งลูกค้าอยู่ที่หน้า “ทันตแพทย์และทีมงาน” หัวข้อ “ตารางทันตแพทย์”</li></ol><p><strong>หมายเหตุ:</strong> ระบบนี้เป็นตารางประจำสัปดาห์ หากตารางมีการเปลี่ยนเฉพาะวัน ควรซ่อนหรือแก้รายการก่อนวันดังกล่าว</p></section>
</main>
</body>
</html>
