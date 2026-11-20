@echo off
setlocal
pushd "%~dp0"
if not exist ".runtime\php" mkdir ".runtime\php"
php -r "exit(extension_loaded('gd') ? 0 : 1);" >nul 2>&1
if errorlevel 1 (>".runtime\php\gd.ini" echo extension=gd)
php -r "exit(extension_loaded('sqlite3') ? 0 : 1);" >nul 2>&1
if errorlevel 1 (>".runtime\php\sqlite3.ini" echo extension=sqlite3)
set "PHP_INI_SCAN_DIR=%~dp0.runtime\php"
call %*
set "posExitCode=%ERRORLEVEL%"
popd
exit /b %posExitCode%
