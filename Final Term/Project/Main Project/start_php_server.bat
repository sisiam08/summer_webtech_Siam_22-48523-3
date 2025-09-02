@echo off
echo Starting PHP development server...
echo.
echo Please visit http://localhost:8000 in your browser
echo.
echo Available entry points:
echo - Main site: http://localhost:8000/index.html
echo - Login: http://localhost:8000/login.html
echo - Admin panel: http://localhost:8000/admin/index.html (after login)
echo - Project reorganization tool: http://localhost:8000/reorganize_project.php
echo.
cd /d "%~dp0"
php -S localhost:8000
pause
