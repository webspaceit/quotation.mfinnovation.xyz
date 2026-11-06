# make-zip.ps1 - build a deployment archive with guaranteed forward-slash paths.
#
# Why this exists:
#   Windows PowerShell 5.1 (what `powershell` resolves to from cmd) writes zip
#   entry names with BACKSLASHES when using Compress-Archive, and even
#   [IO.Compression.ZipFile]::CreateFromDirectory does the same on .NET Framework.
#   A zip entry named "admin\users\index.php" is a single FILE to Linux `unzip`,
#   not a directory path, so every nested file lands in the site root and the
#   real nested files are never updated.
#
#   This script writes entries itself, normalising "\" to "/", and then verifies
#   the result before the deploy proceeds.
#
# Usage: powershell -NoProfile -ExecutionPolicy Bypass -File make-zip.ps1 -Stage <dir> -Zip <file>
# Exit:  0 = archive is good, 1 = archive is unusable

param(
    [Parameter(Mandatory = $true)][string]$Stage,
    [Parameter(Mandatory = $true)][string]$Zip
)

$ErrorActionPreference = 'Stop'
# ZipArchive/ZipArchiveMode live in System.IO.Compression; ZipFile lives in
# System.IO.Compression.FileSystem. Windows PowerShell 5.1 needs both loaded.
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

function Fail($msg) {
    Write-Host "ERROR: $msg"
    exit 1
}

if (-not (Test-Path -LiteralPath $Stage)) { Fail "staging folder not found: $Stage" }

$stageRoot = (Resolve-Path -LiteralPath $Stage).Path.TrimEnd('\')
if (Test-Path -LiteralPath $Zip) { Remove-Item -LiteralPath $Zip -Force }

$added = 0
try {
    $stream = [System.IO.File]::Open($Zip, [System.IO.FileMode]::Create)
    $archive = New-Object System.IO.Compression.ZipArchive($stream, [System.IO.Compression.ZipArchiveMode]::Create)

    Get-ChildItem -LiteralPath $stageRoot -Recurse -File | ForEach-Object {
        $rel = $_.FullName.Substring($stageRoot.Length + 1).Replace('\', '/')
        $entry = $archive.CreateEntry($rel, [System.IO.Compression.CompressionLevel]::Optimal)
        $entryStream = $entry.Open()
        $fileStream = [System.IO.File]::OpenRead($_.FullName)
        try { $fileStream.CopyTo($entryStream) }
        finally { $fileStream.Dispose(); $entryStream.Dispose() }
        $script:added++
    }

    $archive.Dispose()
    $stream.Dispose()
}
catch {
    Fail "could not create archive: $($_.Exception.Message)"
}

# --- verify before anyone uploads this --------------------------------------
$check = [System.IO.Compression.ZipFile]::OpenRead($Zip)
try {
    $names = @($check.Entries | ForEach-Object { $_.FullName })
}
finally {
    $check.Dispose()
}

if ($names.Count -eq 0) { Fail "archive is empty" }

$withBackslash = @($names | Where-Object { $_ -match '\\' })
if ($withBackslash.Count -gt 0) {
    Fail ("archive has backslash separators in {0} entries, e.g. '{1}' - Linux unzip would flatten it" -f $withBackslash.Count, $withBackslash[0])
}

foreach ($required in @('index.php', 'admin/users/index.php', 'includes/functions.php', 'config/database.php')) {
    if ($names -notcontains $required) { Fail "archive is missing '$required' - directory structure was lost" }
}

$size = [math]::Round((Get-Item -LiteralPath $Zip).Length / 1KB, 1)
Write-Host ("  archive OK: {0} files, {1} KB, paths use '/'" -f $names.Count, $size)
exit 0
