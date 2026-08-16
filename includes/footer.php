<?php
// =====================================================
// PUBLIC FOOTER - SHOPI TASARIMI
// Dizin: public/footer.php
// =====================================================
?>



<!-- ============================================ -->
<!-- FEATURES -->
<!-- ============================================ -->
<section class="features">
    <div class="container">
        <div class="features-grid">
            <div class="feature-box">
                <i class="fa-solid fa-truck-fast"></i>
                <div>
                    <h4>Ücretsiz Kargo</h4>
                    <p>750 TL ve üzeri siparişlerde</p>
                </div>
            </div>
            <div class="feature-box">
                <i class="fa-solid fa-shield-halved"></i>
                <div>
                    <h4>Güvenli Alışveriş</h4>
                    <p>256-Bit SSL Koruması</p>
                </div>
            </div>
            <div class="feature-box">
                <i class="fa-solid fa-arrow-rotate-left"></i>
                <div>
                    <h4>Kolay İade</h4>
                    <p>14 Gün Koşulsuz İade</p>
                </div>
            </div>
            <div class="feature-box">
                <i class="fa-solid fa-headset"></i>
                <div>
                    <h4>7/24 Destek</h4>
                    <p>Her zaman yanınızdayız</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- FOOTER -->
<!-- ============================================ -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            
            <div class="footer-about">
                <div class="footer-logo">
                    <div class="footer-logo-icon">
                        <i class="fa-solid fa-bag-shopping"></i>
                    </div>
                    <span><?php echo SITE_NAME; ?></span>
                </div>
                <p><?php echo FOOTER_ABOUT; ?></p>
                <div class="socials">
                    <a href="<?php echo INSTAGRAM_URL; ?>"><i class="fab fa-instagram"></i></a>
                    <a href="<?php echo FACEBOOK_URL; ?>"><i class="fab fa-facebook-f"></i></a>
                    <a href="<?php echo TWITTER_URL; ?>"><i class="fab fa-x-twitter"></i></a>
                    <a href="<?php echo YOUTUBE_URL; ?>"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <div>
                <h3>Kurumsal</h3>
                <ul>
                    <li><a href="<?php echo SITE_URL; ?>public/hakkimizda.php">Hakkımızda</a></li>
                    <li><a href="<?php echo SITE_URL; ?>public/iletisim.php">İletişim</a></li>
                    <li><a href="#">Kariyer</a></li>
                    <li><a href="<?php echo SITE_URL; ?>public/blog.php">Blog</a></li>
                </ul>
            </div>
            
            <div>
                <h3>Müşteri Hizmetleri</h3>
                <ul>
                    <li><a href="#">Sipariş Takibi</a></li>
                    <li><a href="<?php echo SITE_URL; ?>public/sss.php">Sık Sorulan Sorular</a></li>
                    <li><a href="#">İade & Değişim</a></li>
                    <li><a href="#">Kargo Bilgileri</a></li>
                </ul>
            </div>
            
            <div>
                <h3>Yardım</h3>
                <ul>
                    <li><a href="#">Gizlilik Politikası</a></li>
                    <li><a href="#">KVKK</a></li>
                    <li><a href="#">Kullanım Şartları</a></li>
                    <li><a href="#">Çerez Politikası</a></li>
                </ul>
            </div>
            
            <div>
                <h3>Güvenli Ödeme</h3>
                <div class="payments">
                    <img src="https://cdn-icons-png.flaticon.com/512/349/349221.png" alt="Visa">
                    <img src="https://cdn-icons-png.flaticon.com/512/196/196578.png" alt="Mastercard">
                    <img src="https://cdn-icons-png.flaticon.com/512/825/825454.png" alt="Amex">
                    <img src="https://cdn-icons-png.flaticon.com/512/5968/5968144.png" alt="PayPal">
                </div>
                <p class="ssl">🔒 256-Bit SSL Güvenliği</p>
            </div>
            
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo ISLETME_ADI; ?> - <?php echo FOOTER_COPYRIGHT; ?></p>
        </div>
    </div>
</footer>

<!-- ============================================ -->
<!-- SCROLL TO TOP -->
<!-- ============================================ -->
<button class="scroll-top-btn" id="scrollTopBtn">
    <i class="fa-solid fa-arrow-up"></i>
</button>

<!-- ============================================ -->
<!-- FOOTER JS -->
<!-- ============================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // SCROLL TO TOP
    // ============================================
    const scrollBtn = document.getElementById('scrollTopBtn');
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 400) {
            scrollBtn.classList.add('visible');
        } else {
            scrollBtn.classList.remove('visible');
        }
    });
    
    scrollBtn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    
});
</script>

</body>
</html>