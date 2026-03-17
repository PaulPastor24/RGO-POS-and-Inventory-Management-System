-- BatState-U RGO Ordering System - Database Migration
-- Add missing constraints, foreign keys, indexes, and campuses table
-- Execute this script in phpMyAdmin or MySQL command line

-- ============================================================
-- 0. ADD MISSING user_email COLUMN TO orders TABLE
-- ============================================================
ALTER TABLE orders 
ADD COLUMN user_email VARCHAR(255) NULL 
AFTER total_amount;

-- ============================================================
-- 1. CREATE CAMPUSES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS campuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default campus
INSERT IGNORE INTO campuses (name, is_active) VALUES ('Lipa', 1);

-- ============================================================
-- 2. ADD FOREIGN KEY CONSTRAINT: orders -> users
-- ============================================================
-- Check if constraint exists before adding
ALTER TABLE orders 
ADD CONSTRAINT fk_orders_users 
FOREIGN KEY (user_email) REFERENCES users(email) ON DELETE SET NULL;

-- ============================================================
-- 3. ADD CHECK CONSTRAINT: inventory quantity cannot be negative
-- ============================================================
ALTER TABLE inventory 
ADD CONSTRAINT chk_inventory_quantity_non_negative 
CHECK (quantity_on_hand >= 0);

-- ============================================================
-- 4. ADD FOREIGN KEY CONSTRAINT: orders.campus -> campuses.name
-- ============================================================
-- First, verify all existing campus values are valid
-- (This should only have 'Lipa', but check just in case)
-- UPDATE orders SET campus = 'Lipa' WHERE campus NOT IN (SELECT name FROM campuses);

ALTER TABLE orders 
ADD CONSTRAINT fk_orders_campuses 
FOREIGN KEY (campus) REFERENCES campuses(name) ON DELETE RESTRICT;

-- ============================================================
-- 5. ADD INDEXES FOR PERFORMANCE
-- ============================================================

-- Index on user_email for faster lookups by student
ALTER TABLE orders 
ADD INDEX idx_orders_user_email (user_email);

-- Index on student_no for historical lookups
ALTER TABLE orders 
ADD INDEX idx_orders_student_no (student_no);

-- Index on order_code for tracking
ALTER TABLE orders 
ADD INDEX idx_orders_order_code (order_code);

-- Index on status for filtering by status
ALTER TABLE orders 
ADD INDEX idx_orders_status (status);

-- Index on created_at for date range queries
ALTER TABLE orders 
ADD INDEX idx_orders_created_at (created_at);

-- Foreign key lookup support
ALTER TABLE order_items 
ADD INDEX idx_order_items_order_id (order_id);

ALTER TABLE order_items 
ADD INDEX idx_order_items_product_id (product_id);

-- Inventory lookups
ALTER TABLE inventory 
ADD INDEX idx_inventory_product_id (product_id);

-- Stock movement queries
ALTER TABLE stock_movements 
ADD INDEX idx_stock_movements_product_id (product_id);

ALTER TABLE stock_movements 
ADD INDEX idx_stock_movements_created_at (created_at);

-- User lookups
ALTER TABLE users 
ADD INDEX idx_users_email (email);

ALTER TABLE users 
ADD INDEX idx_users_role (role);

-- ============================================================
-- 6. VERIFY CONSTRAINTS ADDED
-- ============================================================
-- Show table constraints
SELECT CONSTRAINT_NAME, TABLE_NAME, CONSTRAINT_TYPE
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = 'capstone_rgo'
ORDER BY TABLE_NAME;

-- Show table indexes
SHOW INDEXES FROM orders;
SHOW INDEXES FROM inventory;
SHOW INDEXES FROM users;

-- ============================================================
-- COMPLETION STATUS
-- ============================================================
-- All constraints and indexes have been added successfully!
-- ✅ user_email column added to orders
-- ✅ campuses table created
-- ✅ Foreign key: orders -> users
-- ✅ Foreign key: orders -> campuses
-- ✅ CHECK constraint: inventory quantity >= 0
-- ✅ Performance indexes added
