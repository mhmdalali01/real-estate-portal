<?php
/**
 * EstateHub — One-time setup script
 * Run this ONCE via browser or CLI: php setup.php
 * Then DELETE this file from your server.
 */

// Prevent accidental double-run in production
if (file_exists(__DIR__ . '/.setup_done')) {
    die('<h2>Setup already completed.</h2><p>Delete .setup_done if you want to re-run.</p>');
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'real_estate_portal');
define('DB_USER', 'root');   // ← change if needed
define('DB_PASS', 'Mhmd2005@');   // ← change if needed

header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><title>EstateHub Setup</title>
<style>body{font-family:sans-serif;max-width:700px;margin:40px auto;padding:0 24px;}
pre{background:#f5f5f5;padding:12px;border-radius:6px;overflow:auto;}
.ok{color:#2D6A4F;} .err{color:#C0392B;} .info{color:#1565C0;}
</style></head><body>';
echo '<h1>🏠 EstateHub — Database Setup</h1>';

$steps = [];
$ok    = true;

try {
    // Connect without DB name first
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");
    $steps[] = ['ok', 'Database `' . DB_NAME . '` created / verified.'];

    // ── Schema ──────────────────────────────────────────────────────────────

    $pdo->exec("DROP TABLE IF EXISTS `favorites`");
    $pdo->exec("DROP TABLE IF EXISTS `inquiries`");
    $pdo->exec("DROP TABLE IF EXISTS `listing_images`");
    $pdo->exec("DROP TABLE IF EXISTS `listings`");
    $pdo->exec("DROP TABLE IF EXISTS `users`");

    $pdo->exec("CREATE TABLE `users` (
        `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `name`       VARCHAR(120) NOT NULL,
        `email`      VARCHAR(180) NOT NULL UNIQUE,
        `password`   VARCHAR(255) NOT NULL,
        `role`       ENUM('user','agent','admin') NOT NULL DEFAULT 'user',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_email` (`email`),
        INDEX `idx_role`  (`role`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE `listings` (
        `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `agent_id`    INT UNSIGNED NOT NULL,
        `title`       VARCHAR(255) NOT NULL,
        `description` TEXT NOT NULL,
        `type`        ENUM('apartment','villa','house','office') NOT NULL,
        `price`       DECIMAL(14,2) NOT NULL,
        `location`    VARCHAR(255) NOT NULL,
        `bedrooms`    TINYINT UNSIGNED NOT NULL DEFAULT 0,
        `bathrooms`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
        `area`        DECIMAL(10,2) NOT NULL,
        `status`      ENUM('active','pending','removed') NOT NULL DEFAULT 'active',
        `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_agent`  (`agent_id`),
        INDEX `idx_status` (`status`),
        INDEX `idx_type`   (`type`),
        CONSTRAINT `fk_listing_agent` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE `listing_images` (
        `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `listing_id` INT UNSIGNED NOT NULL,
        `image_path` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_listing` (`listing_id`),
        CONSTRAINT `fk_image_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE `favorites` (
        `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id`    INT UNSIGNED NOT NULL,
        `listing_id` INT UNSIGNED NOT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_user_listing` (`user_id`, `listing_id`),
        CONSTRAINT `fk_fav_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`    (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_fav_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE `inquiries` (
        `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `listing_id`   INT UNSIGNED NOT NULL,
        `sender_name`  VARCHAR(120) NOT NULL,
        `sender_email` VARCHAR(180) NOT NULL,
        `message`      TEXT NOT NULL,
        `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_listing` (`listing_id`),
        CONSTRAINT `fk_inq_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $steps[] = ['ok', 'All tables created.'];

    // ── Seed users with proper password hashes ────────────────────────────

    $pass = password_hash('password123', PASSWORD_DEFAULT);
    $ins  = $pdo->prepare("INSERT INTO `users` (name, email, password, role) VALUES (?,?,?,?)");

    $ins->execute(['Admin User',    'admin@estatehub.com',  $pass, 'admin']);
    $ins->execute(['Sarah Johnson', 'agent1@estatehub.com', $pass, 'agent']);
    $ins->execute(['Michael Chen',  'agent2@estatehub.com', $pass, 'agent']);
    $ins->execute(['Emily Davis',   'user1@estatehub.com',  $pass, 'user']);
    $ins->execute(['James Wilson',  'user2@estatehub.com',  $pass, 'user']);

    $steps[] = ['ok', 'Users seeded (password: <strong>password123</strong> for all accounts).'];

    // ── Seed listings ────────────────────────────────────────────────────────
    $insList = $pdo->prepare(
        "INSERT INTO `listings` (agent_id, title, description, type, price, location, bedrooms, bathrooms, area, status)
         VALUES (?,?,?,?,?,?,?,?,?,?)"
    );

    $listings = [
        [2, 'Modern Downtown Apartment with City Views',
         'Stunning 2-bedroom apartment in the heart of downtown. Floor-to-ceiling windows offer breathtaking city views. Recently renovated with a sleek open-plan kitchen, premium appliances, and spa-style bathrooms. Building amenities include 24/7 concierge, rooftop terrace, and gym. Walking distance to top restaurants and transit.',
         'apartment', 485000.00, 'Manhattan, New York, NY', 2, 2, 95.00, 'active'],
        [2, 'Charming Brooklyn Brownstone',
         'Classic 4-bedroom Brooklyn brownstone lovingly restored to its original grandeur while featuring modern conveniences. Original hardwood floors, exposed brick, high ceilings, and a private backyard garden. Chef\'s kitchen with marble countertops. Two-car parking.',
         'house', 1250000.00, 'Brooklyn, New York, NY', 4, 3, 210.00, 'active'],
        [2, 'Luxury Beachfront Villa in Miami',
         'Exclusive 5-bedroom villa with direct ocean access. This architectural masterpiece features an infinity pool, home theatre, gourmet kitchen, and smart-home technology throughout. Wraparound terraces, outdoor BBQ area, and private dock. Located in a gated community with 24-hour security.',
         'villa', 3750000.00, 'Miami Beach, Florida', 5, 5, 520.00, 'active'],
        [2, 'Cozy Studio Near Central Park',
         'Bright and efficient studio in a prime Upper West Side location, just two blocks from Central Park. Fully renovated with Scandinavian design elements, built-in storage solutions, and a sleek kitchenette. Perfect for young professionals.',
         'apartment', 320000.00, 'Upper West Side, New York, NY', 0, 1, 42.00, 'active'],
        [3, 'Spacious Family Home in Suburban Oasis',
         'Beautiful 5-bedroom family home on a quiet cul-de-sac with a large backyard and mature trees. Features an open-concept living area, formal dining room, gourmet kitchen, and a dedicated home office. Three-car garage and finished basement. Top school district.',
         'house', 895000.00, 'Naperville, Illinois', 5, 4, 340.00, 'active'],
        [3, 'Contemporary Penthouse Suite',
         'Exclusive top-floor penthouse with 360° panoramic views of the city skyline and mountains. Three bedrooms, a private rooftop terrace, double-height ceilings, chef\'s kitchen, and a master suite with private balcony. Two dedicated underground parking spaces included.',
         'apartment', 2100000.00, 'Downtown Los Angeles, CA', 3, 3, 260.00, 'active'],
        [3, 'Prime Office Space in Financial District',
         'Fully fitted Grade-A office space on the 22nd floor of a landmark building. Open plan with private meeting rooms, server room, reception area, and a kitchenette. Floor-to-ceiling glazing with harbour views. Fibre internet, 24/7 access.',
         'office', 1200000.00, 'Financial District, San Francisco, CA', 0, 2, 180.00, 'active'],
        [3, 'Tuscany-Inspired Villa with Private Pool',
         'Breathtaking 6-bedroom estate with Tuscan architecture set on 2 acres of manicured gardens. Hand-painted ceilings, antique tile floors, a gourmet kitchen, wine cellar, home theatre, and a heated Olympic-size pool with pool house. 4-car garage.',
         'villa', 4850000.00, 'Malibu, California', 6, 6, 680.00, 'active'],
        [2, 'Modern Co-Working Office Hub',
         'State-of-the-art open plan office in a newly built tech campus. Features flexible hot-desking, 8 private meeting rooms, a podcast studio, rooftop terrace, and a fully stocked café kitchen. Excellent transport links. Partial or whole floor leasing available.',
         'office', 780000.00, 'SoHo, New York, NY', 0, 3, 420.00, 'active'],
        [2, 'Rustic Mountain Retreat House',
         'Stunning 3-bedroom mountain home with panoramic alpine views. Warm timber interiors, stone fireplace, vaulted ceilings, gourmet kitchen with Viking appliances, and a wraparound deck. Private hot tub, ski storage, and a 2-car garage. Steps from ski runs.',
         'house', 675000.00, 'Aspen, Colorado', 3, 2, 185.00, 'active'],
        [3, 'Waterfront Luxury Apartment',
         'Spectacular 3-bedroom waterfront apartment with direct marina views. Open-plan living, premium finishes, island kitchen, and floor-to-ceiling glazing. Two balconies and spa-style master bathroom. Building features pool, gym, and concierge. One boat berth included.',
         'apartment', 1450000.00, 'Miami, Florida', 3, 2, 175.00, 'active'],
        [3, 'Urban Loft in Arts District',
         'One-of-a-kind 2-bedroom loft in the heart of the Arts District. Original exposed brick, soaring 14-foot ceilings, polished concrete floors, and oversized industrial windows. Open gourmet kitchen, custom built-ins, private rooftop access.',
         'apartment', 595000.00, 'Arts District, Los Angeles, CA', 2, 2, 130.00, 'active'],
    ];

    foreach ($listings as $l) {
        $insList->execute($l);
    }
    $steps[] = ['ok', count($listings) . ' listings seeded.'];

    // ── Listing Images ────────────────────────────────────────────────────────
    $insImg = $pdo->prepare('INSERT INTO listing_images (listing_id, image_path) VALUES (?, ?)');
    $listingImages = [
        [1,  'apt-1-modern-downtown.jpg'],
        [2,  'apt-2-brooklyn-brownstone.jpg'],
        [3,  'apt-3-beachfront-villa.jpg'],
        [4,  'apt-4-cozy-studio.jpg'],
        [5,  'apt-5-suburban-home.jpg'],
        [6,  'apt-6-penthouse-suite.jpg'],
        [7,  'apt-7-financial-office.jpg'],
        [8,  'apt-8-tuscany-villa.jpg'],
        [9,  'apt-9-modern-office-hub.jpg'],
        [10, 'apt-10-luxury-flat.jpg'],
        [11, 'apt-11-waterfront-apartment.jpg'],
        [12, 'apt-12-urban-loft.jpg'],
    ];
    foreach ($listingImages as $img) {
        $insImg->execute($img);
    }
    $steps[] = ['ok', count($listingImages) . ' listing images seeded.'];

    // ── Inquiries ────────────────────────────────────────────────────────────
    $insInq = $pdo->prepare(
        "INSERT INTO `inquiries` (listing_id, sender_name, sender_email, message) VALUES (?,?,?,?)"
    );
    $insInq->execute([1, 'Emily Davis',  'user1@estatehub.com', 'Hi, I am very interested in this apartment and would love to schedule a viewing this weekend. Is Saturday afternoon available?']);
    $insInq->execute([1, 'James Wilson', 'user2@estatehub.com', 'Could you please share more details about HOA fees and building amenities?']);
    $insInq->execute([3, 'Emily Davis',  'user1@estatehub.com', 'This villa looks incredible! What is the shortest lease term available?']);
    $insInq->execute([5, 'James Wilson', 'user2@estatehub.com', 'We are a family of five and love this home. Could you arrange a private showing?']);
    $insInq->execute([7, 'Emily Davis',  'user1@estatehub.com', 'We are a startup looking for flexible office space for 15 people. Is a partial floor available?']);
    $steps[] = ['ok', '5 sample inquiries seeded.'];

    // ── Favorites ────────────────────────────────────────────────────────────
    $insFav = $pdo->prepare("INSERT INTO `favorites` (user_id, listing_id) VALUES (?,?)");
    foreach ([[4,1],[4,3],[4,6],[5,2],[5,5],[5,8]] as [$u,$l]) {
        $insFav->execute([$u,$l]);
    }
    $steps[] = ['ok', 'Sample favorites seeded.'];

    // Mark done
    file_put_contents(__DIR__ . '/.setup_done', date('Y-m-d H:i:s'));

} catch (Throwable $e) {
    $steps[] = ['err', 'ERROR: ' . $e->getMessage()];
    $ok = false;
}

foreach ($steps as [$type, $msg]) {
    $icon = $type === 'ok' ? '✅' : '❌';
    echo "<p class=\"$type\">$icon $msg</p>";
}

if ($ok): ?>
<hr>
<h2 class="ok">✅ Setup Complete!</h2>
<h3>Demo Accounts (password: <code>password123</code>)</h3>
<table border="1" cellpadding="8" style="border-collapse:collapse;border-color:#ddd;">
<tr><th>Role</th><th>Email</th></tr>
<tr><td>Admin</td><td>admin@estatehub.com</td></tr>
<tr><td>Agent</td><td>agent1@estatehub.com</td></tr>
<tr><td>Agent</td><td>agent2@estatehub.com</td></tr>
<tr><td>User</td><td>user1@estatehub.com</td></tr>
<tr><td>User</td><td>user2@estatehub.com</td></tr>
</table>
<p style="margin-top:20px;"><strong>⚠️ Security:</strong> Delete this file after setup: <code>rm setup.php</code></p>
<p><a href="index.php" style="color:#1E3A5F;font-weight:bold;">→ Go to Homepage</a></p>
<?php else: ?>
<hr>
<h2 class="err">❌ Setup failed. Check your database credentials in setup.php.</h2>
<?php endif; ?>
</body></html>
