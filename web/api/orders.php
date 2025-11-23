<?php
/**
 * Order Management API
 * Handles checkout and order processing
 */

header('Content-Type: application/json');
require_once '../includes/db_config.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// ============================================
// CREATE ORDER
// ============================================
if ($method === 'POST' && $action === 'create_order') {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = $conn->real_escape_string($data['name'] ?? '');
    $address = $conn->real_escape_string($data['address'] ?? '');
    $phone = $conn->real_escape_string($data['phone'] ?? '');
    $cart_items = $data['cart_items'] ?? [];
    $total_amount = $data['total_amount'] ?? 0;
    
    // Validate input
    if (empty($name) || empty($address) || empty($phone) || empty($cart_items)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Create or get user
        $user_query = "INSERT INTO users (name, email, phone, address) VALUES (?, ?, ?, ?)
                       ON DUPLICATE KEY UPDATE address = VALUES(address)";
        $stmt = $conn->prepare($user_query);
        $email = '';  // Initialize email variable
        $stmt->bind_param('ssss', $name, $email, $phone, $address);
        $stmt->execute();
        $user_id = $stmt->insert_id;
        
        // Generate order number
        $order_number = 'ORD-' . date('YmdHis') . '-' . rand(1000, 9999);
        
        // Create order
        $order_query = "INSERT INTO orders (order_number, user_id, total_amount, status) 
                        VALUES (?, ?, ?, 'pending')";
        $stmt = $conn->prepare($order_query);
        $stmt->bind_param('sid', $order_number, $user_id, $total_amount);
        $stmt->execute();
        $order_id = $stmt->insert_id;
        
        // Insert order items
        foreach ($cart_items as $item) {
            $item_query = "INSERT INTO order_items (order_id, product_id, variant_id, quantity, unit_price, line_total) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($item_query);
            $line_total = $item['price'] * $item['qty'];
            $product_id = 1; // Default to first product; adjust based on your logic
            $stmt->bind_param('iisidi', $order_id, $product_id, $item['id'], $item['qty'], $item['price'], $line_total);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Order created successfully',
            'order_id' => $order_id,
            'order_number' => $order_number
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error creating order: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// GET ORDER DETAILS
// ============================================
if ($method === 'GET' && $action === 'get_order') {
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    
    if ($order_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit;
    }
    
    // Get order
    $query = "SELECT o.*, u.name, u.phone, u.address FROM orders o
              JOIN users u ON o.user_id = u.id WHERE o.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    // Get order items
    $items_query = "SELECT oi.*, p.title FROM order_items oi
                    JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
    $stmt = $conn->prepare($items_query);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $order['items'] = $items;
    
    echo json_encode(['success' => true, 'data' => $order]);
    exit;
}

// ============================================
// GET ALL PRODUCTS
// ============================================
if ($method === 'GET' && $action === 'get_products') {
    $query = "SELECT * FROM products";
    $result = $conn->query($query);
    $products = $result->fetch_all(MYSQLI_ASSOC);
    
    // Get variants for each product
    foreach ($products as &$product) {
        $variants_query = "SELECT * FROM product_variants WHERE product_id = ?";
        $stmt = $conn->prepare($variants_query);
        $stmt->bind_param('i', $product['id']);
        $stmt->execute();
        $product['variants'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    echo json_encode(['success' => true, 'data' => $products]);
    exit;
}

// ============================================
// UPDATE ORDER STATUS (Admin)
// ============================================
if ($method === 'PUT' && $action === 'update_order_status') {
    $data = json_decode(file_get_contents('php://input'), true);
    $order_id = intval($data['order_id'] ?? 0);
    $status = $conn->real_escape_string($data['status'] ?? '');
    
    if ($order_id <= 0 || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }
    
    $query = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('si', $status, $order_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Order status updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update order']);
    }
    exit;
}

// Default response
echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
