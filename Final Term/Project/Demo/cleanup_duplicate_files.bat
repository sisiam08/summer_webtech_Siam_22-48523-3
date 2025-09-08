@echo off
REM Create backup folder
mkdir "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup"

REM Shop Owner Section
REM Keep add_product.php and add-product-updated.php, move others to backup
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\add-product-debug.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\add-product-fixed.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\add-product-new.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"

REM Auth Section
REM Keep login.php in main folder and auth/login_process.php, move others to backup
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\login_debug.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\login_fixed.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\quick_login.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\fix_login.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\force_login.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"

REM Testing Files
REM Move all test and debug files to backup
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\auth_test.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\auth_test_enhanced.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\debug_auth.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\direct_auth.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\db_test.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\jquery_test.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\php_test.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\product_debug.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"

REM Product Management
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\get_product_debug.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\get_product_new.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\save_product_debug.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\save_product_new.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\save_product_simple.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\save_product_test.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\direct_product_add.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\fix_save_product.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\update_product_new.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"

REM Session Files
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\session_auth.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\session_debug.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\session_fix.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\session_info.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\set_test_session.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"
move "E:\University\Web Tech\Lab\Final Term\Project\Demo\shop_owner\create_test_session.php" "E:\University\Web Tech\Lab\Final Term\Project\Demo\duplicate_files_backup\"

REM Check if files were moved successfully
echo Files have been moved to the backup folder.
echo Please check the backup folder to ensure all files were moved correctly.
