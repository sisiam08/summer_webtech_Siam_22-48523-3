@echo off
echo =============================================
echo    Online Grocery Store - Development Server
echo =============================================
echo.
echo Starting PHP development server...
echo.
echo Available URLs:
echo - Main site: http://localhost:8000/index.php
echo - Login page: http://localhost:8000/login.html
echo - Admin panel: http://localhost:8000/admin/index.html (after login)
echo - Shop owner panel: http://localhost:8000/shop_owner/index.html (after login)
echo - Delivery panel: http://localhost:8000/delivery/index.html (after login)
echo - Become shop owner: http://localhost:8000/shop_owner/register.html
echo - Apply as delivery personnel: http://localhost:8000/delivery/apply.html
echo.
echo Admin credentials:
echo - Email: admin@example.com
echo - Password: admin123
echo.
echo Press Ctrl+C to stop the server when done
echo.
cd /d "%~dp0"
php -S localhost:8000
pause
