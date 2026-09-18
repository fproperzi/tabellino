<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && auth_csrf_check($_POST['csrf'] ?? null)) {
    auth_logout();
}
header('Location: login.php');
exit;
