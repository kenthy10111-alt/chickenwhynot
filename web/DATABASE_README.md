# CHICKEN WHY NOT? - Database Setup Guide

## Database Overview

The CHICKEN WHY NOT? egg marketplace uses a MySQL/MariaDB database with the following structure:

### Tables:
1. **users** - Customer information
2. **products** - Product catalog (free-range, organic, bulk)
3. **product_variants** - Product sizes and pricing (Small, Medium, Large, XL, XXL)
4. **orders** - Customer orders
5. **order_items** - Individual items in each order
6. **cart_sessions** - (Optional) Persistent cart storage
7. **payments** - (Optional) Payment transaction records

---

## Setup Instructions

### Step 1: Create the Database

1. Open phpMyAdmin (usually at `http://localhost/phpmyadmin`)
2. Click "New" in the left sidebar
3. Enter database name: `kenth_eggs`
4. Click "Create"

Alternatively, use MySQL command line:
```bash
mysql -u root -p < c:\wamp64\www\web\database\kenth_eggs.sql
```

### Step 2: Import the SQL Schema

1. Go to `http://localhost/phpmyadmin`
2. Select the `kenth_eggs` database
3. Click the "Import" tab
4. Choose file: `c:\wamp64\www\web\database\kenth_eggs.sql`
5. Click "Go"

Or from command line:
```bash
mysql -u root kenth_eggs < c:\wamp64\www\web\database\kenth_eggs.sql
```

### Step 3: Verify Configuration

1. Open `c:\wamp64\www\web\includes\db_config.php`
2. Update database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Your WAMP MySQL password
   define('DB_NAME', 'kenth_eggs');
   ```

### Step 4: Test the Connection

Visit: `http://localhost/web/api/orders.php?action=get_products`

You should see a JSON response with all products and variants.

---

## Database API Endpoints

### Create Order (Checkout)
```
POST /api/orders.php?action=create_order
Body (JSON):
{
  "name": "John Doe",
  "address": "123 Farm Lane",
  "phone": "09120706881",
  "total_amount": 19.97,
  "cart_items": [
    {
      "id": "free-medium",
      "price": 5.99,
      "qty": 3
    }
  ]
}
```

Response:
```json
{
  "success": true,
  "message": "Order created successfully",
  "order_id": 1,
  "order_number": "ORD-20231214120530-5847"
}
```

### Get Order Details
```
GET /api/orders.php?action=get_order&order_id=1
```

Response includes order information and items.

### Get All Products
```
GET /api/orders.php?action=get_products
```

Returns all products with variants, pricing, and images.

### Update Order Status (Admin)
```
PUT /api/orders.php?action=update_order_status
Body (JSON):
{
  "order_id": 1,
  "status": "processing"
}
```

Valid statuses: `pending`, `confirmed`, `processing`, `ready_for_pickup`, `completed`, `cancelled`

---

## Sample Data

The database is pre-populated with:
- **3 Products**: Free-range, Organic, Bulk Dozen
- **10 Product Variants**: 5 sizes for Free-range, 5 sizes for Organic
- **Pre-configured stock levels** for each variant

---

## Connecting the Frontend

To integrate the checkout with the database, update `js/shop.js`:

```javascript
// After form submission, send to API:
fetch('/api/orders.php?action=create_order', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    name: formData.name,
    address: formData.address,
    phone: formData.phone,
    total_amount: totalPrice,
    cart_items: cartItems
  })
})
.then(r => r.json())
.then(data => {
  if (data.success) {
    console.log('Order created:', data.order_number);
    // Clear cart and show success
  }
});
```

---

## Database Maintenance

### Backup Database
```bash
mysqldump -u root kenth_eggs > backup_kenth_eggs.sql
```

### Restore Database
```bash
mysql -u root kenth_eggs < backup_kenth_eggs.sql
```

### View Orders in phpMyAdmin
1. Go to `http://localhost/phpmyadmin`
2. Select `kenth_eggs` → `orders`
3. You'll see all customer orders with statuses

---

## Notes

- The database uses `auto_increment` for IDs
- Timestamps are automatically set on creation and update
- Foreign keys ensure data integrity
- Indexes optimize query performance
- Images are stored as URLs (relative paths)

For production, add encryption for sensitive data and implement proper user authentication!
