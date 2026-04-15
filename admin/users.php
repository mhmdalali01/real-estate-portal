<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
if (!hasRole('admin')) redirect('/index.php');

$db   = getDB();
$user = currentUser();

// Change role action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $uid  = (int)$_POST['user_id'];
    $role = in_array($_POST['role'], ['user','agent','admin']) ? $_POST['role'] : 'user';
    if ($uid !== (int)$user['id']) { // Prevent self-demotion
        $db->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $uid]);
        flash('success', 'User role updated.');
    } else {
        flash('error', 'You cannot change your own role.');
    }
    redirect('/admin/users.php');
}

// Delete user action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $uid = (int)$_POST['user_id'];
    if ($uid !== (int)$user['id']) {
        $db->prepare('DELETE FROM favorites WHERE user_id = ?')->execute([$uid]);
        $db->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
        flash('success', 'User removed.');
    } else {
        flash('error', 'You cannot delete yourself.');
    }
    redirect('/admin/users.php');
}

$search = sanitize($_GET['q'] ?? '');
$roleFilter = sanitize($_GET['role'] ?? '');

$where = ['1=1'];
$params = [];
if ($search) {
    $where[] = '(name LIKE ? OR email LIKE ?)';
    $like = "%$search%"; $params[] = $like; $params[] = $like;
}
if ($roleFilter) { $where[] = 'role = ?'; $params[] = $roleFilter; }
$whereSQL = implode(' AND ', $where);

$stmt = $db->prepare("SELECT * FROM users WHERE $whereSQL ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Manage Users — Admin';
include __DIR__ . '/../includes/header.php';
?>

<div style="display:flex;min-height:calc(100vh - 68px);">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main style="flex:1;padding:32px;overflow:auto;">
        <div class="dashboard-header">
            <h1>Manage Users</h1>
            <p><?= count($users) ?> users in the system</p>
        </div>

        <!-- Filters -->
        <form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
            <input type="text" name="q" class="form-control" placeholder="Search by name or email…" value="<?= e($search) ?>" style="max-width:260px;" />
            <select name="role" class="form-control" style="max-width:160px;">
                <option value="">All Roles</option>
                <option value="user"  <?= $roleFilter==='user'?'selected':'' ?>>User</option>
                <option value="agent" <?= $roleFilter==='agent'?'selected':'' ?>>Agent</option>
                <option value="admin" <?= $roleFilter==='admin'?'selected':'' ?>>Admin</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> Filter</button>
            <a href="<?= SITE_URL ?>/admin/users.php" class="btn btn-ghost btn-sm">Reset</a>
        </form>

        <div class="data-table-wrap">
            <div class="data-table-head">
                <h3>All Users</h3>
                <span class="badge badge-blue"><?= count($users) ?> total</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <?php
                            $roleBadge = match($u['role']) {
                                'admin' => 'badge-blue',
                                'agent' => 'badge-terra',
                                default => 'badge-gray',
                            };
                        ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div class="avatar-initials" style="width:34px;height:34px;font-size:.8rem;flex-shrink:0;">
                                        <?= strtoupper(substr($u['name'], 0, 2)) ?>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-sm"><?= e($u['name']) ?></div>
                                        <?php if ($u['id'] == $user['id']): ?>
                                            <span class="text-xs text-terra">(You)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="text-sm"><?= e($u['email']) ?></td>
                            <td>
                                <!-- Role change form -->
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                    <select name="role" class="form-control" style="padding:4px 8px;font-size:.78rem;width:auto;display:inline-block;"
                                            onchange="this.form.submit()" <?= $u['id']==$user['id']?'disabled':'' ?>>
                                        <?php foreach (['user','agent','admin'] as $r): ?>
                                        <option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="change_role" value="1">
                                </form>
                            </td>
                            <td class="text-xs text-gray"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <?php if ($u['id'] != $user['id']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                    <input type="hidden" name="delete_user" value="1">
                                    <button type="submit" class="btn btn-danger btn-sm"
                                            data-confirm="Remove user <?= e($u['name']) ?>? This cannot be undone.">
                                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="text-xs text-gray">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
