<?php
// Cart Test Runner - Simulates hitting the cart_test.php page
$url = "http://localhost/grocery-store/cart_test.php";
echo "Attempting to access $url...\n";

// Use curl to make a request to the page
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response code: $httpCode\n";
if ($httpCode == 200) {
    echo "Cart test page loaded successfully!\n";
} else {
    echo "Failed to load cart test page.\n";
}
