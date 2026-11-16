@echo off
cd /d "%~dp0"
set PATH=C:\php\php-8.5.12-1-Win32-vs17-x64;%PATH%
set PHPRC=%~dp0
echo ========================================================
echo           Menjalankan Z1 Vape Loyalty System
echo ========================================================
echo URL: http://127.0.0.1:8001
echo Tekan Ctrl+C untuk menghentikan server.
echo.
php artisan serve --host=127.0.0.1 --port=8001
pause
