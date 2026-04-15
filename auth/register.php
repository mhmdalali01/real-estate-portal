<?php
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) redirect('/index.php');

$errors  = [];
$values  = ['name' => '', 'email' => '', 'role' => 'user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = sanitize($_POST['name']  ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';
    $role     = in_array($_POST['role'] ?? '', ['user', 'agent']) ? $_POST['role'] : 'user';

    $values = compact('name', 'email', 'role');

    // Validate
    if (strlen($name) < 2)            $errors['name']     = 'Name must be at least 2 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';
    if (strlen($password) < 8)        $errors['password'] = 'Password must be at least 8 characters.';
    if ($password !== $confirm)       $errors['confirm']  = 'Passwords do not match.';

    if (empty($errors)) {
        $db = getDB();
        // Check duplicate email
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = $db->prepare(
                'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)'
            );
            $ins->execute([$name, $email, $hash, $role]);
            flash('success', 'Account created! Please log in.');
            redirect('/auth/login.php');
        }
    }
}

$pageTitle = 'Register — EstateHub';
include __DIR__ . '/../includes/header.php';
?>

<div class="auth-page">
    <div class="container">
        <div class="auth-card">
            <div class="auth-card-header">
                <div class="auth-logo">🏠</div>
                <h2>Create Account</h2>
                <p>Join EstateHub and find your dream property</p>
            </div>
            <div class="auth-card-body">

                <!-- Role toggle -->
                <div class="role-toggle">
                    <label class="role-option">
                        <input type="radio" name="roleToggle" value="user" <?= $values['role']==='user'?'checked':'' ?> id="roleUser">
                        👤 Buyer / Renter
                    </label>
                    <label class="role-option">
                        <input type="radio" name="roleToggle" value="agent" <?= $values['role']==='agent'?'checked':'' ?> id="roleAgent">
                        🏢 Agent
                    </label>
                </div>

                <form method="POST" novalidate>
                    <input type="hidden" name="role" id="roleInput" value="<?= e($values['role']) ?>">

                    <div class="form-group">
                        <label class="form-label" for="name">Full Name <span class="required">*</span></label>
                        <div class="input-group">
                            <span class="input-icon"><i data-lucide="user"></i></span>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control <?= isset($errors['name'])?'border-error':'' ?>"
                                value="<?= e($values['name']) ?>"
                                placeholder="John Smith"
                                required
                            />
                        </div>
                        <?php if (isset($errors['name'])): ?>
                            <p class="form-error"><i data-lucide="alert-circle"></i> <?= e($errors['name']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address <span class="required">*</span></label>
                        <div class="input-group">
                            <span class="input-icon"><i data-lucide="mail"></i></span>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                value="<?= e($values['email']) ?>"
                                placeholder="john@example.com"
                                required
                            />
                        </div>
                        <?php if (isset($errors['email'])): ?>
                            <p class="form-error"><i data-lucide="alert-circle"></i> <?= e($errors['email']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-row form-row-2">
                        <div class="form-group">
                            <label class="form-label" for="password">Password <span class="required">*</span></label>
                            <div class="input-group">
                                <span class="input-icon"><i data-lucide="lock"></i></span>
                                <input type="password" id="password" name="password" class="form-control" placeholder="Min. 8 characters" required />
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <p class="form-error"><i data-lucide="alert-circle"></i> <?= e($errors['password']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="confirm">Confirm Password <span class="required">*</span></label>
                            <div class="input-group">
                                <span class="input-icon"><i data-lucide="lock"></i></span>
                                <input type="password" id="confirm" name="confirm" class="form-control" placeholder="Repeat password" required />
                            </div>
                            <?php if (isset($errors['confirm'])): ?>
                                <p class="form-error"><i data-lucide="alert-circle"></i> <?= e($errors['confirm']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg mt-1">
                        <i data-lucide="user-plus"></i> Create Account
                    </button>
                </form>
            </div>
            <div class="auth-footer">
                Already have an account? <a href="<?= SITE_URL ?>/auth/login.php">Sign In</a>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('input[name="roleToggle"]').forEach(r => {
    r.addEventListener('change', () => {
        document.getElementById('roleInput').value = r.value;
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
