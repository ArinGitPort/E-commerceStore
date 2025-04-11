## DOCUMENTATION.MD

## ADMIN PAGE

# Includes
- session-init - verify users
- sidebar - display sidebar
- db_connection
- admin_session - not in use

# Login/Logout/Register
- login.php - normal login
- login-google - login through google no validation at the moment
- logout - destroys session no pop-up on admin
- Register - register users php mailer required
- forgot-password - resets password php mailer required

# Account Management
- account-management - main page file
- get_user.php - fetches users and display
- toggle_user_status - deactivate/activate not working at the moment

# Order Management
- order-management - main page file for order-management
- get_new_orders - fetches new orders from users
- get_order_details - fetch and display user info and order details
- set_delivery_date - set order delivery date
- update_order_status - updates pending, cancelled, completed etc.
- complete_order.php - remove order and audit

## USER PAGE

# Includes
- session-init - verify users
- sidebar - display sidebar
- db_connection

#  
