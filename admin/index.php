<?php
require __DIR__ . '/../config/db.php';

if (!empty($_SESSION['admin']['id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;