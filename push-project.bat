@echo off
setlocal enabledelayedexpansion

REM ============================================================
REM  push-project.bat — One-click push to GitHub + live server
REM  Project: Quotation Management System
REM  Server:  quotation.mfinnovation.xyz
REM ============================================================

echo.
echo ============================================
echo   Quotation Management System — Deploy
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

REM --- Step 1: Git Add & Commit ---
echo [1/4] Staging changes...
git add -A
if errorlevel 1 (
    echo ERROR: git add failed
    pause
    exit /b 1
)

echo [2/4] Committing...
git diff --cached --quiet
if errorlevel 1 (
    git commit -m "Deploy: %date% %time%"
    if errorlevel 1 (
        echo ERROR: git commit failed
        pause
        exit /b 1
    )
) else (
    echo No changes to commit.
)

echo [3/4] Pushing to GitHub...
git push %GIT_REMOTE% %GIT_BRANCH%
if errorlevel 1 (
    echo WARNING: git push failed. Continuing with server deploy...
)

REM --- Step 2: Create deployment zip ---
echo.
echo [4/4] Creating deployment archive...
if exist "%ZIP_NAME%" del "%ZIP_NAME%"
powershell -Command "Compress-Archive -Path '%PROJECT_DIR%\*' -DestinationPath '%PROJECT_DIR%\%ZIP_NAME%' -Force -CompressionLevel Optimal"
if not exist "%ZIP_NAME%" (
    echo ERROR: Failed to create zip
    pause
    exit /b 1
)

REM --- Step 3: Upload to server via SFTP ---
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

REM --- Step 4: Extract on server ---
echo Extracting on server...
(
    echo put %PROJECT_DIR%\extract_deploy.php %SERVER_DIR%/extract_deploy.php
    echo bye
) > "%TEMP%\sftp_extract.txt"

REM Create extract script
echo ^<?php > "%PROJECT_DIR%\extract_deploy.php"
echo $zip = new ZipArchive(); >> "%PROJECT_DIR%\extract_deploy.php"
echo if($zip->open(__DIR__ . '/%ZIP_NAME%') === TRUE) { >> "%PROJECT_DIR%\extract_deploy.php"
echo     $zip->extractTo(__DIR__); >> "%PROJECT_DIR%\extract_deploy.php"
echo     $zip->close(); >> "%PROJECT_DIR%\extract_deploy.php"
echo     unlink(__DIR__ . '/%ZIP_NAME%'); >> "%PROJECT_DIR%\extract_deploy.php"
echo     unlink(__FILE__); >> "%PROJECT_DIR%\extract_deploy.php"
echo     echo 'Deployed successfully'; >> "%PROJECT_DIR%\extract_deploy.php"
echo } else { >> "%PROJECT_DIR%\extract_deploy.php"
echo     echo 'Failed to open zip'; >> "%PROJECT_DIR%\extract_deploy.php"
echo } >> "%PROJECT_DIR%\extract_deploy.php"
echo ?^> >> "%PROJECT_DIR%\extract_deploy.php"

sftp -i "%SSH_KEY%" -o StrictHostKeyChecking=no -b "%TEMP%\sftp_extract.txt" %SSH_USER%@%SSH_HOST%

REM Trigger extraction via HTTP
echo Triggering extraction...
curl -s "https://%SERVER_DIR%/extract_deploy.php" 2>nul
echo.

REM Clean up local files
if exist "%PROJECT_DIR%\extract_deploy.php" del "%PROJECT_DIR%\extract_deploy.php"
if exist "%PROJECT_DIR%\%ZIP_NAME%" del "%PROJECT_DIR%\%ZIP_NAME%"

echo.
echo ============================================
echo   Deployment complete!
echo ============================================
echo.
pause