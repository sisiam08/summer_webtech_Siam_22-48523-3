<?php
// Include this file at the top of any page where multi-shop functionality is used

// Function to display multi-shop notice if needed
function displayMultiShopNotice() {
    if (!isMultiShopEnabled()) {
        echo '<div class="notice warning-notice">
            <p><strong>Notice:</strong> Multi-shop functionality is available but not enabled. 
            To enable it, run the update_multi_shop_db.bat script to update your database.</p>
        </div>';
    }
}

// Add CSS for the notice
function addMultiShopNoticeStyles() {
    echo '<style>
        .notice {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .warning-notice {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
    </style>';
}
?>
