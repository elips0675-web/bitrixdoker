param([Parameter(Mandatory=$true)][string]$Path)

# Разбор HTML мастера установки Битрикса: что за шаг и какие поля он ждёт.
$ErrorActionPreference = 'Stop'
$bytes = [System.IO.File]::ReadAllBytes($Path)
$utf8 = [System.Text.Encoding]::UTF8.GetString($bytes)
if ($utf8 -notmatch 'charset=UTF-8') {
    $cp = [System.Text.Encoding]::GetEncoding(1251)
    $text = $cp.GetString($bytes)
} else {
    $text = $utf8
}

$out = New-Object System.Collections.Generic.List[string]
$out.Add('=== FORM ===')
foreach ($m in [regex]::Matches($text, '<form[^>]*>')) { $out.Add($m.Value) }
$out.Add('')
$out.Add('=== INPUTS ===')
foreach ($m in [regex]::Matches($text, '<input[^>]*>')) { $out.Add($m.Value) }
$out.Add('')
$out.Add('=== SELECTS ===')
foreach ($m in [regex]::Matches($text, '<select[^>]*>')) { $out.Add($m.Value) }
foreach ($m in [regex]::Matches($text, '<option[^>]*>[^<]*')) { $out.Add('  ' + $m.Value) }
$out.Add('')
$out.Add('=== LINKS ===')
foreach ($m in [regex]::Matches($text, '<a\s[^>]*href="([^"]*)"')) { $out.Add($m.Groups[1].Value) }
$out.Add('')
$out.Add('=== TEXT ===')
$plain = $text -replace '(?s)<script.*?</script>', ' ' -replace '(?s)<style.*?</style>', ' ' -replace '<[^>]+>', ' '
$plain = [System.Net.WebUtility]::HtmlDecode($plain) -replace '\s+', ' '
$out.Add($plain.Trim())

[System.IO.File]::WriteAllText($Path + '.txt', ($out -join "`r`n"), (New-Object System.Text.UTF8Encoding($false)))
Write-Output "OK $Path.txt"
