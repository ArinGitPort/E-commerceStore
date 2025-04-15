-- =============================================
-- User-Related Queries
-- =============================================

-- 1. Get all users with role names
SELECT u.user_id, u.name, u.email, r.role_name, u.created_at
FROM users u
JOIN roles r ON u.role_id = r.role_id;

select * from users;

DELETE FROM users WHERE user_id = 6;


-- 2. Get active users with membership info
SELECT u.user_id, u.name, u.email, mt.type_name, m.start_date, m.expiry_date
FROM users u
JOIN memberships m ON u.user_id = m.user_id
JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id
WHERE u.is_active = 1;

-- 3. Find users with expiring memberships (within 30 days)
SELECT u.user_id, u.name, u.email, m.expiry_date
FROM users u
JOIN memberships m ON u.user_id = m.user_id
WHERE m.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY);

-- =============================================
-- Product & Inventory Queries
-- =============================================

-- 4. Get all products with category info
SELECT p.product_id, p.product_name, c.category_name, p.price, p.stock
FROM products p
JOIN categories c ON p.category_id = c.category_id;

-- 5. Get exclusive products with membership requirements
SELECT p.product_id, p.product_name, mt.type_name AS required_membership
FROM products p
JOIN membership_types mt ON p.min_membership_level = mt.membership_type_id
WHERE p.is_exclusive = 1;

-- 6. Low stock alert (stock < 20)
SELECT product_id, product_name, stock
FROM products
WHERE stock < 20
ORDER BY stock ASC;

-- =============================================
-- Order & Sales Analytics
-- =============================================

-- 7. Get complete order details
SELECT o.order_id, u.name AS customer, o.order_date, 
       SUM(od.quantity * od.total_price) AS total,
       o.order_status
FROM orders o
JOIN order_details od ON o.order_id = od.order_id
JOIN users u ON o.customer_id = u.user_id
GROUP BY o.order_id;

-- 8. Monthly sales report
SELECT DATE_FORMAT(order_date, '%Y-%m') AS month,
       COUNT(*) AS total_orders,
       SUM(total_price) AS total_sales
FROM orders
GROUP BY DATE_FORMAT(order_date, '%Y-%m')
ORDER BY month DESC;

-- 9. Most popular products
SELECT p.product_id, p.product_name,
       SUM(od.quantity) AS total_sold,
       SUM(od.quantity * od.total_price) AS total_revenue
FROM order_details od
JOIN products p ON od.product_id = p.product_id
GROUP BY p.product_id
ORDER BY total_sold DESC
LIMIT 10;

-- =============================================
-- Membership & Discount Queries
-- =============================================

-- 10. Active premium members
SELECT u.user_id, u.name, u.email, m.expiry_date
FROM users u
JOIN memberships m ON u.user_id = m.user_id
JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id
WHERE mt.type_name IN ('Premium', 'VIP')
  AND m.expiry_date > CURDATE();

-- 11. Membership discounts overview
SELECT mt.type_name AS membership_type,
       c.category_name,
       md.discount_rate
FROM membership_discounts md
JOIN membership_types mt ON md.membership_type_id = mt.membership_type_id
LEFT JOIN categories c ON md.category_id = c.category_id;

-- =============================================
-- Audit & Notification Queries
-- =============================================

-- 12. Recent audit logs with user info
SELECT a.timestamp, u.name AS user, a.action, a.table_name, a.record_id
FROM audit_logs a
LEFT JOIN users u ON a.user_id = u.user_id
ORDER BY a.timestamp DESC
LIMIT 100;

-- 13. Active notifications with targets
SELECT n.title, n.message, 
       GROUP_CONCAT(mt.type_name) AS target_memberships,
       n.start_date, n.expiry_date
FROM notifications n
LEFT JOIN notification_membership_targets nmt ON n.notification_id = nmt.notification_id
LEFT JOIN membership_types mt ON nmt.membership_type_id = mt.membership_type_id
WHERE n.is_active = 1
  AND CURDATE() BETWEEN n.start_date AND n.expiry_date
GROUP BY n.notification_id;

-- =============================================
-- Cart & User Activity
-- =============================================

-- 14. Active shopping carts
SELECT u.name, u.email, 
       COUNT(ci.product_id) AS items_in_cart,
       SUM(ci.quantity * p.price) AS cart_total
FROM cart_items ci
JOIN users u ON ci.user_id = u.user_id
JOIN products p ON ci.product_id = p.product_id
GROUP BY u.user_id;

-- 15. User purchase history
SELECT u.user_id, u.name,
       COUNT(o.order_id) AS total_orders,
       SUM(o.total_price) AS lifetime_value
FROM users u
LEFT JOIN orders o ON u.user_id = o.customer_id
GROUP BY u.user_id;

-- =============================================
-- Advanced Analytical Queries
-- =============================================

-- 16. Customer retention analysis
SELECT YEAR(first_order) AS cohort_year,
       MONTH(first_order) AS cohort_month,
       COUNT(DISTINCT user_id) AS total_customers,
       COUNT(DISTINCT CASE WHEN order_count >= 2 THEN user_id END) AS retained_customers
FROM (
    SELECT u.user_id,
           MIN(o.order_date) AS first_order,
           COUNT(o.order_id) AS order_count
    FROM users u
    LEFT JOIN orders o ON u.user_id = o.customer_id
    GROUP BY u.user_id
) AS customer_stats
GROUP BY YEAR(first_order), MONTH(first_order);

-- 17. Product category performance
SELECT c.category_name,
       COUNT(DISTINCT o.order_id) AS orders_count,
       SUM(od.quantity) AS units_sold,
       SUM(od.quantity * od.total_price) AS total_revenue
FROM categories c
JOIN products p ON c.category_id = p.category_id
JOIN order_details od ON p.product_id = od.product_id
JOIN orders o ON od.order_id = o.order_id
GROUP BY c.category_id
ORDER BY total_revenue DESC;

-- 18. Membership effectiveness analysis
SELECT mt.type_name AS membership_type,
       COUNT(DISTINCT o.customer_id) AS total_customers,
       AVG(o.total_price) AS avg_order_value,
       SUM(o.total_price) AS total_revenue
FROM memberships m
JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id
JOIN orders o ON m.user_id = o.customer_id
GROUP BY mt.type_name;