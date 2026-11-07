# Overlay NLB project files onto the installed Bitrix.
# Does not overwrite bitrix/, upload/, .settings.php, dbconn.php, .htaccess.

$src = 'D:\nlbbootstrap-main'
$dst = 'D:\bitrix doker\www'

if (!(Test-Path $dst)) { throw "Missing stand dir: $dst" }

$files = @(
    'index.php', '404.php', 'urlrewrite.php', 'robots.txt',
    'sitemap.php', 'sw.js', 'offline.html', 'manifest.webmanifest'
)
$dirs = @(
    'about', 'api', 'auth', 'books', 'collections', 'contacts', 'demo',
    'events', 'feedback', 'local', 'news', 'personal', 'preview', 'printing',
    'privacy', 'rules', 'search', 'services', 'sitemap', 'subscribe', 'terms',
    'db', 'tools', 'menu'
)

foreach ($f in $files) {
    $p = Join-Path $src $f
    if (Test-Path $p) {
        Copy-Item $p (Join-Path $dst $f) -Force
        Write-Host "file  $f"
    }
}

foreach ($d in $dirs) {
    $s = Join-Path $src $d
    if (!(Test-Path $s)) { continue }
    robocopy $s (Join-Path $dst $d) /E /NFL /NDL /NJH /NJS /NP /XD node_modules .git /XF *.log | Out-Null
    Write-Host "dir   $d"
}

Write-Host "DONE: project files copied to $dst"
