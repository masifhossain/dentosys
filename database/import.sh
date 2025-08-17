#!/bin/bash
#################################################################
# database/import.sh
# -----------------------------------------------------------------
# Quick database import script for DentoSys v2.0
# Usage: ./import.sh [database_name] [mysql_user] [mysql_password]
#################################################################

# Default values
DB_NAME=${1:-"dentosys_db"}
DB_USER=${2:-"root"}
DB_PASS=${3:-"Nostalgia%#512"}
DB_HOST=${4:-"localhost"}

echo "🔧 DentoSys v2.0 Database Import"
echo "================================="
echo "Database: $DB_NAME"
echo "User: $DB_USER"
echo "Host: $DB_HOST"
echo ""

# Check if MySQL is running
if ! command -v mysql &> /dev/null; then
    echo "❌ MySQL client not found. Please install MySQL client."
    exit 1
fi

# Create database if it doesn't exist
echo "📦 Creating database if it doesn't exist..."
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if [ $? -eq 0 ]; then
    echo "✅ Database created/verified successfully"
else
    echo "❌ Failed to create database"
    exit 1
fi

# Import the SQL file
echo "📋 Importing DentoSys v2.0 database structure and data..."
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME < dentosys_db.sql

if [ $? -eq 0 ]; then
    echo "✅ Database imported successfully!"
    echo ""
    echo "🎉 DentoSys v2.0 Database Ready!"
    echo "================================"
    echo "📊 Tables imported:"
    echo "   • Core Tables: Patients, Appointments, Invoices, etc."
    echo "   • NEW: Prescriptions management"
    echo "   • NEW: Insurance claims processing"  
    echo "   • NEW: Enhanced integrations settings"
    echo ""
    echo "👤 Default Login Credentials:"
    echo "   Email: admin@dentosys.local"
    echo "   Password: password"
    echo ""
    echo "🚀 Ready to start your dental practice management!"
else
    echo "❌ Failed to import database"
    exit 1
fi
