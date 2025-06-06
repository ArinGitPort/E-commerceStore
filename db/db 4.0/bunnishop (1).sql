-- =============================================
-- Database Creation
-- =============================================
CREATE DATABASE IF NOT EXISTS bunnishop;
USE bunnishop;
SET GLOBAL event_scheduler = ON;
-- =============================================
-- Table Definitions
-- =============================================


-- 1) Roles Table
CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    modified_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO roles (role_name) VALUES 
    ('Customer'), 
    ('Member'), 
    ('Staff'),
	('Admin'),
    ('Super Admin'),
    ('Brand Partners');

-- 2) Users Table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    address TEXT,
    role_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    modified_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at DATETIME,
	last_logout_at DATETIME DEFAULT NULL,
    last_activity_at DATETIME NULL,
    activation_token VARCHAR(32) DEFAULT NULL,
    is_active BOOLEAN DEFAULT 0,
    oauth_provider VARCHAR(20) DEFAULT NULL,
    oauth_id VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE RESTRICT
);


select * from users;


-- 3) Membership Types Table
CREATE TABLE membership_types (
    membership_type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) UNIQUE NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    description TEXT NULL,
    can_access_exclusive BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    modified_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO membership_types (type_name, can_access_exclusive, price) VALUES
('Free', FALSE, 0.00),                               -- ID 1
('Dreamy Nook', TRUE, 150.00),                         -- ID 2
('Secret Paper Stash (Stationery Tier)', TRUE, 500.00), -- ID 3
('Crafty Wonderland (Tutorial Tier)', TRUE, 750.00),   -- ID 4
('Little Charm Box (Clay Tier)', TRUE, 1100.00),        -- ID 5
("Bunni's Enchanted Garden (All-in Tier)", TRUE, 2000.00); -- ID 6


-- 4) Memberships Table
CREATE TABLE memberships (
    membership_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    start_date DATE NOT NULL,
    expiry_date DATE DEFAULT NULL, 
    membership_type_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    modified_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CHECK (expiry_date > start_date OR expiry_date IS NULL), 
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (membership_type_id) REFERENCES membership_types(membership_type_id) ON DELETE RESTRICT
);

CREATE TABLE subscriptions_audit (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    membership_type_id INT NOT NULL,
    payment_amount DECIMAL(10,2) NOT NULL,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    payment_method ENUM('credit_card', 'paypal', 'gcash', 'bank_transfer', 'other') NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'completed',
    reference_number VARCHAR(50) NULL,
    notes TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (membership_type_id) REFERENCES membership_types(membership_type_id)
);


-- 6) Password Resets Table
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY (user_id),
    UNIQUE KEY (token)
);

-- 7) Categories Table
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) UNIQUE NOT NULL
);

INSERT INTO categories (category_name) VALUES 
('Plushies'),
('Stationery'),
('Accessories');

-- 8) Products Table
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    sku VARCHAR(255),
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL,
    is_exclusive BOOLEAN DEFAULT FALSE,
    min_membership_level INT NULL,
    category_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    modified_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE RESTRICT,
    FOREIGN KEY (min_membership_level) REFERENCES membership_types(membership_type_id) ON DELETE SET NULL
);

-- 9) Product Images Table
CREATE TABLE product_images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    alt_text VARCHAR(100),
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    modified_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- 10) Cart Items Table
CREATE TABLE cart_items (
    cart_item_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, product_id)
);

-- 11) Delivery Methods Table
CREATE TABLE delivery_methods (
    delivery_method_id INT AUTO_INCREMENT PRIMARY KEY,
    method_name VARCHAR(50) UNIQUE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    modified_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    estimated_days INT DEFAULT 3
);

INSERT INTO delivery_methods (method_name, estimated_days)
VALUES 
  ('Standard Shipping', 3),
  ('Express Shipping', 1),
  ('Same Day Delivery', 0),
  ('Pickup In-Store', 0);

-- 12) Orders Table
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    shipping_name VARCHAR(255) NOT NULL,
    shipping_address TEXT NOT NULL,
    shipping_phone VARCHAR(20) NOT NULL,
    order_status ENUM('Pending','Shipped','Delivered','Received','Cancelled','Returned')
	NOT NULL DEFAULT 'Pending',
    total_price DECIMAL(10,2) NOT NULL,
    delivery_method_id INT NOT NULL,
    discount DECIMAL(10,2) DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    modified_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    viewed TINYINT(1) DEFAULT 0,
    estimated_delivery DATE,
    FOREIGN KEY (customer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (delivery_method_id) REFERENCES delivery_methods(delivery_method_id) ON DELETE RESTRICT
);


-- 13) Order Details Table
CREATE TABLE order_details (
    order_detail_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    total_price DECIMAL(10 , 2 ) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    modified_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders (order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products (product_id) ON DELETE CASCADE
);


-- 14) Payment Methods Table
CREATE TABLE payment_methods (
    payment_method_id INT AUTO_INCREMENT PRIMARY KEY,
    method_name VARCHAR(50) UNIQUE NOT NULL
);

INSERT INTO payment_methods (method_name)
VALUES 
  ('GCash'),
  ('Credit Card'),
  ('Debit Card'),
  ('Cash on Delivery'),
  ('Bank Transfer');

-- 15) Payments Table
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method_id INT NOT NULL,
    payment_status ENUM('Pending','Paid','Failed','Refunded') NOT NULL DEFAULT 'Pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    modified_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(payment_method_id) ON DELETE RESTRICT
);

-- 16) Audit Logs Table
CREATE TABLE audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    affected_data JSON,
    action_type ENUM('CREATE', 'READ', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'SYSTEM') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- 17) Membership Discounts Table
CREATE TABLE membership_discounts (
    discount_id INT AUTO_INCREMENT PRIMARY KEY,
    membership_type_id INT NOT NULL,
    category_id INT NULL,
    product_id INT NULL,
    discount_rate DECIMAL(4,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    modified_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (membership_type_id) REFERENCES membership_types(membership_type_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- 18) Archived Orders Table
CREATE TABLE archived_orders (
    order_id INT PRIMARY KEY,
    customer_id INT NOT NULL,
    order_date DATETIME,
    order_status ENUM('Completed', 'Returned', 'Rejected') NOT NULL,  
    total_price DECIMAL(10,2) NOT NULL,
    shipping_address TEXT,
    shipping_phone VARCHAR(20),
    delivery_method_id INT NOT NULL,
    discount DECIMAL(10,2) DEFAULT 0.00,
    created_at DATETIME,
    modified_at DATETIME,
    viewed BOOLEAN DEFAULT FALSE,
    estimated_delivery DATE,
    FOREIGN KEY (customer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (delivery_method_id) REFERENCES delivery_methods(delivery_method_id) ON DELETE RESTRICT
);



CREATE TABLE archived_order_details (
  archived_order_detail_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  total_price DECIMAL(10,2) NOT NULL,
  created_at DATETIME,
  modified_at DATETIME,
  FOREIGN KEY (order_id)    REFERENCES archived_orders(order_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id)  REFERENCES products(product_id) ON DELETE CASCADE
);


-- =============================================
-- Notification System Tables
-- =============================================

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_by INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    start_date DATE DEFAULT NULL,
    expiry_date DATE DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
);


-- Notification Targets Junction Table
CREATE TABLE IF NOT EXISTS notification_membership_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    membership_type_id INT NOT NULL,
    FOREIGN KEY (notification_id) REFERENCES notifications(notification_id) ON DELETE CASCADE,
    FOREIGN KEY (membership_type_id) REFERENCES membership_types(membership_type_id) ON DELETE CASCADE
);



-- Notification Templates Table
CREATE TABLE IF NOT EXISTS notification_templates (
    template_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE notification_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    user_id INT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES notifications(notification_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY (notification_id, user_id)
);


CREATE TABLE returns (
  return_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  is_archived BOOLEAN NOT NULL,
  archived_order_id INT NOT NULL,  -- Now only references archived_orders
  return_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processed_by INT NULL,
  reason VARCHAR(255) NULL,
  return_status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  status_history JSON DEFAULT NULL,
  last_status_update DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (archived_order_id) REFERENCES archived_orders(order_id) ON DELETE CASCADE,
  FOREIGN KEY (processed_by) REFERENCES users(user_id) ON DELETE SET NULL
);


-- 13) Order Details Table 
CREATE TABLE return_details (
  return_detail_id INT AUTO_INCREMENT PRIMARY KEY,
  return_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  FOREIGN KEY (return_id) REFERENCES returns(return_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT
);

-- 2. Return Items Table (modified)
CREATE TABLE return_items (
  return_item_id INT AUTO_INCREMENT PRIMARY KEY,
  return_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL CHECK (quantity > 0),
  unit_price DECIMAL(10,2) NOT NULL, -- Price at time of return
  restocking_fee DECIMAL(10,2) DEFAULT 0.00,
  reason VARCHAR(255) NULL,
  item_condition ENUM('New','Opened','Damaged') DEFAULT 'New',
  FOREIGN KEY (return_id) REFERENCES returns(return_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT
);

-- 3. Return Status History Table (new)
CREATE TABLE return_status_history (
  history_id INT AUTO_INCREMENT PRIMARY KEY,
  return_id INT NOT NULL,
  old_status VARCHAR(20),
  new_status VARCHAR(20) NOT NULL,
  changed_by INT NULL,
  change_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  notes TEXT NULL,
  FOREIGN KEY (return_id) REFERENCES returns(return_id) ON DELETE CASCADE,
  FOREIGN KEY (changed_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Optional: Add index for audit trail performance
CREATE INDEX idx_return_status ON returns(return_status);


-- Set default dates trigger
DELIMITER $$
CREATE TRIGGER set_notification_dates
BEFORE INSERT ON notifications
FOR EACH ROW
BEGIN
  IF NEW.start_date IS NULL THEN
    SET NEW.start_date = CURDATE();
  END IF;
  
  IF NEW.expiry_date IS NULL THEN
    SET NEW.expiry_date = DATE_ADD(NEW.start_date, INTERVAL 30 DAY);
  END IF;
END $$
DELIMITER ;

-- =============================================
-- Sample Data Insertion
-- =============================================


-- Sample Users
INSERT INTO users (name, email, password, role_id, is_active) VALUES 
('User Free', 'free@bunniwinkle.com', 'hashed_pw', 1, 1),
('User Premium', 'premium@bunniwinkle.com', 'hashed_pw', 1, 1),
('User VIP', 'vip@bunniwinkle.com', 'hashed_pw', 1, 1),
('Admin User', 'admin@bunniwinkle.com', 'hashed_pw', 2, 1),
('Super Admin', 'superadmin@bunniwinkle.com', 'hashed_pw', 4, 1);

-- Sample Memberships
INSERT INTO memberships (user_id, start_date, expiry_date, membership_type_id) VALUES
(1, '2024-01-01', '2025-01-01', 1),
(2, '2024-01-01', '2025-01-01', 2),
(3, '2024-01-01', '2025-01-01', 3);

-- Sample Products
INSERT INTO products (product_name, sku, description, price, stock, category_id, is_exclusive, min_membership_level) VALUES 
('Fluffy Bunny Plush', 'PLUSH001', 'Soft and cuddly bunny plush perfect for snuggles.', 14.99, 50, 1, TRUE, 2),
('Kawaii Bunny Notebook', 'NOTE001', 'Adorable bunny-themed notebook for journaling.', 6.49, 100, 2, FALSE, NULL),
('Bunny Enamel Pin', 'PIN001', 'A cute enamel pin to decorate your bag or jacket.', 3.99, 75, 3, FALSE, NULL);

-- Sample Notifications
INSERT INTO notifications (title, message, created_by, is_active, start_date, expiry_date) VALUES
('Holiday Promo!', 'Everyone gets 10% off this week only!', 5, TRUE, CURRENT_DATE(), DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)),
('VIP Gift Incoming!', 'Hey VIP! Your mystery gift is on the way 🎁', 5, TRUE, CURRENT_DATE(), DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY));

-- Sample Notification Targets
INSERT INTO notification_membership_targets (notification_id, membership_type_id) VALUES
(1, 1), -- Holiday promo for Free members
(1, 2), -- Holiday promo for Premium members
(1, 3), -- Holiday promo for VIP members
(2, 3); -- VIP gift only for VIP members

CREATE INDEX idx_notification_targets ON notification_membership_targets(notification_id, membership_type_id);
CREATE INDEX idx_notifications_dates ON notifications(start_date, expiry_date);