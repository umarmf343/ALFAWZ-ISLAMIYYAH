# scripts/migrate-api.ps1
param()
cd apps/api
php artisan migrate --force