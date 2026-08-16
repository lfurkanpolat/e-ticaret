<?php
// =====================================================
// ADMIN FOOTER - admin/footer.php
// =====================================================
?>
        </div>
        <!-- ============================================ -->
        <!-- PAGE CONTENT END -->
        <!-- ============================================ -->
        
    </main>
    
    <!-- ============================================ -->
    <!-- ADMIN SCRIPT -->
    <!-- ============================================ -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // ============================================
            // SIDEBAR TOGGLE
            // ============================================
            const sidebar = document.getElementById('adminSidebar');
            const toggleBtn = document.getElementById('sidebarToggle');
            const toggleMobileBtn = document.getElementById('sidebarToggleMobile');
            
            // Desktop toggle
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                });
            }
            
            // Mobile toggle
            if (toggleMobileBtn) {
                toggleMobileBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('mobile-open');
                });
            }
            
            // Click outside close mobile sidebar
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    if (!sidebar.contains(e.target) && !toggleMobileBtn.contains(e.target)) {
                        sidebar.classList.remove('mobile-open');
                    }
                }
            });
            
            // ============================================
            // RESPONSIVE SIDEBAR
            // ============================================
            function handleResize() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('mobile-open');
                }
            }
            
            window.addEventListener('resize', handleResize);
            
        });
    </script>
    
</body>
</html>