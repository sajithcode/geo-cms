-- Migration Script: Rename Inventory to Store
-- This script renames all inventory-related tables to store-related names
-- Run this script once to update your existing database

-- Step 1: Rename inventory_categories to store_categories
ALTER TABLE `inventory_categories` RENAME TO `store_categories`;

-- Step 2: Rename inventory_items to store_items
ALTER TABLE `inventory_items` RENAME TO `store_items`;

-- Step 3: Update foreign key references in borrow_requests
-- Note: Foreign keys are automatically maintained when renaming tables in MySQL

-- Verification queries (optional - run these after migration to confirm)
-- SHOW TABLES LIKE '%store%';
-- DESCRIBE store_categories;
-- DESCRIBE store_items;

-- Migration complete!
-- After running this script:
-- 1. Update all PHP code to reference store_categories and store_items
-- 2. Update JavaScript API calls
-- 3. Clear any application caches
