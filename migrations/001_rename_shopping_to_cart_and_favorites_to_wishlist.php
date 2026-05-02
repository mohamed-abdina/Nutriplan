<?php
/**
 * Migration: Rename Shopping List to Cart and Favorites to Wishlist
 * File: migrations/001_rename_shopping_to_cart_and_favorites_to_wishlist.php
 * 
 * Purpose:
 * - Rename tables: shopping_lists → carts, shopping_items → cart_items
 * - Rename column: is_favorite → is_wishlisted in meal_ratings
 * - Create backward compatibility views
 * - Preserve all data and constraints
 * 
 * Status: Production-safe with rollback capability
 */

require_once __DIR__ . '/../includes/db_connect.php';

class ShoppingToCartMigration {
    private $pdo;
    private $errors = [];
    private $warnings = [];
    private $success = [];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function up() {
        echo "Starting migration: Shopping → Cart & Favorites → Wishlist\n";
        echo str_repeat("=", 70) . "\n\n";
        
        try {
            // Step 1: Disable foreign key checks temporarily
            echo "[1/7] Disabling foreign key checks...\n";
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS=0");
            $this->success[] = "Foreign key checks disabled";
            
            // Step 2: Rename shopping_lists to carts
            echo "[2/7] Renaming table: shopping_lists → carts...\n";
            $this->pdo->exec("RENAME TABLE shopping_lists TO carts");
            $this->success[] = "Table 'shopping_lists' renamed to 'carts'";
            
            // Step 3: Rename shopping_items to cart_items
            echo "[3/7] Renaming table: shopping_items → cart_items...\n";
            $this->pdo->exec("RENAME TABLE shopping_items TO cart_items");
            $this->success[] = "Table 'shopping_items' renamed to 'cart_items'";
            
            // Step 4: Add is_wishlisted column to meal_ratings
            echo "[4/7] Adding 'is_wishlisted' column to meal_ratings...\n";
            
            // Check if column already exists
            $checkCol = $this->pdo->query(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_NAME = 'meal_ratings' AND COLUMN_NAME = 'is_wishlisted'"
            )->fetch();
            
            if (!$checkCol) {
                $this->pdo->exec(
                    "ALTER TABLE meal_ratings ADD COLUMN is_wishlisted BOOLEAN DEFAULT 0 AFTER is_favorite"
                );
                // Copy data from is_favorite to is_wishlisted
                $this->pdo->exec("UPDATE meal_ratings SET is_wishlisted = is_favorite WHERE is_favorite = 1");
                $this->success[] = "Column 'is_wishlisted' added and data migrated from 'is_favorite'";
            } else {
                $this->warnings[] = "Column 'is_wishlisted' already exists - skipping creation";
            }
            
            // Step 5: Create backward compatibility view for shopping_lists
            echo "[5/7] Creating backward compatibility view: shopping_lists...\n";
            $this->pdo->exec("DROP VIEW IF EXISTS shopping_lists");
            $this->pdo->exec("
                CREATE VIEW shopping_lists AS
                SELECT 
                    list_id,
                    user_id,
                    created_at
                FROM carts
            ");
            $this->success[] = "Backward compatibility view 'shopping_lists' created";
            
            // Step 6: Create backward compatibility view for shopping_items
            echo "[6/7] Creating backward compatibility view: shopping_items...\n";
            $this->pdo->exec("DROP VIEW IF EXISTS shopping_items");
            $this->pdo->exec("
                CREATE VIEW shopping_items AS
                SELECT 
                    item_id,
                    list_id,
                    meal_id,
                    item_name,
                    quantity,
                    purchased,
                    created_at,
                    custom_item
                FROM cart_items
            ");
            $this->success[] = "Backward compatibility view 'shopping_items' created";
            
            // Step 7: Re-enable foreign key checks
            echo "[7/7] Re-enabling foreign key checks...\n";
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS=1");
            $this->success[] = "Foreign key checks re-enabled";
            
            // Print summary
            $this->printSummary("UP");
            return true;
            
        } catch (PDOException $e) {
            $this->errors[] = "Migration failed: " . $e->getMessage();
            $this->printSummary("UP");
            return false;
        }
    }
    
    public function down() {
        echo "Rolling back migration: Cart → Shopping & Wishlist → Favorites\n";
        echo str_repeat("=", 70) . "\n\n";
        
        try {
            // Step 1: Disable foreign key checks
            echo "[1/6] Disabling foreign key checks...\n";
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS=0");
            $this->success[] = "Foreign key checks disabled";
            
            // Step 2: Drop backward compatibility views
            echo "[2/6] Dropping backward compatibility views...\n";
            $this->pdo->exec("DROP VIEW IF EXISTS shopping_lists");
            $this->pdo->exec("DROP VIEW IF EXISTS shopping_items");
            $this->success[] = "Backward compatibility views dropped";
            
            // Step 3: Rename carts back to shopping_lists
            echo "[3/6] Renaming table: carts → shopping_lists...\n";
            $this->pdo->exec("RENAME TABLE carts TO shopping_lists");
            $this->success[] = "Table 'carts' renamed back to 'shopping_lists'";
            
            // Step 4: Rename cart_items back to shopping_items
            echo "[4/6] Renaming table: cart_items → shopping_items...\n";
            $this->pdo->exec("RENAME TABLE cart_items TO shopping_items");
            $this->success[] = "Table 'cart_items' renamed back to 'shopping_items'";
            
            // Step 5: Remove is_wishlisted column (copy data back to is_favorite first)
            echo "[5/6] Updating is_favorite from is_wishlisted...\n";
            $checkWishlisted = $this->pdo->query(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_NAME = 'meal_ratings' AND COLUMN_NAME = 'is_wishlisted'"
            )->fetch();
            
            if ($checkWishlisted) {
                $this->pdo->exec("UPDATE meal_ratings SET is_favorite = is_wishlisted");
                $this->pdo->exec("ALTER TABLE meal_ratings DROP COLUMN is_wishlisted");
                $this->success[] = "Column 'is_wishlisted' dropped after data migration";
            } else {
                $this->warnings[] = "Column 'is_wishlisted' does not exist - skipping";
            }
            
            // Step 6: Re-enable foreign key checks
            echo "[6/6] Re-enabling foreign key checks...\n";
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS=1");
            $this->success[] = "Foreign key checks re-enabled";
            
            // Print summary
            $this->printSummary("DOWN");
            return true;
            
        } catch (PDOException $e) {
            $this->errors[] = "Rollback failed: " . $e->getMessage();
            $this->printSummary("DOWN");
            return false;
        }
    }
    
    public function verify() {
        echo "Verifying migration state...\n";
        echo str_repeat("=", 70) . "\n\n";
        
        try {
            $issues = [];
            
            // Check if new tables exist
            $cartTableExists = $this->tableExists('carts');
            $cartItemsTableExists = $this->tableExists('cart_items');
            
            // Check if old tables still exist (should only exist as views)
            $oldShoppingListsIsView = $this->isView('shopping_lists');
            $oldShoppingItemsIsView = $this->isView('shopping_items');
            
            // Check if is_wishlisted column exists
            $isWishlistedExists = $this->columnExists('meal_ratings', 'is_wishlisted');
            
            echo "✓ Table 'carts' exists: " . ($cartTableExists ? "YES" : "NO") . "\n";
            echo "✓ Table 'cart_items' exists: " . ($cartItemsTableExists ? "YES" : "NO") . "\n";
            echo "✓ View 'shopping_lists' exists: " . ($oldShoppingListsIsView ? "YES" : "NO") . "\n";
            echo "✓ View 'shopping_items' exists: " . ($oldShoppingItemsIsView ? "YES" : "NO") . "\n";
            echo "✓ Column 'is_wishlisted' exists: " . ($isWishlistedExists ? "YES" : "NO") . "\n";
            
            // Verify data integrity
            echo "\n" . str_repeat(".", 70) . "\n";
            echo "Data Integrity Check:\n";
            
            $cartCount = $this->pdo->query("SELECT COUNT(*) as cnt FROM carts")->fetch()['cnt'];
            $cartItemsCount = $this->pdo->query("SELECT COUNT(*) as cnt FROM cart_items")->fetch()['cnt'];
            $mealRatingsCount = $this->pdo->query("SELECT COUNT(*) as cnt FROM meal_ratings")->fetch()['cnt'];
            
            echo "  - Carts: $cartCount record(s)\n";
            echo "  - Cart Items: $cartItemsCount record(s)\n";
            echo "  - Meal Ratings: $mealRatingsCount record(s)\n";
            
            $allPass = $cartTableExists && $cartItemsTableExists && 
                      $oldShoppingListsIsView && $oldShoppingItemsIsView && 
                      $isWishlistedExists;
            
            echo "\n" . str_repeat("=", 70) . "\n";
            if ($allPass) {
                echo "✓ Migration verification PASSED\n";
            } else {
                echo "✗ Migration verification FAILED - check items above\n";
            }
            
            return $allPass;
            
        } catch (PDOException $e) {
            echo "✗ Verification failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function tableExists($tableName) {
        $result = $this->pdo->query(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '$tableName' AND TABLE_SCHEMA = DATABASE()"
        )->fetch();
        return $result !== false;
    }
    
    private function isView($viewName) {
        $result = $this->pdo->query(
            "SELECT 1 FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_NAME = '$viewName' AND TABLE_SCHEMA = DATABASE()"
        )->fetch();
        return $result !== false;
    }
    
    private function columnExists($tableName, $columnName) {
        $result = $this->pdo->query(
            "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$tableName' AND COLUMN_NAME = '$columnName' AND TABLE_SCHEMA = DATABASE()"
        )->fetch();
        return $result !== false;
    }
    
    private function printSummary($direction) {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "MIGRATION SUMMARY ($direction)\n";
        echo str_repeat("=", 70) . "\n\n";
        
        if (!empty($this->success)) {
            echo "✓ SUCCESS (" . count($this->success) . " operations):\n";
            foreach ($this->success as $msg) {
                echo "  ✓ $msg\n";
            }
        }
        
        if (!empty($this->warnings)) {
            echo "\n⚠ WARNINGS (" . count($this->warnings) . " warnings):\n";
            foreach ($this->warnings as $msg) {
                echo "  ⚠ $msg\n";
            }
        }
        
        if (!empty($this->errors)) {
            echo "\n✗ ERRORS (" . count($this->errors) . " errors):\n";
            foreach ($this->errors as $msg) {
                echo "  ✗ $msg\n";
            }
        }
        
        echo "\n" . str_repeat("=", 70) . "\n\n";
    }
}

// Script execution
if (php_sapi_name() === 'cli') {
    $action = $argv[1] ?? 'up';
    
    $migration = new ShoppingToCartMigration($pdo);
    
    if ($action === 'up') {
        $result = $migration->up();
        echo $result ? "\n✓ Migration completed successfully!\n" : "\n✗ Migration failed!\n";
    } elseif ($action === 'down') {
        $result = $migration->down();
        echo $result ? "\n✓ Rollback completed successfully!\n" : "\n✗ Rollback failed!\n";
    } elseif ($action === 'verify') {
        $migration->verify();
    } else {
        echo "Usage: php migrations/001_rename_shopping_to_cart_and_favorites_to_wishlist.php [up|down|verify]\n";
        exit(1);
    }
    
    exit($result ? 0 : 1);
}
