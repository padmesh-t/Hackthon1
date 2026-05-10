<?php
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/schema/install.php';

install(); // auto-create tables & seed admin

// ── Routing ──
$page = $_GET['page'] ?? (uid() ? 'dashboard' : 'login');
$action = $_POST['action'] ?? '';

// ── Handle POST actions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action) {
    $actionFile = __DIR__ . "/actions/{$action}.php";
    if (file_exists($actionFile)) {
        require $actionFile;
        exit; // action file should redirect and exit
    }
}

$flash = getFlash();
$u = user();

// ── Auth pages (no sidebar) ──
// ── Auth pages (no sidebar) ──
if (in_array($page, ['login', 'register'])) {
    require __DIR__ . '/templates/header_auth.php';
    require __DIR__ . "/pages/{$page}.php";
    require __DIR__ . '/templates/footer_auth.php';
    exit;
}

// ── Main app layout ──
requireLogin();
require __DIR__ . '/templates/header.php';
$pageFile = __DIR__ . "/pages/{$page}.php";
if (file_exists($pageFile)) {
    require $pageFile;
} else {
    echo '<div class="card" style="text-align:center;padding:50px">Page not found.</div>';
}
require __DIR__ . '/templates/footer.php';
