@echo off
setlocal enabledelayedexpansion

REM ============================================================
REM  push-project.bat - One-click push to GitHub + live server
REM  Project: Quotation Management System
REM  Server:  quotation.mfinnovation.xyz
REM
REM  IMPORTANT: the archive must preserve directory structure.
REM  The old version used  Get-ChildItem -Recurse -File ^| Compress-Archive
REM  which flattens every FileInfo to its base name. That put
REM  admin/users/index.php into the site ROOT (overwriting index.php),
REM  left the real admin/users/index.php untouched, and broke "/" with
REM  a 500. Do not go back to that pattern.
REM ============================================================

set "PROJECT_DIR=E:\wamp64\www\qms"
set "SSH_KEY=C:\Users\sizub\.ssh\id_ed25519_mf"
set "SSH_USER=mfinnovation.xyz"
set "SSH_HOST=ssh.gb.stackcp.com"
set "SSH_TARGET=%SSH_USER%@%SSH_HOST%"
set "SERVER_DIR=quotation.mfinnovation.xyz"
set "ZIP_NAME=qms_deploy.zip"
set "ZIP_PATH=%TEMP%\%ZIP_NAME%"
set "STAGE=%TEMP%\qms_stage"
set "GIT_REMOTE=origin"
set "GIT_BRANCH=master"

cd /d "%PROJECT_DIR%"
if errorlevel 1 (
    echo ERROR: cannot cd to %PROJECT_DIR%
    pause
    exit /b 1
)

echo.
echo ============================================
echo   Quotation Management System - Deploy
echo ============================================
echo.

REM --- Step 1: Git add, commit, push -------------------------
echo [1/6] Git...
git add -A
if errorlevel 1 (
    echo ERROR: git add failed
    pause
    exit /b 1
)

git diff --cached --quiet
if errorlevel 1 (
    git commit -q -m "Deploy: %date% %time%"
)

git push %GIT_REMOTE% %GIT_BRANCH%
if errorlevel 1 echo WARNING: git push failed, continuing with server deploy.

REM --- Step 2: Stage a clean copy with structure intact ------
echo [2/6] Staging deployment copy...
if exist "%STAGE%" rd /s /q "%STAGE%"
robocopy "%PROJECT_DIR%" "%STAGE%" /E /XD .git _quarantine node_modules deploy /XF .env .htaccess AUDIT_*.md PHASE0_NOTES.md *.zip push-project.bat database.sql README.md /NFL /NDL /NJH /NJS /NP >nul
if errorlevel 8 (
    echo ERROR: staging failed
    pause
    exit /b 1
)

REM --- Step 3: Zip the staged copy (structure preserved) -----
echo [3/6] Creating archive...
if exist "%ZIP_PATH%" del "%ZIP_PATH%"
powershell -NoProfile -Command "Compress-Archive -Path '%STAGE%\*' -DestinationPath '%ZIP_PATH%' -Force"
if not exist "%ZIP_PATH%" (
    echo ERROR: failed to create archive
    pause
    exit /b 1
)
rd /s /q "%STAGE%"

REM --- Step 4: Upload and extract over SSH -------------------
echo [4/6] Uploading...
scp -i "%SSH_KEY%" -o StrictHostKeyChecking=no -o LogLevel=ERROR "%ZIP_PATH%" "%SSH_TARGET%:%SERVER_DIR%/%ZIP_NAME%"
if errorlevel 1 (
    echo ERROR: upload failed
    pause
    exit /b 1
)

echo [5/6] Extracting on server...
ssh -i "%SSH_KEY%" -o StrictHostKeyChecking=no -o LogLevel=ERROR "%SSH_TARGET%" "cd %SERVER_DIR% && unzip -oq %ZIP_NAME% && rm -f %ZIP_NAME% extract_deploy.php && echo EXTRACT_OK"
if errorlevel 1 (
    echo ERROR: extraction failed on server
    pause
    exit /b 1
)

REM Deploy the hardened server .htaccess (host PHP handler + deny rules)
scp -i "%SSH_KEY%" -o StrictHostKeyChecking=no -o LogLevel=ERROR "%PROJECT_DIR%\deploy\server.htaccess" "%SSH_TARGET%:%SERVER_DIR%/.htaccess"
if errorlevel 1 (
    echo WARNING: .htaccess upload failed - security rules NOT applied
)

REM Uploaded media must never execute either
scp -i "%SSH_KEY%" -o StrictHostKeyChecking=no -o LogLevel=ERROR "%PROJECT_DIR%\deploy\uploads.htaccess" "%SSH_TARGET%:%SERVER_DIR%/assets/uploads/.htaccess"
if errorlevel 1 (
    echo WARNING: assets/uploads/.htaccess upload failed
)

REM --- Step 6: Verify the files actually changed -------------
echo [6/6] Verifying...

for %%F in ("%PROJECT_DIR%\admin\users\index.php") do set "LOCAL_SIZE=%%~zF"
for /f "usebackq tokens=*" %%S in (`ssh -i "%SSH_KEY%" -o StrictHostKeyChecking=no -o LogLevel=ERROR "%SSH_TARGET%" "stat -c %%s %SERVER_DIR%/admin/users/index.php"`) do set "REMOTE_SIZE=%%S"

echo   local  admin/users/index.php = %LOCAL_SIZE% bytes
echo   remote admin/users/index.php = %REMOTE_SIZE% bytes

set "FAIL=0"
if not "%LOCAL_SIZE%"=="%REMOTE_SIZE%" (
    echo   *** MISMATCH - the deploy did not update this file ***
    set "FAIL=1"
)
if "%REMOTE_SIZE%"=="" (
    echo   *** Could not read the remote file ***
    set "FAIL=1"
)

REM Root index.php must stay small (front controller), not a page copy
for /f "usebackq tokens=*" %%S in (`ssh -i "%SSH_KEY%" -o StrictHostKeyChecking=no -o LogLevel=ERROR "%SSH_TARGET%" "stat -c %%s %SERVER_DIR%/index.php"`) do set "ROOT_SIZE=%%S"
if not "%ROOT_SIZE%"=="188" (
    echo   *** WARNING: root index.php is %ROOT_SIZE% bytes, expected 188 ***
    set "FAIL=1"
)

powershell -NoProfile -Command "try { $r = Invoke-WebRequest -Uri 'https://%SERVER_DIR%/login.php' -UseBasicParsing -TimeoutSec 20; Write-Host ('  https login.php -> ' + $r.StatusCode) } catch { Write-Host '  *** https check failed ***'; exit 1 }"
if errorlevel 1 set "FAIL=1"

echo.
if "%FAIL%"=="1" (
    echo ============================================
    echo   DEPLOY COMPLETED WITH PROBLEMS - see above
    echo ============================================
) else (
    echo ============================================
    echo   Deployment verified
    echo ============================================
)

if exist "%ZIP_PATH%" del "%ZIP_PATH%"
echo.
pause
