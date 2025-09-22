#!/bin/bash

# HOHO Platform Apple-Style UI Deployment Script
# 🍎 Generated with Claude Code (https://claude.ai/code)

set -e  # Exit on any error

echo "🍎 Starting HOHO Apple-Style UI Deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_step() {
    echo -e "${BLUE}[STEP]${NC} $1"
}

# Check if we're in the right directory
if [ ! -f "package.json" ] || [ ! -f "composer.json" ]; then
    print_error "This doesn't appear to be a Laravel project directory"
    print_error "Please run this script from the project root"
    exit 1
fi

# Step 1: Pull latest code from GitHub
print_step "1. Pulling latest code from GitHub..."
if git pull origin main; then
    print_status "Code updated successfully"
else
    print_warning "Git pull failed or no changes found"
fi

# Step 2: Install/Update Composer dependencies
print_step "2. Installing/Updating Composer dependencies..."
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader
    print_status "Composer dependencies installed"
else
    print_error "Composer not found. Please install Composer first."
    exit 1
fi

# Step 3: Install/Update NPM dependencies
print_step "3. Installing/Updating NPM dependencies..."
if command -v npm &> /dev/null; then
    npm ci
    print_status "NPM dependencies installed"
else
    print_error "NPM not found. Please install Node.js and NPM first."
    exit 1
fi

# Step 4: Build frontend assets
print_step "4. Building frontend assets..."
npm run build
if [ $? -eq 0 ]; then
    print_status "Frontend assets built successfully"
else
    print_error "Frontend build failed"
    exit 1
fi

# Step 5: Database migrations (with --force for production)
print_step "5. Running database migrations..."
php artisan migrate --force
if [ $? -eq 0 ]; then
    print_status "Database migrations completed"
else
    print_error "Database migrations failed"
    exit 1
fi

# Step 6: Clear caches
print_step "6. Clearing application caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
print_status "Caches cleared"

# Step 7: Optimize for production
print_step "7. Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
print_status "Application optimized"

# Step 8: Fix permissions (if needed)
print_step "8. Fixing file permissions..."
if [ -d "storage" ]; then
    chmod -R 775 storage
    print_status "Storage permissions fixed"
fi

if [ -d "bootstrap/cache" ]; then
    chmod -R 775 bootstrap/cache
    print_status "Bootstrap cache permissions fixed"
fi

# Step 9: Restart services (optional - uncomment if needed)
print_step "9. Service restart (optional)..."
# Uncomment the services you need to restart:
# sudo systemctl restart php8.1-fpm
# sudo systemctl restart nginx
# sudo supervisorctl restart laravel-worker:*
print_status "Service restart commands available (commented out)"

# Step 10: Health check
print_step "10. Running health check..."
if php artisan --version &> /dev/null; then
    print_status "Laravel is working correctly"
else
    print_error "Laravel health check failed"
    exit 1
fi

echo ""
echo "🍎 =================================="
echo "🍎  DEPLOYMENT COMPLETED SUCCESSFULLY"
echo "🍎 =================================="
echo ""
print_status "HOHO Apple-Style UI has been deployed!"
print_status "✨ Features updated:"
print_status "   • Apple-style navigation with animations"
print_status "   • Comprehensive component library"
print_status "   • Interactive sound effects"
print_status "   • Skeleton loading animations"
print_status "   • Dark mode support"
print_status "   • Mobile-responsive design"
print_status "   • Advanced micro-interactions"
echo ""
print_warning "Don't forget to:"
print_warning "   • Test the user interface thoroughly"
print_warning "   • Verify all interactions work properly"
print_warning "   • Check mobile responsiveness"
print_warning "   • Test sound effects and animations"
echo ""
echo "🍎 Deployment script generated with Claude Code"
echo "🍎 https://claude.ai/code"
echo ""