<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
ssm_require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ssm_verify_csrf($_POST['csrf'] ?? null)) {
    http_response_code(403);
    exit('Forbidden');
}
$data = ssm_schedule_data();
$payload = json_encode([
    'app' => 'SuperSmile Dental Clinic',
    'type' => 'doctor-schedule-backup',
    'exported_at' => date(DATE_ATOM),
    'data' => $data,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="supersmile-schedule-backup-' . date('Ymd-His') . '.json"');
header('Cache-Control: no-store');
echo $payload;
