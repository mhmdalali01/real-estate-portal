<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
session_destroy();
header('Location: ' . SITE_URL . '/index.php');
exit;
