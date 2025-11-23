# Admin Panel Setup Guide

## What Was Created

I've created a complete admin panel for managing orders with the following features:

1. **Admin Authentication** (`includes/admin_auth.php`)
   - Checks if logged-in user is an admin
   - Admin user IDs are configurable in the file (default: user ID 1)

2. **Admin Dashboard** (`admin/index.php`)
   - View order statistics (total, pending, accepted, declined)
   - See total revenue from accepted orders
   - View recent orders with quick access to details

3. **Orders Management** (`admin/orders.php`)
   - View all orders with filtering by status (pending, accepted, declined)
   - Quick view with customer and order details
   - Easy navigation and sorting

4. **Order Detail Page** (`admin/order-detail.php`)
   - Full order information with customer details
   - List of items in the order
   - **Accept or Decline order buttons** (only available for pending orders)
   - Status updates and confirmation

## How to Access Admin Panel

1. **Start WAMP/Apache** and ensure MySQL is running.

2. **Login to the site** at http://localhost/web/
   - Use credentials for user ID 1 (the first registered user or set yourself as admin)

3. **Access Admin Panel** at http://localhost/web/admin/
   - Only accessible if logged in with an admin account

## Setting Your Admin Account

### Option 1: Make User ID 1 the Admin (Default)
The admin panel is already configured to use User ID 1 as admin. If you registered as the first user, you're already an admin.

### Option 2: Change Admin User ID
Edit `includes/admin_auth.php` and change this line:
```php
define('ADMIN_USER_IDS', '1'); // Change 1 to your user ID
```

To add multiple admins, use comma-separated IDs:
```php
define('ADMIN_USER_IDS', '1,2,3'); // Multiple admin IDs
```

### Option 3: Add Admin Role to Database (Advanced)
If you want a database-driven admin system:

1. Run this SQL query in MySQL:
```sql
ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user';
UPDATE users SET role = 'admin' WHERE id = 1;
```

2. Update `includes/admin_auth.php` to check the role:
```php
function is_admin() {
    if (!is_logged_in()) {
        return false;
    }
    
    global $conn;
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    return $user && $user['role'] === 'admin';
}
```

## Features

### Dashboard
- View statistics at a glance
- Quick stats for all orders, pending, accepted, declined
- Total revenue from accepted orders
- Recent orders with status

### Orders Management
- Filter orders by status
- See all customer information
- View order details and items
- One-click accept or decline

### Order Actions
- **Accept Order**: Marks order as accepted (for processing)
- **Decline Order**: Marks order as declined (customer notification recommended)
- Status is locked once accepted/declined (can be changed again in database if needed)

## Database Tables Used
The admin panel uses these tables:
- `orders` - Main order data with status field
- `order_items` - Items in each order
- `users` - Customer/admin user information

## Next Steps

1. Start WAMP and navigate to http://localhost/web/admin/
2. Try accepting and declining orders
3. Monitor revenue and order statistics

## Tips
- Make sure your admin user account (ID 1 or configured ID) is created
- Orders show real-time status updates
- Revenue only counts "accepted" orders
- All order information is preserved even when status changes
