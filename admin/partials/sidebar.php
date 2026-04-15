<aside class="sidebar">
    <div class="sidebar-section">
        <p class="sidebar-label">Admin Panel</p>
        <a href="<?= SITE_URL ?>/admin/dashboard.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='dashboard.php'?'active':'' ?>">
            <i data-lucide="layout-dashboard"></i> Dashboard
        </a>
        <a href="<?= SITE_URL ?>/admin/listings.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='listings.php'?'active':'' ?>">
            <i data-lucide="home"></i> Listings
        </a>
        <a href="<?= SITE_URL ?>/admin/users.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='users.php'?'active':'' ?>">
            <i data-lucide="users"></i> Users
        </a>
        <a href="<?= SITE_URL ?>/admin/inquiries.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='inquiries.php'?'active':'' ?>">
            <i data-lucide="message-circle"></i> Inquiries
        </a>
    </div>
    <div class="sidebar-section" style="margin-top:16px;">
        <p class="sidebar-label">Quick Links</p>
        <a href="<?= SITE_URL ?>/listings/create.php" class="sidebar-link">
            <i data-lucide="plus-circle"></i> Add Listing
        </a>
        <a href="<?= SITE_URL ?>/index.php" class="sidebar-link">
            <i data-lucide="globe"></i> View Site
        </a>
    </div>
    <div class="sidebar-section" style="margin-top:16px;">
        <p class="sidebar-label">Account</p>
        <a href="<?= SITE_URL ?>/auth/profile.php" class="sidebar-link"><i data-lucide="user"></i> Profile</a>
        <a href="<?= SITE_URL ?>/auth/logout.php" class="sidebar-link text-danger"><i data-lucide="log-out"></i> Logout</a>
    </div>
</aside>
