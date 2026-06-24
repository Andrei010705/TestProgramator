param(
    [switch]$SkipMigrate,
    [switch]$SkipSeed,
    [switch]$SkipDatabase
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

function Find-LaragonMySqlBinPath {
    $roots = @(
        "C:\laragon\bin\mysql",
        "C:\laragon\bin\mariadb"
    )

    foreach ($root in $roots) {
        if (-not (Test-Path $root)) {
            continue
        }

        $binPath = Get-ChildItem $root -Directory |
            Sort-Object Name -Descending |
            ForEach-Object { Join-Path $_.FullName "bin" } |
            Where-Object { Test-Path (Join-Path $_ "mysql.exe") } |
            Select-Object -First 1

        if ($binPath) {
            return $binPath
        }
    }

    return $null
}

function Enable-PhpExtensions {
    param(
        [string]$PhpPath
    )

    if (-not $PhpPath -or -not (Test-Path $PhpPath)) {
        return
    }

    $phpIni = Join-Path $PhpPath "php.ini"
    $phpIniDevelopment = Join-Path $PhpPath "php.ini-development"
    $phpIniProduction = Join-Path $PhpPath "php.ini-production"
    $extPath = Join-Path $PhpPath "ext"

    if (-not (Test-Path $phpIni)) {
        if (Test-Path $phpIniDevelopment) {
            Copy-Item $phpIniDevelopment $phpIni
            Write-Host "Created php.ini from php.ini-development." -ForegroundColor Green
        } elseif (Test-Path $phpIniProduction) {
            Copy-Item $phpIniProduction $phpIni
            Write-Host "Created php.ini from php.ini-production." -ForegroundColor Green
        } else {
            Write-Host "Could not find a php.ini template in $PhpPath." -ForegroundColor Yellow
            return
        }
    }

    $extensions = @(
        "openssl",
        "curl",
        "fileinfo",
        "mbstring",
        "mysqli",
        "pdo_mysql",
        "zip"
    )

    $availableExtensions = $extensions | Where-Object {
        Test-Path (Join-Path $extPath "php_$_.dll")
    }

    $lines = Get-Content $phpIni
    $newLines = New-Object System.Collections.Generic.List[string]
    $extensionDirWritten = $false
    $enabledExtensions = @{}

    foreach ($line in $lines) {
        $trimmed = $line.Trim()

        if ($trimmed -match '^;?\s*extension_dir\s*=') {
            if (-not $extensionDirWritten) {
                $newLines.Add("extension_dir = `"$extPath`"")
                $extensionDirWritten = $true
            }

            continue
        }

        $matchedManagedExtension = $false

        foreach ($extension in $availableExtensions) {
            if ($trimmed -match "^;?\s*extension\s*=\s*(php_)?$([regex]::Escape($extension))(\.dll)?\s*$") {
                if (-not $enabledExtensions.ContainsKey($extension)) {
                    $newLines.Add("extension=$extension")
                    $enabledExtensions[$extension] = $true
                }

                $matchedManagedExtension = $true
                break
            }
        }

        if (-not $matchedManagedExtension) {
            $newLines.Add($line)
        }
    }

    if (-not $extensionDirWritten) {
        $newLines.Add("extension_dir = `"$extPath`"")
    }

    foreach ($extension in $availableExtensions) {
        if (-not $enabledExtensions.ContainsKey($extension)) {
            $newLines.Add("extension=$extension")
        }
    }

    Set-Content -Path $phpIni -Value $newLines -Encoding ASCII
    Write-Host "Enabled PHP extensions in $phpIni." -ForegroundColor Green
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

function Install-ComposerIfMissing {
    $localComposerPath = Join-Path (Get-Location) ".tools\composer"
    $localComposerPhar = Join-Path $localComposerPath "composer.phar"

    if ((Test-Path $localComposerPhar) -or (Get-Command "composer" -ErrorAction SilentlyContinue)) {
        if (Test-Path $localComposerPath) {
            $env:Path = "$localComposerPath;$env:Path"
        }
        return
    }

    $composerPaths = @(
        $localComposerPath,
        "C:\ProgramData\ComposerSetup\bin",
        "$env:APPDATA\Composer\vendor\bin",
        "$env:LOCALAPPDATA\Programs\Composer\bin"
    )

    Add-PathForCommand "composer.bat" $composerPaths | Out-Null

    if (Get-Command "composer" -ErrorAction SilentlyContinue) {
        return
    }

    if (-not (Get-Command "winget" -ErrorAction SilentlyContinue)) {
        Write-Host "Composer is missing and winget is not available. Falling back to local Composer installer..." -ForegroundColor Yellow
        Install-LocalComposer $localComposerPath
        return
    }

    Write-Host "Composer is missing. Installing Composer with winget..." -ForegroundColor Cyan
    winget install --id Composer.Composer --exact --accept-package-agreements --accept-source-agreements

    if ($LASTEXITCODE -ne 0) {
        Write-Host "winget could not install Composer. Falling back to local Composer installer..." -ForegroundColor Yellow
        Install-LocalComposer $localComposerPath
    }

    Add-PathForCommand "composer.bat" $composerPaths | Out-Null

    if (-not (Get-Command "composer" -ErrorAction SilentlyContinue)) {
        Write-Host ""
        Write-Host "Composer was installed, but this PowerShell session cannot see it yet." -ForegroundColor Yellow
        Write-Host "Close and reopen PowerShell, then run .\setup-windows.ps1 again." -ForegroundColor Yellow
        exit 1
    }
}

function Install-LocalComposer {
    param(
        [string]$InstallPath
    )

    if (-not (Get-Command "php" -ErrorAction SilentlyContinue)) {
        Write-Host ""
        Write-Host "Cannot install Composer locally because php is missing." -ForegroundColor Red
        exit 1
    }

    New-Item -ItemType Directory -Force -Path $InstallPath | Out-Null

    $installerPath = Join-Path $env:TEMP "composer-setup.php"
    $composerPharPath = Join-Path $InstallPath "composer.phar"
    $composerBatPath = Join-Path $InstallPath "composer.bat"

    Write-Host "Downloading Composer installer..." -ForegroundColor Cyan
    Invoke-WebRequest -Uri "https://getcomposer.org/installer" -OutFile $installerPath

    Write-Host "Installing Composer locally into $InstallPath..." -ForegroundColor Cyan
    php $installerPath --install-dir=$InstallPath --filename=composer.phar

    if (-not (Test-Path $composerPharPath)) {
        Write-Host "Local Composer installation failed: composer.phar was not created." -ForegroundColor Red
        exit 1
    }

    Set-Content -Path $composerBatPath -Value '@php "%~dp0composer.phar" %*' -Encoding ASCII
    $env:Path = "$InstallPath;$env:Path"

    Write-Host "Local Composer is ready at $composerBatPath" -ForegroundColor Green
}

function Invoke-Composer {
    param(
        [Parameter(ValueFromRemainingArguments = $true)]
        [string[]]$ComposerArgs
    )

    $localComposerPhar = Join-Path (Get-Location) ".tools\composer\composer.phar"

    if (Test-Path $localComposerPhar) {
        & php $localComposerPhar @ComposerArgs | ForEach-Object { Write-Host $_ }
        $exitCode = $LASTEXITCODE
        return $exitCode
    }

    & composer @ComposerArgs | ForEach-Object { Write-Host $_ }
    $exitCode = $LASTEXITCODE
    return $exitCode
}

function Test-TcpPort {
    param(
        [string]$HostName,
        [string]$Port
    )

    try {
        $client = New-Object System.Net.Sockets.TcpClient
        $asyncResult = $client.BeginConnect($HostName, [int]$Port, $null, $null)
        $success = $asyncResult.AsyncWaitHandle.WaitOne(2000, $false)

        if ($success) {
            $client.EndConnect($asyncResult)
            $client.Close()
            return $true
        }

        $client.Close()
        return $false
    } catch {
        return $false
    }
}

function Get-DotEnvValue {
    param(
        [string]$Name,
        [string]$DefaultValue = ""
    )

    if (-not (Test-Path ".env")) {
        return $DefaultValue
    }

    $line = Get-Content ".env" |
        Where-Object { $_ -match "^\s*$([regex]::Escape($Name))\s*=" } |
        Select-Object -First 1

    if (-not $line) {
        return $DefaultValue
    }

    $value = ($line -split "=", 2)[1].Trim()

    if (($value.StartsWith('"') -and $value.EndsWith('"')) -or ($value.StartsWith("'") -and $value.EndsWith("'"))) {
        $value = $value.Substring(1, $value.Length - 2)
    }

    return $value
}

function Invoke-MySql {
    param(
        [string]$HostName,
        [string]$Port,
        [string]$UserName,
        [string]$Password,
        [string]$Sql
    )

    $args = @(
        "--host=$HostName",
        "--port=$Port",
        "--user=$UserName",
        "--execute=$Sql"
    )

    if ($Password) {
        $args += "--password=$Password"
    }

    $output = & mysql @args 2>&1

    return [pscustomobject]@{
        ExitCode = $LASTEXITCODE
        Output = $output
    }
}

function Ensure-Database {
    if ($SkipDatabase) {
        Write-Host "Skipping database creation because -SkipDatabase was passed." -ForegroundColor Yellow
        return
    }

    $dbConnection = Get-DotEnvValue "DB_CONNECTION" "mysql"

    if ($dbConnection -ne "mysql") {
        Write-Host "Skipping database creation because DB_CONNECTION is '$dbConnection'." -ForegroundColor Yellow
        return
    }

    $mysqlBinPath = Find-LaragonMySqlBinPath
    $mysqlPaths = @(
        $mysqlBinPath,
        "C:\xampp\mysql\bin",
        "C:\Program Files\MySQL\MySQL Server 8.0\bin",
        "C:\Program Files\MariaDB 11.4\bin",
        "C:\Program Files\MariaDB 11.3\bin",
        "C:\Program Files\MariaDB 10.11\bin"
    ) | Where-Object { $_ }

    Add-PathForCommand "mysql.exe" $mysqlPaths | Out-Null

    if (-not (Get-Command "mysql" -ErrorAction SilentlyContinue)) {
        Write-Host ""
        Write-Host "mysql.exe was not found." -ForegroundColor Yellow
        Write-Host "Start Laragon and make sure MySQL/MariaDB is installed, then rerun this script." -ForegroundColor Yellow
        Write-Host "You can also create the database manually and rerun with -SkipDatabase." -ForegroundColor Yellow
        exit 1
    }

    $dbHost = Get-DotEnvValue "DB_HOST" "127.0.0.1"
    $dbPort = Get-DotEnvValue "DB_PORT" "3306"
    $dbName = Get-DotEnvValue "DB_DATABASE" "test_programator"
    $dbUser = Get-DotEnvValue "DB_USERNAME" "root"
    $dbPassword = Get-DotEnvValue "DB_PASSWORD" ""

    if ($dbName -notmatch '^[A-Za-z0-9_]+$') {
        Write-Host "DB_DATABASE '$dbName' contains unsupported characters for automatic creation." -ForegroundColor Red
        Write-Host "Use letters, numbers, and underscores, or create the database manually." -ForegroundColor Yellow
        exit 1
    }

    $mysqlProcesses = Get-Process -ErrorAction SilentlyContinue |
        Where-Object { $_.ProcessName -match 'mysql|maria' } |
        Select-Object ProcessName,Id,Path

    if ($mysqlProcesses) {
        Write-Host "Detected MySQL/MariaDB process:" -ForegroundColor Green
        $mysqlProcesses | Format-Table -AutoSize | Out-String | Write-Host
    } else {
        Write-Host "No mysqld process detected yet." -ForegroundColor Yellow
    }

    if (-not (Test-TcpPort $dbHost $dbPort)) {
        Write-Host "Port ${dbHost}:${dbPort} is not accepting TCP connections yet. Waiting 5 seconds..." -ForegroundColor Yellow
        Start-Sleep -Seconds 5
    }

    Write-Host "Checking MySQL connection to ${dbHost}:${dbPort} as $dbUser..." -ForegroundColor Cyan
    $effectiveDbHost = $dbHost
    $testResult = Invoke-MySql $dbHost $dbPort $dbUser $dbPassword "SELECT 1;"

    if ($testResult.ExitCode -ne 0 -and $dbHost -eq "127.0.0.1") {
        Write-Host "Retrying MySQL connection with host 'localhost'..." -ForegroundColor Yellow
        $testResult = Invoke-MySql "localhost" $dbPort $dbUser $dbPassword "SELECT 1;"
        if ($testResult.ExitCode -eq 0) {
            $effectiveDbHost = "localhost"
        }
    }

    if ($testResult.ExitCode -ne 0) {
        Write-Host ""
        Write-Host "Could not connect to MySQL." -ForegroundColor Red
        if ($testResult.Output) {
            Write-Host "MySQL output:" -ForegroundColor Yellow
            $testResult.Output | ForEach-Object { Write-Host $_ }
        }
        Write-Host "Open Laragon, click Start All, and confirm MySQL/MariaDB is running." -ForegroundColor Yellow
        Write-Host "If your MySQL user has a password, set DB_PASSWORD in .env and rerun." -ForegroundColor Yellow
        exit 1
    }

    Write-Host "Creating database '$dbName' if it does not exist..." -ForegroundColor Cyan
    $createSql = "CREATE DATABASE IF NOT EXISTS $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    $createResult = Invoke-MySql $effectiveDbHost $dbPort $dbUser $dbPassword $createSql

    if ($createResult.ExitCode -ne 0) {
        Write-Host "Database creation failed." -ForegroundColor Red
        if ($createResult.Output) {
            $createResult.Output | ForEach-Object { Write-Host $_ }
        }
        exit 1
    }

    Write-Host "Database '$dbName' is ready." -ForegroundColor Green
}

function Assert-LaravelProject {
    if (-not (Test-Path "artisan") -or -not (Test-Path "composer.json")) {
        Write-Host ""
        Write-Host "This folder does not look like a Laravel project scaffold." -ForegroundColor Red
        Write-Host "Run this script from C:\Users\artio\Desktop\TestProgramator." -ForegroundColor Yellow
        exit 1
    }
}

Write-Host "Checking Windows Laravel prerequisites..." -ForegroundColor Cyan

Assert-LaravelProject

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

if ($laragonPhpPath) {
    Enable-PhpExtensions $laragonPhpPath
}

Install-ComposerIfMissing

Write-Host "PHP:" -ForegroundColor Green
php -v | Select-Object -First 1

Write-Host "Composer:" -ForegroundColor Green
$composerVersionExitCode = Invoke-Composer --version
if ($composerVersionExitCode -ne 0) {
    Write-Host "Composer version check returned exit code $composerVersionExitCode, but setup will continue to composer install." -ForegroundColor Yellow
}

if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Host "Created .env from .env.example" -ForegroundColor Green
} else {
    Write-Host ".env already exists; leaving it unchanged." -ForegroundColor Yellow
}

Ensure-Database

Write-Host "Installing Composer dependencies..." -ForegroundColor Cyan
$composerInstallExitCode = Invoke-Composer install
if ($composerInstallExitCode -ne 0) {
    Write-Host "Composer install failed." -ForegroundColor Red
    exit 1
}

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
