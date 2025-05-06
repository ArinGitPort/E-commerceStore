DELIMITER $$

CREATE TRIGGER after_membership_update_audit
AFTER UPDATE ON memberships
FOR EACH ROW
BEGIN
    -- Declare variables first, before any other statements
    DECLARE old_type_name VARCHAR(50);
    DECLARE new_type_name VARCHAR(50);
    
    -- Only log if membership type has changed
    IF OLD.membership_type_id != NEW.membership_type_id THEN
        -- Get the old and new membership type names for better logging
        SELECT type_name INTO old_type_name 
        FROM membership_types 
        WHERE membership_type_id = OLD.membership_type_id;
        
        SELECT type_name INTO new_type_name 
        FROM membership_types 
        WHERE membership_type_id = NEW.membership_type_id;
        
        INSERT INTO audit_logs (
            user_id, 
            action, 
            table_name, 
            record_id, 
            action_type, 
            affected_data
        )
        VALUES (
            NEW.user_id, 
            CONCAT('Membership upgraded from ', old_type_name, ' to ', new_type_name), 
            'memberships', 
            NEW.membership_id, 
            'UPDATE',
            JSON_OBJECT(
                'old_type', old_type_name,
                'new_type', new_type_name,
                'old_start_date', OLD.start_date,
                'new_start_date', NEW.start_date,
                'old_expiry_date', OLD.expiry_date,
                'new_expiry_date', NEW.expiry_date
            )
        );
    END IF;
END $$

DELIMITER ;

DELIMITER $$

CREATE TRIGGER after_user_role_update_audit
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    -- Declare variables first
    DECLARE old_role_name VARCHAR(50);
    DECLARE new_role_name VARCHAR(50);
    
    -- Only log if role has changed
    IF OLD.role_id != NEW.role_id THEN
        -- Get the old and new role names for better logging
        SELECT role_name INTO old_role_name 
        FROM roles 
        WHERE role_id = OLD.role_id;
        
        SELECT role_name INTO new_role_name 
        FROM roles 
        WHERE role_id = NEW.role_id;
        
        INSERT INTO audit_logs (
            user_id, 
            action, 
            table_name, 
            record_id, 
            action_type, 
            affected_data
        )
        VALUES (
            NEW.user_id, 
            CONCAT('User role changed from ', old_role_name, ' to ', new_role_name), 
            'users', 
            NEW.user_id, 
            'UPDATE',
            JSON_OBJECT(
                'old_role_id', OLD.role_id,
                'new_role_id', NEW.role_id,
                'old_role_name', old_role_name,
                'new_role_name', new_role_name,
                'changed_at', NOW()
            )
        );
    END IF;
END $$

DELIMITER ;

DELIMITER $$

CREATE TRIGGER after_user_activation_update_audit
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    -- Only log if activation status has changed
    IF OLD.is_active != NEW.is_active THEN
        INSERT INTO audit_logs (
            user_id, 
            action, 
            table_name, 
            record_id, 
            action_type, 
            affected_data
        )
        VALUES (
            NEW.user_id, 
            CASE 
                WHEN NEW.is_active = 1 THEN 'User account activated'
                ELSE 'User account deactivated'
            END, 
            'users', 
            NEW.user_id, 
            'UPDATE',
            JSON_OBJECT(
                'old_status', OLD.is_active,
                'new_status', NEW.is_active,
                'changed_at', NOW()
            )
        );
    END IF;
END $$

DELIMITER ;

DELIMITER $$
CREATE TRIGGER after_membership_expiry_update_audit
AFTER UPDATE ON memberships
FOR EACH ROW
BEGIN
    -- Declare variables first
    DECLARE membership_name VARCHAR(50);
    
    -- Log if expiry date has been extended
    IF OLD.expiry_date != NEW.expiry_date AND NEW.expiry_date > OLD.expiry_date THEN
        -- Get membership type name
        SELECT type_name INTO membership_name 
        FROM membership_types 
        WHERE membership_type_id = NEW.membership_type_id;
        
        INSERT INTO audit_logs (
            user_id, 
            action, 
            table_name, 
            record_id, 
            action_type, 
            affected_data
        )
        VALUES (
            NEW.user_id, 
            CONCAT('Membership "', membership_name, '" extended/renewed'), 
            'memberships', 
            NEW.membership_id, 
            'UPDATE',
            JSON_OBJECT(
                'membership_type', membership_name,
                'old_expiry_date', OLD.expiry_date,
                'new_expiry_date', NEW.expiry_date,
                'extended_by_days', DATEDIFF(NEW.expiry_date, OLD.expiry_date)
            )
        );
    END IF;
END $$
DELIMITER ;

DELIMITER $$

CREATE TRIGGER after_subscription_payment_audit
AFTER INSERT ON subscriptions_audit
FOR EACH ROW
BEGIN
    -- Declare variables first
    DECLARE membership_name VARCHAR(50);
    
    -- Get membership type name
    SELECT type_name INTO membership_name 
    FROM membership_types 
    WHERE membership_type_id = NEW.membership_type_id;
    
    INSERT INTO audit_logs (
        user_id, 
        action, 
        table_name, 
        record_id, 
        action_type, 
        affected_data
    )
    VALUES (
        NEW.user_id, 
        CONCAT('Payment for "', membership_name, '" subscription - ', 
               CAST(NEW.payment_amount AS CHAR), ' via ', NEW.payment_method), 
        'subscriptions_audit', 
        NEW.audit_id, 
        'CREATE',
        JSON_OBJECT(
            'membership_type', membership_name,
            'payment_amount', NEW.payment_amount,
            'payment_method', NEW.payment_method,
            'payment_status', NEW.payment_status,
            'reference_number', NEW.reference_number
        )
    );
END $$

DELIMITER ;

DELIMITER $$

CREATE TRIGGER after_role_insert_audit
AFTER INSERT ON roles
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (
        user_id, 
        action, 
        table_name, 
        record_id, 
        action_type
    )
    VALUES (
        NULL, -- System action or could be captured from application context
        CONCAT('New role created: ', NEW.role_name), 
        'roles', 
        NEW.role_id, 
        'CREATE'
    );
END $$

DELIMITER ;

DELIMITER $$

CREATE TRIGGER after_role_update_audit
AFTER UPDATE ON roles
FOR EACH ROW
BEGIN
    IF OLD.role_name != NEW.role_name THEN
        INSERT INTO audit_logs (
            user_id, 
            action, 
            table_name, 
            record_id, 
            action_type, 
            affected_data
        )
        VALUES (
            NULL, -- System action or could be captured from application context
            CONCAT('Role name changed from "', OLD.role_name, '" to "', NEW.role_name, '"'), 
            'roles', 
            NEW.role_id, 
            'UPDATE',
            JSON_OBJECT(
                'old_name', OLD.role_name,
                'new_name', NEW.role_name
            )
        );
    END IF;
END $$

DELIMITER ;

DELIMITER $$

CREATE TRIGGER after_membership_type_insert_audit
AFTER INSERT ON membership_types
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (
        user_id, 
        action, 
        table_name, 
        record_id, 
        action_type, 
        affected_data
    )
    VALUES (
        NULL, -- System action or could be captured from application context
        CONCAT('New membership type created: ', NEW.type_name), 
        'membership_types', 
        NEW.membership_type_id, 
        'CREATE',
        JSON_OBJECT(
            'type_name', NEW.type_name,
            'price', NEW.price,
            'can_access_exclusive', NEW.can_access_exclusive
        )
    );
END $$

DELIMITER ;

DELIMITER $$

CREATE TRIGGER after_membership_type_update_audit
AFTER UPDATE ON membership_types
FOR EACH ROW
BEGIN
    DECLARE changes JSON DEFAULT JSON_OBJECT();
    
    IF OLD.type_name != NEW.type_name THEN
        SET changes = JSON_SET(changes, '$.type_name', 
                           JSON_OBJECT('old', OLD.type_name, 'new', NEW.type_name));
    END IF;
    
    IF OLD.price != NEW.price THEN
        SET changes = JSON_SET(changes, '$.price', 
                           JSON_OBJECT('old', OLD.price, 'new', NEW.price));
    END IF;
    
    IF OLD.can_access_exclusive != NEW.can_access_exclusive THEN
        SET changes = JSON_SET(changes, '$.can_access_exclusive', 
                           JSON_OBJECT('old', OLD.can_access_exclusive, 'new', NEW.can_access_exclusive));
    END IF;
    
    IF JSON_LENGTH(changes) > 0 THEN
        INSERT INTO audit_logs (
            user_id, 
            action, 
            table_name, 
            record_id, 
            action_type, 
            affected_data
        )
        VALUES (
            NULL, -- System action or could be captured from application context
            CONCAT('Membership type updated: ', NEW.type_name), 
            'membership_types', 
            NEW.membership_type_id, 
            'UPDATE',
            changes
        );
    END IF;
END $$

DELIMITER ;