<?php
/**
 * Database Configuration
 * Uses PDO with prepared statements for all queries
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'real_estate_portal');
define('DB_USER', 'root');       // Change to your MySQL username
define('DB_PASS', 'Mhmd2005@');           // Change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

define('SITE_URL', 'http://localhost:8000');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production, log instead of exposing the message
            error_log('DB connection failed: ' . $e->getMessage());
            die('<div style="padding:2rem;font-family:sans-serif;color:#c0392b;">
                <h2>Database Connection Error</h2>
                <p>Could not connect to the database. Please check your configuration in <code>config/db.php</code>.</p>
            </div>');
        }
    }
    return $pdo;
}
