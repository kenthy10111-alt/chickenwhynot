<?php
/**
 * Database Helper Functions
 * Reusable functions for common database operations
 */

require_once 'db_config.php';

// ============================================
// USER FUNCTIONS
// ============================================

/**
 * Get or create user
 */
function get_or_create_user($name, $phone, $address) {
    global $conn;
    
    $query = "SELECT id FROM users WHERE phone = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id'];
    }
    
    // Create new user
    $email = '';
    $insert_query = "INSERT INTO users (name, email, phone, address) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param('ssss', $name, $email, $phone, $address);
    $stmt->execute();
    
    return $conn->insert_id;
}

// ============================================
// PRODUCT FUNCTIONS
// ============================================

/**
 * Get all products with variants
 */
function get_all_products() {
    global $conn;
    
    $query = "SELECT * FROM products";
    $result = $conn->query($query);
    $products = $result->fetch_all(MYSQLI_ASSOC);
    
    foreach ($products as &$product) {
        $variants_query = "SELECT * FROM product_variants WHERE product_id = ?";
        $stmt = $conn->prepare($variants_query);
        $stmt->bind_param('i', $product['id']);
        $stmt->execute();
        $product['variants'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    return $products;
}

/**
 * Get product by ID
 */
function get_product($product_id) {
    global $conn;
    
    $query = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if ($product) {
        $variants_query = "SELECT * FROM product_variants WHERE product_id = ?";
        $stmt = $conn->prepare($variants_query);
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $product['variants'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    return $product;
}

// ============================================
// ORDER FUNCTIONS
// ============================================

/**
 * Create a new order
 */
function create_order($user_id, $total_amount, $items) {
    global $conn;
    
    try {
        $conn->begin_transaction();
        
        // Generate unique order number
        $order_number = 'ORD-' . date('YmdHis') . '-' . rand(10000, 99999);
        
        // Create order
        $order_query = "INSERT INTO orders (order_number, user_id, total_amount, status) 
                        VALUES (?, ?, ?, 'pending')";
        $stmt = $conn->prepare($order_query);
        $stmt->bind_param('sid', $order_number, $user_id, $total_amount);
        $stmt->execute();
        $order_id = $conn->insert_id;
        
        // Insert order items
        foreach ($items as $item) {
            $item_query = "INSERT INTO order_items (order_id, product_id, variant_id, quantity, unit_price, line_total) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($item_query);
            $product_id = 1; // Adjust based on your logic
            $line_total = $item['price'] * $item['qty'];
            $stmt->bind_param('iisidi', $order_id, $product_id, $item['id'], $item['qty'], $item['price'], $line_total);
            $stmt->execute();
        }
        
        $conn->commit();
        
        return [
            'success' => true,
            'order_id' => $order_id,
            'order_number' => $order_number
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        return [
            'success' => false,
            'message' => 'Error creating order: ' . $e->getMessage()
        ];
    }
}

/**
 * Get order details
 */
function get_order($order_id) {
    global $conn;
    
    $query = "SELECT o.*, u.name, u.phone, u.address FROM orders o
              JOIN users u ON o.user_id = u.id WHERE o.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    if (!$order) {
        return null;
    }
    
    // Get order items
    $items_query = "SELECT oi.*, p.title FROM order_items oi
                    JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
    $stmt = $conn->prepare($items_query);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $order['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return $order;
}

/**
 * Update order status
 */
function update_order_status($order_id, $status) {
    // Backwards-compatible wrapper: no changed_by provided
    return update_order_status_admin($order_id, $status, null, null);
}

/**
 * Update order status (admin-aware)
 * - updates the orders table
 * - inserts an explicit row into order_status_log with changed_by when provided
 * Uses a transaction to ensure both actions succeed.
 */
function update_order_status_admin($order_id, $new_status, $changed_by = null, $notes = null) {
    global $conn;

    $valid_statuses = ['pending', 'accepted', 'declined'];

    if (!in_array($new_status, $valid_statuses)) {
        return ['success' => false, 'message' => 'Invalid status'];
    }

    try {
        $conn->begin_transaction();

        // Get current status
        $select = "SELECT status FROM orders WHERE id = ? FOR UPDATE";
        $stmt = $conn->prepare($select);
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            $conn->rollback();
            return ['success' => false, 'message' => 'Order not found'];
        }
        $row = $res->fetch_assoc();
        $old_status = $row['status'];

        // Update orders table
        $update = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($update);
        $stmt->bind_param('si', $new_status, $order_id);
        if (!$stmt->execute()) {
            $conn->rollback();
            return ['success' => false, 'message' => 'Failed to update order status'];
        }

        // Insert into order_status_log with changed_by if available
        $insert = "INSERT INTO order_status_log (order_id, old_status, new_status, changed_by, notes, changed_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert);
        // For changed_by, allow NULL
        if ($changed_by === null) {
            $null = null;
            $stmt->bind_param('issis', $order_id, $old_status, $new_status, $null, $notes);
        } else {
            $stmt->bind_param('issis', $order_id, $old_status, $new_status, $changed_by, $notes);
        }
        if (!$stmt->execute()) {
            $conn->rollback();
            return ['success' => false, 'message' => 'Failed to record order status change'];
        }

        $conn->commit();
        return ['success' => true, 'message' => 'Order status updated', 'order_id' => $order_id];

    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Get all orders (for admin)
 */
function get_all_orders($limit = 50) {
    // Simple wrapper: fetch first $limit orders
    return get_orders(null, null, 0, $limit);
}

/**
 * Get orders with optional filters for admin (supports pagination and search)
 * @param string|null $status 'pending'|'accepted'|'declined' or null for all
 * @param string|null $search text to search order_number or customer name/phone
 * @param int $offset
 * @param int $limit
 * @return array ['total'=>int, 'orders'=>array]
 */
function get_orders($status = null, $search = null, $offset = 0, $limit = 50) {
    global $conn;

    $where = [];
    $types = '';
    $params = [];

    if ($status && in_array($status, ['pending','accepted','declined'])) {
        $where[] = 'o.status = ?';
        $types .= 's';
        $params[] = $status;
    }

    if ($search) {
        $where[] = '(o.order_number LIKE ? OR u.name LIKE ? OR u.phone LIKE ?)';
        $types .= 'sss';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $where_sql = '';
    if (count($where) > 0) {
        $where_sql = 'WHERE ' . implode(' AND ', $where);
    }

    // Count total
    $count_sql = "SELECT COUNT(*) as cnt FROM orders o JOIN users u ON o.user_id = u.id $where_sql";
    $count_stmt = $conn->prepare($count_sql);
    if ($types) {
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $total = $count_stmt->get_result()->fetch_assoc()['cnt'] ?? 0;

    // Fetch rows
    $sql = "SELECT o.*, u.name, u.phone, u.email FROM orders o JOIN users u ON o.user_id = u.id $where_sql ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    // bind params + offset/limit
    if ($types) {
        // dynamic bind: params..., limit, offset
        $bind_params = $params;
        // limit and offset as integers
        $bind_params[] = $limit;
        $bind_params[] = $offset;
        $bind_types = $types . 'ii';
        $stmt->bind_param($bind_types, ...$bind_params);
    } else {
        $stmt->bind_param('ii', $limit, $offset);
    }
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    return ['total' => (int)$total, 'orders' => $orders];
}

/**
 * Get order status history (audit log)
 */
function get_order_status_history($order_id) {
    global $conn;

    $sql = "SELECT l.*, u.name AS changed_by_name FROM order_status_log l LEFT JOIN users u ON l.changed_by = u.id WHERE l.order_id = ? ORDER BY l.changed_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ============================================
// STOCK FUNCTIONS
// ============================================

/**
 * Update product variant stock
 */
function update_stock($variant_id, $quantity) {
    global $conn;
    
    $query = "UPDATE product_variants SET stock = stock - ? WHERE variant_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('is', $quantity, $variant_id);
    
    return $stmt->execute();
}

/**
 * Check stock availability
 */
function check_stock($variant_id, $quantity) {
    global $conn;
    
    $query = "SELECT stock FROM product_variants WHERE variant_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $variant_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result && $result['stock'] >= $quantity;
}

?>
