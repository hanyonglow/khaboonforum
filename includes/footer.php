        </main>

        <footer class="main-footer">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-info-circle"></i> About KhaboonForum</h3>
                    <p>A simple, anonymous social media platform where you can share pictures and thoughts without the need for login.</p>
                </div>
                
                <div class="footer-section">
                    <h3><i class="fas fa-shield-alt"></i> Privacy</h3>
                    <p>No login required. Your name is cached locally in your browser. IP addresses are stored for moderation purposes only.</p>
                </div>
                
                <div class="footer-section">
                    <h3><i class="fas fa-exclamation-triangle"></i> Community Guidelines</h3>
                    <p>Be respectful. No hate speech, harassment, or illegal content. Keep it friendly!</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> KhaboonForum. Built for Hostinger Basic Hosting.</p>
                <p class="stats">
                    <?php
                    $pdo = get_db_connection();
                    if ($pdo) {
                        $post_count = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
                        $comment_count = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
                        echo "<i class='fas fa-newspaper'></i> $post_count posts • <i class='fas fa-comments'></i> $comment_count comments";
                    }
                    ?>
                </p>
            </div>
        </footer>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Theme toggle
        document.getElementById('theme-toggle').addEventListener('click', function(e) {
            e.preventDefault();
            const body = document.body;
            const icon = this.querySelector('i');
            
            if (body.classList.contains('dark-theme')) {
                body.classList.remove('dark-theme');
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
                localStorage.setItem('theme', 'light');
            } else {
                body.classList.add('dark-theme');
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
                localStorage.setItem('theme', 'dark');
            }
        });

        // Load saved theme
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-theme');
            document.querySelector('#theme-toggle i').classList.remove('fa-moon');
            document.querySelector('#theme-toggle i').classList.add('fa-sun');
        }

        // Auto-save name to cookie when typing in name fields
        document.addEventListener('DOMContentLoaded', function() {
            const nameInputs = document.querySelectorAll('input[name="name"], input[name="user_name"]');
            const cachedName = document.getElementById('cached_name').value;
            
            nameInputs.forEach(input => {
                if (!input.value && cachedName) {
                    input.value = cachedName;
                }
                
                input.addEventListener('input', function() {
                    if (this.value.trim()) {
                        document.cookie = `khaboon_name=${encodeURIComponent(this.value)}; path=/; max-age=${60*60*24*30}`; // 30 days
                    }
                });
            });
        });
    </script>
</body>
</html>