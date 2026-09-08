@echo off
title Laravel ESL Server Launcher
echo ====================================================
echo Starting Laravel Development Server...
echo ====================================================

:: Set Environment Paths for current session
set "PATH=C:\php83;C:\xampp\mysql\bin;%PATH%"

:: Check if mysqld is running
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [OK] MySQL is already running.
) else (
    echo [*] Starting MySQL service...
    if exist "C:\xampp\mysql\bin\mysqld.exe" (
        start /B "" "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone --console
        timeout /t 2 /nobreak >nul
    )
)

:: Navigate to project and start Laravel Artisan Server
cd /d "%~dp0"
echo.
echo ====================================================
echo Laravel Server is running at: http://127.0.0.1:8000
echo Press Ctrl+C to stop the web server.
echo ====================================================
echo.
"C:\php83\php.exe" artisan serve --host=127.0.0.1 --port=8000

