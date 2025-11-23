-- CHICKEN WHY NOT? - Egg Marketplace Database Schema
CREATE DATABASE IF NOT EXISTS kenth_eggs;
USE kenth_eggs;

-- ============================================
-- TABLE: users
-- ============================================
CREATE TABLE IF NOT EXISTS users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE,
  phone VARCHAR(20) NOT NULL,
  -- password is required for auth; store bcrypt hashes
  password VARCHAR(255) NOT NULL,
  -- is_admin: 1 = admin, 0 = regular user
  is_admin TINYINT(1) DEFAULT 0,
  address TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- TABLE: products
-- ============================================
CREATE TABLE IF NOT EXISTS products (
  id INT PRIMARY KEY AUTO_INCREMENT,
  product_id VARCHAR(50) UNIQUE NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT,
  image_url VARCHAR(255),
  base_price DECIMAL(10, 2),
  is_variant BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- TABLE: product_variants
-- ============================================
CREATE TABLE IF NOT EXISTS product_variants (
  id INT PRIMARY KEY AUTO_INCREMENT,
  product_id INT NOT NULL,
  variant_id VARCHAR(50) UNIQUE NOT NULL,
  size VARCHAR(50) NOT NULL,
  price DECIMAL(10, 2) NOT NULL,
  image_url VARCHAR(255),
  stock INT DEFAULT 100,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ============================================
-- TABLE: orders
-- ============================================
CREATE TABLE IF NOT EXISTS orders (
  id INT PRIMARY KEY AUTO_INCREMENT,
  order_number VARCHAR(50) UNIQUE NOT NULL,
  user_id INT NOT NULL,
  total_amount DECIMAL(10, 2) NOT NULL,
  -- status values aligned with application code: pending / accepted / declined
  status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
  pickup_date DATE,
  pickup_time TIME,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_status (status),
  INDEX idx_created_at (created_at)
);

-- ============================================
-- TABLE: order_items
-- ============================================
CREATE TABLE IF NOT EXISTS order_items (
  id INT PRIMARY KEY AUTO_INCREMENT,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  variant_id VARCHAR(50),
  quantity INT NOT NULL,
  unit_price DECIMAL(10, 2) NOT NULL,
  line_total DECIMAL(10, 2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

-- ============================================
-- TABLE: cart_sessions (optional, for persistent cart)
-- ============================================
CREATE TABLE IF NOT EXISTS cart_sessions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  session_id VARCHAR(128) UNIQUE NOT NULL,
  cart_data JSON NOT NULL,
  user_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- TABLE: payments (optional, for future integration)
-- ============================================
CREATE TABLE IF NOT EXISTS payments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  order_id INT NOT NULL,
  amount DECIMAL(10, 2) NOT NULL,
  payment_method VARCHAR(50),
  transaction_id VARCHAR(100),
  status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- ============================================
-- TABLE: order_status_log (audit trail for status changes)
-- ============================================
CREATE TABLE IF NOT EXISTS order_status_log (
  id INT PRIMARY KEY AUTO_INCREMENT,
  order_id INT NOT NULL,
  old_status VARCHAR(50) NOT NULL,
  new_status VARCHAR(50) NOT NULL,
  changed_by INT NULL,
  notes TEXT,
  changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_order_id (order_id)
);

-- Trigger: record status changes automatically
-- Note: phpMyAdmin may require you to run DELIMITER commands in SQL tab.
DELIMITER $$
CREATE TRIGGER trg_orders_status_change
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
  IF OLD.status <> NEW.status THEN
    INSERT INTO order_status_log (order_id, old_status, new_status, changed_at)
    VALUES (NEW.id, OLD.status, NEW.status, NOW());
  END IF;
END$$
DELIMITER ;

-- Optional: create an admin user via SQL (recommended to use site registration instead)
-- To create an admin by SQL, generate a bcrypt hash using PHP CLI and replace <hash> below:
-- php -r "echo password_hash('YourStrongPassword', PASSWORD_DEFAULT);"
-- Example (replace <hash> and values):
-- INSERT INTO users (name, email, phone, password, is_admin) VALUES ('Admin','admin@example.com','09171234567','<hash>',1);

-- ============================================
-- SAMPLE DATA
-- ============================================

-- Insert Products
INSERT INTO products (product_id, title, description, image_url, base_price, is_variant) VALUES
('free-range', 'Free-range Chicken Eggs', 'High-quality free-range eggs from our farm', 'eggs/OIP (5).webp', 5.99, TRUE),
('organic', 'Organic Chicken Eggs', 'Certified organic eggs with no additives', 'eggs/OIP (4).webp', 7.49, TRUE),
('bulk', 'Bulk Dozen (12 x Dozens)', 'Large bulk purchase for businesses and families', 'eggs/OIP (3).webp', 60.00, FALSE);

-- Insert Product Variants (Free-range)
INSERT INTO product_variants (product_id, variant_id, size, price, image_url, stock) VALUES
(1, 'free-small', 'Small', 4.99, 'eggs/OIP (5).webp', 50),
(1, 'free-medium', 'Medium', 5.99, 'eggs/OIP (5).webp', 75),
(1, 'free-large', 'Large', 6.99, 'eggs/OIP (5).webp', 60),
(1, 'free-xl', 'XL', 8.50, 'eggs/OIP (5).webp', 40),
(1, 'free-xxl', 'XXL', 10.00, 'eggs/OIP (5).webp', 30);

-- Insert Product Variants (Organic)
INSERT INTO product_variants (product_id, variant_id, size, price, image_url, stock) VALUES
(2, 'org-small', 'Small', 6.49, 'eggs/OIP (4).webp', 40),
(2, 'org-medium', 'Medium', 7.49, 'eggs/OIP (4).webp', 60),
(2, 'org-large', 'Large', 8.49, 'eggs/OIP (4).webp', 50),
(2, 'org-xl', 'XL', 10.50, 'eggs/OIP (4).webp', 35),
(2, 'org-xxl', 'XXL', 12.00, 'eggs/OIP (4).webp', 25);

-- ============================================
-- INDEXES for Performance
-- ============================================
CREATE INDEX idx_products_product_id ON products(product_id);
CREATE INDEX idx_variants_product_id ON product_variants(product_id);
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_order_items_order_id ON order_items(order_id);
CREATE INDEX idx_payments_order_id ON payments(order_id);
