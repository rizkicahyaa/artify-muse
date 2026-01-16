<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_name'])) {
    header('Location: login.php');
    exit;
}

$current_user = $_SESSION['user_name'];

// Ambil semua rating dari database
$ratings_query = "SELECT user_name, museum_name, rating FROM museum_ratings";
$ratings_result = $conn->query($ratings_query);

// Membangun matriks user-item (user x museum)
$user_item_matrix = [];
$all_museums = [];
$all_users = [];

while ($row = $ratings_result->fetch_assoc()) {
    $user = $row['user_name'];
    $museum = $row['museum_name'];
    $rating = intval($row['rating']);
    
    if (!isset($user_item_matrix[$user])) {
        $user_item_matrix[$user] = [];
    }
    
    $user_item_matrix[$user][$museum] = $rating;
    
    if (!in_array($museum, $all_museums)) {
        $all_museums[] = $museum;
    }
    
    if (!in_array($user, $all_users)) {
        $all_users[] = $user;
    }
}

// Fungsi untuk menghitung cosine similarity (kemiripan) antara dua user
function cosineSimilarity($user1_ratings, $user2_ratings) {
    $dot_product = 0;  // Hasil perkalian dot product
    $norm1 = 0;        // Norm (panjang vektor) user 1
    $norm2 = 0;         // Norm (panjang vektor) user 2
    
    // Cari museum yang direview oleh kedua user (item yang sama)
    $common_items = array_intersect_key($user1_ratings, $user2_ratings);
    
    // Jika tidak ada museum yang sama, similarity = 0
    if (count($common_items) == 0) {
        return 0;
    }
    
    // Hitung dot product dan norm untuk setiap museum yang sama
    foreach ($common_items as $item => $rating1) {
        $rating2 = $user2_ratings[$item];
        $dot_product += $rating1 * $rating2;  // Akumulasi perkalian rating
        $norm1 += $rating1 * $rating1;        // Akumulasi kuadrat rating user 1
        $norm2 += $rating2 * $rating2;        // Akumulasi kuadrat rating user 2
    }
    
    // Jika norm = 0, similarity = 0
    if ($norm1 == 0 || $norm2 == 0) {
        return 0;
    }
    
    // Rumus cosine similarity: dot_product / (sqrt(norm1) * sqrt(norm2))
    return $dot_product / (sqrt($norm1) * sqrt($norm2));
}

// Ambil rating dari user yang sedang login
$current_user_ratings = isset($user_item_matrix[$current_user]) ? $user_item_matrix[$current_user] : [];

// Hitung similarity (kemiripan) dengan semua user lain
$user_similarities = [];
foreach ($all_users as $user) {
    // Skip user yang sedang login dan pastikan user tersebut ada di matrix
    if ($user != $current_user && isset($user_item_matrix[$user])) {
        // Hitung cosine similarity antara user saat ini dengan user lain
        $similarity = cosineSimilarity($current_user_ratings, $user_item_matrix[$user]);
        // Simpan hanya similarity yang lebih dari 0 (ada kemiripan)
        if ($similarity > 0) {
            $user_similarities[$user] = $similarity;
        }
    }
}

// Urutkan berdasarkan similarity dari tertinggi ke terendah
arsort($user_similarities);

// Ambil museum yang belum direview oleh user saat ini
$unrated_museums = array_diff($all_museums, array_keys($current_user_ratings));

// Calculate predicted ratings for unrated museums
$predicted_ratings = [];

foreach ($unrated_museums as $museum) {
    $weighted_sum = 0;
    $similarity_sum = 0;
    
    // Ambil 10 user yang paling mirip (dibatasi untuk efisiensi)
    $top_similar_users = array_slice($user_similarities, 0, 10, true);
    
    foreach ($top_similar_users as $similar_user => $similarity) {
        // Cek apakah user yang mirip ini sudah mereview museum tersebut
        if (isset($user_item_matrix[$similar_user][$museum])) {
            $rating = $user_item_matrix[$similar_user][$museum];
            // Akumulasi rating yang sudah dikalikan dengan similarity (weighted)
            $weighted_sum += $similarity * $rating;
            // Akumulasi total similarity (untuk confidence score)
            // Confidence = seberapa banyak user mirip yang mereview museum ini
            $similarity_sum += abs($similarity);
        }
    }
    
    if ($similarity_sum > 0) {
        // Prediksi rating = rata-rata tertimbang dari rating user-user mirip
        $predicted_rating = $weighted_sum / $similarity_sum;
        $predicted_ratings[$museum] = [
            'rating' => round($predicted_rating, 2),
            // TINGKAT KEYAKINAN (CONFIDENCE):
            // - Menunjukkan seberapa banyak user yang mirip dengan Anda yang sudah mereview museum ini
            // - Semakin tinggi confidence, semakin banyak user mirip yang mereview = lebih dapat dipercaya
            // - Confidence tinggi = rekomendasi berdasarkan banyak data dari user mirip
            // - Confidence rendah = rekomendasi hanya berdasarkan sedikit user mirip (kurang reliable)
            // Contoh: confidence 2.5 berarti ada beberapa user mirip (total similarity mereka = 2.5)
            //         yang sudah mereview museum ini, jadi prediksi lebih dapat dipercaya
            'confidence' => $similarity_sum
        ];
    }
}

// Urutkan berdasarkan prediksi rating dari tertinggi ke terendah
arsort($predicted_ratings);

// Ambil museum yang sudah direview user untuk ditampilkan
$user_reviews_query = "SELECT museum_name, rating, review FROM museum_ratings WHERE user_name = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($user_reviews_query);
$stmt->bind_param("s", $current_user);
$stmt->execute();
$user_reviews_result = $stmt->get_result();

$user_reviews = [];
while ($row = $user_reviews_result->fetch_assoc()) {
    $user_reviews[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artify - Hasil Rekomendasi Museum</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php 
    $current_page = 'recommendations';
    include 'navbar.php'; 
    ?>

    <div class="container mt-4 mb-5">
        <div class="hero-section fade-in">
            <h1><i class="fas fa-star me-3"></i>Rekomendasi Museum untuk Anda</h1>
            <p>Berdasarkan preferensi dan review Anda, berikut adalah museum yang kami rekomendasikan!</p>
        </div>
        
        <?php if (count($user_reviews) == 0): ?>
            <div class="card fade-in">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h4>Belum Ada Review</h4>
                        <p>Anda belum memberikan review apapun. Silakan berikan review terlebih dahulu untuk mendapatkan rekomendasi.</p>
                        <!-- <a href="index.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Beranda
                        </a> -->
                    </div>
                </div>
            </div>
        <?php elseif (count($predicted_ratings) == 0): ?>
            <div class="card fade-in">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h4>Belum Ada Rekomendasi</h4>
                        <p>Belum ada rekomendasi yang dapat diberikan. Coba berikan lebih banyak review untuk mendapatkan rekomendasi yang lebih akurat.</p>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Berikan Review Lagi
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row fade-in">
                <div class="col-md-12">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="mb-0">
                                <i class="fas fa-trophy me-2"></i>Museum yang Direkomendasikan
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php 
                                $rank = 1;
                                foreach ($predicted_ratings as $museum => $data): 
                                    $rating = $data['rating'];
                                    $confidence = $data['confidence'];
                                    $delay = ($rank - 1) * 0.1;
                                ?>
                                    <div class="col-md-6 mb-4 slide-in" style="animation-delay: <?php echo $delay; ?>s">
                                        <div class="museum-card">
                                            <div class="d-flex align-items-start mb-3">
                                                <div class="badge-rank me-3">#<?php echo $rank++; ?></div>
                                                <div class="flex-grow-1">
                                                    <h5 class="mb-2">
                                                        <i class="fas fa-museum text-primary me-2"></i>
                                                        <?php echo htmlspecialchars($museum); ?>
                                                    </h5>
                                                    <div class="mb-3">
                                                        <div class="rating-stars mb-2">
                                                            <?php 
                                                            for ($i = 1; $i <= 5; $i++) {
                                                                $class = $i <= round($rating) ? 'star-filled' : 'star-empty';
                                                                echo '<span class="' . $class . '">★</span>';
                                                            }
                                                            ?>
                                                        </div>
                                                        <div class="d-flex align-items-center">
                                                            <span class="badge bg-primary me-2"><?php echo number_format($rating, 2); ?>/5.00</span>
                                                            <small class="text-muted">
                                                                <i class="fas fa-chart-line me-1"></i>Prediksi Rating
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label class="form-label small text-muted mb-2">
                                                            <i class="fas fa-shield-alt me-1"></i>Tingkat Keyakinan
                                                        </label>
                                                        <div class="progress">
                                                            <div class="progress-bar" role="progressbar" 
                                                                 style="width: <?php echo min(100, ($confidence / 5) * 100); ?>%"
                                                                 aria-valuenow="<?php echo $confidence; ?>" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="5">
                                                                <?php echo number_format($confidence, 2); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (count($user_reviews) > 0): ?>
            <div class="row mt-5 slide-in">
                <div class="col-md-12">
                    <div class="d-flex align-items-center mb-4">
                        <div class="icon-wrapper">
                            <i class="fas fa-history"></i>
                        </div>
                        <h3 class="mb-0 ms-3">Review Saya</h3>
                    </div>
                    <div class="row">
                        <?php foreach ($user_reviews as $review): ?>
                            <div class="col-md-6 mb-3">
                                <div class="review-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="mb-0">
                                            <i class="fas fa-museum text-primary me-2"></i>
                                            <?php echo htmlspecialchars($review['museum_name']); ?>
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
                                    <!-- review ulasan -->
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-primary btn-lg">
                <i class="fas fa-arrow-left me-2"></i>Kembali ke Beranda
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
