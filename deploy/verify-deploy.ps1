# verify-deploy.ps1 - confirm the deployed files actually match local.
#
# Why this is a .ps1 and not a :label inside the .bat:
#   A batch `for /f ... in (`ssh -o StrictHostKeyChecking=no ...`)` gets its
#   command line re-parsed by cmd, which splits the "-o Key=Value" pair. ssh
#   then reports: no argument after keyword "stricthostkeychecking", returns
#   nothing, and every file looks like remote=0. Running ssh from PowerShell
#   passes each argument as an array element, so nothing is re-split.
#
# Usage: powershell -NoProfile -ExecutionPolicy Bypass -File verify-deploy.ps1 `
#            -ProjectDir <dir> -SshKey <keyfile> -SshTarget <user@host> -ServerDir <dir>
# Exit:  0 = all files match, N = number of mismatches

param(
    [Parameter(Mandatory = $true)][string]$ProjectDir,
    [Parameter(Mandatory = $true)][string]$SshKey,
    [Parameter(Mandatory = $true)][string]$SshTarget,
    [Parameter(Mandatory = $true)][string]$ServerDir
)

$ErrorActionPreference = 'Continue'

$checks = @(
    @('index.php',                'index.php'),
    @('login.php',                'login.php'),
    @('logout.php',               'logout.php'),
    @('admin\users\index.php',    'admin/users/index.php'),
    @('admin\settings\index.php', 'admin/settings/index.php'),
    @('includes\functions.php',   'includes/functions.php'),
    @('classes\User.php',         'classes/User.php'),
    @('config\database.php',      'config/database.php')
)

$failures = 0

foreach ($pair in $checks) {
    $localRel  = $pair[0]
    $remoteRel = $pair[1]
    $localPath = Join-Path $ProjectDir $localRel

    $localSize = -1
    if (Test-Path -LiteralPath $localPath) {
        $localSize = (Get-Item -LiteralPath $localPath).Length
    }

    $remotePath = "$ServerDir/$remoteRel"
    $output = & ssh -i $SshKey -o StrictHostKeyChecking=no -o LogLevel=ERROR $SshTarget "stat -c %s `"$remotePath`"" 2>$null
    $sshExit = $LASTEXITCODE

    $remoteSize = -1
    if ($sshExit -eq 0 -and $output) {
        $text = ($output -join '').Trim()
        $parsed = 0
        if ([int]::TryParse($text, [ref]$parsed)) { $remoteSize = $parsed }
    }

    if ($localSize -lt 0) {
        Write-Host ("  FAIL  {0,-28} local file missing" -f $remoteRel)
        $failures++
    }
    elseif ($localSize -eq $remoteSize) {
        Write-Host ("  OK    {0,-28} [{1} bytes]" -f $remoteRel, $localSize)
    }
    else {
        $shown = if ($remoteSize -lt 0) { 'unreadable' } else { "$remoteSize" }
        Write-Host ("  FAIL  {0,-28} local={1} remote={2}" -f $remoteRel, $localSize, $shown)
        $failures++
    }
}

if ($failures -gt 0) {
    Write-Host ""
    Write-Host "  ssh exit code was $sshExit for the last check" -ErrorAction SilentlyContinue
}

exit $failures
