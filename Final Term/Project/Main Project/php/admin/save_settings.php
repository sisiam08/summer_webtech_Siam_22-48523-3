<?php
// Start session if not already started
session_start();

// Include database connection
require_once '../db_connection.php';
require_once '../functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    echo json_encode([
        'error' => 'Unauthorized access'
    ]);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Check if settings type is provided
if (!isset($_POST['type']) || empty($_POST['type'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Settings type is required'
    ]);
    exit;
}

// Get settings type
$type = $_POST['type'];
$validTypes = ['general', 'shipping', 'payment', 'email', 'appearance'];

if (!in_array($type, $validTypes)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid settings type'
    ]);
    exit;
}

// Begin transaction
$conn->begin_transaction();

try {
    // Handle file uploads for appearance settings
    if ($type === 'appearance') {
        // Handle logo upload
        if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
            $logoName = 'logo_' . time() . '.' . pathinfo($_FILES['store_logo']['name'], PATHINFO_EXTENSION);
            $logoPath = '../uploads/images/' . $logoName;
            
            // Create directory if it doesn't exist
            if (!file_exists('../uploads/images/')) {
                mkdir('../uploads/images/', 0777, true);
            }
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['store_logo']['tmp_name'], $logoPath)) {
                // Update logo path setting
                updateSetting('logo_path', 'uploads/images/' . $logoName, $type, $conn);
            }
        }
        
        // Handle favicon upload
        if (isset($_FILES['store_favicon']) && $_FILES['store_favicon']['error'] === UPLOAD_ERR_OK) {
            $faviconName = 'favicon_' . time() . '.' . pathinfo($_FILES['store_favicon']['name'], PATHINFO_EXTENSION);
            $faviconPath = '../uploads/images/' . $faviconName;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['store_favicon']['tmp_name'], $faviconPath)) {
                // Update favicon path setting
                updateSetting('favicon_path', 'uploads/images/' . $faviconName, $type, $conn);
            }
        }
    }
    
    // Process all form fields
    foreach ($_POST as $key => $value) {
        // Skip the type field
        if ($key === 'type') continue;
        
        // Special handling for array values
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        
        // Update setting
        updateSetting($key, $value, $type, $conn);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => ucfirst($type) . ' settings saved successfully'
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save settings: ' . $e->getMessage()
    ]);
}

// Function to update a setting
function updateSetting($key, $value, $type, $conn) {
    // Check if setting exists
    $checkSql = "SELECT id FROM settings WHERE setting_key = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing setting
        $updateSql = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param('ss', $value, $key);
    } else {
        // Insert new setting
        $insertSql = "INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insertSql);
        $stmt->bind_param('sss', $key, $value, $type);
    }
    
    return $stmt->execute();
}
?>
