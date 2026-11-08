param(
    [Parameter(Mandatory = $true)]
    [string]$Destination,

    [string]$Source = '',

    [switch]$SkipPreview
)

$ErrorActionPreference = 'Stop'

if ([string]::IsNullOrWhiteSpace($Source)) {
    $Source = Split-Path -Parent $PSScriptRoot
}

if (!(Test-Path $Destination)) {
    throw "Destination not found: $Destination"
}
if (!(Test-Path (Join-Path $Destination 'bitrix'))) {
    throw "Destination does not look like a Bitrix root (no bitrix/): $Destination"
}
if (!(Test-Path (Join-Path $Source 'index.php'))) {
    throw "Source does not look like the NLB project (no index.php): $Source"
}

$files = @(
    'index.php', '404.php', 'urlrewrite.php', 'robots.txt',
    'sitemap.php', 'sw.js', 'offline.html', 'manifest.webmanifest',
    '.section.php'
)
$dirs = @(
    'about', 'api', 'auth', 'books', 'collections', 'contacts', 'demo',
    'events', 'feedback', 'local', 'news', 'personal', 'printing',
    'privacy', 'rules', 'search', 'services', 'sitemap', 'subscribe', 'terms',
    'db', 'tools'
)
if (!$SkipPreview) {
    $dirs += 'preview'
}

foreach ($f in $files) {
    $p = Join-Path $Source $f
    if (Test-Path $p) {
        Copy-Item $p (Join-Path $Destination $f) -Force
        Write-Host "file  $f"
    }
}

foreach ($d in $dirs) {
    $s = Join-Path $Source $d
    if (!(Test-Path $s)) { continue }
    robocopy $s (Join-Path $Destination $d) /E /NFL /NDL /NJH /NJS /NP /XD node_modules .git /XF *.log | Out-Null
    if ($LASTEXITCODE -ge 8) {
        throw "robocopy failed for $d (exit $LASTEXITCODE)"
    }
    Write-Host "dir   $d"
}

Write-Host "done: $Source -> $Destination"
