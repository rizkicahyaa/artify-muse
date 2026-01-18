<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-museum me-2"></i>Artify
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="navbar-nav ms-auto align-items-lg-center">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home me-1"></i>Beranda
                </a>
                <a class="nav-link" href="review.php">
                    <i class="fas fa-edit me-1"></i>Review
                </a>
                <?php if (isset($_SESSION['user_name'])): ?>
                    <a class="nav-link" href="recommendations.php">
                        <i class="fas fa-star me-1"></i>Rekomendasi
                    </a>
                    <span class="nav-link d-flex align-items-center pe-0">
                        <i class="fas fa-user-circle me-2"></i>
                        <span>Halo, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    </span>
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                <?php else: ?>
                    <a class="nav-link" href="login.php">
                        <i class="fas fa-sign-in-alt me-1"></i>Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
