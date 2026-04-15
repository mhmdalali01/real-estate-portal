<?php
require_once __DIR__ . '/includes/functions.php';

$db = getDB();
$pageTitle = 'EstateHub — Find Your Dream Home';
$bodyClass = 'home-page';

// Fetch featured listings (latest active)
$featured = $db->query(
    "SELECT l.*, u.name AS agent_name,
            (SELECT image_path FROM listing_images li WHERE li.listing_id = l.id ORDER BY li.id ASC LIMIT 1) AS primary_image
     FROM listings l
     JOIN users u ON u.id = l.agent_id
     WHERE l.status = 'active'
     ORDER BY l.created_at DESC
     LIMIT 6"
)->fetchAll();

// Stats
$stats = $db->query("SELECT
    (SELECT COUNT(*) FROM listings WHERE status='active') AS total_listings,
    (SELECT COUNT(*) FROM users WHERE role='agent') AS total_agents,
    (SELECT COUNT(*) FROM users) AS total_users,
    (SELECT COUNT(*) FROM inquiries) AS total_inquiries
")->fetch();

include __DIR__ . '/includes/header.php';
?>
<!-- siteUrl meta -->
<meta name="site-url" content="<?= SITE_URL ?>">

<!-- ── Hero ───────────────────────────────────────────────────── -->
<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <div class="hero-badge">
            <span>🏆</span> Trusted by thousands of families
        </div>
        <h1>Find Your Perfect<br><span>Dream Home</span></h1>
        <p>Explore thousands of premium listings — apartments, villas, houses and offices curated by expert agents.</p>

        <!-- Search bar -->
        <form class="search-bar" action="<?= SITE_URL ?>/listings/search.php" method="GET">
            <input
                type="text"
                name="q"
                placeholder="Search by location, title…"
                autocomplete="off"
            />
            <div class="search-divider"></div>
            <select name="type">
                <option value="">All Types</option>
                <option value="apartment">Apartment</option>
                <option value="villa">Villa</option>
                <option value="house">House</option>
                <option value="office">Office</option>
            </select>
            <div class="search-divider"></div>
            <select name="bedrooms">
                <option value="">Bedrooms</option>
                <option value="1">1+</option>
                <option value="2">2+</option>
                <option value="3">3+</option>
                <option value="4">4+</option>
            </select>
            <button type="submit" class="btn btn-terra">
                <i data-lucide="search"></i> Search
            </button>
        </form>

        <div class="hero-stats">
            <div class="hero-stat">
                <strong><?= number_format($stats['total_listings']) ?>+</strong>
                <span>Active Listings</span>
            </div>
            <div class="hero-stat">
                <strong><?= number_format($stats['total_agents']) ?>+</strong>
                <span>Expert Agents</span>
            </div>
            <div class="hero-stat">
                <strong><?= number_format($stats['total_users']) ?>+</strong>
                <span>Happy Clients</span>
            </div>
        </div>
    </div>
</section>

<!-- ── Type chips ─────────────────────────────────────────────── -->
<section class="section-sm" style="background:var(--white); border-bottom:1px solid var(--gray-200);">
    <div class="container">
        <div class="type-chips" style="justify-content:center;">
            <a href="<?= SITE_URL ?>/listings/search.php" class="type-chip active">🏘️ All Properties</a>
            <a href="<?= SITE_URL ?>/listings/search.php?type=apartment" class="type-chip">🏢 Apartments</a>
            <a href="<?= SITE_URL ?>/listings/search.php?type=villa" class="type-chip">🏖️ Villas</a>
            <a href="<?= SITE_URL ?>/listings/search.php?type=house" class="type-chip">🏡 Houses</a>
            <a href="<?= SITE_URL ?>/listings/search.php?type=office" class="type-chip">🏗️ Offices</a>
        </div>
    </div>
</section>

<!-- ── Featured Listings ──────────────────────────────────────── -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Featured Listings</h2>
                <p class="section-sub">Hand-picked properties from our top agents</p>
            </div>
            <a href="<?= SITE_URL ?>/listings/search.php" class="btn btn-outline btn-sm">
                View All <i data-lucide="arrow-right"></i>
            </a>
        </div>

        <?php if ($featured): ?>
        <div class="card-grid">
            <?php foreach ($featured as $listing): ?>
            <?php
                $imgSrc = $listing['primary_image']
                    ? getImageUrl($listing['primary_image'])
                    : SITE_URL . '/assets/images/placeholder.svg';
                $user = currentUser();
                $faved = $user ? isFavorited((int)$user['id'], (int)$listing['id']) : false;
                $typeLabels = ['apartment'=>'Apartment','villa'=>'Villa','house'=>'House','office'=>'Office'];
            ?>
            <article class="property-card" style="position:relative;">
                <a href="<?= SITE_URL ?>/listings/view.php?id=<?= (int)$listing['id'] ?>">
                    <div class="card-image">
                        <img src="<?= $imgSrc ?>" alt="<?= e($listing['title']) ?>" loading="lazy" />
                        <span class="card-badge"><?= e($typeLabels[$listing['type']] ?? $listing['type']) ?></span>
                        <button
                            class="card-fav <?= $faved ? 'active' : '' ?>"
                            data-id="<?= (int)$listing['id'] ?>"
                            title="<?= $faved ? 'Remove from favorites' : 'Add to favorites' ?>"
                            onclick="event.preventDefault()"
                        ><i data-lucide="heart"></i></button>
                    </div>
                </a>
                <div class="card-body">
                    <div class="card-price"><?= formatPrice((float)$listing['price']) ?></div>
                    <h3 class="card-title">
                        <a href="<?= SITE_URL ?>/listings/view.php?id=<?= (int)$listing['id'] ?>"><?= e($listing['title']) ?></a>
                    </h3>
                    <p class="card-location">
                        <i data-lucide="map-pin"></i>
                        <?= e($listing['location']) ?>
                    </p>
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
            <i data-lucide="home"></i>
            <h3>No listings yet</h3>
            <p>Check back soon — agents are adding new properties.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ── Why Choose Us ──────────────────────────────────────────── -->
<section class="section" style="background:var(--white);">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="section-title">Why Choose EstateHub?</h2>
            <p class="section-sub">Everything you need to find, buy, or rent your next property</p>
        </div>
        <div class="feature-grid">
            <div class="feature-tile">
                <div class="feature-icon"><i data-lucide="shield-check"></i></div>
                <h3>Verified Listings</h3>
                <p>All properties are reviewed and verified by our admin team before going live.</p>
            </div>
            <div class="feature-tile">
                <div class="feature-icon"><i data-lucide="search"></i></div>
                <h3>Smart Search</h3>
                <p>Advanced filters for type, price range, location, bedrooms, and more.</p>
            </div>
            <div class="feature-tile">
                <div class="feature-icon"><i data-lucide="users"></i></div>
                <h3>Expert Agents</h3>
                <p>Connect directly with licensed agents who know the market inside out.</p>
            </div>
            <div class="feature-tile">
                <div class="feature-icon"><i data-lucide="heart"></i></div>
                <h3>Save Favorites</h3>
                <p>Bookmark properties you love and compare them at your own pace.</p>
            </div>
        </div>
    </div>
</section>

<!-- ── CTA Banner ─────────────────────────────────────────────── -->
<?php if (!isLoggedIn()): ?>
<section class="section" style="background:linear-gradient(135deg,var(--blue) 0%,var(--blue-mid) 100%);color:#fff;">
    <div class="container text-center">
        <h2 style="font-size:2rem;font-weight:700;margin-bottom:12px;">Ready to Find Your Home?</h2>
        <p style="opacity:.85;margin-bottom:28px;max-width:480px;margin-left:auto;margin-right:auto;">Create a free account and start saving your favorite properties today.</p>
        <div class="d-flex gap-2" style="justify-content:center;flex-wrap:wrap;">
            <a href="<?= SITE_URL ?>/auth/register.php" class="btn btn-terra btn-lg">Get Started Free</a>
            <a href="<?= SITE_URL ?>/listings/search.php" class="btn btn-lg" style="background:rgba(255,255,255,.15);color:#fff;border:2px solid rgba(255,255,255,.4);">Browse Listings</a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
