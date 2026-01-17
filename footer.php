<?php
// Footer component untuk semua halaman
// Pastikan session sudah dimulai sebelum include file ini
// Parameter $current_page untuk menentukan halaman aktif (opsional)
$current_page = isset($current_page) ? $current_page : '';
?>
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <a class="footer-brand" href="index.php">
                <i class="fas fa-museum me-2"></i>Artify
            </a>
            <div class="footer-nav">
                <?php if ($current_page != 'index'): ?>
                    <a class="footer-link" href="index.php">
                        <i class="fas fa-home me-1"></i>Beranda
                    </a>
                <?php endif; ?>
                <a class="footer-link" href="review.php">
                    <i class="fas fa-edit me-1"></i>Review
                </a>
                <?php if (isset($_SESSION['user_name'])): ?>
                    <a class="footer-link" href="recommendations.php">
                        <i class="fas fa-star me-1"></i>Rekomendasi
                    </a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_name'])): ?>
                    <a class="footer-link" href="logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                <?php else: ?>
                    <a class="footer-link" href="login.php">
                        <i class="fas fa-sign-in-alt me-1"></i>Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="footer-copyright">
            <i class="far fa-copyright me-1"></i>
            <?php echo date('Y'); ?> Artify. All rights reserved.
        </div>
    </div>
</footer>
