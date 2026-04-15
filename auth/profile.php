<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user   = currentUser();
$db     = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = sanitize($_POST['name']    ?? '');
    $email   = sanitize($_POST['email']   ?? '');
    $newPass = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $current = $_POST['current_password'] ?? '';

    if (strlen($name) < 2) $errors['name'] = 'Name must be at least 2 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email address.';

    // Check email uniqueness if changed
    if ($email !== $user['email']) {
        $chk = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $chk->execute([$email, $user['id']]);
        if ($chk->fetch()) $errors['email'] = 'Email already in use.';
    }

    // Password change
    if ($newPass || $current) {
        $dbUser = $db->prepare('SELECT password FROM users WHERE id = ?');
        $dbUser->execute([$user['id']]);
        $dbRow = $dbUser->fetch();
        if (!password_verify($current, $dbRow['password'])) {
            $errors['current_password'] = 'Current password is incorrect.';
        } elseif (strlen($newPass) < 8) {
            $errors['new_password'] = 'New password must be at least 8 characters.';
        } elseif ($newPass !== $confirm) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }
    }

    if (empty($errors)) {
        if ($newPass) {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $db->prepare('UPDATE users SET name=?, email=?, password=? WHERE id=?')
               ->execute([$name, $email, $hash, $user['id']]);
        } else {
            $db->prepare('UPDATE users SET name=?, email=? WHERE id=?')
               ->execute([$name, $email, $user['id']]);
        }
        // Update session
        $_SESSION['user']['name']  = $name;
        $_SESSION['user']['email'] = $email;
        flash('success', 'Profile updated successfully.');
        redirect('/auth/profile.php');
    }
}

// Re-fetch user
$dbUser = $db->prepare('SELECT * FROM users WHERE id = ?');
$dbUser->execute([$user['id']]);
$profile = $dbUser->fetch();

$pageTitle = 'My Profile — EstateHub';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>My Profile</h1>
        <p>Manage your account settings</p>
    </div>
</div>

<div class="container" style="max-width:640px;padding-bottom:60px;">
    <div class="listing-detail-card">
        <form method="POST" novalidate>
            <div class="form-group">
                <label class="form-label">Full Name <span class="required">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= e($profile['name']) ?>" required />
                <?php if (isset($errors['name'])): ?><p class="form-error"><?= e($errors['name']) ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label">Email Address <span class="required">*</span></label>
                <input type="email" name="email" class="form-control" value="<?= e($profile['email']) ?>" required />
                <?php if (isset($errors['email'])): ?><p class="form-error"><?= e($errors['email']) ?></p><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label">Role</label>
                <input type="text" class="form-control" value="<?= ucfirst(e($profile['role'])) ?>" disabled />
            </div>

            <hr style="margin:24px 0;border-color:var(--gray-200);">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:16px;">Change Password</h3>
            <p class="form-hint mb-2">Leave blank to keep your current password.</p>

            <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" placeholder="Enter current password" />
                <?php if (isset($errors['current_password'])): ?><p class="form-error"><?= e($errors['current_password']) ?></p><?php endif; ?>
            </div>
            <div class="form-row form-row-2">
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Min. 8 characters" />
                    <?php if (isset($errors['new_password'])): ?><p class="form-error"><?= e($errors['new_password']) ?></p><?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password" />
                    <?php if (isset($errors['confirm_password'])): ?><p class="form-error"><?= e($errors['confirm_password']) ?></p><?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg mt-2">
                <i data-lucide="save"></i> Save Changes
            </button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
