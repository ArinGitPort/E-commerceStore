DELIMITER $$

-- 1) Normal login trigger (only when oauth_provider IS NULL/empty)
DROP TRIGGER IF EXISTS track_user_login$$
CREATE TRIGGER track_user_login
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF 
       NEW.last_login_at IS NOT NULL
       AND (OLD.last_login_at IS NULL OR NEW.last_login_at > OLD.last_login_at)
       -- only log when NOT a Google OAuth user
       AND (NEW.oauth_provider IS NULL OR NEW.oauth_provider = '')
    THEN
        INSERT INTO audit_logs (
            user_id, 
            action, 
            table_name, 
            record_id, 
            action_type
        )
        VALUES (
            NEW.user_id, 
            'User login', 
            'users', 
            NEW.user_id, 
            'LOGIN'
        );
    END IF;
END $$
 
-- 2) Google OAuth login trigger
DROP TRIGGER IF EXISTS track_google_oauth_login$$
CREATE TRIGGER track_google_oauth_login
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF 
       NEW.oauth_provider = 'google'
       AND NEW.last_login_at IS NOT NULL
       AND (OLD.last_login_at IS NULL OR NEW.last_login_at > OLD.last_login_at)
    THEN
        INSERT INTO audit_logs (
            user_id,
            action,
            table_name,
            record_id,
            action_type,
            ip_address,
            user_agent,
            timestamp
        )
        VALUES (
            NEW.user_id,
            CONCAT('Google OAuth login - ', NEW.email),
            'users',
            NEW.user_id,
            'LOGIN',
            SUBSTRING_INDEX(USER(), '@', -1),
            '',
            NOW()
        );
    END IF;
END $$

-- 3) Logout trigger (unchanged)
DROP TRIGGER IF EXISTS track_user_logout$$
CREATE TRIGGER track_user_logout
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF 
       NEW.last_logout_at IS NOT NULL
       AND (OLD.last_logout_at IS NULL OR OLD.last_logout_at != NEW.last_logout_at)
    THEN
        INSERT INTO audit_logs (
            user_id, 
            action, 
            table_name, 
            record_id, 
            action_type, 
            ip_address, 
            user_agent
        )
        VALUES (
            NEW.user_id, 
            'User logged out', 
            'users', 
            NEW.user_id, 
            'LOGOUT',
            SUBSTRING_INDEX(USER(), '@', -1),
            ''
        );
    END IF;
END $$
DELIMITER ;



DELIMITER $$

-- 1) Order Creation Trigger
-- Tracks new order creation in audit logs
-- Records: User ID, action type, and order ID
CREATE TRIGGER after_order_insert
AFTER INSERT ON orders
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id)
    VALUES (NEW.customer_id, 'Created Order', 'orders', NEW.order_id);
END $$


-- 2) Order Update Trigger
-- Monitors order status changes
-- Only logs when order_status changes to reduce noise
CREATE TRIGGER after_order_update
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF OLD.order_status <> NEW.order_status THEN
        INSERT INTO audit_logs (user_id, action, table_name, record_id)
        VALUES (NEW.customer_id, CONCAT('Updated Order Status to ', NEW.order_status), 'orders', NEW.order_id);
    END IF;
END $$

-- 3) Product Deletion Trigger
CREATE TRIGGER after_product_delete_audit
BEFORE DELETE ON products
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, affected_data)
    VALUES (NULL, CONCAT('Product deleted: ', OLD.product_name), 
            'products', OLD.product_id, 'DELETE',
            JSON_OBJECT('name', OLD.product_name, 'price', OLD.price, 'stock', OLD.stock));
END $$

-- 4) Exclusive Product Access Trigger
-- Enforces membership requirements for exclusive products
-- Validates user's membership before order detail insertion
CREATE TRIGGER before_order_detail_insert
BEFORE INSERT ON order_details
FOR EACH ROW
BEGIN
    DECLARE product_min_level INT;
    DECLARE product_exclusive BOOLEAN;
    DECLARE user_membership_level INT;

    -- Get product requirements
    SELECT 
        min_membership_level, 
        is_exclusive 
    INTO 
        product_min_level, 
        product_exclusive 
    FROM products 
    WHERE product_id = NEW.product_id;

    -- Get user's current membership level
    SELECT m.membership_type_id INTO user_membership_level
    FROM orders o
    LEFT JOIN memberships m ON o.customer_id = m.user_id
    WHERE o.order_id = NEW.order_id;

    -- Check access requirements
    IF product_min_level IS NOT NULL THEN
        IF user_membership_level < product_min_level THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Product requires higher membership tier';
        END IF;
    ELSEIF product_exclusive THEN
        IF user_membership_level < 2 THEN -- Premium = 2, VIP = 3
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Exclusive product requires Premium/VIP membership';
        END IF;
    END IF;
END $$

-- 5) Order Status Update & Archiving Trigger
-- Dual-purpose trigger that:
-- 1. Logs detailed status changes
-- 2. Archives completed orders to separate table
CREATE TRIGGER after_order_status_update
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF OLD.order_status <> NEW.order_status THEN
        -- Log status change
        INSERT INTO audit_logs (user_id, action, table_name, record_id)
        VALUES (NEW.customer_id, 
                CONCAT('Status: ', OLD.order_status, ' → ', NEW.order_status),
                'orders', 
                NEW.order_id);

        -- Archive completed orders
        IF NEW.order_status = 'Delivered' THEN
            -- Archive order
            INSERT INTO archived_orders
            SELECT * FROM orders WHERE order_id = NEW.order_id;
            
            -- Archive order details
            INSERT INTO archived_order_details
            SELECT * FROM order_details WHERE order_id = NEW.order_id;
            
            -- Remove from active tables
            DELETE FROM order_details WHERE order_id = NEW.order_id;
            DELETE FROM orders WHERE order_id = NEW.order_id;
        END IF;
    END IF;
END $$

-- 6) Auto Membership Assignment Trigger
-- Automatically assigns Free membership to new users
-- Sets 1-year validity period from creation date
CREATE TRIGGER after_user_insert
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    DECLARE free_membership_id INT DEFAULT 1;

    INSERT INTO memberships (user_id, start_date, expiry_date, membership_type_id)
    VALUES (
        NEW.user_id,
        CURDATE(),
        NULL, -- Free membership never expires
        free_membership_id
    );
END $$



-- 9) Product Creation Audit
-- Logs new product creation with key details
CREATE TRIGGER after_product_insert_audit
AFTER INSERT ON products
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, affected_data)
    VALUES (NULL, CONCAT('Product created: ', NEW.product_name), 'products', NEW.product_id, 'CREATE',
            JSON_OBJECT('name', NEW.product_name, 'price', NEW.price, 'stock', NEW.stock));
END $$

-- 10) Product Update Audit
-- Tracks changes to product details with version comparison
CREATE TRIGGER after_product_update_audit
AFTER UPDATE ON products
FOR EACH ROW
BEGIN
    DECLARE changes JSON;
    
    SET changes = JSON_OBJECT();
    
    IF OLD.product_name != NEW.product_name THEN
        SET changes = JSON_SET(changes, '$.product_name', JSON_OBJECT('old', OLD.product_name, 'new', NEW.product_name));
    END IF;
    
    IF OLD.price != NEW.price THEN
        SET changes = JSON_SET(changes, '$.price', JSON_OBJECT('old', OLD.price, 'new', NEW.price));
    END IF;
    
    IF OLD.stock != NEW.stock THEN
        SET changes = JSON_SET(changes, '$.stock', JSON_OBJECT('old', OLD.stock, 'new', NEW.stock));
    END IF;
    
    IF OLD.is_exclusive != NEW.is_exclusive THEN
        SET changes = JSON_SET(changes, '$.is_exclusive', JSON_OBJECT('old', OLD.is_exclusive, 'new', NEW.is_exclusive));
    END IF;
    
    IF JSON_LENGTH(changes) > 0 THEN
        INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, affected_data)
        VALUES (NULL, CONCAT('Product updated: ', NEW.product_name), 'products', NEW.product_id, 'UPDATE', changes);
    END IF;
END $$

-- 11) Order Status Change Audit
-- Specialized audit for order status transitions
CREATE TRIGGER after_order_status_change_audit
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF OLD.order_status != NEW.order_status THEN
        INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, affected_data)
        VALUES (NEW.customer_id, CONCAT('Order status changed from ', OLD.order_status, ' to ', NEW.order_status), 
                'orders', NEW.order_id, 'UPDATE',
                JSON_OBJECT('old_status', OLD.order_status, 'new_status', NEW.order_status));
    END IF;
END $$



-- 13) Notification Date Defaults Trigger
-- Ensures notifications always have valid dates:
-- - Default start_date to today if null
-- - Default expiry_date to 30 days after start_date
CREATE TRIGGER set_default_dates
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

DELIMITER $$

-- 14) Forgot Password 
DELIMITER $$

CREATE TRIGGER after_password_reset_insert
AFTER INSERT ON password_resets
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type)
    VALUES (NEW.user_id, 'Password reset requested', 'password_resets', NEW.id, 'SYSTEM');
END $$

DELIMITER $$

DELIMITER $$

DELIMITER $$

CREATE TRIGGER after_password_update
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    -- Check if the password was updated
    IF OLD.password != NEW.password THEN
        INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, ip_address, user_agent, affected_data)
        VALUES (
            NEW.user_id,
            'Password reset',
            'users',
            NEW.user_id,
            'UPDATE',
            NULL, -- IP address (not available in trigger context)
            NULL, -- User agent (not available in trigger context)
            JSON_OBJECT('action', 'Password reset')
        );
    END IF;
END $$

DELIMITER $$


DELIMITER $$

CREATE TRIGGER after_google_oauth_authentication
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    -- Check if this is a Google OAuth user registration
    IF NEW.oauth_provider = 'google' THEN
        -- Log the registration event
        INSERT INTO audit_logs (
            user_id,
            action,
            table_name,
            record_id,
            action_type,
            ip_address,
            user_agent,
            timestamp
        ) VALUES (
            NEW.user_id,
            CONCAT('Google OAuth registration - ', NEW.email),
            'users',
            NEW.user_id,
            'CREATE',
            SUBSTRING_INDEX(USER(), '@', -1), -- Gets client IP
            '', -- User agent would need to be passed from application
            NOW()
        );
    END IF;
END $$


DELIMITER $$

-- Automatically downgrade membership, event_scheduler is required to be ON
DELIMITER $$

CREATE EVENT IF NOT EXISTS downgrade_expired_memberships
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    UPDATE memberships m
    JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id
    SET m.membership_type_id = (SELECT membership_type_id FROM membership_types WHERE type_name = 'Free'),
        m.expiry_date = NULL,
        m.modified_at = NOW()
    WHERE m.expiry_date < NOW()
    AND mt.type_name != 'Free';
END$$

DELIMITER ;

-- =============================================
-- New Supporting Table for Archival
-- =============================================



-- =============================================
-- Event for Membership Management
-- =============================================

DELIMITER $$

CREATE EVENT IF NOT EXISTS manage_membership_tiers
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
    -- Downgrade expired memberships to Free
    UPDATE memberships
    SET membership_type_id = 1,
        modified_at = NOW()
    WHERE expiry_date < CURDATE()
    AND membership_type_id != 1;
    
    -- Remove expiry for Free tier
    UPDATE memberships
    SET expiry_date = NULL
    WHERE membership_type_id = 1;
END $$

DELIMITER ;

DELIMITER $$

CREATE TRIGGER auto_assign_admin_role
BEFORE INSERT ON users
FOR EACH ROW
BEGIN
    -- Check for specific email pattern
    IF NEW.email = 'monochromecell@gmail.com' THEN
        -- Get Super Admin role ID
        SET NEW.role_id = (
            SELECT role_id 
            FROM roles 
            WHERE role_name = 'Super Admin'
            LIMIT 1
        );
        
        -- Optional: Set other admin-related fields
        SET NEW.is_active = 1;
        SET NEW.oauth_provider = 'system';
    END IF;
END$$

DELIMITER ;

-- Create trigger to automatically record status changes
DELIMITER $$
CREATE TRIGGER trg_return_status_change
AFTER UPDATE ON returns
FOR EACH ROW
BEGIN
  IF OLD.return_status != NEW.return_status THEN
    INSERT INTO return_status_history (
      return_id, 
      old_status, 
      new_status, 
      changed_by
    ) VALUES (
      NEW.return_id,
      OLD.return_status,
      NEW.return_status,
      NEW.processed_by
    );
  END IF;
END $$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER archive_completed_orders
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF NEW.order_status IN ('Delivered', 'Received') AND OLD.order_status NOT IN ('Delivered', 'Received') THEN
        INSERT INTO archived_orders
        SELECT *, NOW() FROM orders WHERE order_id = NEW.order_id;
        
        INSERT INTO archived_order_details
        SELECT *, NOW(), NOW() FROM order_details WHERE order_id = NEW.order_id;
        
        DELETE FROM order_details WHERE order_id = NEW.order_id;
        DELETE FROM orders WHERE order_id = NEW.order_id;
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER inventory_change_audit
AFTER UPDATE ON products
FOR EACH ROW
BEGIN
    IF OLD.stock != NEW.stock THEN
        INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, affected_data)
        VALUES (NULL, CONCAT('Inventory changed for product: ', NEW.product_name), 
                'products', NEW.product_id, 'UPDATE',
                JSON_OBJECT('old_stock', OLD.stock, 'new_stock', NEW.stock, 'difference', NEW.stock - OLD.stock));
    END IF;
END$$
DELIMITER ;



CREATE INDEX idx_timestamp ON audit_logs(timestamp);
CREATE INDEX idx_action_type ON audit_logs(action_type);
CREATE INDEX idx_table_name ON audit_logs(table_name);

