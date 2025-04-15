UPDATE products
SET is_exclusive = TRUE, min_membership_level = 2
WHERE product_id = 1;

UPDATE users
SET role_id = 4
WHERE user_id = 5;

UPDATE memberships
SET membership_type_id = 3  -- 3 = VIP (based on your insert order)
WHERE user_id = 4;

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