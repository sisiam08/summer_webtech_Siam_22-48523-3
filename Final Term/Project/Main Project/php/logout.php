<?php
session_start();

// Destroy the session
session_destroy();

// Return success response
echo json_encode(['success' => true]);
?>
