<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
if (!hasRole('admin')) redirect('/index.php');

$db   = getDB();
$page = max(1, (int)($_GET['page'] ?? 1));

// Quick status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    $lid    = (int)$_POST['listing_id'];
    $status = in_array($_POST['status'], ['active','pending','removed']) ? $_POST['status'] : 'active';
    $db->prepare('UPDATE listings SET status = ? WHERE id = ?')->execute([$status, $lid]);
    flash('success', 'Listing status updated.');
    redirect('/admin/listings.php?page=' . $page);
}

$filterType   = sanitize($_GET['type']   ?? '');
$filterStatus = sanitize($_GET['status'] ?? '');
$search       = sanitize($_GET['q']      ?? '');
$perPage      = 15;

$where  = ['1=1'];
$params = [];
if ($filterType) { $where[] = 'l.type = ?'; $params[] = $filterType; }
if ($filterStatus) { $where[] = 'l.status = ?'; $params[] = $filterStatus; }
if ($search) {
    $where[] = '(l.title LIKE ? OR l.location LIKE ? OR u.name LIKE ?)';
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}
$whereSQL = implode(' AND ', $where);

$total = (int)$db->prepare("SELECT COUNT(*) FROM listings l JOIN users u ON u.id=l.agent_id WHERE $whereSQL")->execute($params) ?
    $db->prepare("SELECT COUNT(*) FROM listings l JOIN users u ON u.id=l.agent_id WHERE $whereSQL")->execute($params) : 0;

$countStmt = $db->prepare("SELECT COUNT(*) FROM listings l JOIN users u ON u.id=l.agent_id WHERE $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$pager = paginate($total, $perPage, $page);

$listStmt = $db->prepare(
    "SELECT l.*, u.name AS agent_name,
            (SELECT COUNT(*) FROM inquiries iq WHERE iq.listing_id=l.id) AS inquiry_count
     FROM listings l
     JOIN users u ON u.id = l.agent_id
     WHERE $whereSQL
     ORDER BY l.created_at DESC
     LIMIT $perPage OFFSET {$pager['offset']}"
);
$listStmt->execute($params);
$listings = $listStmt->fetchAll();

$pageTitle = 'Manage Listings — Admin';
include __DIR__ . '/../includes/header.php';
?>

<div style="display:flex;min-height:calc(100vh - 68px);">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main style="flex:1;padding:32px;overflow:auto;">
        <div class="dashboard-header">
            <h1>Manage Listings</h1>
            <p><?= number_format($total) ?> total listings</p>
        </div>

        <!-- Filters -->
        <form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
            <input type="text" name="q" class="form-control" placeholder="Search…" value="<?= e($search) ?>" style="max-width:220px;" />
            <select name="type" class="form-control" style="max-width:150px;">
                <option value="">All Types</option>
                <?php foreach (['apartment','villa','house','office'] as $t): ?>
                <option value="<?= $t ?>" <?= $filterType===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="form-control" style="max-width:150px;">
                <option value="">All Statuses</option>
                <?php foreach (['active','pending','removed'] as $st): ?>
                <option value="<?= $st ?>" <?= $filterStatus===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> Filter</button>
            <a href="<?= SITE_URL ?>/admin/listings.php" class="btn btn-ghost btn-sm">Reset</a>
        </form>

        <div class="data-table-wrap">
            <div class="data-table-head">
                <h3>All Listings</h3>
                <a href="<?= SITE_URL ?>/listings/create.php" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Add</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Title</th><th>Agent</th><th>Type</th><th>Price</th><th>Status</th><th>Inqs</th><th>Date</th><th>Actions</th></tr>
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
                            <td class="font-semibold text-sm"><?= e($l['title']) ?></td>
                            <td class="text-sm text-gray"><?= e($l['agent_name']) ?></td>
                            <td><span class="badge badge-blue"><?= ucfirst(e($l['type'])) ?></span></td>
                            <td class="font-semibold"><?= formatPrice((float)$l['price']) ?></td>
                            <td>
                                <!-- Quick status change inline -->
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>">
                                    <select name="status" class="form-control text-xs"
                                            style="padding:3px 8px;font-size:.75rem;width:auto;display:inline-block;"
                                            onchange="this.form.submit()">
                                        <?php foreach (['active'=>'Active','pending'=>'Pending','removed'=>'Removed'] as $sv=>$sl): ?>
                                        <option value="<?= $sv ?>" <?= $l['status']===$sv?'selected':'' ?>><?= $sl ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="change_status" value="1">
                                </form>
                            </td>
                            <td><?= (int)$l['inquiry_count'] ?></td>
                            <td class="text-xs text-gray"><?= date('M j, Y', strtotime($l['created_at'])) ?></td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <a href="<?= SITE_URL ?>/listings/view.php?id=<?= (int)$l['id'] ?>" class="btn btn-ghost btn-sm"><i data-lucide="eye" style="width:14px;height:14px;"></i></a>
                                    <a href="<?= SITE_URL ?>/listings/edit.php?id=<?= (int)$l['id'] ?>" class="btn btn-outline btn-sm"><i data-lucide="edit" style="width:14px;height:14px;"></i></a>
                                    <a href="<?= SITE_URL ?>/listings/delete.php?id=<?= (int)$l['id'] ?>" class="btn btn-danger btn-sm" data-confirm="Permanently delete this listing?"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pager['pages'] > 1): ?>
            <?php
                $base = SITE_URL . '/admin/listings.php?' . http_build_query(array_filter(['q'=>$search,'type'=>$filterType,'status'=>$filterStatus])) . '&page=';
            ?>
            <div class="pagination" style="padding:16px 0;">
                <a class="page-item <?= $pager['current']===1?'disabled':'' ?>" href="<?= $base.($pager['current']-1) ?>">‹</a>
                <?php for ($i=1;$i<=$pager['pages'];$i++): ?>
                    <a class="page-item <?= $i===$pager['current']?'active':'' ?>" href="<?= $base.$i ?>"><?= $i ?></a>
                <?php endfor; ?>
                <a class="page-item <?= $pager['current']===$pager['pages']?'disabled':'' ?>" href="<?= $base.($pager['current']+1) ?>">›</a>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
