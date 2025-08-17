#################################################################
# database/import.ps1
# -----------------------------------------------------------------
# Quick database import script for DentoSys v2.0 (Windows)
# Usage: .\import.ps1 [-DatabaseName "dentosys_db"] [-User "root"] [-Password "Nostalgia%#512"]
#################################################################

param(
    [string]$DatabaseName = "dentosys_db",
    [string]$User = "root", 
    [string]$Password = "Nostalgia%#512",
    [string]$Host = "localhost"
)

Write-Host "🔧 DentoSys v2.0 Database Import" -ForegroundColor Cyan
Write-Host "=================================" -ForegroundColor Cyan
Write-Host "Database: $DatabaseName"
Write-Host "User: $User"
Write-Host "Host: $Host"
Write-Host ""

# Check if MySQL is available
try {
    $mysqlPath = where.exe mysql 2>$null
    if (-not $mysqlPath) {
        Write-Host "❌ MySQL client not found. Please ensure MySQL/MariaDB is installed and in PATH." -ForegroundColor Red
        exit 1
    }
} catch {
    Write-Host "❌ MySQL client not found. Please ensure MySQL/MariaDB is installed and in PATH." -ForegroundColor Red
    exit 1
}

# Create database if it doesn't exist
Write-Host "📦 Creating database if it doesn't exist..."
$createDbCmd = "CREATE DATABASE IF NOT EXISTS ``$DatabaseName`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
$result = mysql -h $Host -u $User -p$Password -e $createDbCmd 2>&1

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Database created/verified successfully" -ForegroundColor Green
} else {
    Write-Host "❌ Failed to create database: $result" -ForegroundColor Red
    exit 1
}

# Import the SQL file
Write-Host "📋 Importing DentoSys v2.0 database structure and data..."
$importResult = mysql -h $Host -u $User -p$Password $DatabaseName < "dentosys_db.sql" 2>&1

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Database imported successfully!" -ForegroundColor Green
    Write-Host ""
    Write-Host "🎉 DentoSys v2.0 Database Ready!" -ForegroundColor Yellow
    Write-Host "================================" -ForegroundColor Yellow
    Write-Host "📊 Tables imported:" -ForegroundColor White
    Write-Host "   • Core Tables: Patients, Appointments, Invoices, etc." -ForegroundColor Gray
    Write-Host "   • NEW: Prescriptions management" -ForegroundColor Green
    Write-Host "   • NEW: Insurance claims processing" -ForegroundColor Green
    Write-Host "   • NEW: Enhanced integrations settings" -ForegroundColor Green
    Write-Host ""
    Write-Host "👤 Default Login Credentials:" -ForegroundColor White
    Write-Host "   Email: admin@dentosys.local" -ForegroundColor Yellow
    Write-Host "   Password: password" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "🚀 Ready to start your dental practice management!" -ForegroundColor Cyan
} else {
    Write-Host "❌ Failed to import database: $importResult" -ForegroundColor Red
    exit 1
}
