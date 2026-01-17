<?php
session_start();
require_once 'config.php';

// Ambil 10 museum dengan rating rata-rata tertinggi
// Atau jika tidak ada rating, ambil 10 museum pertama
$museums_query = "
    SELECT 
        museum_name,
        AVG(rating) as avg_rating,
        COUNT(*) as total_reviews
    FROM museum_ratings
    GROUP BY museum_name
    ORDER BY avg_rating DESC, total_reviews DESC
    LIMIT 10
";

$museums_result = $conn->query($museums_query);

$museums = [];
if ($museums_result && $museums_result->num_rows > 0) {
    while ($row = $museums_result->fetch_assoc()) {
        $museums[] = [
            'name' => $row['museum_name'],
            'avg_rating' => round($row['avg_rating'], 2),
            'total_reviews' => $row['total_reviews']
        ];
    }
} else {
    // Jika tidak ada data rating, ambil museum unik saja
    $fallback_query = "SELECT DISTINCT museum_name FROM museum_ratings ORDER BY museum_name LIMIT 10";
    $fallback_result = $conn->query($fallback_query);
    if ($fallback_result && $fallback_result->num_rows > 0) {
        while ($row = $fallback_result->fetch_assoc()) {
            $museums[] = [
                'name' => $row['museum_name'],
                'avg_rating' => 0,
                'total_reviews' => 0
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artify - Dashboard Museum</title>
    <!-- icon -->
    <link rel="icon" href="images/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php 
    $current_page = 'index';
    include 'navbar.php'; 
    ?>

    <div class="container mt-4 mb-5">
        <div class="hero-section fade-in">
            <h1><i class="fas fa-th-large me-3"></i>Dashboard Museum</h1>
            <p>Jelajahi 10 museum terpopuler dan terbaik berdasarkan rating pengunjung</p>
        </div>

        <div class="row mt-4">
            <?php if (count($museums) > 0): ?>
                <?php foreach ($museums as $index => $museum): ?>
                    <div class="col-md-6 col-lg-4 mb-4 fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s">
                        <div class="card museum-dashboard-card h-100">
                            <div class="museum-image-wrapper">
                                <img src="images/museums/<?php echo strtolower(str_replace(' ', '-', $museum['name'])); ?>.jpg" 
                                     alt="<?php echo htmlspecialchars($museum['name']); ?>"
                                     class="museum-image img-fluid object-fit-cover"
                                     onerror="this.src='https://via.placeholder.com/400x250/667eea/ffffff?text=<?php echo urlencode($museum['name']); ?>'">
                                <div class="museum-overlay">
                                    <div class="museum-badge">
                                        <i class="fas fa-trophy me-1"></i>#<?php echo $index + 1; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title museum-title">
                                    <i class="fas fa-museum text-primary me-2"></i>
                                    <?php echo htmlspecialchars($museum['name']); ?>
                                </h5>
                                
                                <div class="museum-rating mb-3">
                                    <div class="rating-stars mb-2">
                                        <?php 
                                        $rating = round($museum['avg_rating']);
                                        for ($i = 1; $i <= 5; $i++) {
                                            $class = $i <= $rating ? 'star-filled' : 'star-empty';
                                            echo '<span class="' . $class . '">★</span>';
                                        }
                                        ?>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-primary">
                                            <i class="fas fa-star me-1"></i>
                                            <?php echo number_format($museum['avg_rating'], 2); ?>/5.00
                                        </span>
                                        <!-- taruh sini -->
                                    </div>
                                </div>

                                <div class="museum-actions">
                                    <?php if (isset($_SESSION['user_name'])): ?>
                                        <a href="review.php" class="btn btn-primary btn-sm w-100">
                                            <i class="fas fa-edit me-2"></i>Berikan Review
                                        </a>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-outline-primary btn-sm w-100">
                                            <i class="fas fa-sign-in-alt me-2"></i>Login untuk Review
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h4>Belum Ada Museum</h4>
                                <p>Belum ada museum yang tersedia. Silakan tambahkan review museum terlebih dahulu.</p>
                                <a href="review.php" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i>Berikan Review
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
