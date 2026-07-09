<?php
require __DIR__ . '/db.php';

if (empty($_SESSION['admin']['id'])) {
    $isAjax = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
           || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
    if ($isAjax) {
        jsonOut(['ok' => false, 'mesej' => 'Sesi tamat. Sila log masuk semula.', 'login' => true], 401);
    }
    header('Location: login.php');
    exit;
}
