<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
$user = currentUser();
$pageTitle = $pageTitle ?? 'EstateHub — Find Your Dream Home';
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="EstateHub — Browse premium properties, apartments, villas, and offices." />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css" />
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
</head>
<body class="<?= e($bodyClass) ?>">

<!-- ── Navbar ──────────────────────────────────────────────────────────────── -->
<nav class="navbar" id="navbar">
    <div class="container navbar-inner">
        <a href="<?= SITE_URL ?>/index.php" class="brand">
            <span class="brand-icon">🏠</span>
            <span class="brand-text">EstateHub</span>
        </a>

        <button class="nav-toggle" id="navToggle" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>

        <ul class="nav-links" id="navLinks">
            <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
            <li><a href="<?= SITE_URL ?>/listings/search.php">Browse</a></li>
            <?php if ($user): ?>
                <?php if (hasRole('agent', 'admin')): ?>
                    <li><a href="<?= SITE_URL ?>/agent/dashboard.php">Agent Panel</a></li>
                <?php endif; ?>
                <?php if (hasRole('admin')): ?>
                    <li><a href="<?= SITE_URL ?>/admin/dashboard.php">Admin</a></li>
                <?php endif; ?>
                <li class="nav-dropdown">
                    <button class="nav-avatar" id="userMenuBtn">
                        <span class="avatar-initials"><?= e(strtoupper(substr($user['name'], 0, 2))) ?></span>
                        <span><?= e(explode(' ', $user['name'])[0]) ?></span>
                        <i data-lucide="chevron-down" class="icon-sm"></i>
                    </button>
                    <ul class="dropdown-menu" id="userMenu">
                        <li><a href="<?= SITE_URL ?>/auth/profile.php"><i data-lucide="user"></i> Profile</a></li>
                        <li><a href="<?= SITE_URL ?>/listings/favorites.php"><i data-lucide="heart"></i> Favorites</a></li>
                        <li class="dropdown-divider"></li>
                        <li><a href="<?= SITE_URL ?>/auth/logout.php" class="text-danger"><i data-lucide="log-out"></i> Logout</a></li>
                    </ul>
                </li>
            <?php else: ?>
                <li><a href="<?= SITE_URL ?>/auth/login.php" class="btn btn-outline btn-sm">Login</a></li>
                <li><a href="<?= SITE_URL ?>/auth/register.php" class="btn btn-primary btn-sm">Register</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<!-- Flash messages -->
<?php
$success = getFlash('success');
$error   = getFlash('error');
$info    = getFlash('info');
?>
<?php if ($success || $error || $info): ?>
<div class="flash-container">
    <?php if ($success): ?>
        <div class="alert alert-success"><i data-lucide="check-circle"></i> <?= e($success) ?> <button class="alert-close">×</button></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><i data-lucide="x-circle"></i> <?= e($error) ?> <button class="alert-close">×</button></div>
    <?php endif; ?>
    <?php if ($info): ?>
        <div class="alert alert-info"><i data-lucide="info"></i> <?= e($info) ?> <button class="alert-close">×</button></div>
    <?php endif; ?>
</div>
<?php endif; ?>
