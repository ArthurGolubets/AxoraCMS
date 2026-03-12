#!/bin/bash

# Script to refactor HolartCMS to use package models directly
# This script updates all references from App\Models to HolartWeb\HolartCMS\Models

PACKAGE_DIR="/Users/holart/Desktop/Work/HolartCMS/holart-cms/packages/holartweb/holart-cms"
APP_DIR="/Users/holart/Desktop/Work/HolartCMS/holart-cms"

echo "╔════════════════════════════════════════╗"
echo "║  HolartCMS Package Refactoring Tool   ║"
echo "╚════════════════════════════════════════╝"
echo ""

# Step 1: Update all models in package to use correct namespace
echo "Step 1: Updating model namespaces in package..."

# Shop models
find "$PACKAGE_DIR/src/Models/Shop" -name "*.php" -type f | while read file; do
    sed -i '' 's/namespace App\\Models;/namespace HolartWeb\\HolartCMS\\Models\\Shop;/g' "$file"
    echo "✓ Updated $(basename $file)"
done

# Pages models
find "$PACKAGE_DIR/src/Models/Pages" -name "*.php" -type f 2>/dev/null | while read file; do
    sed -i '' 's/namespace App\\Models;/namespace HolartWeb\\HolartCMS\\Models\\Pages;/g' "$file"
    echo "✓ Updated $(basename $file)"
done

# Menus models
find "$PACKAGE_DIR/src/Models/Menus" -name "*.php" -type f 2>/dev/null | while read file; do
    sed -i '' 's/namespace App\\Models;/namespace HolartWeb\\HolartCMS\\Models\\Menus;/g' "$file"
    echo "✓ Updated $(basename $file)"
done

# Callback models
find "$PACKAGE_DIR/src/Models/Callback" -name "*.php" -type f 2>/dev/null | while read file; do
    sed -i '' 's/namespace App\\Models;/namespace HolartWeb\\HolartCMS\\Models\\Callback;/g' "$file"
    echo "✓ Updated $(basename $file)"
done

# InfoBlocks models
find "$PACKAGE_DIR/src/Models/InfoBlocks" -name "*.php" -type f 2>/dev/null | while read file; do
    sed -i '' 's/namespace App\\Models;/namespace HolartWeb\\HolartCMS\\Models\\InfoBlocks;/g' "$file"
    echo "✓ Updated $(basename $file)"
done

echo ""

# Step 2: Update controllers to use package models
echo "Step 2: Updating controllers to use package models..."

find "$PACKAGE_DIR/src/Http/Controllers" -name "*Controller.php" -type f | while read file; do
    # Shop models
    sed -i '' 's/use App\\Models\\TCatalog;/use HolartWeb\\HolartCMS\\Models\\Shop\\TCatalog;/g' "$file"
    sed -i '' 's/use App\\Models\\TProduct;/use HolartWeb\\HolartCMS\\Models\\Shop\\TProduct;/g' "$file"
    sed -i '' 's/use App\\Models\\TProductVariant;/use HolartWeb\\HolartCMS\\Models\\Shop\\TProductVariant;/g' "$file"
    sed -i '' 's/use App\\Models\\TFilter;/use HolartWeb\\HolartCMS\\Models\\Shop\\TFilter;/g' "$file"
    sed -i '' 's/use App\\Models\\TFilterValue;/use HolartWeb\\HolartCMS\\Models\\Shop\\TFilterValue;/g' "$file"

    # Pages models
    sed -i '' 's/use App\\Models\\TPage;/use HolartWeb\\HolartCMS\\Models\\Pages\\TPage;/g' "$file"
    sed -i '' 's/use App\\Models\\TPageBlock;/use HolartWeb\\HolartCMS\\Models\\Pages\\TPageBlock;/g' "$file"
    sed -i '' 's/use App\\Models\\TPageBlockType;/use HolartWeb\\HolartCMS\\Models\\Pages\\TPageBlockType;/g' "$file"

    # Menus models
    sed -i '' 's/use App\\Models\\TMenu;/use HolartWeb\\HolartCMS\\Models\\Menus\\TMenu;/g' "$file"
    sed -i '' 's/use App\\Models\\TMenuItem;/use HolartWeb\\HolartCMS\\Models\\Menus\\TMenuItem;/g' "$file"

    # Callback models
    sed -i '' 's/use App\\Models\\TUsersEmails;/use HolartWeb\\HolartCMS\\Models\\Callback\\TUsersEmails;/g' "$file"
    sed -i '' 's/use App\\Models\\TComments;/use HolartWeb\\HolartCMS\\Models\\Callback\\TComments;/g' "$file"
    sed -i '' 's/use App\\Models\\TUserRequests;/use HolartWeb\\HolartCMS\\Models\\Callback\\TUserRequests;/g' "$file"

    # InfoBlocks models
    sed -i '' 's/use App\\Models\\TInfoBlock;/use HolartWeb\\HolartCMS\\Models\\InfoBlocks\\TInfoBlock;/g' "$file"

    echo "✓ Updated $(basename $file)"
done

echo ""

# Step 3: Update admin routes
echo "Step 3: Updating admin routes..."

if [ -f "$APP_DIR/routes/admin.php" ]; then
    sed -i '' 's/App\\Http\\Controllers\\/HolartWeb\\HolartCMS\\Http\\Controllers\\Shop\\/g' "$APP_DIR/routes/admin.php"
    sed -i '' 's/HolartWeb\\HolartCMS\\Http\\Controllers\\Shop\\PagesController/HolartWeb\\HolartCMS\\Http\\Controllers\\Pages\\PagesController/g' "$APP_DIR/routes/admin.php"
    sed -i '' 's/HolartWeb\\HolartCMS\\Http\\Controllers\\Shop\\PageBlocksController/HolartWeb\\HolartCMS\\Http\\Controllers\\Pages\\PageBlocksController/g' "$APP_DIR/routes/admin.php"
    sed -i '' 's/HolartWeb\\HolartCMS\\Http\\Controllers\\Shop\\PageBlockTypesController/HolartWeb\\HolartCMS\\Http\\Controllers\\Pages\\PageBlockTypesController/g' "$APP_DIR/routes/admin.php"
    sed -i '' 's/HolartWeb\\HolartCMS\\Http\\Controllers\\Shop\\MenusController/HolartWeb\\HolartCMS\\Http\\Controllers\\Menus\\MenusController/g' "$APP_DIR/routes/admin.php"
    sed -i '' 's/HolartWeb\\HolartCMS\\Http\\Controllers\\Shop\\MenuItemsController/HolartWeb\\HolartCMS\\Http\\Controllers\\Menus\\MenuItemsController/g' "$APP_DIR/routes/admin.php"

    echo "✓ Updated admin.php"
fi

echo ""

# Step 4: Delete copied files from app/
echo "Step 4: Deleting copied files from app/..."

# Delete Shop models
rm -f "$APP_DIR/app/Models/TCatalog.php"
rm -f "$APP_DIR/app/Models/TProduct.php"
rm -f "$APP_DIR/app/Models/TProductVariant.php"
rm -f "$APP_DIR/app/Models/TFilter.php"
rm -f "$APP_DIR/app/Models/TFilterValue.php"

# Delete Pages models
rm -f "$APP_DIR/app/Models/TPage.php"
rm -f "$APP_DIR/app/Models/TPageBlock.php"
rm -f "$APP_DIR/app/Models/TPageBlockType.php"

# Delete Menus models
rm -f "$APP_DIR/app/Models/TMenu.php"
rm -f "$APP_DIR/app/Models/TMenuItem.php"

# Delete Callback models
rm -f "$APP_DIR/app/Models/TUsersEmails.php"
rm -f "$APP_DIR/app/Models/TComments.php"
rm -f "$APP_DIR/app/Models/TUserRequests.php"

# Delete InfoBlocks models
rm -f "$APP_DIR/app/Models/TInfoBlock.php"

# Delete controllers
rm -f "$APP_DIR/app/Http/Controllers/CatalogController.php"
rm -f "$APP_DIR/app/Http/Controllers/ProductController.php"
rm -f "$APP_DIR/app/Http/Controllers/FilterController.php"
rm -f "$APP_DIR/app/Http/Controllers/PagesController.php"
rm -f "$APP_DIR/app/Http/Controllers/PageBlocksController.php"
rm -f "$APP_DIR/app/Http/Controllers/PageBlockTypesController.php"
rm -f "$APP_DIR/app/Http/Controllers/MenusController.php"
rm -f "$APP_DIR/app/Http/Controllers/MenuItemsController.php"
rm -f "$APP_DIR/app/Http/Controllers/UsersEmailsController.php"
rm -f "$APP_DIR/app/Http/Controllers/CommentsController.php"
rm -f "$APP_DIR/app/Http/Controllers/UserRequestsController.php"
rm -f "$APP_DIR/app/Http/Controllers/InfoBlocksController.php"

echo "✓ Deleted copied models and controllers"
echo ""

# Step 5: Run composer dump-autoload
echo "Step 5: Updating autoload..."
cd "$APP_DIR"
composer dump-autoload

echo ""
echo "╔════════════════════════════════════════╗"
echo "║    Refactoring Complete!               ║"
echo "╚════════════════════════════════════════╝"
echo ""
echo "Next steps:"
echo "1. Run: php artisan config:clear"
echo "2. Run: php artisan route:clear"
echo "3. Run: php artisan view:clear"
echo "4. Test your application"
echo ""
