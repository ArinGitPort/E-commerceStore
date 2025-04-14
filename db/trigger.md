-- =============================================
-- Trigger Definitions
-- =============================================
DELIMITER $$

-- 1) Order Creation Trigger
CREATE TRIGGER after_order_insert
AFTER INSERT ON orders
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id)
    VALUES (NEW.customer_id, 'Created Order', 'orders', NEW.order_id);
END $$

-- 2) Order Update Trigger
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
CREATE TRIGGER after_product_delete
AFTER DELETE ON products
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id)
    VALUES (NULL, 'Deleted Product', 'products', OLD.product_id);
END $$



-- 4) Exclusive Product Access Trigger
CREATE TRIGGER before_order_detail_insert
BEFORE INSERT ON order_details
FOR EACH ROW
BEGIN
    DECLARE product_exclusive BOOLEAN;
    DECLARE user_has_access BOOLEAN;

    SELECT is_exclusive INTO product_exclusive
    FROM products
    WHERE product_id = NEW.product_id;

    SELECT mt.can_access_exclusive INTO user_has_access
    FROM users u
    LEFT JOIN memberships m ON u.user_id = m.user_id
    LEFT JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id
    WHERE u.user_id = (
        SELECT customer_id 
        FROM orders 
        WHERE order_id = NEW.order_id
    );

    IF product_exclusive AND (user_has_access IS NULL OR NOT user_has_access) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Access denied: This product requires a valid membership';
    END IF;
END $$

DELIMITER ;

DELIMITER $$
CREATE TRIGGER after_order_status_update
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    -- Log status changes
    IF OLD.order_status <> NEW.order_status THEN
        INSERT INTO audit_logs (user_id, action, table_name, record_id)
        VALUES (NEW.customer_id, 
                CONCAT('Order Status Changed: ', OLD.order_status, ' → ', NEW.order_status),
                'orders', 
                NEW.order_id);
        
        -- Archive completed orders
        IF NEW.order_status = 'Completed' THEN
            INSERT INTO archived_orders (
                order_id, customer_id, order_date, shipping_address, order_status, total_price,
                delivery_method_id, discount, created_at, modified_at, viewed, estimated_delivery
            )
            VALUES (
                NEW.order_id, NEW.customer_id, NEW.order_date, NEW.shipping_address, 'Completed', NEW.total_price,
                NEW.delivery_method_id, NEW.discount, NEW.created_at, NEW.modified_at, NEW.viewed, NEW.estimated_delivery
            );
            
            -- Delete from orders table
            DELETE FROM orders WHERE order_id = NEW.order_id;
        END IF;
    END IF;
END $$
DELIMITER ;

-- AUTO ASSIGN FREE  MEMBERSHIP  TYPE
DELIMITER $$

CREATE TRIGGER after_user_insert
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    DECLARE free_membership_id INT;

    -- Get ID of 'Free' membership type
    SELECT membership_type_id INTO free_membership_id
    FROM membership_types
    WHERE type_name = 'Free'
    LIMIT 1;

    -- Insert a membership valid for 1 year
    INSERT INTO memberships (user_id, start_date, expiry_date, membership_type_id)
    VALUES (
        NEW.user_id,
        CURDATE(),
        DATE_ADD(CURDATE(), INTERVAL 1 YEAR),
        free_membership_id
    );
END $$

DELIMITER ;

-- =============================================
-- Audit Logs Trigger
-- =============================================

DELIMITER $$

-- User Account Activities
CREATE TRIGGER after_user_insert_audit
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, affected_data)
    VALUES (NEW.user_id, 'User account created', 'users', NEW.user_id, 'CREATE',
            JSON_OBJECT('email', NEW.email, 'role_id', NEW.role_id));
END $$

CREATE TRIGGER after_user_update_audit
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    DECLARE changes JSON;
    
    SET changes = JSON_OBJECT();
    
    IF OLD.name != NEW.name THEN
        SET changes = JSON_SET(changes, '$.name', JSON_OBJECT('old', OLD.name, 'new', NEW.name));
    END IF;
    
    IF OLD.email != NEW.email THEN
        SET changes = JSON_SET(changes, '$.email', JSON_OBJECT('old', OLD.email, 'new', NEW.email));
    END IF;
    
    IF OLD.role_id != NEW.role_id THEN
        SET changes = JSON_SET(changes, '$.role_id', JSON_OBJECT('old', OLD.role_id, 'new', NEW.role_id));
    END IF;
    
    IF OLD.is_active != NEW.is_active THEN
        SET changes = JSON_SET(changes, '$.is_active', JSON_OBJECT('old', OLD.is_active, 'new', NEW.is_active));
    END IF;
    
    IF JSON_LENGTH(changes) > 0 THEN
        INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, affected_data)
        VALUES (NEW.user_id, 'User account updated', 'users', NEW.user_id, 'UPDATE', changes);
    END IF;
END $$

-- Product Activities
CREATE TRIGGER after_product_insert_audit
AFTER INSERT ON products
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, affected_data)
    VALUES (NULL, CONCAT('Product created: ', NEW.product_name), 'products', NEW.product_id, 'CREATE',
            JSON_OBJECT('name', NEW.product_name, 'price', NEW.price, 'stock', NEW.stock));
END $$

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

-- Order Activities
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

-- Login Tracking
CREATE TRIGGER after_user_login_audit
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF OLD.last_login_at IS NULL AND NEW.last_login_at IS NOT NULL THEN
        INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type)
        VALUES (NEW.user_id, 'User logged in', 'users', NEW.user_id, 'LOGIN');
    END IF;
END $$

DELIMITER ;


DELIMITER $$
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
DELIMITER ;

