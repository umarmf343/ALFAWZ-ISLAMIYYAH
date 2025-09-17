# scripts/schedule-run.ps1
param()
cd apps/api
php artisan schedule:run