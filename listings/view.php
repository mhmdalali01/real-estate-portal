<?php
require_once __DIR__ . '/../includes/functions.php';

$db  = getDB();
$id  = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/listings/search.php');

// Fetch listing
$stmt = $db->prepare(
    "SELECT l.*, u.name AS agent_name, u.email AS agent_email
     FROM listings l
     JOIN users u ON u.id = l.agent_id
     WHERE l.id = ? AND l.status = 'active'"
);
$stmt->execute([$id]);
$listing = $stmt->fetch();
if (!$listing) {
    flash('error', 'Listing not found or no longer available.');
    redirect('/listings/search.php');
}

// Images
$images = getListingImages($id);

// Inquiry form
$inquiryErrors  = [];
$inquirySuccess = false;
$user           = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_inquiry'])) {
    $senderName  = sanitize($_POST['sender_name']  ?? '');
    $senderEmail = sanitize($_POST['sender_email'] ?? '');
    $message     = sanitize($_POST['message']      ?? '');

    if (strlen($senderName) < 2)  $inquiryErrors['sender_name']  = 'Please enter your name.';
    if (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) $inquiryErrors['sender_email'] = 'Valid email required.';
    if (strlen($message) < 10)    $inquiryErrors['message']      = 'Message must be at least 10 characters.';

    if (empty($inquiryErrors)) {
        $ins = $db->prepare(
            'INSERT INTO inquiries (listing_id, sender_name, sender_email, message) VALUES (?, ?, ?, ?)'
        );
        $ins->execute([$id, $senderName, $senderEmail, $message]);
        $inquirySuccess = true;
    }
}

// Iqs count (for agent)
$iqCount = (int)$db->prepare('SELECT COUNT(*) FROM inquiries WHERE listing_id = ?')->execute([$id]) ? 0 : 0;

// Related listings
$related = $db->prepare(
    "SELECT l.*, (SELECT image_path FROM listing_images li WHERE li.listing_id = l.id ORDER BY li.id LIMIT 1) AS primary_image
     FROM listings l
     WHERE l.status='active' AND l.type=? AND l.id != ?
     ORDER BY RAND() LIMIT 3"
);
$related->execute([$listing['type'], $id]);
$relatedListings = $related->fetchAll();

$typeLabels = ['apartment'=>'Apartment','villa'=>'Villa','house'=>'House','office'=>'Office'];
$faved = $user ? isFavorited((int)$user['id'], $id) : false;

$pageTitle = e($listing['title']) . ' — EstateHub';
include __DIR__ . '/../includes/header.php';
?>
<meta name="site-url" content="<?= SITE_URL ?>">

<div class="container section">

    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="<?= SITE_URL ?>">Home</a>
        <i data-lucide="chevron-right"></i>
        <a href="<?= SITE_URL ?>/listings/search.php">Browse</a>
        <i data-lucide="chevron-right"></i>
        <a href="<?= SITE_URL ?>/listings/search.php?type=<?= e($listing['type']) ?>"><?= e($typeLabels[$listing['type']] ?? ucfirst($listing['type'])) ?></a>
        <i data-lucide="chevron-right"></i>
        <span class="breadcrumb-current"><?= e($listing['title']) ?></span>
    </nav>

    <div class="listing-layout">

        <!-- ── Left: Gallery + Details ── -->
        <div>

            <!-- Gallery -->
            <div class="gallery">
                <div class="gallery-main">
                    <?php $firstImg = $images[0]['image_path'] ?? null; ?>
                    <img
                        src="<?= $firstImg ? getImageUrl($firstImg) : SITE_URL . '/assets/images/placeholder.svg' ?>"
                        alt="<?= e($listing['title']) ?>"
                        id="galleryMainImg"
                    />
                </div>
                <?php if (count($images) > 1): ?>
                <div class="gallery-thumbs">
                    <?php foreach ($images as $img): ?>
                    <div class="gallery-thumb">
                        <img src="<?= getImageUrl($img['image_path']) ?>" alt="Property image" />
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Main details card -->
            <div class="listing-detail-card">
                <div class="d-flex align-center gap-2" style="flex-wrap:wrap;margin-bottom:10px;">
                    <span class="badge badge-blue"><?= e($typeLabels[$listing['type']] ?? ucfirst($listing['type'])) ?></span>
                    <span class="badge badge-green">Active</span>
                </div>

                <div class="listing-price-big"><?= formatPrice((float)$listing['price']) ?></div>
                <h1 class="listing-title-big"><?= e($listing['title']) ?></h1>

                <p style="display:flex;align-items:center;gap:6px;color:var(--gray-600);font-size:.95rem;">
                    <i data-lucide="map-pin" style="color:var(--terra);width:16px;height:16px;"></i>
                    <?= e($listing['location']) ?>
                </p>

                <div class="listing-meta-row">
                    <?php if ($listing['bedrooms']): ?>
                    <div class="meta-item">
                        <i data-lucide="bed-double"></i>
                        <div><strong><?= (int)$listing['bedrooms'] ?></strong> Bedrooms</div>
                    </div>
                    <?php endif; ?>
                    <?php if ($listing['bathrooms']): ?>
                    <div class="meta-item">
                        <i data-lucide="bath"></i>
                        <div><strong><?= (int)$listing['bathrooms'] ?></strong> Bathrooms</div>
                    </div>
                    <?php endif; ?>
                    <div class="meta-item">
                        <i data-lucide="maximize"></i>
                        <div><strong><?= (int)$listing['area'] ?> m²</strong> Area</div>
                    </div>
                    <div class="meta-item">
                        <i data-lucide="calendar"></i>
                        <div>Listed <strong><?= date('M j, Y', strtotime($listing['created_at'])) ?></strong></div>
                    </div>
                </div>

                <h3 style="font-size:1rem;font-weight:700;margin-bottom:12px;">About This Property</h3>
                <div class="listing-description">
                    <?= nl2br(e($listing['description'])) ?>
                </div>

                <?php if ($user && in_array($user['role'], ['agent','admin']) && $user['id'] == $listing['agent_id']): ?>
                <div class="d-flex gap-2 mt-4">
                    <a href="<?= SITE_URL ?>/listings/edit.php?id=<?= $id ?>" class="btn btn-outline">
                        <i data-lucide="edit"></i> Edit Listing
                    </a>
                    <a href="<?= SITE_URL ?>/listings/delete.php?id=<?= $id ?>"
                       class="btn btn-danger"
                       data-confirm="Are you sure you want to delete this listing? This cannot be undone.">
                        <i data-lucide="trash-2"></i> Delete
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Map -->
            <div class="listing-detail-card">
                <h3 style="font-size:1rem;font-weight:700;margin-bottom:14px;"><i data-lucide="map" style="width:18px;height:18px;vertical-align:middle;margin-right:6px;"></i>Location</h3>
                <div class="map-wrap">
                    <iframe
                        src="https://maps.google.com/maps?q=<?= urlencode($listing['location']) ?>&output=embed&z=14"
                        allowfullscreen=""
                        loading="lazy"
                        title="Property location map"
                    ></iframe>
                </div>
            </div>
        </div>

        <!-- ── Right: Agent + Inquiry ── -->
        <div>
            <!-- Favorite button -->
            <button
                class="btn btn-block mb-3 card-fav-large <?= $faved?'btn-terra':'btn-outline-terra' ?>"
                data-id="<?= $id ?>"
                id="favBtn"
                style="border-radius:var(--radius-sm);padding:12px 22px;font-size:.9rem;"
            >
                <i data-lucide="heart"></i>
                <span id="favText"><?= $faved?'Saved to Favorites':'Save to Favorites' ?></span>
            </button>

            <!-- Agent card -->
            <div class="agent-card mb-3">
                <h3 style="font-size:.85rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-500);margin-bottom:14px;">Listed By</h3>
                <div class="agent-info">
                    <div class="agent-avatar-lg"><?= strtoupper(substr($listing['agent_name'], 0, 2)) ?></div>
                    <div>
                        <p class="agent-name"><?= e($listing['agent_name']) ?></p>
                        <p class="agent-tag">Licensed Agent</p>
                    </div>
                </div>
                <a href="mailto:<?= e($listing['agent_email']) ?>" class="btn btn-outline btn-block btn-sm">
                    <i data-lucide="mail"></i> <?= e($listing['agent_email']) ?>
                </a>
            </div>

            <!-- Inquiry form -->
            <div class="agent-card inquiry-form">
                <h3 style="font-size:1rem;font-weight:700;margin-bottom:16px;">
                    <i data-lucide="message-circle" style="width:18px;height:18px;vertical-align:middle;margin-right:6px;"></i>
                    Send an Inquiry
                </h3>

                <?php if ($inquirySuccess): ?>
                    <div class="alert alert-success" style="position:static;animation:none;">
                        <i data-lucide="check-circle"></i> Your inquiry has been sent! The agent will contact you soon.
                    </div>
                <?php else: ?>
                <form method="POST" novalidate>
                    <div class="form-group">
                        <label class="form-label">Your Name <span class="required">*</span></label>
                        <input
                            type="text"
                            name="sender_name"
                            class="form-control"
                            value="<?= $user ? e($user['name']) : '' ?>"
                            placeholder="Full name"
                            required
                        />
                        <?php if (isset($inquiryErrors['sender_name'])): ?>
                            <p class="form-error"><?= e($inquiryErrors['sender_name']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address <span class="required">*</span></label>
                        <input
                            type="email"
                            name="sender_email"
                            class="form-control"
                            value="<?= $user ? e($user['email']) : '' ?>"
                            placeholder="you@example.com"
                            required
                        />
                        <?php if (isset($inquiryErrors['sender_email'])): ?>
                            <p class="form-error"><?= e($inquiryErrors['sender_email']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Message <span class="required">*</span></label>
                        <textarea
                            name="message"
                            class="form-control"
                            rows="4"
                            maxlength="1000"
                            placeholder="I'm interested in this property and would like to schedule a viewing…"
                            required
                        ><?= isset($_POST['message']) ? e(sanitize($_POST['message'])) : "Hi, I'm interested in this property. Could we schedule a viewing?" ?></textarea>
                        <?php if (isset($inquiryErrors['message'])): ?>
                            <p class="form-error"><?= e($inquiryErrors['message']) ?></p>
                        <?php endif; ?>
                    </div>
                    <button type="submit" name="send_inquiry" class="btn btn-terra btn-block">
                        <i data-lucide="send"></i> Send Inquiry
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Related listings -->
    <?php if ($relatedListings): ?>
    <div class="mt-5">
        <h2 class="section-title" style="margin-bottom:24px;">Similar Properties</h2>
        <div class="card-grid">
            <?php foreach ($relatedListings as $r): ?>
            <?php
                $rImg = $r['primary_image']
                    ? getImageUrl($r['primary_image'])
                    : SITE_URL . '/assets/images/placeholder.svg';
            ?>
            <article class="property-card">
                <a href="<?= SITE_URL ?>/listings/view.php?id=<?= (int)$r['id'] ?>">
                    <div class="card-image">
                        <img src="<?= $rImg ?>" alt="<?= e($r['title']) ?>" loading="lazy" />
                        <span class="card-badge"><?= e($typeLabels[$r['type']] ?? ucfirst($r['type'])) ?></span>
                    </div>
                </a>
                <div class="card-body">
                    <div class="card-price"><?= formatPrice((float)$r['price']) ?></div>
                    <h3 class="card-title"><a href="<?= SITE_URL ?>/listings/view.php?id=<?= (int)$r['id'] ?>"><?= e($r['title']) ?></a></h3>
                    <p class="card-location"><i data-lucide="map-pin"></i> <?= e($r['location']) ?></p>
                    <div class="card-meta">
                        <span class="card-meta-item"><i data-lucide="bed-double"></i> <?= (int)$r['bedrooms'] ?> Beds</span>
                        <span class="card-meta-item"><i data-lucide="bath"></i> <?= (int)$r['bathrooms'] ?> Baths</span>
                        <span class="card-meta-item"><i data-lucide="maximize"></i> <?= (int)$r['area'] ?> m²</span>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
// Favorite button on detail page
const favBtn  = document.getElementById('favBtn');
const favText = document.getElementById('favText');
if (favBtn) {
    favBtn.addEventListener('click', async () => {
        const listingId = favBtn.dataset.id;
        const res  = await fetch(`${siteUrl}/listings/toggle_favorite.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `listing_id=${listingId}`,
        });
        const data = await res.json();
        if (data.status === 'added') {
            favBtn.classList.remove('btn-outline-terra'); favBtn.classList.add('btn-terra');
            favText.textContent = 'Saved to Favorites';
        } else if (data.status === 'removed') {
            favBtn.classList.remove('btn-terra'); favBtn.classList.add('btn-outline-terra');
            favText.textContent = 'Save to Favorites';
        } else if (data.status === 'login_required') {
            window.location.href = `${siteUrl}/auth/login.php`;
        }
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
