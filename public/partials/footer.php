    </main>
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div>
                    <h3>🧴 Scentology</h3>
                    <p>Your personal fragrance vault, marketplace, and community for perfume enthusiasts.</p>
                </div>
                <div>
                    <h3>Explore</h3>
                    <a href="perfumes.php">Browse Perfumes</a>
                    <a href="brands.php">Popular Brands</a>
                    <a href="shops.php">Find Shops</a>
                    <a href="reviews.php">Community Reviews</a>
                </div>
                <div>
                    <h3>Community</h3>
                    <a href="trades.php">Trade Requests</a>
                    <a href="listings.php">Market Listings</a>
                    <a href="wishlist.php">My Wishlist</a>
                    <?php if (is_logged_in()): ?>
                        <a href="profile.php">My Profile</a>
                    <?php endif; ?>
                </div>
                <div>
                    <h3>Support</h3>
                    <a href="#">About Us</a>
                    <a href="#">Contact</a>
                    <a href="#">FAQ</a>
                    <a href="#">Privacy Policy</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Scentology. All rights reserved. Built for fragrance lovers, by fragrance lovers. 💎</p>
            </div>
        </div>
    </footer>
</body>
</html>
