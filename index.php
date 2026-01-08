<?php
session_start();
require_once 'config.php';

// Get all unique museums from database
$museums_query = "SELECT DISTINCT museum_name FROM museum_ratings ORDER BY museum_name";
$museums_result = $conn->query($museums_query);

$museums = [];
if ($museums_result && $museums_result->num_rows > 0) {
    while ($row = $museums_result->fetch_assoc()) {
        $museums[] = $row['museum_name'];
    }
}

// Get current user's reviews if logged in
$user_reviews = [];
if (isset($_SESSION['user_name'])) {
    $user_name = $_SESSION['user_name'];
    $reviews_query = "SELECT museum_name, rating, review FROM museum_ratings WHERE user_name = ?";
    $stmt = $conn->prepare($reviews_query);
    $stmt->bind_param("s", $user_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
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
    <title>Sistem Rekomendasi Museum</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Museum Recommendation</a>
            <div class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_name'])): ?>
                    <span class="navbar-text me-3">Halo, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                    <a class="nav-link" href="recommendations.php">Rekomendasi</a>
                    <a class="nav-link" href="logout.php">Logout</a>
                <?php else: ?>
                    <a class="nav-link" href="login.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4">Berikan Review Museum</h2>
                
                <?php if (!isset($_SESSION['user_name'])): ?>
                    <div class="alert alert-warning">
                        Silakan <a href="login.php">login</a> terlebih dahulu untuk memberikan review.
                    </div>
                <?php else: ?>
                    <form action="submit_review.php" method="POST">
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="museum_name" class="form-label">Pilih Museum</label>
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
                                
                                <div class="mb-3">
                                    <label for="rating" class="form-label">Rating (1-5)</label>
                                    <input type="number" class="form-control" id="rating" name="rating" 
                                           min="1" max="5" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="review" class="form-label">Review</label>
                                    <textarea class="form-control" id="review" name="review" rows="3" required></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Submit Review</button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($_SESSION['user_name']) && count($user_reviews) > 0): ?>
            <div class="row mt-4">
                <div class="col-md-12">
                    <h3>Review Saya</h3>
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
                                <?php foreach ($user_reviews as $museum => $review): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($museum); ?></td>
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
                    <a href="recommendations.php" class="btn btn-success mt-3">Lihat Rekomendasi Museum</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
