# scripts/build-web.ps1
# Builds Next.js and mirrors /out to Laravel public/app
param()
Write-Host "Building Next.js web..." -ForegroundColor Cyan
cd apps/web
npm install
npm run build
npm run export
cd ../..
if (!(Test-Path apps/api/public/app)) { New-Item -ItemType Directory -Path apps/api/public/app | Out-Null }
robocopy apps\web\out apps\api\public\app /MIR
Write-Host "Done. Web exported to api/public/app" -ForegroundColor Green