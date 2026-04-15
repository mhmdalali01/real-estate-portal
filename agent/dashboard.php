<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
if (!hasRole('agent', 'admin')) redirect('/index.php');

$user = currentUser();
$db   = getDB();

// Stats
$stats = $db->prepare(
    "SELECT
        COUNT(*) AS total_listings,
        SUM(status='active') AS active_listings,
        (SELECT COUNT(*) FROM inquiries iq JOIN listings l ON l.id = iq.listing_id WHERE l.agent_id = ?) AS total_inquiries,
        (SELECT COUNT(*) FROM favorites f JOIN listings l ON l.id = f.listing_id WHERE l.agent_id = ?) AS total_favorites
     FROM listings WHERE agent_id = ?"
);
$stats->execute([$user['id'], $user['id'], $user['id']]);
$s = $stats->fetch();

// My listings
$listStmt = $db->prepare(
    "SELECT l.*,
            (SELECT image_path FROM listing_images li WHERE li.listing_id = l.id ORDER BY li.id LIMIT 1) AS primary_image,
            (SELECT COUNT(*) FROM inquiries iq WHERE iq.listing_id = l.id) AS inquiry_count
     FROM listings l
     WHERE l.agent_id = ?
     ORDER BY l.created_at DESC"
);
$listStmt->execute([$user['id']]);
$listings = $listStmt->fetchAll();

$pageTitle = 'Agent Dashboard — EstateHub';
include __DIR__ . '/../includes/header.php';
?>

<div style="display:flex;min-height:calc(100vh - 68px);">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-section">
            <p class="sidebar-label">Agent Panel</p>
            <a href="<?= SITE_URL ?>/agent/dashboard.php" class="sidebar-link active">
                <i data-lucide="layout-dashboard"></i> Dashboard
            </a>
            <a href="<?= SITE_URL ?>/listings/create.php" class="sidebar-link">
                <i data-lucide="plus-circle"></i> Add Listing
            </a>
            <a href="<?= SITE_URL ?>/agent/inquiries.php" class="sidebar-link">
                <i data-lucide="message-circle"></i> Inquiries
                <?php if ($s['total_inquiries'] > 0): ?>
                    <span class="badge badge-terra" style="margin-left:auto;"><?= (int)$s['total_inquiries'] ?></span>
                <?php endif; ?>
            </a>
        </div>
        <div class="sidebar-section" style="margin-top:16px;">
            <p class="sidebar-label">Account</p>
            <a href="<?= SITE_URL ?>/auth/profile.php" class="sidebar-link">
                <i data-lucide="user"></i> Profile
            </a>
            <a href="<?= SITE_URL ?>/auth/logout.php" class="sidebar-link text-danger">
                <i data-lucide="log-out"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Content -->
    <main style="flex:1;padding:32px;overflow:auto;">
        <div class="dashboard-header">
            <h1>Agent Dashboard</h1>
            <p>Welcome back, <?= e($user['name']) ?>!</p>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon stat-icon-blue"><i data-lucide="home"></i></div>
                <div>
                    <div class="stat-val"><?= (int)$s['total_listings'] ?></div>
                    <div class="stat-label">Total Listings</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-green"><i data-lucide="check-circle"></i></div>
                <div>
                    <div class="stat-val"><?= (int)$s['active_listings'] ?></div>
                    <div class="stat-label">Active</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-terra"><i data-lucide="message-circle"></i></div>
                <div>
                    <div class="stat-val"><?= (int)$s['total_inquiries'] ?></div>
                    <div class="stat-label">Inquiries</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-gray"><i data-lucide="heart"></i></div>
                <div>
                    <div class="stat-val"><?= (int)$s['total_favorites'] ?></div>
                    <div class="stat-label">Saves</div>
                </div>
            </div>
        </div>

        <!-- Listings table -->
        <div class="data-table-wrap">
            <div class="data-table-head">
                <h3>My Listings</h3>
                <a href="<?= SITE_URL ?>/listings/create.php" class="btn btn-primary btn-sm">
                    <i data-lucide="plus"></i> Add New
                </a>
            </div>

            <?php if ($listings): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Type</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Inquiries</th>
                            <th>Posted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listings as $l): ?>
                        <?php
                            $statusBadge = match($l['status']) {
                                'active'  => 'badge-green',
                                'pending' => 'badge-terra',
                                default   => 'badge-gray',
                            };
                        ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <?php if ($l['primary_image']): ?>
                                    <img src="<?= getImageUrl($l['primary_image']) ?>"
                                         style="width:44px;height:36px;object-fit:cover;border-radius:6px;" />
                                    <?php else: ?>
                                    <div style="width:44px;height:36px;border-radius:6px;background:var(--gray-200);"></div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="font-semibold text-sm"><?= e($l['title']) ?></div>
                                        <div class="text-xs text-gray"><?= e($l['location']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-blue"><?= ucfirst(e($l['type'])) ?></span></td>
                            <td class="font-semibold"><?= formatPrice((float)$l['price']) ?></td>
                            <td><span class="badge <?= $statusBadge ?>"><?= ucfirst(e($l['status'])) ?></span></td>
                            <td>
                                <a href="<?= SITE_URL ?>/agent/inquiries.php?listing=<?= (int)$l['id'] ?>"
                                   style="color:var(--blue);font-weight:600;">
                                    <?= (int)$l['inquiry_count'] ?>
                                </a>
                            </td>
                            <td class="text-sm text-gray"><?= date('M j, Y', strtotime($l['created_at'])) ?></td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <a href="<?= SITE_URL ?>/listings/view.php?id=<?= (int)$l['id'] ?>"
                                       class="btn btn-ghost btn-sm" title="View">
                                        <i data-lucide="eye" style="width:14px;height:14px;"></i>
                                    </a>
                                    <a href="<?= SITE_URL ?>/listings/edit.php?id=<?= (int)$l['id'] ?>"
                                       class="btn btn-outline btn-sm" title="Edit">
                                        <i data-lucide="edit" style="width:14px;height:14px;"></i>
                                    </a>
                                    <a href="<?= SITE_URL ?>/listings/delete.php?id=<?= (int)$l['id'] ?>"
                                       class="btn btn-danger btn-sm"
                                       data-confirm="Delete this listing permanently?"
                                       title="Delete">
                                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i data-lucide="home"></i>
                <h3>No listings yet</h3>
                <p>Create your first property listing to get started.</p>
                <a href="<?= SITE_URL ?>/listings/create.php" class="btn btn-primary mt-2">
                    <i data-lucide="plus-circle"></i> Create Listing
                </a>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
