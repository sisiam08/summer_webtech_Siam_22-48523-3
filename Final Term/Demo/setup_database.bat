@echo off
echo =============================================
echo   Database Setup Script
echo =============================================
echo.

echo This script will check and set up the database with required tables.
echo.
echo Press any key to continue or Ctrl+C to cancel...
echo.

pause

echo.
echo Setting up database...
echo.

php setup_database.php

echo.
echo Database setup completed.
echo.

pause
