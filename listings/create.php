<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
if (!hasRole('agent', 'admin')) redirect('/index.php');

$errors = [];
$values = [
    'title'       => '',
    'type'        => 'apartment',
    'price'       => '',
    'location'    => '',
    'bedrooms'    => '',
    'bathrooms'   => '',
    'area'        => '',
    'description' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['title']       = sanitize($_POST['title']       ?? '');
    $values['type']        = sanitize($_POST['type']        ?? '');
    $values['price']       = sanitize($_POST['price']       ?? '');
    $values['location']    = sanitize($_POST['location']    ?? '');
    $values['bedrooms']    = sanitize($_POST['bedrooms']    ?? '');
    $values['bathrooms']   = sanitize($_POST['bathrooms']   ?? '');
    $values['area']        = sanitize($_POST['area']        ?? '');
    $values['description'] = sanitize($_POST['description'] ?? '');

    // Validate
    if (strlen($values['title']) < 5)     $errors['title']       = 'Title must be at least 5 characters.';
    if (!in_array($values['type'], ['apartment','villa','house','office'])) $errors['type'] = 'Invalid type.';
    if (!is_numeric($values['price']) || (float)$values['price'] <= 0)  $errors['price']    = 'Enter a valid price.';
    if (strlen($values['location']) < 3)  $errors['location']    = 'Location is required.';
    if (!is_numeric($values['area']) || (float)$values['area'] <= 0)    $errors['area']     = 'Enter valid area.';
    if (strlen($values['description']) < 20) $errors['description'] = 'Description must be at least 20 characters.';

    // Images
    $uploadedImages = [];
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $idx => $tmp) {
            if (!$tmp) continue;
            $file = [
                'name'     => $_FILES['images']['name'][$idx],
                'type'     => $_FILES['images']['type'][$idx],
                'tmp_name' => $tmp,
                'error'    => $_FILES['images']['error'][$idx],
                'size'     => $_FILES['images']['size'][$idx],
            ];
            if ($file['error'] !== UPLOAD_ERR_OK) continue;
            try {
                $uploadedImages[] = handleImageUpload($file);
            } catch (RuntimeException $e) {
                $errors['images'] = $e->getMessage();
                break;
            }
        }
    }

    if (empty($errors)) {
        $user = currentUser();
        $db   = getDB();

        $ins = $db->prepare(
            'INSERT INTO listings (agent_id, title, type, price, location, bedrooms, bathrooms, area, description, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([
            $user['id'],
            $values['title'],
            $values['type'],
            (float)$values['price'],
            $values['location'],
            (int)($values['bedrooms'] ?: 0),
            (int)($values['bathrooms'] ?: 0),
            (float)$values['area'],
            $values['description'],
            'active',
        ]);
        $listingId = (int)$db->lastInsertId();

        // Save images
        foreach ($uploadedImages as $imgPath) {
            $db->prepare('INSERT INTO listing_images (listing_id, image_path) VALUES (?, ?)')
               ->execute([$listingId, $imgPath]);
        }

        flash('success', 'Listing created successfully!');
        redirect('/listings/view.php?id=' . $listingId);
    }
}

$pageTitle = 'Create Listing — EstateHub';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Create New Listing</h1>
        <p>Fill in the details below to post your property</p>
    </div>
</div>

<div class="container" style="max-width:860px;padding-bottom:60px;">
    <div class="listing-detail-card">
        <form method="POST" enctype="multipart/form-data" novalidate>

            <h3 style="font-size:1rem;font-weight:700;margin-bottom:18px;color:var(--blue);">Basic Information</h3>

            <div class="form-group">
                <label class="form-label">Listing Title <span class="required">*</span></label>
                <input type="text" name="title" class="form-control" value="<?= e($values['title']) ?>"
                       placeholder="e.g. Modern 2-Bedroom Apartment in Downtown" required />
                <?php if (isset($errors['title'])): ?><p class="form-error"><?= e($errors['title']) ?></p><?php endif; ?>
            </div>

            <div class="form-row form-row-2">
                <div class="form-group">
                    <label class="form-label">Property Type <span class="required">*</span></label>
                    <select name="type" class="form-control" required>
                        <?php foreach (['apartment'=>'Apartment','villa'=>'Villa','house'=>'House','office'=>'Office'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= $values['type']===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['type'])): ?><p class="form-error"><?= e($errors['type']) ?></p><?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">Price (USD) <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="input-icon"><i data-lucide="dollar-sign"></i></span>
                        <input type="number" name="price" class="form-control" value="<?= e($values['price']) ?>"
                               placeholder="250000" min="0" step="1" required />
                    </div>
                    <?php if (isset($errors['price'])): ?><p class="form-error"><?= e($errors['price']) ?></p><?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Location / Address <span class="required">*</span></label>
                <div class="input-group">
                    <span class="input-icon"><i data-lucide="map-pin"></i></span>
                    <input type="text" name="location" class="form-control" value="<?= e($values['location']) ?>"
                           placeholder="123 Main Street, New York, NY" required />
                </div>
                <?php if (isset($errors['location'])): ?><p class="form-error"><?= e($errors['location']) ?></p><?php endif; ?>
            </div>

            <div class="form-row form-row-3">
                <div class="form-group">
                    <label class="form-label">Bedrooms</label>
                    <input type="number" name="bedrooms" class="form-control" value="<?= e($values['bedrooms']) ?>"
                           placeholder="0" min="0" max="20" />
                </div>
                <div class="form-group">
                    <label class="form-label">Bathrooms</label>
                    <input type="number" name="bathrooms" class="form-control" value="<?= e($values['bathrooms']) ?>"
                           placeholder="0" min="0" max="20" />
                </div>
                <div class="form-group">
                    <label class="form-label">Area (m²) <span class="required">*</span></label>
                    <input type="number" name="area" class="form-control" value="<?= e($values['area']) ?>"
                           placeholder="85" min="1" required />
                    <?php if (isset($errors['area'])): ?><p class="form-error"><?= e($errors['area']) ?></p><?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description <span class="required">*</span></label>
                <textarea name="description" class="form-control" rows="6" maxlength="5000"
                          placeholder="Describe the property — its features, condition, nearby amenities…" required><?= e($values['description']) ?></textarea>
                <?php if (isset($errors['description'])): ?><p class="form-error"><?= e($errors['description']) ?></p><?php endif; ?>
            </div>

            <hr style="margin:24px 0;border-color:var(--gray-200);">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:14px;color:var(--blue);">Property Photos</h3>
            <?php if (isset($errors['images'])): ?><p class="form-error mb-2"><?= e($errors['images']) ?></p><?php endif; ?>

            <div class="upload-area" id="uploadArea">
                <i data-lucide="upload-cloud"></i>
                <p>Click or drag & drop images here</p>
                <small>JPG, PNG, WebP — max 5 MB each — up to 10 images</small>
                <input type="file" name="images[]" id="imageInput" accept="image/*" multiple style="display:none;" />
            </div>
            <div class="image-preview-grid" id="imagePreviewGrid"></div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i data-lucide="plus-circle"></i> Publish Listing
                </button>
                <a href="<?= SITE_URL ?>/agent/dashboard.php" class="btn btn-ghost btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
