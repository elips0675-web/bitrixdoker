param(
    [string]$Base = 'http://localhost:8080/',
    [string]$Step = 'create_modules',
    [string]$Next = 'main',
    [string]$Stage = 'database',
    [int]$MaxIterations = 300
)

# Проходит AJAX-цикл мастера установки: каждый POST выполняет один этап
# и возвращает [response]window.ajaxForm.Post('next','stage')[/response].
$ErrorActionPreference = 'Stop'
$dir = Split-Path -Parent $MyInvocation.MyCommand.Path
$cookies = Join-Path $dir 'bx.cookies'
$stageDir = Join-Path $dir 'stages'
New-Item -ItemType Directory -Force -Path $stageDir | Out-Null
$log = Join-Path $dir 'stages.log'
if (Test-Path $log) { Remove-Item $log }

function Log([string]$s) {
    $line = ('[{0}] {1}' -f (Get-Date -Format 'HH:mm:ss'), $s)
    Add-Content -Path $log -Value $line -Encoding UTF8
    Write-Host $line
}

$i = 0
while ($i -lt $MaxIterations) {
    $i++
    $body = "CurrentStepID=$Step&__wiz_nextStep=$Next&__wiz_nextStepStage=$Stage"
    $out = Join-Path $stageDir ('{0:d3}_{1}_{2}.html' -f $i, $Next, $Stage)
    $raw = & curl.exe -s -b $cookies -c $cookies --max-time 1800 `
        -d $body $Base 2>&1 | Out-String
    [System.IO.File]::WriteAllText($out, $raw, (New-Object System.Text.UTF8Encoding($false)))

    $m = [regex]::Match($raw, '(?s)\[response\](.*?)\[/response\]')
    if (-not $m.Success) {
        Log "ITER $i  next=$Next stage=$Stage  -> НЕТ [response], ответ сохранён: $out"
        Log ('  tail: ' + ($raw.Substring([Math]::Max(0, $raw.Length - 800)) -replace '\s+', ' '))
        break
    }

    $resp = $m.Groups[1].Value
    $p = [regex]::Match($resp, "Post\(\s*\{\s*['`"]nextStep['`"]\s*:\s*['`"]([^'`"]*)['`"]\s*,\s*['`"]nextStepStage['`"]\s*:\s*['`"]([^'`"]*)['`"]")
    if (-not $p.Success) {
        $p = [regex]::Match($resp, "Post\(\s*['`"]([^'`"]*)['`"]\s*,\s*['`"]([^'`"]*)['`"]")
    }
    if (-not $p.Success) {
        Log "ITER $i  -> [response] без Post(): $resp"
        break
    }

    $nextStep = $p.Groups[1].Value
    $nextStage = $p.Groups[2].Value
    $st = [regex]::Match($resp, "SetStatus\('(\d+)'\)")
    $pct = if ($st.Success) { $st.Groups[1].Value + '%' } else { '?' }
    Log "ITER $i  $Next/$Stage -> $nextStep/$nextStage  ($pct)"

    if ($nextStep -eq 'finish') {
        $raw2 = & curl.exe -s -b $cookies -c $cookies --max-time 600 `
            -d "CurrentStepID=$Step&__wiz_nextStep=finish&__wiz_nextStepStage=$nextStage" $Base 2>&1 | Out-String
        [System.IO.File]::WriteAllText((Join-Path $stageDir 'finish.html'), $raw2, (New-Object System.Text.UTF8Encoding($false)))
        Log 'FINISH отправлен, ответ сохранён: stages/finish.html'
        break
    }

    $Next = $nextStep
    $Stage = $nextStage
    Start-Sleep -Milliseconds 200
}
