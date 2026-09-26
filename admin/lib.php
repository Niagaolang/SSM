<?php
declare(strict_types=1);

const SSM_ROOT = __DIR__ . '/..';
const SSM_STORAGE = SSM_ROOT . '/storage';
const SSM_DATA_PREFIX = "<?php http_response_code(403); exit; ?>\n";

function ssm_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' https://fonts.googleapis.com 'unsafe-inline'; font-src https://fonts.gstatic.com; script-src 'self' 'unsafe-inline'; form-action 'self'; frame-ancestors 'self'; base-uri 'self'");
}

function ssm_client_key(string $username = ''): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return hash('sha256', strtolower(trim($username)) . '|' . $ip);
}

function ssm_login_limit_status(string $username): array
{
    $key = ssm_client_key($username);
    $now = time();
    $window = 15 * 60;
    $max = 5;
    $data = ssm_storage_read('login_attempts', ['attempts' => []]);
    $entry = is_array($data['attempts'][$key] ?? null) ? $data['attempts'][$key] : ['times' => []];
    $times = array_values(array_filter(array_map('intval', $entry['times'] ?? []), static fn(int $t): bool => $t > $now - $window));
    $blocked = count($times) >= $max;
    $retry = $blocked ? max(1, $window - ($now - min($times))) : 0;
    return ['blocked' => $blocked, 'retry_after' => $retry, 'count' => count($times)];
}

function ssm_record_failed_login(string $username): void
{
    $key = ssm_client_key($username);
    $now = time();
    $window = 15 * 60;
    ssm_storage_update('login_attempts', ['attempts' => []], static function (array $data) use ($key, $now, $window): array {
        $attempts = is_array($data['attempts'] ?? null) ? $data['attempts'] : [];
        foreach ($attempts as $k => $entry) {
            $times = array_values(array_filter(array_map('intval', is_array($entry) ? ($entry['times'] ?? []) : []), static fn(int $t): bool => $t > $now - $window));
            if ($times) $attempts[$k] = ['times' => $times]; else unset($attempts[$k]);
        }
        $times = array_values(array_filter(array_map('intval', $attempts[$key]['times'] ?? []), static fn(int $t): bool => $t > $now - $window));
        $times[] = $now;
        $attempts[$key] = ['times' => $times];
        $data['attempts'] = $attempts;
        return $data;
    });
}

function ssm_clear_login_attempts(string $username): void
{
    $key = ssm_client_key($username);
    ssm_storage_update('login_attempts', ['attempts' => []], static function (array $data) use ($key): array {
        if (isset($data['attempts'][$key])) unset($data['attempts'][$key]);
        return $data;
    });
}

function ssm_change_password(string $username, string $currentPassword, string $newPassword): bool
{
    $changed = false;
    ssm_storage_update('users', ['users' => []], static function (array $data) use ($username, $currentPassword, $newPassword, &$changed): array {
        foreach ($data['users'] ?? [] as &$user) {
            if ((string) ($user['username'] ?? '') !== $username) continue;
            $hash = (string) ($user['password_hash'] ?? '');
            if ($hash === '' || !password_verify($currentPassword, $hash)) return $data;
            $user['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            $user['password_changed_at'] = date(DATE_ATOM);
            $changed = true;
            break;
        }
        unset($user);
        return $data;
    });
    return $changed;
}

function ssm_boot_session(): void
{
    ssm_security_headers();
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('ssm_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    $now = time();
    if (isset($_SESSION['last_activity']) && $now - (int) $_SESSION['last_activity'] > 3600) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['last_activity'] = $now;
}

function ssm_ensure_storage(): void
{
    if (!is_dir(SSM_STORAGE) && !mkdir(SSM_STORAGE, 0750, true) && !is_dir(SSM_STORAGE)) {
        throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์ storage ได้');
    }
}

function ssm_storage_path(string $name): string
{
    if (!preg_match('/^[a-z0-9_-]+$/i', $name)) {
        throw new InvalidArgumentException('ชื่อไฟล์ storage ไม่ถูกต้อง');
    }
    return SSM_STORAGE . '/' . $name . '.php';
}

function ssm_storage_read_unlocked(string $name, array $default): array
{
    ssm_ensure_storage();
    $path = ssm_storage_path($name);
    if (!is_file($path)) {
        return $default;
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        return $default;
    }

    $pos = strpos($raw, "\n");
    if ($pos !== false) {
        $raw = substr($raw, $pos + 1);
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $default;
}

function ssm_storage_read(string $name, array $default): array
{
    ssm_ensure_storage();
    $lockPath = SSM_STORAGE . '/' . $name . '.lock';
    $lock = fopen($lockPath, 'c');
    if ($lock === false) {
        return ssm_storage_read_unlocked($name, $default);
    }
    flock($lock, LOCK_SH);
    try {
        return ssm_storage_read_unlocked($name, $default);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function ssm_storage_write_unlocked(string $name, array $data): void
{
    ssm_ensure_storage();
    $path = ssm_storage_path($name);
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false) {
        throw new RuntimeException('ไม่สามารถแปลงข้อมูลเป็น JSON ได้');
    }

    $tmp = $path . '.tmp.' . bin2hex(random_bytes(4));
    $payload = SSM_DATA_PREFIX . $json . "\n";
    if (file_put_contents($tmp, $payload, LOCK_EX) === false) {
        throw new RuntimeException('ไม่สามารถบันทึกข้อมูลได้ กรุณาตรวจสิทธิ์โฟลเดอร์ storage');
    }
    @chmod($tmp, 0640);
    if (!rename($tmp, $path)) {
        @unlink($tmp);
        throw new RuntimeException('ไม่สามารถอัปเดตข้อมูลได้');
    }
}

function ssm_storage_update(string $name, array $default, callable $callback): array
{
    ssm_ensure_storage();
    $lockPath = SSM_STORAGE . '/' . $name . '.lock';
    $lock = fopen($lockPath, 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) {
        throw new RuntimeException('ไม่สามารถล็อกข้อมูลเพื่อบันทึกได้');
    }

    try {
        $current = ssm_storage_read_unlocked($name, $default);
        $next = $callback($current);
        if (!is_array($next)) {
            throw new RuntimeException('ข้อมูลใหม่ไม่ถูกต้อง');
        }
        ssm_storage_write_unlocked($name, $next);
        return $next;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function ssm_users(): array
{
    return ssm_storage_read('users', ['users' => []]);
}

function ssm_has_admin(): bool
{
    $users = ssm_users();
    return !empty($users['users']);
}

function ssm_csrf_token(): string
{
    ssm_boot_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(24));
    }
    return (string) $_SESSION['csrf'];
}

function ssm_verify_csrf(?string $token): bool
{
    ssm_boot_session();
    return is_string($token) && isset($_SESSION['csrf']) && hash_equals((string) $_SESSION['csrf'], $token);
}

function ssm_is_logged_in(): bool
{
    ssm_boot_session();
    return isset($_SESSION['admin_user']) && is_string($_SESSION['admin_user']);
}

function ssm_require_admin(): void
{
    if (!ssm_has_admin()) {
        header('Location: setup.php');
        exit;
    }
    if (!ssm_is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function ssm_login(string $username, string $password): bool
{
    $data = ssm_users();
    foreach (($data['users'] ?? []) as $user) {
        if (!is_array($user)) {
            continue;
        }
        $storedUsername = (string) ($user['username'] ?? '');
        $hash = (string) ($user['password_hash'] ?? '');
        if ($storedUsername !== '' && hash_equals($storedUsername, $username) && $hash !== '' && password_verify($password, $hash)) {
            ssm_boot_session();
            session_regenerate_id(true);
            $_SESSION['admin_user'] = $storedUsername;
            $_SESSION['csrf'] = bin2hex(random_bytes(24));
            ssm_clear_login_attempts($storedUsername);
            return true;
        }
    }
    return false;
}

function ssm_logout(): void
{
    ssm_boot_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

function ssm_schedule_data(): array
{
    return ssm_storage_read('schedules', ['items' => [], 'updated_at' => null]);
}

function ssm_weekdays(): array
{
    return [
        1 => 'จันทร์',
        2 => 'อังคาร',
        3 => 'พุธ',
        4 => 'พฤหัสบดี',
        5 => 'ศุกร์',
        6 => 'เสาร์',
        7 => 'อาทิตย์',
    ];
}

function ssm_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function ssm_valid_time(string $time): bool
{
    return (bool) preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time);
}

function ssm_strlen(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function ssm_sort_schedules(array &$items): void
{
    usort($items, static function (array $a, array $b): int {
        $day = ((int) ($a['weekday'] ?? 99)) <=> ((int) ($b['weekday'] ?? 99));
        if ($day !== 0) return $day;
        $time = strcmp((string) ($a['start_time'] ?? ''), (string) ($b['start_time'] ?? ''));
        if ($time !== 0) return $time;
        return strcmp((string) ($a['doctor_name'] ?? ''), (string) ($b['doctor_name'] ?? ''));
    });
}

