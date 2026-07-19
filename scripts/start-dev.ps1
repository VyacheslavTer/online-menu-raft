param(
    [int]$Port = 8765
)

$ErrorActionPreference = "Stop"

$php = Get-Command php -ErrorAction SilentlyContinue | Select-Object -First 1 -ExpandProperty Source

if (-not $php) {
    $wingetPhp = Join-Path $env:LOCALAPPDATA 'Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe'
    if (Test-Path -LiteralPath $wingetPhp) {
        $php = $wingetPhp
    }
}

if (-not $php) {
    throw 'PHP is not installed or not visible in PATH.'
}

Write-Host "Starting PHP dev server: http://127.0.0.1:$Port"
& $php -S "127.0.0.1:$Port" -t public