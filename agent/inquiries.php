<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
if (!hasRole('agent', 'admin')) redirect('/index.php');

$user      = currentUser();
$db        = getDB();
$filterLid = (int)($_GET['listing'] ?? 0);

$sql = "SELECT iq.*, l.title AS listing_title, l.id AS listing_id
        FROM inquiries iq
        JOIN listings l ON l.id = iq.listing_id
        WHERE l.agent_id = ?";
$params = [$user['id']];

if ($filterLid) {
    $sql     .= ' AND iq.listing_id = ?';
    $params[] = $filterLid;
}
$sql .= ' ORDER BY iq.created_at DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$inquiries = $stmt->fetchAll();

// My listings for filter dropdown
$myListings = $db->prepare('SELECT id, title FROM listings WHERE agent_id = ? ORDER BY title');
$myListings->execute([$user['id']]);
$myListings = $myListings->fetchAll();

$pageTitle = 'My Inquiries — EstateHub';
include __DIR__ . '/../includes/header.php';
?>

<div style="display:flex;min-height:calc(100vh - 68px);">
    <aside class="sidebar">
        <div class="sidebar-section">
            <p class="sidebar-label">Agent Panel</p>
            <a href="<?= SITE_URL ?>/agent/dashboard.php" class="sidebar-link">
                <i data-lucide="layout-dashboard"></i> Dashboard
            </a>
            <a href="<?= SITE_URL ?>/listings/create.php" class="sidebar-link">
                <i data-lucide="plus-circle"></i> Add Listing
            </a>
            <a href="<?= SITE_URL ?>/agent/inquiries.php" class="sidebar-link active">
                <i data-lucide="message-circle"></i> Inquiries
            </a>
        </div>
        <div class="sidebar-section" style="margin-top:16px;">
            <p class="sidebar-label">Account</p>
            <a href="<?= SITE_URL ?>/auth/profile.php" class="sidebar-link"><i data-lucide="user"></i> Profile</a>
            <a href="<?= SITE_URL ?>/auth/logout.php" class="sidebar-link text-danger"><i data-lucide="log-out"></i> Logout</a>
        </div>
    </aside>

    <main style="flex:1;padding:32px;overflow:auto;">
        <div class="dashboard-header">
            <h1>Inquiries</h1>
            <p>Messages from potential buyers and renters</p>
        </div>

        <!-- Filter by listing -->
        <div style="margin-bottom:20px;display:flex;gap:10px;align-items:center;">
            <select class="form-control" style="max-width:320px;"
                    onchange="window.location='<?= SITE_URL ?>/agent/inquiries.php'+(this.value?'?listing='+this.value:'')">
                <option value="">All Listings</option>
                <?php foreach ($myListings as $ml): ?>
                <option value="<?= (int)$ml['id'] ?>" <?= $filterLid===$ml['id']?'selected':'' ?>>
                    <?= e($ml['title']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <span class="text-sm text-gray"><?= count($inquiries) ?> inquir<?= count($inquiries)===1?'y':'ies' ?></span>
        </div>

        <?php if ($inquiries): ?>
        <div style="display:flex;flex-direction:column;gap:14px;">
            <?php foreach ($inquiries as $iq): ?>
            <div class="listing-detail-card" style="padding:20px;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
                    <div>
                        <p class="font-semibold" style="margin-bottom:2px;"><?= e($iq['sender_name']) ?></p>
                        <a href="mailto:<?= e($iq['sender_email']) ?>" style="color:var(--blue);font-size:.875rem;">
                            <?= e($iq['sender_email']) ?>
                        </a>
                    </div>
                    <div style="text-align:right;">
                        <a href="<?= SITE_URL ?>/listings/view.php?id=<?= (int)$iq['listing_id'] ?>"
                           style="font-size:.875rem;color:var(--terra);font-weight:600;">
                            <?= e($iq['listing_title']) ?>
                        </a>
                        <p class="text-xs text-gray mt-1"><?= date('M j, Y — g:i A', strtotime($iq['created_at'])) ?></p>
                    </div>
                </div>
                <p style="margin-top:14px;padding-top:14px;border-top:1px solid var(--gray-100);font-size:.9rem;color:var(--gray-700);line-height:1.7;">
                    <?= nl2br(e($iq['message'])) ?>
                </p>
                <div style="margin-top:12px;">
                    <a href="mailto:<?= e($iq['sender_email']) ?>?subject=Re: <?= urlencode($iq['listing_title']) ?>"
                       class="btn btn-outline btn-sm">
                        <i data-lucide="reply"></i> Reply via Email
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i data-lucide="message-circle"></i>
            <h3>No inquiries yet</h3>
            <p>Inquiries from potential buyers will appear here.</p>
        </div>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
