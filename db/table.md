-- =============================================
-- Database Creation
-- =============================================
CREATE DATABASE bunnishop;
USE bunnishop;

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
    ('Admin'), 
    ('Member'),
    ('Super Admin');


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
    last_activity_at DATETIME NULL,
    activation_token VARCHAR(32) DEFAULT NULL,
    is_active BOOLEAN DEFAULT 0,
	oauth_provider VARCHAR(20) DEFAULT NULL,
	oauth_id VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE RESTRICT
);

-- Free member (no access to exclusive)
INSERT INTO users (name, email, password, role_id, is_active)
VALUES ('User Free', 'free@bunniwinkle.com', 'hashed_pw', 1, 1);

-- Premium member (has access to exclusive)
INSERT INTO users (name, email, password, role_id, is_active)
VALUES ('User Premium', 'premium@bunniwinkle.com', 'hashed_pw', 1, 1);

-- VIP member (has access to exclusive)
INSERT INTO users (name, email, password, role_id, is_active)
VALUES ('User VIP', 'vip@bunniwinkle.com', 'hashed_pw', 1, 1);


SELECT u.user_id, u.name, mt.type_name
FROM users u
JOIN memberships m ON u.user_id = m.user_id
JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id;


select * from users;

UPDATE users
SET role_id = 4
WHERE user_id = 5;

UPDATE memberships
SET membership_type_id = 3  -- 3 = VIP (based on your insert order)
WHERE user_id = 4;


-- SAVING USER SESSION FOR FUTURE IMPLEMENTATION
CREATE TABLE user_sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    user_id INT,
    payload TEXT NOT NULL,
    last_activity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

select * from user_sessions;


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

select * from password_resets;

-- 3) Membership Types Table
CREATE TABLE membership_types (
    membership_type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) UNIQUE NOT NULL,
    can_access_exclusive BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    modified_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO membership_types (type_name, can_access_exclusive) VALUES
('Free', FALSE),        -- ID 1
('Premium', TRUE),      -- ID 2
('VIP', TRUE);          -- ID 3



-- 4) Memberships Table
CREATE TABLE memberships (
    membership_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    start_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    membership_type_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    modified_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CHECK (expiry_date > start_date),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (membership_type_id) REFERENCES membership_types(membership_type_id) ON DELETE RESTRICT
);

-- User ID 1: Free
INSERT INTO memberships (user_id, start_date, expiry_date, membership_type_id)
VALUES (1, '2024-01-01', '2025-01-01', 1);

-- User ID 2: Premium
INSERT INTO memberships (user_id, start_date, expiry_date, membership_type_id)
VALUES (2, '2024-01-01', '2025-01-01', 2);

-- User ID 3: VIP
INSERT INTO memberships (user_id, start_date, expiry_date, membership_type_id)
VALUES (3, '2024-01-01', '2025-01-01', 3);


-- 5) Categories Table
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) UNIQUE NOT NULL
);

INSERT INTO categories (category_name) VALUES 
('Plushies'),
('Stationery'),
('Accessories');


-- 6) Products Table
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

INSERT INTO products (product_name, description, price, stock, category_id) VALUES 
('Fluffy Bunny Plush', 'Soft and cuddly bunny plush perfect for snuggles.', 14.99, 50, 1),
('Kawaii Bunny Notebook', 'Adorable bunny-themed notebook for journaling.', 6.49, 100, 2),
('Bunny Enamel Pin', 'A cute enamel pin to decorate your bag or jacket.', 3.99, 75, 3);

UPDATE products
SET is_exclusive = TRUE, min_membership_level = 2
WHERE product_id = 1;



-- 7) Product Images Table (NEW)
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

select * from products;

-- =============================================
-- CART ITEMS TABLE
-- =============================================

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

SELECT * FROM cart_items;
SELECT user_id, product_id, quantity FROM cart_items WHERE user_id = 1;



-- 8) Delivery Methods Table
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


-- 9) Orders Table
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
	shipping_address TEXT,
    order_status ENUM('Pending','Shipped','Delivered','Cancelled','Returned') NOT NULL DEFAULT 'Pending',
    total_price DECIMAL(10,2) NOT NULL,
    delivery_method_id INT NOT NULL,
    discount DECIMAL(10,2) DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    modified_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    viewed BOOLEAN DEFAULT FALSE,
    estimated_delivery DATE,
    FOREIGN KEY (delivery_method_id) REFERENCES delivery_methods(delivery_method_id) ON DELETE RESTRICT
);


select * from orders;

-- 10) Order Details Table
CREATE TABLE order_details (
    order_detail_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    modified_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- 11) Payment Methods Table
CREATE TABLE payment_methods (
    payment_method_id INT AUTO_INCREMENT PRIMARY KEY,
    method_name VARCHAR(50) UNIQUE NOT NULL
);

-- 12) Payments Table
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

INSERT INTO payment_methods (method_name)
VALUES 
  ('GCash'),
  ('Credit Card'),
  ('Debit Card'),
  ('Cash on Delivery'),
  ('Bank Transfer');


-- 13) Audit Logs Table
CREATE TABLE audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- 14) Membership Discounts Table
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


CREATE TABLE archived_orders (
    order_id INT PRIMARY KEY,
    customer_id INT NOT NULL,
    order_date DATETIME,
    shipping_address TEXT,
    order_status ENUM('Completed') NOT NULL,  -- Only holds completed orders
    total_price DECIMAL(10,2) NOT NULL,
    delivery_method_id INT NOT NULL,
    discount DECIMAL(10,2) DEFAULT 0.00,
    created_at DATETIME,
    modified_at DATETIME,
    viewed BOOLEAN DEFAULT FALSE,
    estimated_delivery DATE,
    -- Additional columns if necessary
    FOREIGN KEY (customer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (delivery_method_id) REFERENCES delivery_methods(delivery_method_id) ON DELETE RESTRICT
);


select * from archived_orders;

ALTER TABLE audit_logs
ADD COLUMN ip_address VARCHAR(45),
ADD COLUMN user_agent VARCHAR(255),
ADD COLUMN affected_data JSON,
ADD COLUMN action_type ENUM('CREATE', 'READ', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'SYSTEM') NOT NULL,
MODIFY COLUMN action VARCHAR(255) NOT NULL;


CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    membership_type_id INT NULL, -- NULL = send to all types
    created_by INT, -- admin user_id
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (membership_type_id) REFERENCES membership_types(membership_type_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Message for all members
INSERT INTO notifications (title, message, membership_type_id, created_by)
VALUES ('Holiday Promo!', 'Everyone gets 10% off this week only!', NULL, 5);

-- Message for VIPs only
INSERT INTO notifications (title, message, membership_type_id, created_by)
VALUES ('VIP Gift Incoming!', 'Hey VIP! Your mystery gift is on the way 🎁', 3, 5);



CREATE TABLE notification_recipients (
    recipient_id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    user_id INT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at DATETIME DEFAULT NULL,
    FOREIGN KEY (notification_id) REFERENCES notifications(notification_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);


CREATE TABLE notification_templates (
    template_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    membership_type_id INT NULL,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (membership_type_id) REFERENCES membership_types(membership_type_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
);

CREATE TABLE notification_membership_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    membership_type_id INT NOT NULL,
    FOREIGN KEY (notification_id) REFERENCES notifications(notification_id) ON DELETE CASCADE,
    FOREIGN KEY (membership_type_id) REFERENCES membership_types(membership_type_id) ON DELETE CASCADE
);


ALTER TABLE notifications
  ADD COLUMN start_date DATE DEFAULT NULL,
  ADD COLUMN expiry_date DATE DEFAULT NULL;

ALTER TABLE notifications
  MODIFY COLUMN start_date DATE DEFAULT (CURRENT_DATE());



