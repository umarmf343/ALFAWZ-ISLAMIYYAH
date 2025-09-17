# AlFawz Qur'an Institute — generated with TRAE
# Author: Auto-scaffold (review required)
# Complete deployment script for AlFawz Qur'an Institute

param(
    [switch]$Fresh = $false,
    [switch]$Seed = $false,
    [switch]$SkipWeb = $false,
    [switch]$SkipMigrations = $false,
    [switch]$Verbose = $false,
    [switch]$Production = $false
)

# Set error action preference
$ErrorActionPreference = "Stop"

# Function to write colored output
function Write-ColorOutput {
    param(
        [string]$Message,
        [string]$Color = "White"
    )
    Write-Host $Message -ForegroundColor $Color
}

# Function to check if command exists
function Test-Command {
    param([string]$Command)
    try {
        Get-Command $Command -ErrorAction Stop | Out-Null
        return $true
    } catch {
        return $false
    }
}

try {
    Write-ColorOutput "🚀 AlFawz Qur'an Institute - Complete Deployment" "Cyan"
    Write-ColorOutput "=" * 60 "Gray"
    
    $startTime = Get-Date
    
    # Check prerequisites
    Write-ColorOutput "📋 Checking prerequisites..." "Yellow"
    
    $missingTools = @()
    
    if (-not (Test-Command "php")) {
        $missingTools += "PHP 8.2+"
    }
    
    if (-not (Test-Command "composer")) {
        $missingTools += "Composer"
    }
    
    if (-not (Test-Command "node")) {
        $missingTools += "Node.js 20+"
    }
    
    if (-not (Test-Command "npm")) {
        $missingTools += "npm"
    }
    
    if ($missingTools.Count -gt 0) {
        throw "Missing required tools: $($missingTools -join ', '). Please install them and try again."
    }
    
    # Get versions
    $phpVersion = (php --version | Select-Object -First 1).Split(' ')[1]
    $nodeVersion = node --version
    $composerVersion = (composer --version | Select-Object -First 1).Split(' ')[2]
    
    Write-ColorOutput "✅ PHP version: $phpVersion" "Green"
    Write-ColorOutput "✅ Node.js version: $nodeVersion" "Green"
    Write-ColorOutput "✅ Composer version: $composerVersion" "Green"
    
    # Navigate to project root
    $projectRoot = Split-Path $PSScriptRoot -Parent
    Set-Location $projectRoot
    
    Write-ColorOutput "📁 Project root: $projectRoot" "Yellow"
    
    # Step 1: Install Laravel dependencies
    Write-ColorOutput "📦 Installing Laravel API dependencies..." "Yellow"
    Set-Location "apps\api"
    
    $composerArgs = @("install")
    if ($Production) {
        $composerArgs += "--no-dev", "--optimize-autoloader"
    }
    if (-not $Verbose) {
        $composerArgs += "--quiet"
    }
    
    $composerProcess = Start-Process -FilePath "composer" -ArgumentList $composerArgs -Wait -PassThru -NoNewWindow
    if ($composerProcess.ExitCode -ne 0) {
        throw "Composer install failed with exit code $($composerProcess.ExitCode)"
    }
    Write-ColorOutput "✅ Laravel dependencies installed" "Green"
    
    # Generate application key if needed
    if (-not (Test-Path ".env")) {
        if (Test-Path ".env.example") {
            Copy-Item ".env.example" ".env"
            Write-ColorOutput "📄 Created .env from .env.example" "Yellow"
        }
    }
    
    # Generate app key if not set
    $envContent = Get-Content ".env" -Raw
    if ($envContent -match "APP_KEY=\s*$") {
        Write-ColorOutput "🔑 Generating application key..." "Yellow"
        $keyProcess = Start-Process -FilePath "php" -ArgumentList @("artisan", "key:generate", "--force") -Wait -PassThru -NoNewWindow
        if ($keyProcess.ExitCode -eq 0) {
            Write-ColorOutput "✅ Application key generated" "Green"
        }
    }
    
    # Step 2: Run database migrations
    if (-not $SkipMigrations) {
        Write-ColorOutput "🗄️  Running database migrations..." "Yellow"
        
        $migrateArgs = @("artisan")
        if ($Fresh) {
            $migrateArgs += "migrate:fresh"
            if ($Seed) {
                $migrateArgs += "--seed"
            }
        } else {
            $migrateArgs += "migrate"
        }
        
        if ($Production) {
            $migrateArgs += "--force"
        }
        
        $migrateProcess = Start-Process -FilePath "php" -ArgumentList $migrateArgs -Wait -PassThru -NoNewWindow
        if ($migrateProcess.ExitCode -eq 0) {
            Write-ColorOutput "✅ Database migrations completed" "Green"
        } else {
            Write-ColorOutput "⚠️  Database migrations failed, but continuing..." "Yellow"
        }
        
        # Run seeders separately if not fresh and seed is requested
        if (-not $Fresh -and $Seed) {
            Write-ColorOutput "🌱 Running database seeders..." "Yellow"
            $seedArgs = @("artisan", "db:seed")
            if ($Production) {
                $seedArgs += "--force"
            }
            
            $seedProcess = Start-Process -FilePath "php" -ArgumentList $seedArgs -Wait -PassThru -NoNewWindow
            if ($seedProcess.ExitCode -eq 0) {
                Write-ColorOutput "✅ Database seeding completed" "Green"
            } else {
                Write-ColorOutput "⚠️  Database seeding failed, but continuing..." "Yellow"
            }
        }
    } else {
        Write-ColorOutput "⏭️  Skipping database migrations" "Gray"
    }
    
    # Step 3: Clear and cache Laravel configurations
    Write-ColorOutput "🔧 Optimizing Laravel configuration..." "Yellow"
    
    $optimizeCommands = @(
        @("artisan", "config:clear"),
        @("artisan", "route:clear"),
        @("artisan", "view:clear")
    )
    
    if ($Production) {
        $optimizeCommands += @(
            @("artisan", "config:cache"),
            @("artisan", "route:cache"),
            @("artisan", "view:cache")
        )
    }
    
    foreach ($cmd in $optimizeCommands) {
        $process = Start-Process -FilePath "php" -ArgumentList $cmd -Wait -PassThru -NoNewWindow -RedirectStandardOutput "nul" -RedirectStandardError "nul"
    }
    Write-ColorOutput "✅ Laravel optimization completed" "Green"
    
    # Step 4: Build and deploy web application
    if (-not $SkipWeb) {
        Set-Location $projectRoot
        Write-ColorOutput "🌐 Building Next.js web application..." "Yellow"
        
        Set-Location "apps\web"
        
        # Install web dependencies
        $webInstallArgs = @("install")
        if (-not $Verbose) {
            $webInstallArgs += "--silent"
        }
        
        $webInstallProcess = Start-Process -FilePath "npm" -ArgumentList $webInstallArgs -Wait -PassThru -NoNewWindow
        if ($webInstallProcess.ExitCode -ne 0) {
            throw "Web dependencies installation failed with exit code $($webInstallProcess.ExitCode)"
        }
        Write-ColorOutput "✅ Web dependencies installed" "Green"
        
        # Build web application
        $buildProcess = Start-Process -FilePath "npm" -ArgumentList @("run", "build") -Wait -PassThru -NoNewWindow
        if ($buildProcess.ExitCode -ne 0) {
            throw "Web build failed with exit code $($buildProcess.ExitCode)"
        }
        Write-ColorOutput "✅ Web application built" "Green"
        
        # Export static files
        $exportProcess = Start-Process -FilePath "npm" -ArgumentList @("run", "export") -Wait -PassThru -NoNewWindow
        if ($exportProcess.ExitCode -ne 0) {
            throw "Web export failed with exit code $($exportProcess.ExitCode)"
        }
        Write-ColorOutput "✅ Web application exported" "Green"
        
        # Copy to Laravel public directory
        $outDir = Join-Path (Get-Location) "out"
        $publicAppDir = Join-Path $projectRoot "apps\api\public\app"
        
        if (-not (Test-Path $publicAppDir)) {
            New-Item -ItemType Directory -Path $publicAppDir -Force | Out-Null
        }
        
        $robocopyArgs = @(
            $outDir,
            $publicAppDir,
            "/MIR",
            "/R:3",
            "/W:1",
            "/NP",
            "/NFL",
            "/NDL"
        )
        
        $robocopyProcess = Start-Process -FilePath "robocopy" -ArgumentList $robocopyArgs -Wait -PassThru -NoNewWindow
        if ($robocopyProcess.ExitCode -gt 7) {
            throw "File copy failed with robocopy exit code $($robocopyProcess.ExitCode)"
        }
        
        Write-ColorOutput "✅ Web files copied to Laravel public directory" "Green"
    } else {
        Write-ColorOutput "⏭️  Skipping web application build" "Gray"
    }
    
    # Step 5: Set proper permissions (if on Unix-like system or WSL)
    Set-Location $projectRoot
    $apiDir = "apps\api"
    
    Write-ColorOutput "🔒 Setting up storage permissions..." "Yellow"
    
    # Create storage directories if they don't exist
    $storageDirs = @(
        "$apiDir\storage\logs",
        "$apiDir\storage\framework\cache",
        "$apiDir\storage\framework\sessions",
        "$apiDir\storage\framework\views",
        "$apiDir\bootstrap\cache"
    )
    
    foreach ($dir in $storageDirs) {
        if (-not (Test-Path $dir)) {
            New-Item -ItemType Directory -Path $dir -Force | Out-Null
        }
    }
    
    Write-ColorOutput "✅ Storage directories created" "Green"
    
    # Step 6: Health check
    Write-ColorOutput "🏥 Running health checks..." "Yellow"
    
    Set-Location "$apiDir"
    
    # Test artisan command
    $artisanTest = Start-Process -FilePath "php" -ArgumentList @("artisan", "--version") -Wait -PassThru -NoNewWindow -RedirectStandardOutput "nul" -RedirectStandardError "nul"
    if ($artisanTest.ExitCode -eq 0) {
        Write-ColorOutput "✅ Laravel artisan is working" "Green"
    } else {
        Write-ColorOutput "⚠️  Laravel artisan test failed" "Yellow"
    }
    
    # Test database connection
    $dbTest = Start-Process -FilePath "php" -ArgumentList @("artisan", "migrate:status") -Wait -PassThru -NoNewWindow -RedirectStandardOutput "nul" -RedirectStandardError "nul"
    if ($dbTest.ExitCode -eq 0) {
        Write-ColorOutput "✅ Database connection is working" "Green"
    } else {
        Write-ColorOutput "⚠️  Database connection test failed" "Yellow"
    }
    
    # Calculate deployment time
    $endTime = Get-Date
    $deploymentTime = [math]::Round(($endTime - $startTime).TotalSeconds, 2)
    
    # Final summary
    Set-Location $projectRoot
    
    Write-ColorOutput "=" * 60 "Gray"
    Write-ColorOutput "🎉 Deployment completed successfully!" "Green"
    Write-ColorOutput "⏱️  Total deployment time: ${deploymentTime} seconds" "Cyan"
    Write-ColorOutput "" "White"
    Write-ColorOutput "📋 Deployment Summary:" "Cyan"
    Write-ColorOutput "   • Laravel API: apps/api (configured and migrated)" "White"
    if (-not $SkipWeb) {
        Write-ColorOutput "   • Next.js Web: apps/api/public/app (static export)" "White"
    }
    Write-ColorOutput "   • Scripts: scripts/ (PowerShell automation)" "White"
    Write-ColorOutput "" "White"
    Write-ColorOutput "🌐 Access URLs:" "Cyan"
    Write-ColorOutput "   • API Health: http://yourdomain.com/api/health" "White"
    if (-not $SkipWeb) {
        Write-ColorOutput "   • Web App: http://yourdomain.com/app/" "White"
    }
    Write-ColorOutput "" "White"
    Write-ColorOutput "📝 Next Steps:" "Cyan"
    Write-ColorOutput "   1. Configure your web server (Apache/Nginx)" "White"
    Write-ColorOutput "   2. Set up SSL certificates" "White"
    Write-ColorOutput "   3. Configure cPanel cron jobs for Laravel scheduler" "White"
    Write-ColorOutput "   4. Test all API endpoints and web functionality" "White"
    Write-ColorOutput "=" * 60 "Gray"
    
} catch {
    Write-ColorOutput "❌ Deployment failed: $($_.Exception.Message)" "Red"
    
    # Return to original directory on error
    Set-Location $PSScriptRoot
    
    exit 1
}