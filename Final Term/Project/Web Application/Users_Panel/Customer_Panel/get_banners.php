<?php
// API endpoint to get active banners for homepage
require_once __DIR__ . '/../../Database/database.php';

header('Content-Type: application/json');

try {
    $conn = connectDB();
    
    // Get active banners ordered by display_order
    $stmt = $conn->prepare("
        SELECT id, title, subtitle, image_path, link_url, button_text 
        FROM banners 
        WHERE is_active = 1 
        ORDER BY display_order ASC, created_at ASC
    ");
    $stmt->execute();
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert relative paths to full URLs
    foreach ($banners as &$banner) {
        $banner['image_url'] = '../../' . $banner['image_path'];
    }
    
    echo json_encode([
        'success' => true,
        'banners' => $banners
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading banners: ' . $e->getMessage()
    ]);
}
?>