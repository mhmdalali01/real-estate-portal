<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <div class="brand">
                    <span class="brand-icon">🏠</span>
                    <span class="brand-text">EstateHub</span>
                </div>
                <p class="footer-desc">Connecting people with their perfect properties since 2024. Trusted by thousands of buyers and agents.</p>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
                    <li><a href="<?= SITE_URL ?>/listings/search.php">Browse Listings</a></li>
                    <li><a href="<?= SITE_URL ?>/auth/register.php">Register</a></li>
                    <li><a href="<?= SITE_URL ?>/auth/login.php">Login</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Property Types</h4>
                <ul>
                    <li><a href="<?= SITE_URL ?>/listings/search.php?type=apartment">Apartments</a></li>
                    <li><a href="<?= SITE_URL ?>/listings/search.php?type=villa">Villas</a></li>
                    <li><a href="<?= SITE_URL ?>/listings/search.php?type=house">Houses</a></li>
                    <li><a href="<?= SITE_URL ?>/listings/search.php?type=office">Offices</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Contact</h4>
                <ul class="footer-contact">
                    <li><i data-lucide="map-pin"></i> 123 Realty Ave, NY 10001</li>
                    <li><i data-lucide="phone"></i> +1 (800) 555-0199</li>
                    <li><i data-lucide="mail"></i> hello@estatehub.com</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> EstateHub. All rights reserved.</p>
            <p>Built with PHP &amp; MySQL</p>
        </div>
    </div>
</footer>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<script>
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
</body>
</html>
