<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_name'])) {
    header('Location: login.php');
    exit;
}

$current_user = $_SESSION['user_name'];

// Get all ratings from database
$ratings_query = "SELECT user_name, museum_name, rating FROM museum_ratings";
$ratings_result = $conn->query($ratings_query);

// Build user-item matrix
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

// Function to calculate cosine similarity between two users
function cosineSimilarity($user1_ratings, $user2_ratings) {
    $dot_product = 0;
    $norm1 = 0;
    $norm2 = 0;
    
    $common_items = array_intersect_key($user1_ratings, $user2_ratings);
    
    if (count($common_items) == 0) {
        return 0;
    }
    
    foreach ($common_items as $item => $rating1) {
        $rating2 = $user2_ratings[$item];
        $dot_product += $rating1 * $rating2;
        $norm1 += $rating1 * $rating1;
        $norm2 += $rating2 * $rating2;
    }
    
    if ($norm1 == 0 || $norm2 == 0) {
        return 0;
    }
    
    return $dot_product / (sqrt($norm1) * sqrt($norm2));
}

// Get current user's ratings
$current_user_ratings = isset($user_item_matrix[$current_user]) ? $user_item_matrix[$current_user] : [];

// Calculate similarity with all other users
$user_similarities = [];
foreach ($all_users as $user) {
    if ($user != $current_user && isset($user_item_matrix[$user])) {
        $similarity = cosineSimilarity($current_user_ratings, $user_item_matrix[$user]);
        if ($similarity > 0) {
            $user_similarities[$user] = $similarity;
        }
    }
}

// Sort by similarity (descending)
arsort($user_similarities);

// Get museums that current user hasn't rated yet
$unrated_museums = array_diff($all_museums, array_keys($current_user_ratings));

// Calculate predicted ratings for unrated museums
$predicted_ratings = [];

foreach ($unrated_museums as $museum) {
    $weighted_sum = 0;
    $similarity_sum = 0;
    
    // Get top similar users (limit to top 10 for efficiency)
    $top_similar_users = array_slice($user_similarities, 0, 10, true);
    
    foreach ($top_similar_users as $similar_user => $similarity) {
        if (isset($user_item_matrix[$similar_user][$museum])) {
            $rating = $user_item_matrix[$similar_user][$museum];
            $weighted_sum += $similarity * $rating;
            $similarity_sum += abs($similarity);
        }
    }
    
    if ($similarity_sum > 0) {
        $predicted_rating = $weighted_sum / $similarity_sum;
        $predicted_ratings[$museum] = [
            'rating' => round($predicted_rating, 2),
            'confidence' => $similarity_sum
        ];
    }
}

// Sort by predicted rating (descending)
arsort($predicted_ratings);

// Get user's reviewed museums for display
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
    <title>Rekomendasi Museum</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Museum Recommendation</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Halo, <?php echo htmlspecialchars($current_user); ?>!</span>
                <a class="nav-link" href="index.php">Beranda</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Rekomendasi Museum untuk Anda</h2>
        
        <?php if (count($user_reviews) == 0): ?>
            <div class="alert alert-warning">
                Anda belum memberikan review apapun. Silakan berikan review terlebih dahulu untuk mendapatkan rekomendasi.
                <a href="index.php" class="alert-link">Kembali ke Beranda</a>
            </div>
        <?php elseif (count($predicted_ratings) == 0): ?>
            <div class="alert alert-info">
                Belum ada rekomendasi yang dapat diberikan. Coba berikan lebih banyak review untuk mendapatkan rekomendasi yang lebih akurat.
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0">Museum yang Direkomendasikan</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Nama Museum</th>
                                            <th>Prediksi Rating</th>
                                            <th>Tingkat Keyakinan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $rank = 1;
                                        foreach ($predicted_ratings as $museum => $data): 
                                            $rating = $data['rating'];
                                            $confidence = $data['confidence'];
                                            $stars = '';
                                            for ($i = 1; $i <= 5; $i++) {
                                                $stars .= $i <= round($rating) ? '★' : '☆';
                                            }
                                        ?>
                                            <tr>
                                                <td><strong>#<?php echo $rank++; ?></strong></td>
                                                <td><strong><?php echo htmlspecialchars($museum); ?></strong></td>
                                                <td>
                                                    <span class="text-warning"><?php echo $stars; ?></span>
                                                    <span class="ms-2"><?php echo number_format($rating, 2); ?>/5.00</span>
                                                </td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar" role="progressbar" 
                                                             style="width: <?php echo min(100, ($confidence / 5) * 100); ?>%"
                                                             aria-valuenow="<?php echo $confidence; ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="5">
                                                            <?php echo number_format($confidence, 2); ?>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row mt-4">
            <div class="col-md-12">
                <h3>Review Saya</h3>
                <?php if (count($user_reviews) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Museum</th>
                                    <th>Rating</th>
                                    <th>Review</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user_reviews as $review): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($review['museum_name']); ?></td>
                                        <td>
                                            <?php 
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo $i <= $review['rating'] ? '★' : '☆';
                                            }
                                            ?> (<?php echo $review['rating']; ?>)
                                        </td>
                                        <td><?php echo htmlspecialchars($review['review']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Anda belum memberikan review apapun.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-4">
            <a href="index.php" class="btn btn-primary">Kembali ke Beranda</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
