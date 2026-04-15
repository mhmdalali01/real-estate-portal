<?php
/**
 * AJAX endpoint — toggle a listing in the user's favorites
 * Returns JSON: { status: "added" | "removed" | "login_required" | "error" }
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['status' => 'login_required']);
    exit;
}

$listingId = (int)($_POST['listing_id'] ?? 0);
if (!$listingId) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid listing']);
    exit;
}

$user = currentUser();
$db   = getDB();

// Check if already favorited
$check = $db->prepare('SELECT id FROM favorites WHERE user_id = ? AND listing_id = ?');
$check->execute([$user['id'], $listingId]);

if ($check->fetch()) {
    // Remove
    $db->prepare('DELETE FROM favorites WHERE user_id = ? AND listing_id = ?')
       ->execute([$user['id'], $listingId]);
    echo json_encode(['status' => 'removed']);
} else {
    // Add — verify listing exists
    $verify = $db->prepare("SELECT id FROM listings WHERE id = ? AND status = 'active'");
    $verify->execute([$listingId]);
    if (!$verify->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Listing not found']);
        exit;
    }
    $db->prepare('INSERT INTO favorites (user_id, listing_id) VALUES (?, ?)')
       ->execute([$user['id'], $listingId]);
    echo json_encode(['status' => 'added']);
}
