<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
if (!hasRole('admin')) redirect('/index.php');

$db = getDB();

$stats = $db->query("SELECT
    (SELECT COUNT(*) FROM listings)             AS total_listings,
    (SELECT COUNT(*) FROM listings WHERE status='active') AS active_listings,
    (SELECT COUNT(*) FROM users)                AS total_users,
    (SELECT COUNT(*) FROM users WHERE role='agent') AS total_agents,
    (SELECT COUNT(*) FROM inquiries)            AS total_inquiries,
    (SELECT COUNT(*) FROM favorites)            AS total_favorites
")->fetch();

// Recent listings
$recentListings = $db->query(
    "SELECT l.*, u.name AS agent_name FROM listings l
     JOIN users u ON u.id = l.agent_id
     ORDER BY l.created_at DESC LIMIT 8"
)->fetchAll();

// Recent users
$recentUsers = $db->query(
    "SELECT * FROM users ORDER BY created_at DESC LIMIT 6"
)->fetchAll();

$pageTitle = 'Admin Dashboard — EstateHub';
include __DIR__ . '/../includes/header.php';
?>

<div style="display:flex;min-height:calc(100vh - 68px);">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main style="flex:1;padding:32px;overflow:auto;">
        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
            <p>Overview of the EstateHub platform</p>
        </div>

        <!-- Stats -->
        <div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr));">
            <div class="stat-card">
                <div class="stat-icon stat-icon-blue"><i data-lucide="home"></i></div>
                <div><div class="stat-val"><?= (int)$stats['total_listings'] ?></div><div class="stat-label">Total Listings</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-green"><i data-lucide="check-circle"></i></div>
                <div><div class="stat-val"><?= (int)$stats['active_listings'] ?></div><div class="stat-label">Active Listings</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-gray"><i data-lucide="users"></i></div>
                <div><div class="stat-val"><?= (int)$stats['total_users'] ?></div><div class="stat-label">Total Users</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-terra"><i data-lucide="briefcase"></i></div>
                <div><div class="stat-val"><?= (int)$stats['total_agents'] ?></div><div class="stat-label">Agents</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-blue"><i data-lucide="message-circle"></i></div>
                <div><div class="stat-val"><?= (int)$stats['total_inquiries'] ?></div><div class="stat-label">Inquiries</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-terra"><i data-lucide="heart"></i></div>
                <div><div class="stat-val"><?= (int)$stats['total_favorites'] ?></div><div class="stat-label">Favorites</div></div>
            </div>
        </div>

        <!-- Recent Listings -->
        <div class="data-table-wrap mb-4">
            <div class="data-table-head">
                <h3>Recent Listings</h3>
                <a href="<?= SITE_URL ?>/admin/listings.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th><th>Agent</th><th>Type</th>
                            <th>Price</th><th>Status</th><th>Date</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentListings as $l): ?>
                        <?php
                            $statusBadge = match($l['status']) {
                                'active'  => 'badge-green',
                                'pending' => 'badge-terra',
                                default   => 'badge-gray',
                            };
                        ?>
                        <tr>
                            <td class="font-semibold text-sm"><?= e($l['title']) ?></td>
                            <td class="text-sm text-gray"><?= e($l['agent_name']) ?></td>
                            <td><span class="badge badge-blue"><?= ucfirst(e($l['type'])) ?></span></td>
                            <td class="font-semibold"><?= formatPrice((float)$l['price']) ?></td>
                            <td><span class="badge <?= $statusBadge ?>"><?= ucfirst(e($l['status'])) ?></span></td>
                            <td class="text-xs text-gray"><?= date('M j, Y', strtotime($l['created_at'])) ?></td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <a href="<?= SITE_URL ?>/listings/view.php?id=<?= (int)$l['id'] ?>" class="btn btn-ghost btn-sm" title="View"><i data-lucide="eye" style="width:14px;height:14px;"></i></a>
                                    <a href="<?= SITE_URL ?>/listings/edit.php?id=<?= (int)$l['id'] ?>" class="btn btn-outline btn-sm" title="Edit"><i data-lucide="edit" style="width:14px;height:14px;"></i></a>
                                    <a href="<?= SITE_URL ?>/listings/delete.php?id=<?= (int)$l['id'] ?>" class="btn btn-danger btn-sm" data-confirm="Delete this listing?" title="Delete"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Users -->
        <div class="data-table-wrap">
            <div class="data-table-head">
                <h3>Recent Users</h3>
                <a href="<?= SITE_URL ?>/admin/users.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentUsers as $u): ?>
                        <?php
                            $roleBadge = match($u['role']) {
                                'admin' => 'badge-blue',
                                'agent' => 'badge-terra',
                                default => 'badge-gray',
                            };
                        ?>
                        <tr>
                            <td class="font-semibold text-sm"><?= e($u['name']) ?></td>
                            <td class="text-sm"><?= e($u['email']) ?></td>
                            <td><span class="badge <?= $roleBadge ?>"><?= ucfirst(e($u['role'])) ?></span></td>
                            <td class="text-xs text-gray"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
