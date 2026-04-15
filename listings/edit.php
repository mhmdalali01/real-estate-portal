<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/agent/dashboard.php');

$user = currentUser();

// Fetch listing — agents can only edit their own; admins can edit any
$sql = hasRole('admin')
    ? 'SELECT * FROM listings WHERE id = ?'
    : 'SELECT * FROM listings WHERE id = ? AND agent_id = ?';
$params = hasRole('admin') ? [$id] : [$id, $user['id']];

$stmt = $db->prepare($sql);
$stmt->execute($params);
$listing = $stmt->fetch();
if (!$listing) {
    flash('error', 'Listing not found or access denied.');
    redirect('/agent/dashboard.php');
}

$errors = [];
$values = $listing; // Pre-fill from existing data

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['title']       = sanitize($_POST['title']       ?? '');
    $values['type']        = sanitize($_POST['type']        ?? '');
    $values['price']       = sanitize($_POST['price']       ?? '');
    $values['location']    = sanitize($_POST['location']    ?? '');
    $values['bedrooms']    = sanitize($_POST['bedrooms']    ?? '');
    $values['bathrooms']   = sanitize($_POST['bathrooms']   ?? '');
    $values['area']        = sanitize($_POST['area']        ?? '');
    $values['description'] = sanitize($_POST['description'] ?? '');
    $values['status']      = in_array($_POST['status'] ?? '', ['active','pending','removed'])
                             ? $_POST['status'] : 'active';

    if (strlen($values['title']) < 5) $errors['title'] = 'Title must be at least 5 characters.';
    if (!in_array($values['type'], ['apartment','villa','house','office'])) $errors['type'] = 'Invalid type.';
    if (!is_numeric($values['price']) || (float)$values['price'] <= 0) $errors['price'] = 'Enter a valid price.';
    if (strlen($values['location']) < 3) $errors['location'] = 'Location is required.';
    if (!is_numeric($values['area']) || (float)$values['area'] <= 0) $errors['area'] = 'Enter valid area.';
    if (strlen($values['description']) < 20) $errors['description'] = 'Description must be at least 20 characters.';

    // New images
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $idx => $tmp) {
            if (!$tmp || $_FILES['images']['error'][$idx] !== UPLOAD_ERR_OK) continue;
            $file = [
                'name'     => $_FILES['images']['name'][$idx],
                'type'     => $_FILES['images']['type'][$idx],
                'tmp_name' => $tmp,
                'error'    => $_FILES['images']['error'][$idx],
                'size'     => $_FILES['images']['size'][$idx],
            ];
            try {
                $imgPath = handleImageUpload($file);
                $db->prepare('INSERT INTO listing_images (listing_id, image_path) VALUES (?, ?)')
                   ->execute([$id, $imgPath]);
            } catch (RuntimeException $e) {
                $errors['images'] = $e->getMessage();
                break;
            }
        }
    }

    // Delete selected images
    $deleteIds = array_map('intval', $_POST['delete_images'] ?? []);
    foreach ($deleteIds as $imgId) {
        $imgRow = $db->prepare('SELECT image_path FROM listing_images WHERE id = ? AND listing_id = ?');
        $imgRow->execute([$imgId, $id]);
        $row = $imgRow->fetch();
        if ($row) {
            @unlink(UPLOAD_PATH . $row['image_path']);
            $db->prepare('DELETE FROM listing_images WHERE id = ?')->execute([$imgId]);
        }
    }

    if (empty($errors)) {
        $db->prepare(
            'UPDATE listings SET title=?, type=?, price=?, location=?, bedrooms=?, bathrooms=?, area=?, description=?, status=?
             WHERE id=?'
        )->execute([
            $values['title'], $values['type'], (float)$values['price'],
            $values['location'], (int)$values['bedrooms'], (int)$values['bathrooms'],
            (float)$values['area'], $values['description'], $values['status'], $id
        ]);

        flash('success', 'Listing updated successfully!');
        redirect('/listings/view.php?id=' . $id);
    }
}

$images    = getListingImages($id);
$pageTitle = 'Edit Listing — EstateHub';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Edit Listing</h1>
        <p><?= e($listing['title']) ?></p>
    </div>
</div>

<div class="container" style="max-width:860px;padding-bottom:60px;">
    <div class="listing-detail-card">
        <form method="POST" enctype="multipart/form-data" novalidate>

            <h3 style="font-size:1rem;font-weight:700;margin-bottom:18px;color:var(--blue);">Basic Information</h3>

            <div class="form-group">
                <label class="form-label">Listing Title <span class="required">*</span></label>
                <input type="text" name="title" class="form-control" value="<?= e($values['title']) ?>" required />
                <?php if (isset($errors['title'])): ?><p class="form-error"><?= e($errors['title']) ?></p><?php endif; ?>
            </div>

            <div class="form-row form-row-2">
                <div class="form-group">
                    <label class="form-label">Property Type <span class="required">*</span></label>
                    <select name="type" class="form-control">
                        <?php foreach (['apartment'=>'Apartment','villa'=>'Villa','house'=>'House','office'=>'Office'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= $values['type']===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <?php foreach (['active'=>'Active','pending'=>'Pending','removed'=>'Removed'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= $values['status']===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row form-row-2">
                <div class="form-group">
                    <label class="form-label">Price (USD) <span class="required">*</span></label>
                    <input type="number" name="price" class="form-control" value="<?= e($values['price']) ?>" min="0" required />
                    <?php if (isset($errors['price'])): ?><p class="form-error"><?= e($errors['price']) ?></p><?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">Location <span class="required">*</span></label>
                    <input type="text" name="location" class="form-control" value="<?= e($values['location']) ?>" required />
                    <?php if (isset($errors['location'])): ?><p class="form-error"><?= e($errors['location']) ?></p><?php endif; ?>
                </div>
            </div>

            <div class="form-row form-row-3">
                <div class="form-group">
                    <label class="form-label">Bedrooms</label>
                    <input type="number" name="bedrooms" class="form-control" value="<?= e($values['bedrooms']) ?>" min="0" />
                </div>
                <div class="form-group">
                    <label class="form-label">Bathrooms</label>
                    <input type="number" name="bathrooms" class="form-control" value="<?= e($values['bathrooms']) ?>" min="0" />
                </div>
                <div class="form-group">
                    <label class="form-label">Area (m²) <span class="required">*</span></label>
                    <input type="number" name="area" class="form-control" value="<?= e($values['area']) ?>" min="1" required />
                    <?php if (isset($errors['area'])): ?><p class="form-error"><?= e($errors['area']) ?></p><?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description <span class="required">*</span></label>
                <textarea name="description" class="form-control" rows="6" maxlength="5000"><?= e($values['description']) ?></textarea>
                <?php if (isset($errors['description'])): ?><p class="form-error"><?= e($errors['description']) ?></p><?php endif; ?>
            </div>

            <!-- Current images -->
            <?php if ($images): ?>
            <hr style="margin:24px 0;border-color:var(--gray-200);">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:14px;color:var(--blue);">Current Photos</h3>
            <p class="form-hint mb-2">Check images to delete them.</p>
            <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:10px;">
                <?php
                $imgStmt = $db->prepare('SELECT id, image_path FROM listing_images WHERE listing_id = ? ORDER BY id');
                $imgStmt->execute([$id]);
                $imgRows = $imgStmt->fetchAll();
                foreach ($imgRows as $img):
                ?>
                <div style="position:relative;">
                    <img src="<?= UPLOAD_URL . e($img['image_path']) ?>" style="width:80px;height:80px;object-fit:cover;border-radius:8px;" />
                    <label style="position:absolute;top:2px;right:2px;background:rgba(0,0,0,.6);border-radius:4px;padding:2px 4px;cursor:pointer;display:flex;align-items:center;gap:2px;">
                        <input type="checkbox" name="delete_images[]" value="<?= (int)$img['id'] ?>" style="accent-color:var(--error);" />
                        <span style="color:#fff;font-size:.65rem;">Del</span>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <hr style="margin:24px 0;border-color:var(--gray-200);">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:14px;color:var(--blue);">Add More Photos</h3>
            <?php if (isset($errors['images'])): ?><p class="form-error mb-2"><?= e($errors['images']) ?></p><?php endif; ?>
            <div class="upload-area" id="uploadArea">
                <i data-lucide="upload-cloud"></i>
                <p>Click or drag & drop to add images</p>
                <small>JPG, PNG, WebP — max 5 MB each</small>
                <input type="file" name="images[]" id="imageInput" accept="image/*" multiple style="display:none;" />
            </div>
            <div class="image-preview-grid" id="imagePreviewGrid"></div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i data-lucide="save"></i> Save Changes
                </button>
                <a href="<?= SITE_URL ?>/listings/view.php?id=<?= $id ?>" class="btn btn-ghost btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
