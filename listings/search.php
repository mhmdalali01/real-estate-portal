<?php
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();

// ── Filter inputs ────────────────────────────────────────────────────────────
$q         = sanitize($_GET['q']         ?? '');
$type      = sanitize($_GET['type']      ?? '');
$location  = sanitize($_GET['location']  ?? '');
$priceMin  = (int)($_GET['price_min']    ?? 0);
$priceMax  = (int)($_GET['price_max']    ?? 0);
$bedrooms  = (int)($_GET['bedrooms']     ?? 0);
$sortBy    = in_array($_GET['sort'] ?? '', ['price_asc','price_desc','newest','oldest'])
             ? $_GET['sort'] : 'newest';
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 9;

// ── Build query ──────────────────────────────────────────────────────────────
$where  = ["l.status = 'active'"];
$params = [];

if ($q) {
    $where[]  = "(l.title LIKE ? OR l.location LIKE ? OR l.description LIKE ?)";
    $like     = "%$q%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($type) {
    $where[]  = "l.type = ?";
    $params[] = $type;
}
if ($location) {
    $where[]  = "l.location LIKE ?";
    $params[] = "%$location%";
}
if ($priceMin > 0) {
    $where[]  = "l.price >= ?";
    $params[] = $priceMin;
}
if ($priceMax > 0) {
    $where[]  = "l.price <= ?";
    $params[] = $priceMax;
}
if ($bedrooms > 0) {
    $where[]  = "l.bedrooms >= ?";
    $params[] = $bedrooms;
}

$whereSQL = implode(' AND ', $where);

$orderSQL = match($sortBy) {
    'price_asc'  => 'l.price ASC',
    'price_desc' => 'l.price DESC',
    'oldest'     => 'l.created_at ASC',
    default      => 'l.created_at DESC',
};

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM listings l WHERE $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$pager  = paginate($total, $perPage, $page);
$offset = $pager['offset'];

// Fetch listings
$listStmt = $db->prepare(
    "SELECT l.*, u.name AS agent_name,
            (SELECT image_path FROM listing_images li WHERE li.listing_id = l.id ORDER BY li.id ASC LIMIT 1) AS primary_image
     FROM listings l
     JOIN users u ON u.id = l.agent_id
     WHERE $whereSQL
     ORDER BY $orderSQL
     LIMIT $perPage OFFSET $offset"
);
$listStmt->execute($params);
$listings = $listStmt->fetchAll();

$typeLabels = ['apartment'=>'Apartment','villa'=>'Villa','house'=>'House','office'=>'Office'];
$user       = currentUser();

$pageTitle = 'Browse Listings — EstateHub';
include __DIR__ . '/../includes/header.php';
?>
<meta name="site-url" content="<?= SITE_URL ?>">

<div class="page-header">
    <div class="container">
        <h1>Browse Properties</h1>
        <p><?= number_format($total) ?> <?= $total === 1 ? 'property' : 'properties' ?> found<?= $q ? ' for "' . e($q) . '"' : '' ?></p>
    </div>
</div>

<div class="container section-sm">
    <div class="search-layout">

        <!-- ── Filter Sidebar ── -->
        <aside class="filter-sidebar">
            <form method="GET" id="filterForm">
                <div class="filter-title">
                    <span><i data-lucide="sliders-horizontal" style="width:18px;height:18px;vertical-align:middle;"></i> Filters</span>
                    <a href="<?= SITE_URL ?>/listings/search.php" class="text-sm text-terra">Reset</a>
                </div>

                <div class="filter-section">
                    <p class="filter-section-label">Keyword</p>
                    <div class="input-group">
                        <span class="input-icon"><i data-lucide="search"></i></span>
                        <input type="text" name="q" class="form-control" value="<?= e($q) ?>" placeholder="Title or location…" />
                    </div>
                </div>

                <div class="filter-section">
                    <p class="filter-section-label">Property Type</p>
                    <div class="checkbox-group">
                        <?php foreach (['apartment','villa','house','office'] as $t): ?>
                        <label class="checkbox-item">
                            <input type="radio" name="type" value="<?= $t ?>" <?= $type===$t?'checked':'' ?>>
                            <?= ucfirst($t) ?>
                        </label>
                        <?php endforeach; ?>
                        <label class="checkbox-item">
                            <input type="radio" name="type" value="" <?= !$type?'checked':'' ?>>
                            All Types
                        </label>
                    </div>
                </div>

                <div class="filter-section">
                    <p class="filter-section-label">Price Range</p>
                    <div class="form-row form-row-2">
                        <input type="number" name="price_min" class="form-control" placeholder="Min $" value="<?= $priceMin ?: '' ?>" min="0" />
                        <input type="number" name="price_max" class="form-control" placeholder="Max $" value="<?= $priceMax ?: '' ?>" min="0" />
                    </div>
                </div>

                <div class="filter-section">
                    <p class="filter-section-label">Min. Bedrooms</p>
                    <select name="bedrooms" class="form-control">
                        <option value="0" <?= !$bedrooms?'selected':'' ?>>Any</option>
                        <?php foreach ([1,2,3,4,5] as $b): ?>
                        <option value="<?= $b ?>" <?= $bedrooms===$b?'selected':'' ?>><?= $b ?>+</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-section">
                    <p class="filter-section-label">Location</p>
                    <input type="text" name="location" class="form-control" placeholder="City or neighbourhood" value="<?= e($location) ?>" />
                </div>

                <input type="hidden" name="sort" value="<?= e($sortBy) ?>" />
                <button type="submit" class="btn btn-primary btn-block mt-2">
                    <i data-lucide="filter"></i> Apply Filters
                </button>
            </form>
        </aside>

        <!-- ── Results ── -->
        <div class="search-results">
            <div class="results-header">
                <p class="results-count">
                    <strong><?= number_format($total) ?></strong> <?= $total===1?'property':'properties' ?> found
                </p>
                <select class="sort-select" onchange="changeSortOrder(this.value)">
                    <option value="newest"     <?= $sortBy==='newest'?'selected':'' ?>>Newest First</option>
                    <option value="oldest"     <?= $sortBy==='oldest'?'selected':'' ?>>Oldest First</option>
                    <option value="price_asc"  <?= $sortBy==='price_asc'?'selected':'' ?>>Price: Low → High</option>
                    <option value="price_desc" <?= $sortBy==='price_desc'?'selected':'' ?>>Price: High → Low</option>
                </select>
            </div>

            <?php if ($listings): ?>
            <div class="card-grid">
                <?php foreach ($listings as $listing): ?>
                <?php
                    $imgSrc = $listing['primary_image']
                        ? getImageUrl($listing['primary_image'])
                        : SITE_URL . '/assets/images/placeholder.svg';
                    $faved  = $user ? isFavorited((int)$user['id'], (int)$listing['id']) : false;
                ?>
                <article class="property-card" style="position:relative;">
                    <a href="<?= SITE_URL ?>/listings/view.php?id=<?= (int)$listing['id'] ?>">
                        <div class="card-image">
                            <img src="<?= $imgSrc ?>" alt="<?= e($listing['title']) ?>" loading="lazy" />
                            <span class="card-badge"><?= e($typeLabels[$listing['type']] ?? ucfirst($listing['type'])) ?></span>
                            <button
                                class="card-fav <?= $faved?'active':'' ?>"
                                data-id="<?= (int)$listing['id'] ?>"
                                onclick="event.preventDefault()"
                                title="<?= $faved?'Remove from favorites':'Add to favorites' ?>"
                            ><i data-lucide="heart"></i></button>
                        </div>
                    </a>
                    <div class="card-body">
                        <div class="card-price"><?= formatPrice((float)$listing['price']) ?></div>
                        <h3 class="card-title">
                            <a href="<?= SITE_URL ?>/listings/view.php?id=<?= (int)$listing['id'] ?>"><?= e($listing['title']) ?></a>
                        </h3>
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

            <!-- Pagination -->
            <?php if ($pager['pages'] > 1): ?>
            <?php
                $queryParams = $_GET;
                unset($queryParams['page']);
                $baseUrl = SITE_URL . '/listings/search.php?' . http_build_query($queryParams) . '&page=';
            ?>
            <div class="pagination mt-4">
                <a class="page-item <?= $pager['current']===1?'disabled':'' ?>"
                   href="<?= $baseUrl . ($pager['current']-1) ?>">‹</a>
                <?php for ($i = 1; $i <= $pager['pages']; $i++): ?>
                    <?php if (abs($i - $pager['current']) <= 2 || $i === 1 || $i === $pager['pages']): ?>
                        <a class="page-item <?= $i===$pager['current']?'active':'' ?>"
                           href="<?= $baseUrl . $i ?>"><?= $i ?></a>
                    <?php elseif (abs($i - $pager['current']) === 3): ?>
                        <span class="page-item disabled">…</span>
                    <?php endif; ?>
                <?php endfor; ?>
                <a class="page-item <?= $pager['current']===$pager['pages']?'disabled':'' ?>"
                   href="<?= $baseUrl . ($pager['current']+1) ?>">›</a>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="empty-state">
                <i data-lucide="search-x"></i>
                <h3>No properties found</h3>
                <p>Try adjusting your search filters.</p>
                <a href="<?= SITE_URL ?>/listings/search.php" class="btn btn-outline mt-2">Clear Filters</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function changeSortOrder(val) {
    const form = document.getElementById('filterForm');
    form.querySelector('[name="sort"]').value = val;
    form.submit();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
