<?php
require __DIR__ . '/../config/db.php';
unset($_SESSION['admin']);
session_regenerate_id(true);
header('Location: login.php');
exit;
