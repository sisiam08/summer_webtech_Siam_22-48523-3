<?php
require_once __DIR__ . '/../../Database/database.php';

// Get all categories from the database
$conn = connectDB();

try {
    $query = "SELECT * FROM categories ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    // Fetch categories
    $categories = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<li><a href="#" data-category="' . $row['id'] . '" class="category-link">' . htmlspecialchars($row['name']) . '</a></li>';
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo '<li>Error loading categories</li>';
} finally {
    $conn = null;
}
?>
