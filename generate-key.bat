@echo off
echo ========================================
echo   Generando APP_KEY para Railway
echo ========================================
echo.
cd /d %~dp0
php artisan key:generate --show
echo.
echo ========================================
echo   COPIA el key de arriba e ingresalo en Railway como APP_KEY
echo ========================================
pause

