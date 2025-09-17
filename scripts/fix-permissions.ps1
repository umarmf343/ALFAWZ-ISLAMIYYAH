# AlFawz Qur'an Institute — generated with TRAE
# Author: Auto-scaffold (review required)

# Fix Laravel File Permissions for Windows
# This script ensures proper permissions for Laravel directories

param(
    [string]$LaravelPath = "apps\api"
)

Write-Host "Fixing Laravel file permissions..." -ForegroundColor Cyan

# Change to Laravel directory
Set-Location $LaravelPath

# Directories that need write permissions
$WritableDirs = @(
    "storage",
    "storage\app",
    "storage\app\public",
    "storage\framework",
    "storage\framework\cache",
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\logs",
    "bootstrap\cache",
    "public"
)

# Ensure directories exist and are writable
foreach ($dir in $WritableDirs) {
    if (!(Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Host "Created directory: $dir" -ForegroundColor Green
    }
    
    # Set full control for current user (Windows equivalent of 755/775)
    try {
        $acl = Get-Acl $dir
        $accessRule = New-Object System.Security.AccessControl.FileSystemAccessRule(
            [System.Security.Principal.WindowsIdentity]::GetCurrent().Name,
            "FullControl",
            "ContainerInherit,ObjectInherit",
            "None",
            "Allow"
        )
        $acl.SetAccessRule($accessRule)
        Set-Acl -Path $dir -AclObject $acl
        Write-Host "Set permissions for: $dir" -ForegroundColor Green
    }
    catch {
        Write-Warning "Could not set permissions for: $dir - $($_.Exception.Message)"
    }
}

# Clear Laravel caches
Write-Host "Clearing Laravel caches..." -ForegroundColor Yellow
try {
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear
    Write-Host "Caches cleared successfully" -ForegroundColor Green
}
catch {
    Write-Warning "Could not clear caches - ensure Laravel is properly installed"
}

# Set proper permissions for key files
$KeyFiles = @(
    "artisan",
    "public\index.php",
    "public\.htaccess"
)

foreach ($file in $KeyFiles) {
    if (Test-Path $file) {
        try {
            $acl = Get-Acl $file
            $accessRule = New-Object System.Security.AccessControl.FileSystemAccessRule(
                [System.Security.Principal.WindowsIdentity]::GetCurrent().Name,
                "FullControl",
                "Allow"
            )
            $acl.SetAccessRule($accessRule)
            Set-Acl -Path $file -AclObject $acl
            Write-Host "Set permissions for file: $file" -ForegroundColor Green
        }
        catch {
            Write-Warning "Could not set permissions for file: $file - $($_.Exception.Message)"
        }
    }
}

Write-Host "File permissions setup completed!" -ForegroundColor Green
Write-Host "Note: On production servers, ensure web server user has appropriate permissions" -ForegroundColor Yellow

# Return to original directory
Set-Location ..\..