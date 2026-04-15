<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user = currentUser();
$db   = getDB();

$favs = $db->prepare(
    "SELECT l.*, u.name AS agent_name,
            (SELECT image_path FROM listing_images li WHERE li.listing_id = l.id ORDER BY li.id LIMIT 1) AS primary_image
     FROM favorites f
     JOIN listings l ON l.id = f.listing_id
     JOIN users u ON u.id = l.agent_id
     WHERE f.user_id = ? AND l.status = 'active'
     ORDER BY f.created_at DESC"
);
$favs->execute([$user['id']]);
$listings = $favs->fetchAll();

$typeLabels = ['apartment'=>'Apartment','villa'=>'Villa','house'=>'House','office'=>'Office'];
$pageTitle  = 'My Favorites — EstateHub';
include __DIR__ . '/../includes/header.php';
?>
<meta name="site-url" content="<?= SITE_URL ?>">

<div class="page-header">
    <div class="container">
        <h1>My Favorites</h1>
        <p><?= count($listings) ?> saved <?= count($listings)===1?'property':'properties' ?></p>
    </div>
</div>

<div class="container section-sm">
    <?php if ($listings): ?>
    <div class="card-grid">
        <?php foreach ($listings as $listing): ?>
        <?php
            $imgSrc = $listing['primary_image']
                ? UPLOAD_URL . e($listing['primary_image'])
                : SITE_URL . '/assets/images/placeholder.svg';
        ?>
        <article class="property-card" style="position:relative;">
            <a href="<?= SITE_URL ?>/listings/view.php?id=<?= (int)$listing['id'] ?>">
                <div class="card-image">
                    <img src="<?= $imgSrc ?>" alt="<?= e($listing['title']) ?>" loading="lazy" />
                    <span class="card-badge"><?= e($typeLabels[$listing['type']] ?? ucfirst($listing['type'])) ?></span>
                    <button class="card-fav active" data-id="<?= (int)$listing['id'] ?>" onclick="event.preventDefault()" title="Remove from favorites">
                        <i data-lucide="heart"></i>
                    </button>
                </div>
            </a>
            <div class="card-body">
                <div class="card-price"><?= formatPrice((float)$listing['price']) ?></div>
                <h3 class="card-title"><a href="<?= SITE_URL ?>/listings/view.php?id=<?= (int)$listing['id'] ?>"><?= e($listing['title']) ?></a></h3>
                <p class="card-location"><i data-lucide="map-pin"></i> <?= e($listing['location']) ?></p>
                <div class="card-meta">
                    <span class="card-meta-item"><i data-lucide="bed-double"></i> <?= (int)$listing['bedrooms'] ?> Beds</span>
                    <span class="card-meta-item"><i data-lucide="bath"></i> <?= (int)$listing['bathrooms'] ?> Baths</span>
                    <span class="card-meta-item"><i data-lucide="maximize"></i> <?= (int)$listing['area'] ?> m²</span>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i data-lucide="heart"></i>
        <h3>No favorites yet</h3>
        <p>Save properties you like by clicking the heart icon.</p>
        <a href="<?= SITE_URL ?>/listings/search.php" class="btn btn-primary mt-2">Browse Listings</a>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
