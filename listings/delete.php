<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$db   = getDB();
$id   = (int)($_GET['id'] ?? 0);
$user = currentUser();

if (!$id) redirect('/agent/dashboard.php');

$sql    = hasRole('admin') ? 'SELECT * FROM listings WHERE id = ?' : 'SELECT * FROM listings WHERE id = ? AND agent_id = ?';
$params = hasRole('admin') ? [$id] : [$id, $user['id']];

$stmt = $db->prepare($sql);
$stmt->execute($params);
$listing = $stmt->fetch();

if (!$listing) {
    flash('error', 'Listing not found or access denied.');
    redirect('/agent/dashboard.php');
}

// Delete images from disk
$imgs = getListingImages($id);
foreach ($imgs as $img) {
    @unlink(UPLOAD_PATH . $img['image_path']);
}

// Delete DB records (FK cascades handle child rows if set up; otherwise manual)
$db->prepare('DELETE FROM listing_images WHERE listing_id = ?')->execute([$id]);
$db->prepare('DELETE FROM favorites WHERE listing_id = ?')->execute([$id]);
$db->prepare('DELETE FROM inquiries WHERE listing_id = ?')->execute([$id]);
$db->prepare('DELETE FROM listings WHERE id = ?')->execute([$id]);

flash('success', 'Listing "' . $listing['title'] . '" has been deleted.');
redirect(hasRole('admin') ? '/admin/listings.php' : '/agent/dashboard.php');
