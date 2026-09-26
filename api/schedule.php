<?php
declare(strict_types=1);
require __DIR__ . '/../admin/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60, stale-while-revalidate=120');
header('X-Content-Type-Options: nosniff');

try {
    $data = ssm_schedule_data();
    $items = array_values(array_filter(is_array($data['items'] ?? null) ? $data['items'] : [], static fn(array $item): bool => (bool) ($item['active'] ?? false)));
    ssm_sort_schedules($items);

    $publicItems = array_map(static function (array $item): array {
        return [
            'doctor_name' => (string) ($item['doctor_name'] ?? ''),
            'weekday' => (int) ($item['weekday'] ?? 0),
            'start_time' => (string) ($item['start_time'] ?? ''),
            'end_time' => (string) ($item['end_time'] ?? ''),
            'note' => (string) ($item['note'] ?? ''),
        ];
    }, $items);

    echo json_encode([
        'ok' => true,
        'updated_at' => $data['updated_at'] ?? null,
        'items' => $publicItems,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'items' => []], JSON_UNESCAPED_UNICODE);
}
