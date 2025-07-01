        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><?php echo htmlspecialchars($settings['site_title']); ?></h3>
                    <p><?php echo htmlspecialchars($settings['site_description']); ?></p>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="/">Home</a></li>
                        <li><a href="/admin/">Admin Panel</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>About</h4>
                    <p>Powered by SimpleBlog - A modern, responsive blogging platform.</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['site_title']); ?>. All rights reserved.</p>
                <p class="powered-by">Powered by <strong>SimpleBlog</strong></p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.querySelector('.mobile-menu-toggle');
            const navigation = document.querySelector('.main-navigation');
            
            if (mobileToggle && navigation) {
                mobileToggle.addEventListener('click', function() {
                    navigation.classList.toggle('active');
                    mobileToggle.classList.toggle('active');
                });
                
                // Close mobile menu when clicking outside
                document.addEventListener('click', function(e) {
                    if (!navigation.contains(e.target) && !mobileToggle.contains(e.target)) {
                        navigation.classList.remove('active');
                        mobileToggle.classList.remove('active');
                    }
                });
            }
            
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>