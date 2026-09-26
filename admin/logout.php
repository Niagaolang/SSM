<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ssm_verify_csrf($_POST['csrf'] ?? null)) {
    ssm_logout();
}
header('Location: login.php');
exit;
