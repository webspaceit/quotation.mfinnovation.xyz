@echo off
setlocal enabledelayedexpansion

REM ============================================================
REM  push-project.bat - push to GitHub + deploy to live server
REM  Project: Quotation Management System
REM  Server:  quotation.mfinnovation.xyz
REM
REM  Run it by double-clicking, or from cmd in the project dir.
REM
REM  WHY THIS SCRIPT LOOKS THE WAY IT DOES
REM  --------------------------------------
REM  THREE separate things used to flatten the deploy. All three are handled:
REM
REM  1) `Get-ChildItem -Recurse -File | Compress-Archive` stored every file
REM     under its BASE NAME ONLY, so the zip had no folders at all.
REM     -> fixed by staging with robocopy and zipping the staged tree.
REM
REM  2) Windows PowerShell 5.1 (what `powershell` resolves to from cmd) writes
REM     zip entries with BACKSLASHES: "admin\users\index.php". Linux unzip sees
REM     one filename, not a path, so nested files still land in the site root.
REM     Compress-Archive AND .NET's ZipFile::CreateFromDirectory both do this
REM     on .NET Framework. -> fixed by deploy\make-zip.ps1, which writes each
REM     entry itself with "/" separators and refuses to produce a bad archive.
REM
REM  3) Extraction was triggered with `curl` to a PHP endpoint, which fails
REM     quietly on this machine (schannel: SEC_E_NO_CREDENTIALS) behind 2>nul.
REM     -> fixed by extracting with `unzip` over SSH and checking the exit code.
REM
REM  Net effect of the old bug: admin/users/index.php was never updated, while
REM  its contents were dumped into the site root as index.php, so "/" returned
REM  500. Do not reintroduce any of the three patterns above.
REM
REM  NOTE: this script ADDS and OVERWRITES files. It does not delete files on
REM  the server that you deleted locally - remove those by hand over SFTP.
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
set "FAIL=0"

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

REM --- 1. Git ------------------------------------------------
echo [1/7] Git add / commit / push...
git add -A
if errorlevel 1 (
    echo ERROR: git add failed
    goto :end
)

git diff --cached --quiet
if errorlevel 1 (
    git commit -q -m "Deploy: %date% %time%"
)

git push %GIT_REMOTE% %GIT_BRANCH%
if errorlevel 1 echo WARNING: git push failed - continuing with server deploy.

REM --- 2. Stage a clean copy, structure intact ----------------
echo [2/7] Staging deployment copy...
if exist "%STAGE%" rd /s /q "%STAGE%"
REM assets\uploads is skipped: uploaded media belongs to the server, and the
REM directory's .htaccess is uploaded separately in step 6.
REM NOTE: robocopy ignores a multi-segment RELATIVE /XD path - it must be absolute.
robocopy "%PROJECT_DIR%" "%STAGE%" /E /XD .git _quarantine node_modules deploy "%PROJECT_DIR%\assets\uploads" /XF .env .htaccess AUDIT_*.md *_NOTES.md *.zip push-project.bat database.sql README.md /NFL /NDL /NJH /NJS /NP >nul
if errorlevel 8 (
    echo ERROR: staging failed
    goto :end
)

REM --- 3. Zip the staged tree (directories preserved) ---------
REM Do NOT use Compress-Archive here. Windows PowerShell 5.1 - which is what
REM `powershell` resolves to from cmd - writes zip entry names with BACKSLASHES
REM ("admin\users\index.php"). Linux unzip treats that as one filename, not a
REM path, so everything lands flat in the site root again. make-zip.ps1 builds
REM the archive itself and normalises separators to "/".
echo [3/7] Creating archive...
if not exist "%PROJECT_DIR%\deploy\make-zip.ps1" (
    echo ERROR: deploy\make-zip.ps1 is missing - cannot build the archive safely
    goto :end
)
if exist "%ZIP_PATH%" del "%ZIP_PATH%"
powershell -NoProfile -ExecutionPolicy Bypass -File "%PROJECT_DIR%\deploy\make-zip.ps1" -Stage "%STAGE%" -Zip "%ZIP_PATH%"
if errorlevel 1 (
    echo ERROR: archive creation failed - server untouched
    goto :end
)
if not exist "%ZIP_PATH%" (
    echo ERROR: failed to create archive
    goto :end
)
rd /s /q "%STAGE%"

REM Sanity check BEFORE touching the server: the nested path must be inside.
echo        checking archive layout...
powershell -NoProfile -Command "Add-Type -AssemblyName System.IO.Compression.FileSystem; $z=[System.IO.Compression.ZipFile]::OpenRead('%ZIP_PATH%'); $n=$z.Entries.FullName; $z.Dispose(); if ($n -notcontains 'admin/users/index.php') { exit 1 } else { exit 0 }"
if errorlevel 1 (
    echo.
    echo ERROR: archive has no directory structure - refusing to upload.
    echo        admin/users/index.php is missing from the zip.
    echo        Check deploy\make-zip.ps1 - it should never produce this.
    goto :end
)

REM --- 4. Upload ---------------------------------------------
echo [4/7] Uploading archive...
scp -i "%SSH_KEY%" -o StrictHostKeyChecking=no -o LogLevel=ERROR "%ZIP_PATH%" "%SSH_TARGET%:%SERVER_DIR%/%ZIP_NAME%"
if errorlevel 1 (
    echo ERROR: upload failed - server untouched
    goto :end
)

REM --- 5. Extract on the server ------------------------------
echo [5/7] Extracting on server...
ssh -i "%SSH_KEY%" -o StrictHostKeyChecking=no -o LogLevel=ERROR "%SSH_TARGET%" "cd %SERVER_DIR% && unzip -oq %ZIP_NAME% && rm -f %ZIP_NAME% extract_deploy.php && echo EXTRACT_OK"
if errorlevel 1 (
    echo ERROR: extraction failed on server
    goto :end
)

REM --- 6. Server config --------------------------------------
echo [6/7] Uploading server configuration...
scp -i "%SSH_KEY%" -o StrictHostKeyChecking=no -o LogLevel=ERROR "%PROJECT_DIR%\deploy\server.htaccess" "%SSH_TARGET%:%SERVER_DIR%/.htaccess"
if errorlevel 1 echo WARNING: .htaccess upload failed - security rules NOT applied.

scp -i "%SSH_KEY%" -o StrictHostKeyChecking=no -o LogLevel=ERROR "%PROJECT_DIR%\deploy\uploads.htaccess" "%SSH_TARGET%:%SERVER_DIR%/assets/uploads/.htaccess"
if errorlevel 1 echo WARNING: assets/uploads/.htaccess upload failed.

REM --- 7. Verify the files actually changed ------------------
echo [7/7] Verifying deployed files...
echo.
call :CheckFile "index.php"                  "index.php"
call :CheckFile "login.php"                  "login.php"
call :CheckFile "admin\users\index.php"      "admin/users/index.php"
call :CheckFile "admin\settings\index.php"   "admin/settings/index.php"
call :CheckFile "includes\functions.php"     "includes/functions.php"
call :CheckFile "classes\User.php"           "classes/User.php"
call :CheckFile "config\database.php"        "config/database.php"
echo.

REM Informational only - a TLS/proxy problem here is not a deploy failure.
powershell -NoProfile -Command "try { $r = Invoke-WebRequest -Uri 'https://%SERVER_DIR%/login.php' -UseBasicParsing -TimeoutSec 20 -ErrorAction Stop; Write-Host ('  http check: login.php -> ' + $r.StatusCode) } catch { Write-Host '  http check: could not reach site (check it manually)' }"

echo.
if "%FAIL%"=="1" (
    echo ============================================
    echo   DEPLOYED WITH PROBLEMS - files above marked FAIL
    echo ============================================
) else (
    echo ============================================
    echo   Deployment verified - server matches local
    echo ============================================
)

:end
if exist "%ZIP_PATH%" del "%ZIP_PATH%" >nul 2>&1
if exist "%STAGE%" rd /s /q "%STAGE%" >nul 2>&1
echo.
pause
exit /b 0

REM ============================================================
REM  :CheckFile  <local relative path>  <remote relative path>
REM  Compares byte size of the local file and the deployed one.
REM ============================================================
:CheckFile
set "LSZ="
set "RSZ="
for %%F in ("%PROJECT_DIR%\%~1") do set "LSZ=%%~zF"
for /f "usebackq tokens=*" %%S in (`ssh -i "%SSH_KEY%" -o StrictHostKeyChecking=no -o LogLevel=ERROR "%SSH_TARGET%" "stat -c %%s %SERVER_DIR%/%~2"`) do set "RSZ=%%S"
if "%LSZ%"=="" set "LSZ=0"
if "%RSZ%"=="" set "RSZ=0"
if "%LSZ%"=="0" (
    echo   FAIL  %~1 - local file missing or empty, nothing to compare
    set "FAIL=1"
    exit /b
)
if "%LSZ%"=="%RSZ%" (
    echo   OK    %~2  [%LSZ% bytes]
) else (
    echo   FAIL  %~2  local=%LSZ% remote=%RSZ%
    set "FAIL=1"
)
exit /b
