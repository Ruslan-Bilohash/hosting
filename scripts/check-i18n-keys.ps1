$ErrorActionPreference = 'Stop'
function Get-LangKeys([string]$Path) {
    $keys = [System.Collections.Generic.HashSet[string]]::new()
    Get-Content $Path | ForEach-Object {
        if ($_ -match "^\s+'([a-zA-Z0-9_]+)'\s*=>") { [void]$keys.Add($Matches[1]) }
    }
    return $keys
}
$files = @{
    'public-uk' = 'C:\bilohash\hosting\lang\uk.php'
    'public-en' = 'C:\bilohash\hosting\lang\en.php'
    'public-no' = 'C:\bilohash\hosting\lang\no.php'
    'panel-uk'  = 'C:\bilohash\hosting\lang\panel-uk.php'
    'panel-en'  = 'C:\bilohash\hosting\lang\panel-en.php'
    'panel-no'  = 'C:\bilohash\hosting\lang\panel-no.php'
}
$all = @{}
foreach ($k in $files.Keys) { $all[$k] = Get-LangKeys $files[$k] }
$pairs = @(
    @('public-uk','public-en'),
    @('public-uk','public-no'),
    @('panel-uk','panel-en'),
    @('panel-uk','panel-no')
)
foreach ($pair in $pairs) {
    $ref = $pair[0]; $tgt = $pair[1]
    $miss = $all[$ref] | Where-Object { -not $all[$tgt].Contains($_) } | Sort-Object
    $extra = $all[$tgt] | Where-Object { -not $all[$ref].Contains($_) } | Sort-Object
    Write-Host "=== $ref -> $tgt : missing $($miss.Count), extra $($extra.Count) ==="
    if ($miss.Count) { $miss | Out-File "C:\bilohash\hosting\scripts\missing-$tgt.txt" -Encoding utf8; Write-Host "  saved scripts/missing-$tgt.txt" }
}