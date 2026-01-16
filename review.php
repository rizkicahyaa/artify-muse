<?php
session_start();
require_once 'config.php';

// Ambil semua nama museum unik dari database
$museums_query = "SELECT DISTINCT museum_name FROM museum_ratings ORDER BY museum_name";
$museums_result = $conn->query($museums_query);

$museums = [];
if ($museums_result && $museums_result->num_rows > 0) {
    while ($row = $museums_result->fetch_assoc()) {
        $museums[] = $row['museum_name'];
    }
}

// Ambil review user yang sedang login (jika sudah login)
$user_reviews = [];
if (isset($_SESSION['user_name'])) {
    $user_name = $_SESSION['user_name'];
    $reviews_query = "SELECT museum_name, rating, review FROM museum_ratings WHERE user_name = ?";
    $stmt = $conn->prepare($reviews_query);
    $stmt->bind_param("s", $user_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Simpan review dalam array dengan key nama museum
    while ($row = $result->fetch_assoc()) {
        $user_reviews[$row['museum_name']] = [
            'rating' => $row['rating'],
            'review' => $row['review']
        ];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artify - Review Museum</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php 
    $current_page = 'review';
    include 'navbar.php'; 
    ?>

    <div class="container mt-4 mb-5">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show fade-in" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show fade-in" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="hero-section fade-in">
            <h1><i class="fas fa-edit me-3"></i>Berikan Review Museum</h1>
            <p>Bagikan pengalaman Anda setelah mengunjungi museum dan bantu pengunjung lain menemukan museum terbaik!</p>
        </div>
        
        <div class="row fade-in">
            <div class="col-md-12">
                <div class="d-flex align-items-center mb-4">
                    <div class="icon-wrapper">
                        <i class="fas fa-edit"></i>
                    </div>
                    <h2 class="mb-0 ms-3">Form Review</h2>
                </div>
                
                <?php if (!isset($_SESSION['user_name'])): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        Silakan <a href="login.php" class="alert-link fw-bold">login</a> terlebih dahulu untuk memberikan review.
                    </div>
                <?php else: ?>
                    <form action="submit_review.php" method="POST">
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label for="museum_name" class="form-label">
                                        <i class="fas fa-museum me-2 text-primary"></i>Pilih Museum
                                    </label>
                                    <select class="form-select" id="museum_name" name="museum_name" required>
                                        <option value="">-- Pilih Museum --</option>
                                        <?php foreach ($museums as $museum): ?>
                                            <option value="<?php echo htmlspecialchars($museum); ?>" 
                                                <?php echo isset($user_reviews[$museum]) ? 'disabled' : ''; ?>>
                                                <?php echo htmlspecialchars($museum); ?>
                                                <?php if (isset($user_reviews[$museum])): ?>
                                                    (Sudah direview - Rating: <?php echo $user_reviews[$museum]['rating']; ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="rating" class="form-label">
                                        <i class="fas fa-star me-2 text-warning"></i>Rating (1-5)
                                    </label>
                                    <div class="d-flex align-items-center">
                                        <input type="number" class="form-control me-3" id="rating" name="rating" 
                                               min="1" max="5" required style="max-width: 100px;">
                                        <div id="starPreview" class="rating-stars">
                                            <span class="star-empty">☆☆☆☆☆</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- <div class="mb-4">
                                    <label for="review" class="form-label">
                                        <i class="fas fa-comment-alt me-2 text-primary"></i>Review
                                    </label>
                                    <textarea class="form-control" id="review" name="review" rows="4" 
                                              placeholder="Tuliskan review Anda tentang museum ini..." required></textarea>
                                </div> -->
                                
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Review
                                </button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($_SESSION['user_name']) && count($user_reviews) > 0): ?>
            <div class="row mt-5 slide-in">
                <div class="col-md-12">
                    <div class="d-flex align-items-center mb-4">
                        <div class="icon-wrapper">
                            <i class="fas fa-list"></i>
                        </div>
                        <h3 class="mb-0 ms-3">Review Saya</h3>
                    </div>
                    <div class="row">
                        <?php foreach ($user_reviews as $museum => $review): ?>
                            <div class="col-md-6 mb-3">
                                <div class="review-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="mb-0">
                                            <i class="fas fa-museum text-primary me-2"></i>
                                            <?php echo htmlspecialchars($museum); ?>
                                        </h5>
                                    </div>
                                    <div class="mb-2">
                                        <div class="rating-stars">
                                            <?php 
                                            for ($i = 1; $i <= 5; $i++) {
                                                $class = $i <= $review['rating'] ? 'star-filled' : 'star-empty';
                                                echo '<span class="' . $class . '">★</span>';
                                            }
                                            ?>
                                            <span class="ms-2 text-muted">(<?php echo $review['rating']; ?>/5)</span>
                                        </div>
                                    </div>
                                    <!-- Review ulasan -->
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-4">
                        <a href="recommendations.php" class="btn btn-success btn-lg">
                            <i class="fas fa-magic me-2"></i>Lihat Rekomendasi Museum
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview bintang saat user mengetik rating
        document.getElementById('rating')?.addEventListener('input', function() {
            const rating = parseInt(this.value) || 0;
            const preview = document.getElementById('starPreview');
            if (preview && rating >= 1 && rating <= 5) {
                let stars = '';
                // Generate bintang sesuai rating
                for (let i = 1; i <= 5; i++) {
                    stars += i <= rating ? '<span class="star-filled">★</span>' : '<span class="star-empty">☆</span>';
                }
                preview.innerHTML = stars;
            }
        });
    </script>
</body>
</html>
