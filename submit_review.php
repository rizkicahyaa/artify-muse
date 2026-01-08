<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_name'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_name = $_SESSION['user_name'];
    $museum_name = trim($_POST['museum_name']);
    $rating = intval($_POST['rating']);
    $review = trim($_POST['review']);
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $_SESSION['error'] = 'Rating harus antara 1-5!';
        header('Location: index.php');
        exit;
    }
    
    // Check if user already reviewed this museum
    $check_query = "SELECT id FROM museum_ratings WHERE user_name = ? AND museum_name = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ss", $user_name, $museum_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'Anda sudah memberikan review untuk museum ini!';
        $stmt->close();
        header('Location: index.php');
        exit;
    }
    $stmt->close();
    
    // Insert review
    $insert_query = "INSERT INTO museum_ratings (user_name, museum_name, rating, review, created_at) 
                     VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ssis", $user_name, $museum_name, $rating, $review);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Review berhasil disimpan!';
    } else {
        $_SESSION['error'] = 'Gagal menyimpan review: ' . $conn->error;
    }
    
    $stmt->close();
    header('Location: index.php');
    exit;
} else {
    header('Location: index.php');
    exit;
}
?>
