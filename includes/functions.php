<?php
/**
 * Shared utility functions
 */

require_once __DIR__ . '/../config/db.php';

// ── Session helpers ──────────────────────────────────────────────────────────

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function currentUser(): ?array {
    startSession();
    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool {
    return currentUser() !== null;
}

function hasRole(string ...$roles): bool {
    $user = currentUser();
    return $user && in_array($user['role'], $roles, true);
}

function requireLogin(string $redirect = '/auth/login.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . $redirect);
        exit;
    }
}

function requireRole(string $role, string $redirect = '/'): void {
    requireLogin();
    if (!hasRole($role, 'admin')) {
        header('Location: ' . SITE_URL . $redirect);
        exit;
    }
}

// ── Input / Output helpers ───────────────────────────────────────────────────

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function sanitize(string $value): string {
    return trim(strip_tags($value));
}

function redirect(string $path): void {
    header('Location: ' . SITE_URL . $path);
    exit;
}

function flash(string $key, string $message): void {
    startSession();
    $_SESSION['flash'][$key] = $message;
}

function getFlash(string $key): ?string {
    startSession();
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

// ── Listing helpers ──────────────────────────────────────────────────────────

function formatPrice(float $price): string {
    return '$' . number_format($price, 0, '.', ',');
}

function getListingPrimaryImage(int $listingId): string {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT image_path FROM listing_images WHERE listing_id = ? ORDER BY id ASC LIMIT 1'
    );
    $stmt->execute([$listingId]);
    $row = $stmt->fetch();
    
    if (!$row) {
        return SITE_URL . '/assets/images/placeholder.svg';
    }
    
    return getImageUrl($row['image_path']);
}

function getListingImages(int $listingId): array {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT image_path FROM listing_images WHERE listing_id = ? ORDER BY id ASC'
    );
    $stmt->execute([$listingId]);
    return $stmt->fetchAll();
}

function isFavorited(int $userId, int $listingId): bool {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT 1 FROM favorites WHERE user_id = ? AND listing_id = ?'
    );
    $stmt->execute([$userId, $listingId]);
    return (bool) $stmt->fetch();
}

// ── Image URL helper ────────────────────────────────────────────────────────

function getImageUrl(string $imagePath): string {
    // If it's already a full URL (starts with http), use it as-is
    if (strpos($imagePath, 'http') === 0) {
        return e($imagePath);
    }
    
    // Otherwise, it's a local file path
    return UPLOAD_URL . e($imagePath);
}

// ── Image upload ─────────────────────────────────────────────────────────────

function handleImageUpload(array $file): string {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes, true)) {
        throw new RuntimeException('Only JPG, PNG, and WebP images are allowed.');
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new RuntimeException('Image must be smaller than 5 MB.');
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = bin2hex(random_bytes(12)) . '.' . strtolower($ext);
    $dest = UPLOAD_PATH . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Failed to move uploaded file.');
    }
    return $filename;
}

// ── Pagination helper ────────────────────────────────────────────────────────

function paginate(int $total, int $perPage, int $current): array {
    $pages = (int) ceil($total / $perPage);
    return [
        'total'   => $total,
        'pages'   => max(1, $pages),
        'current' => max(1, min($current, $pages)),
        'offset'  => ($current - 1) * $perPage,
        'limit'   => $perPage,
    ];
}
