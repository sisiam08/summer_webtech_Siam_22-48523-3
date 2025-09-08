@echo off
echo Running Project Cleanup Script...
echo.

cd /d "%~dp0"

REM Check if PHP is available
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo Error: PHP is not installed or not in the system PATH.
    echo Please install PHP or add it to your PATH.
    goto :end
)

REM Start PHP server
echo Starting PHP server on port 8000...
start "" php -S localhost:8000

REM Wait a moment for the server to start
timeout /t 2 > nul

REM Open the browser
echo Opening cleanup page in browser...
start "" http://localhost:8000/cleanup.php

echo.
echo The cleanup page has been opened in your browser.
echo When you're done, close this window to shut down the PHP server.
echo.
echo Press Ctrl+C to stop the server and exit...

REM Keep the server running until the user presses Ctrl+C
cmd /k

:end
