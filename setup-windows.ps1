param(
    [switch]$SkipMigrate,
    [switch]$SkipSeed
)

$ErrorActionPreference = "Stop"

function Add-PathForCommand {
    param(
        [string]$CommandName,
        [string[]]$CandidatePaths
    )

    if (Get-Command $CommandName -ErrorAction SilentlyContinue) {
        return $true
    }

    foreach ($path in $CandidatePaths) {
        if ((Test-Path $path) -and (Test-Path (Join-Path $path $CommandName))) {
            $env:Path = "$path;$env:Path"
            Write-Host "Found $CommandName in $path and added it for this PowerShell session." -ForegroundColor Green
            return $true
        }
    }

    return $false
}

function Find-LaragonPhpPath {
    $root = "C:\laragon\bin\php"

    if (-not (Test-Path $root)) {
        return $null
    }

    return Get-ChildItem $root -Directory |
        Sort-Object Name -Descending |
        ForEach-Object { $_.FullName } |
        Where-Object { Test-Path (Join-Path $_ "php.exe") } |
        Select-Object -First 1
}

function Require-Command {
    param(
        [string]$Name,
        [string]$InstallHint
    )

    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        Write-Host ""
        Write-Host "Missing required command: $Name" -ForegroundColor Red
        Write-Host $InstallHint -ForegroundColor Yellow
        exit 1
    }
}

Write-Host "Checking Windows Laravel prerequisites..." -ForegroundColor Cyan

$laragonPhpPath = Find-LaragonPhpPath
$phpPaths = @(
    $laragonPhpPath,
    "C:\xampp\php",
    "$env:USERPROFILE\.config\herd\bin",
    "$env:USERPROFILE\AppData\Local\Programs\PHP",
    "C:\php"
) | Where-Object { $_ }

Add-PathForCommand "php.exe" $phpPaths | Out-Null

Require-Command "php" "Install PHP 8.2+ with Laragon, XAMPP, Herd, or winget, then add php.exe to PATH."
Require-Command "composer" "Install Composer from https://getcomposer.org/download/ and reopen PowerShell."

Write-Host "PHP:" -ForegroundColor Green
php -v | Select-Object -First 1

Write-Host "Composer:" -ForegroundColor Green
composer --version

if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Host "Created .env from .env.example" -ForegroundColor Green
} else {
    Write-Host ".env already exists; leaving it unchanged." -ForegroundColor Yellow
}

Write-Host "Installing Composer dependencies..." -ForegroundColor Cyan
composer install

Write-Host "Generating Laravel app key..." -ForegroundColor Cyan
php artisan key:generate

if (-not $SkipMigrate) {
    Write-Host "Running migrations..." -ForegroundColor Cyan
    php artisan migrate
}

if (-not $SkipSeed) {
    Write-Host "Running seeders..." -ForegroundColor Cyan
    php artisan db:seed
}

Write-Host ""
Write-Host "Setup complete." -ForegroundColor Green
Write-Host "Run the app with: php artisan serve"
Write-Host "Then open: http://127.0.0.1:8000/products"
