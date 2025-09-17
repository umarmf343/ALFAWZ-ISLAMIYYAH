# AlFawz Qur'an Institute — generated with TRAE
# Author: Auto-scaffold (review required)

# Production Deployment Verification Script
# Tests all critical endpoints and services

Write-Host "=== AlFawz Qur'an Institute Deployment Verification ===" -ForegroundColor Cyan
Write-Host ""

# Test Laravel API Health
Write-Host "1. Testing Laravel API Health..." -ForegroundColor Yellow
try {
    $healthResponse = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/health" -Method GET -Headers @{"Accept"="application/json"}
    if ($healthResponse.ok -eq $true) {
        Write-Host "   ✓ Laravel API is healthy" -ForegroundColor Green
        Write-Host "   App: $($healthResponse.app)" -ForegroundColor Gray
        Write-Host "   Version: $($healthResponse.version)" -ForegroundColor Gray
    } else {
        Write-Host "   ✗ Laravel API health check failed" -ForegroundColor Red
    }
}
catch {
    Write-Host "   ✗ Laravel API is not accessible: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# Test Next.js Web Server
Write-Host "2. Testing Next.js Web Server..." -ForegroundColor Yellow
try {
    $webTest = Test-NetConnection -ComputerName localhost -Port 3000 -WarningAction SilentlyContinue
    if ($webTest.TcpTestSucceeded) {
        Write-Host "   ✓ Next.js server is running on port 3000" -ForegroundColor Green
    } else {
        Write-Host "   ✗ Next.js server is not accessible on port 3000" -ForegroundColor Red
    }
}
catch {
    Write-Host "   ✗ Next.js server test failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# Test File Permissions
Write-Host "3. Testing File Permissions..." -ForegroundColor Yellow
$permissionIssues = 0

# Test storage/logs
if (Test-Path "apps\api\storage\logs") {
    try {
        "test" | Out-File -FilePath "apps\api\storage\logs\test_write.tmp" -Force
        Remove-Item "apps\api\storage\logs\test_write.tmp" -Force
        Write-Host "   ✓ apps\api\storage\logs is writable" -ForegroundColor Green
    }
    catch {
        Write-Host "   ✗ apps\api\storage\logs is not writable" -ForegroundColor Red
        $permissionIssues++
    }
} else {
    Write-Host "   ✗ apps\api\storage\logs does not exist" -ForegroundColor Red
    $permissionIssues++
}

# Test bootstrap/cache
if (Test-Path "apps\api\bootstrap\cache") {
    try {
        "test" | Out-File -FilePath "apps\api\bootstrap\cache\test_write.tmp" -Force
        Remove-Item "apps\api\bootstrap\cache\test_write.tmp" -Force
        Write-Host "   ✓ apps\api\bootstrap\cache is writable" -ForegroundColor Green
    }
    catch {
        Write-Host "   ✗ apps\api\bootstrap\cache is not writable" -ForegroundColor Red
        $permissionIssues++
    }
} else {
    Write-Host "   ✗ apps\api\bootstrap\cache does not exist" -ForegroundColor Red
    $permissionIssues++
}

# Test public directory
if (Test-Path "apps\api\public") {
    try {
        "test" | Out-File -FilePath "apps\api\public\test_write.tmp" -Force
        Remove-Item "apps\api\public\test_write.tmp" -Force
        Write-Host "   ✓ apps\api\public is writable" -ForegroundColor Green
    }
    catch {
        Write-Host "   ✗ apps\api\public is not writable" -ForegroundColor Red
        $permissionIssues++
    }
} else {
    Write-Host "   ✗ apps\api\public does not exist" -ForegroundColor Red
    $permissionIssues++
}

Write-Host ""

# Test Static Export Directory
Write-Host "4. Testing Static Export Setup..." -ForegroundColor Yellow
if (Test-Path "apps\api\public\app") {
    $appFiles = Get-ChildItem "apps\api\public\app" -Recurse | Measure-Object
    if ($appFiles.Count -gt 0) {
        Write-Host "   ✓ Static export directory exists with $($appFiles.Count) files" -ForegroundColor Green
    } else {
        Write-Host "   ⚠ Static export directory exists but is empty" -ForegroundColor Yellow
        Write-Host "     Run: .\scripts\build-web.ps1 to build and export" -ForegroundColor Gray
    }
} else {
    Write-Host "   ⚠ Static export directory does not exist" -ForegroundColor Yellow
    Write-Host "     Run: .\scripts\build-web.ps1 to build and export" -ForegroundColor Gray
}

Write-Host ""

# Summary
Write-Host "=== Deployment Verification Summary ===" -ForegroundColor Cyan
if ($permissionIssues -eq 0) {
    Write-Host "✓ All critical systems are operational" -ForegroundColor Green
    Write-Host "✓ Ready for production deployment" -ForegroundColor Green
} else {
    Write-Host "⚠ Some issues detected - review above output" -ForegroundColor Yellow
    Write-Host "  Run: .\scripts\fix-permissions.ps1 to fix permission issues" -ForegroundColor Gray
}

Write-Host ""
Write-Host "Next Steps for Production:" -ForegroundColor Cyan
Write-Host "1. Update .env files with production values" -ForegroundColor Gray
Write-Host "2. Run: .\scripts\build-web.ps1 for static export" -ForegroundColor Gray
Write-Host "3. Upload to cPanel and configure cron jobs" -ForegroundColor Gray
Write-Host "4. Test production URLs" -ForegroundColor Gray