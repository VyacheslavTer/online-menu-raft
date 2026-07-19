param(
    [switch]$DryRun
)

$ErrorActionPreference = "Stop"

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).ProviderPath
$configPath = Join-Path $projectRoot "deploy.config.local.ps1"
$exampleConfigPath = Join-Path $projectRoot "deploy.config.example.ps1"

if (-not (Test-Path -LiteralPath $configPath)) {
    if ($DryRun -and (Test-Path -LiteralPath $exampleConfigPath)) {
        $configPath = $exampleConfigPath
        Write-Host "Using deploy.config.example.ps1 for dry run."
    } else {
        throw "Create deploy.config.local.ps1 from deploy.config.example.ps1 first."
    }
}

. $configPath

$excludeDirs = @(
    ".git",
    ".agents",
    ".codex",
    "storage",
    "public\uploads\menu",
    "public\uploads\menu-source",
    "scripts",
    "sql"
)

$excludeFiles = @(
    "deploy.config.local.ps1",
    "app\config.local.php",
    "storage\database.sqlite",
    "README.md",
    "HANDOFF.md",
    "deploy.config.example.ps1",
    ".gitignore",
    ".htaccess"
)

function Get-RelativePath {
    param(
        [string]$Root,
        [string]$FullName
    )

    return $FullName.Substring($Root.Length).TrimStart("\", "/")
}

function Test-IsExcluded {
    param([string]$RelativePath)

    if ($RelativePath -notlike "*\*" -and $RelativePath -match "(?i)\.jpe?g$") {
        return $true
    }

    foreach ($dir in $excludeDirs) {
        if ($RelativePath -eq $dir -or $RelativePath.StartsWith($dir + "\")) {
            return $true
        }
    }

    foreach ($file in $excludeFiles) {
        if ($RelativePath -eq $file) {
            return $true
        }
    }

    return $false
}

function Convert-ToFtpPath {
    param([string]$RelativePath)

    $remote = ($RemoteRoot.TrimEnd("/") + "/" + ($RelativePath -replace "\\", "/")).TrimEnd("/")
    return "ftp://$DeployHost`:$DeployPort$remote"
}

function New-RemoteDirectory {
    param([string]$RelativePath)

    if ([string]::IsNullOrWhiteSpace($RelativePath)) {
        return
    }

    $parts = $RelativePath -split "\\"
    $path = ""
    foreach ($part in $parts) {
        if ([string]::IsNullOrWhiteSpace($part)) {
            continue
        }
        $path = if ($path) { Join-Path $path $part } else { $part }
        $uri = Convert-ToFtpPath $path
        if ($DryRun) {
            Write-Host "MKDIR $uri"
            continue
        }

        try {
            $request = [System.Net.FtpWebRequest]::Create($uri)
            $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
            $request.Credentials = New-Object System.Net.NetworkCredential($DeployUser, $DeployPassword)
            $request.EnableSsl = [bool]$UseFtps
            $response = $request.GetResponse()
            $response.Close()
        } catch {
            # FTP returns an error when the directory already exists.
        }
    }
}

function Send-File {
    param(
        [string]$LocalPath,
        [string]$RelativePath
    )

    $dir = Split-Path $RelativePath -Parent
    New-RemoteDirectory $dir

    $uri = Convert-ToFtpPath $RelativePath
    if ($DryRun) {
        Write-Host "UPLOAD $RelativePath -> $uri"
        return
    }

    $request = [System.Net.FtpWebRequest]::Create($uri)
    $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
    $request.Credentials = New-Object System.Net.NetworkCredential($DeployUser, $DeployPassword)
    $request.EnableSsl = [bool]$UseFtps
    $request.UseBinary = $true

    $bytes = [System.IO.File]::ReadAllBytes($LocalPath)
    $request.ContentLength = $bytes.Length
    $stream = $request.GetRequestStream()
    $stream.Write($bytes, 0, $bytes.Length)
    $stream.Close()

    $response = $request.GetResponse()
    $response.Close()
    Write-Host "Uploaded $RelativePath"
}

$files = Get-ChildItem -LiteralPath $projectRoot -Recurse -File | ForEach-Object {
    $relative = Get-RelativePath -Root $projectRoot -FullName $_.FullName
    if (-not (Test-IsExcluded $relative)) {
        [PSCustomObject]@{
            FullName = $_.FullName
            Relative = $relative
        }
    }
}

foreach ($file in $files) {
    Send-File -LocalPath $file.FullName -RelativePath $file.Relative
}

Write-Host "Deploy finished."