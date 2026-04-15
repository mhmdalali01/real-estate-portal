<?php
/**
 * Add house images to listings using direct URLs (no download needed)
 * Uses free, high-quality image services: Unsplash, Pexels, Picsum
 * 
 * Usage: php seed_images_urls.php
 */

require_once __DIR__ . '/config/db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Collection of free, real house images with direct URLs
$houseImages = [
    'apartment' => [
        'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=60', // Modern apartment
        'https://images.unsplash.com/photo-1572177812156-58036aae439c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60', // Luxury apartment
        'https://images.unsplash.com/photo-1545324418-cc1a9a6fded0?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60', // Modern living room
        'https://images.unsplash.com/photo-1512917774080-9b41ca00a8b8?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60', // Urban apartment
        'https://images.unsplash.com/photo-1536512010609-5f6f4fa4fa1e?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60', // Contemporary apartment
    ],
    'house' => [
        'https://images.unsplash.com/photo-1568605114967-8130f3a36994?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60', // Modern house
        'https://images.unsplash.com/photo-1570129477492-45a003537e1f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60', // Family home
        'https://images.unsplash.com/photo-1570129477492-45a003537e1f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60', // Suburban house
        'https://images.unsplash.com/photo-1549399542-7e3f8b83ad38?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60', // Contemporary house
        'https://images.unsplash.com/photo-1552321554-5fefe8c9ef14?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60', // Residential house
    ],
    'villa' => [
        'https://images.unsplash.com/photo-1570129477492-45a003537e1f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60', // Luxury villa
        'https://images.unsplash.com/photo-1512917774080-9b41ca00a8b8?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60', // Modern villa
        'https://images.unsplash.com/photo-1568605114967-8130f3a36994?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60', // Elegant villa
        'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60', // Resort villa
        'https://images.unsplash.com/photo-1552321554-5fefe8c9ef14?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60', // Luxury estate
    ],
    'office' => [
        'https://images.unsplash.com/photo-1497366216548-37526070297c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60', // Modern office
        'https://images.unsplash.com/photo-1552664730-d307ca884978?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60', // Office space
        'https://images.unsplash.com/photo-1575377135033-3712569cfd40?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60', // Business office
        'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60', // Corporate office
        'https://images.unsplash.com/photo-1557804506-669714d2e9d8?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60', // Creative office
    ]
];

function main(): void {
    global $houseImages;
    
    $db = getDB();
    
    // Get all listings
    $stmt = $db->prepare('SELECT id, type FROM listings ORDER BY id');
    $stmt->execute();
    $listings = $stmt->fetchAll();
    
    if (empty($listings)) {
        echo "[!] No listings found!\n";
        return;
    }
    
    echo sprintf("[*] Found %d listings. Adding house images...\n\n", count($listings));
    
    $updated = 0;
    
    foreach ($listings as $i => $listing) {
        $id = $listing['id'];
        $type = $listing['type'];
        
        // Pick a random image for this property type
        $images = $houseImages[$type] ?? [];
        if (empty($images)) {
            echo sprintf("[%d/%d] No images available for type: %s\n", $i + 1, count($listings), $type);
            continue;
        }
        
        $imageUrl = $images[array_rand($images)];
        
        // Delete old images for this listing
        $deleteStmt = $db->prepare('DELETE FROM listing_images WHERE listing_id = ?');
        $deleteStmt->execute([$id]);
        
        // Insert new image URL
        $insertStmt = $db->prepare(
            'INSERT INTO listing_images (listing_id, image_path) VALUES (?, ?)'
        );
        $insertStmt->execute([$id, $imageUrl]);
        
        echo sprintf("[%d/%d] ✓ Added high-quality %s image\n", $i + 1, count($listings), $type);
        $updated++;
    }
    
    echo sprintf("\n[✓] Complete: %d listings updated with images!\n", $updated);
    echo "[✓] Images loaded from Unsplash (free, high-quality)\n";
}

// Run
main();
