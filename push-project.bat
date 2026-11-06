@echo off
setlocal enabledelayedexpansion

REM ============================================================
REM  push-project.bat - One-click push to GitHub + live server
REM  Project: Quotation Management System
REM  Server:  quotation.mfinnovation.xyz
REM ============================================================

echo.
echo ============================================
echo   Quotation Management System - Deploy
echo ============================================
echo.

REM --- Configuration ---
set "PROJECT_DIR=E:\wamp64\www\qms"
set "SSH_KEY=C:\Users\sizub\.ssh\id_ed25519_mf"
set "SSH_USER=mfinnovation.xyz"
set "SSH_HOST=ssh.gb.stackcp.com"
set "SERVER_DIR=quotation.mfinnovation.xyz"
set "ZIP_NAME=qms_deploy.zip"
set "GIT_REMOTE=origin"
set "GIT_BRANCH=master"

cd /d "%PROJECT_DIR%"

REM --- Step 1: Git Add, Commit & Push ---
echo [1/5] Staging changes...
git add -A
if errorlevel 1 (
    echo ERROR: git add failed
    pause
    exit /b 1
)

echo [2/5] Committing...
git diff --cached --quiet
if errorlevel 1 (
    git commit -m "Deploy: %date% %time%"
)

echo [3/5] Pushing to GitHub...
git push %GIT_REMOTE% %GIT_BRANCH%
if errorlevel 1 (
    echo WARNING: git push failed. Continuing with server deploy...
)

REM --- Step 2: Create deployment zip (excluding ignored files) ---
echo.
echo [4/5] Creating deployment archive...
if exist "%ZIP_NAME%" del "%ZIP_NAME%"
powershell -NoProfile -Command "Get-ChildItem -Path '%PROJECT_DIR%' -Recurse -File | Where-Object { $_.Name -notmatch '^\.env$' -and $_.Name -notmatch '^\.htaccess$' -and $_.Name -notmatch '^AUDIT_' -and $_.Name -notmatch '^PHASE0' -and $_.FullName -notmatch '\\\.git\\' -and $_.FullName -notmatch '\\assets\\uploads\\' } | Compress-Archive -DestinationPath '%PROJECT_DIR%\%ZIP_NAME%' -Force"
if not exist "%ZIP_NAME%" (
    echo ERROR: Failed to create zip
    pause
    exit /b 1
)

REM --- Step 3: Upload to server ---
echo Uploading to server...
(
    echo put %PROJECT_DIR%\%ZIP_NAME% %SERVER_DIR%/%ZIP_NAME%
    echo bye
) > "%TEMP%\sftp_upload.txt"

sftp -i "%SSH_KEY%" -o StrictHostKeyChecking=no -b "%TEMP%\sftp_upload.txt" %SSH_USER%@%SSH_HOST%
if errorlevel 1 (
    echo ERROR: SFTP upload failed
    pause
    exit /b 1
)

REM --- Step 4: Create and upload extract script ---
echo ^<?php > "%PROJECT_DIR%\extract_deploy.php"
echo $zip = new ZipArchive^(^); >> "%PROJECT_DIR%\extract_deploy.php"
echo if^($zip-^>open^(__DIR__ . '/%ZIP_NAME%'^) === TRUE^) { >> "%PROJECT_DIR%\extract_deploy.php"
echo     $zip-^>extractTo^(__DIR__^); >> "%PROJECT_DIR%\extract_deploy.php"
echo     $zip-^>close^(^); >> "%PROJECT_DIR%\extract_deploy.php"
echo     unlink^(__DIR__ . '/%ZIP_NAME%'^); >> "%PROJECT_DIR%\extract_deploy.php"
echo     unlink^(__FILE__^); >> "%PROJECT_DIR%\extract_deploy.php"
echo     echo 'Deployed successfully'; >> "%PROJECT_DIR%\extract_deploy.php"
echo } else { >> "%PROJECT_DIR%\extract_deploy.php"
echo     echo 'Failed to open zip'; >> "%PROJECT_DIR%\extract_deploy.php"
echo } >> "%PROJECT_DIR%\extract_deploy.php"
echo ?^> >> "%PROJECT_DIR%\extract_deploy.php"

(
    echo put %PROJECT_DIR%\extract_deploy.php %SERVER_DIR%/extract_deploy.php
    echo bye
) > "%TEMP%\sftp_extract.txt"

sftp -i "%SSH_KEY%" -o StrictHostKeyChecking=no -b "%TEMP%\sftp_extract.txt" %SSH_USER%@%SSH_HOST%

REM --- Step 5: Upload .htaccess with PHP handler ---
echo #+PHPVersion > "%PROJECT_DIR%\htaccess_deploy.txt"
echo #=php84 >> "%PROJECT_DIR%\htaccess_deploy.txt"
echo AddHandler x-httpd-php84 .php >> "%PROJECT_DIR%\htaccess_deploy.txt"
echo #-PHPVersion >> "%PROJECT_DIR%\htaccess_deploy.txt"
echo. >> "%PROJECT_DIR%\htaccess_deploy.txt"
echo DirectoryIndex index.php >> "%PROJECT_DIR%\htaccess_deploy.txt"

(
    echo put %PROJECT_DIR%\htaccess_deploy.txt %SERVER_DIR%/.htaccess
    echo bye
) > "%TEMP%\sftp_htaccess.txt"

sftp -i "%SSH_KEY%" -o StrictHostKeyChecking=no -b "%TEMP%\sftp_htaccess.txt" %SSH_USER%@%SSH_HOST%

REM --- Step 6: Trigger extraction ---
echo Triggering extraction...
curl -s "https://%SERVER_DIR%/extract_deploy.php" -k 2>nul
echo.

REM Clean up local files
if exist "%PROJECT_DIR%\extract_deploy.php" del "%PROJECT_DIR%\extract_deploy.php"
if exist "%PROJECT_DIR%\htaccess_deploy.txt" del "%PROJECT_DIR%\htaccess_deploy.txt"
if exist "%PROJECT_DIR%\%ZIP_NAME%" del "%PROJECT_DIR%\%ZIP_NAME%"

echo.
echo ============================================
echo   Deployment complete!
echo ============================================
echo.
pause