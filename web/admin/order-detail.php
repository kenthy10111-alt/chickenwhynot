<?php
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/db_helper.php';

require_admin();

$order_id = $_GET['id'] ?? null;

if (!$order_id) {
    header('Location: /web/admin/orders.php');
    exit;
}

// Get order details
$stmt = $conn->prepare("
  SELECT o.*, u.name, u.phone, u.email, u.address 
  FROM orders o 
  LEFT JOIN users u ON o.user_id = u.id 
  WHERE o.id = ?
");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    header('Location: /web/admin/orders.php');
    exit;
}

// Get order items
$items_stmt = $conn->prepare("
  SELECT oi.*, p.title as product_name 
  FROM order_items oi 
  LEFT JOIN products p ON oi.product_id = p.id 
  WHERE oi.order_id = ?
");
$items_stmt->bind_param('i', $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

// Get status history
$history = get_order_status_history($order_id);

// Handle status update
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['status'] ?? '';
    
    if (in_array($new_status, ['accepted', 'declined', 'pending'])) {
        $admin_id = $_SESSION['user_id'] ?? null;
        $res = update_order_status_admin($order_id, $new_status, $admin_id, 'Changed via order detail');
        if ($res['success']) {
            $message = "Order status updated to " . ucfirst($new_status) . "!";
            $message_type = 'success';
            $order['status'] = $new_status;
        } else {
            $message = $res['message'] ?? "Error updating order status.";
            $message_type = 'error';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Order #<?php echo htmlspecialchars($order['order_number']); ?> — Admin — CHICKEN WHY NOT?</title>
  <link rel="stylesheet" href="/web/css/admin.css">
</head>
<body>
  <div class="admin-container">
    <!-- Header -->
    <div class="admin-header">
      <h1>Order #<?php echo htmlspecialchars($order['order_number']); ?></h1>
      <div class="admin-header-actions">
        <a href="/web/admin/orders.php" class="btn btn-secondary btn-small">Back to Orders</a>
      </div>
    </div>

    <!-- Navigation -->
    <div class="admin-nav">
      <a href="/web/admin/">Dashboard</a>
      <a href="/web/admin/orders.php" class="active">All Orders</a>
      <a href="/web/">Back to Shop</a>
    </div>

    <!-- Message -->
    <?php if ($message): ?>
      <div class="alert alert-<?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <!-- Order Details -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
      <!-- Customer Info -->
      <div class="info-box">
        <h3 style="margin-top: 0; margin-bottom: 1rem;">Customer Information</h3>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($order['name'] ?? 'N/A'); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?></p>
        <p><strong>Address:</strong> <?php echo htmlspecialchars($order['address'] ?? 'N/A'); ?></p>
      </div>

      <!-- Order Info -->
      <div class="info-box">
        <h3 style="margin-top: 0; margin-bottom: 1rem;">Order Information</h3>
        <p><strong>Order Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
        <p><strong>Total Amount:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?></p>
        <p><strong>Status:</strong> 
          <span class="status-badge status-<?php echo htmlspecialchars($order['status']); ?>">
            <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
          </span>
        </p>
      </div>
    </div>

    <!-- Order Items -->
    <h2>Order Items</h2>
    <div class="admin-table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Product</th>
            <th>Quantity</th>
            <th>Price</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($item = $items_result->fetch_assoc()): ?>
            <tr>
              <td><?php echo htmlspecialchars($item['product_name'] ?? 'Unknown'); ?></td>
              <td><?php echo $item['quantity']; ?></td>
              <td>₱<?php echo number_format($item['unit_price'] ?? 0, 2); ?></td>
              <td>₱<?php echo number_format(($item['unit_price'] ?? 0) * $item['quantity'], 2); ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- Order status history -->
    <div class="info-box" style="margin-bottom: 1.5rem;">
      <h3 style="margin-top: 0;">Status History</h3>
      <?php if (!empty($history)): ?>
        <ul style="list-style: none; padding-left: 0; margin: 0;">
          <?php foreach ($history as $h): ?>
            <li style="padding: 0.5rem 0; border-bottom: 1px solid #eee;">
              <strong><?php echo htmlspecialchars($h['old_status']); ?></strong> → <strong><?php echo htmlspecialchars($h['new_status']); ?></strong>
              <div style="font-size: 0.9rem; color: #666;">By: <?php echo htmlspecialchars($h['changed_by_name'] ?? 'System'); ?> on <?php echo date('M d, Y H:i', strtotime($h['changed_at'])); ?></div>
              <?php if (!empty($h['notes'])): ?>
                <div style="font-size: 0.9rem; margin-top: 0.25rem;">Note: <?php echo htmlspecialchars($h['notes']); ?></div>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <div style="color: var(--text-light);">No status changes recorded yet.</div>
      <?php endif; ?>
    </div>

    <!-- Actions: allow changing status even after accepted/declined -->
    <div style="margin-top: 2rem; display: flex; gap: 1rem;">
      <form method="POST" style="flex: 1;">
        <input type="hidden" name="status" value="accepted">
        <button type="submit" class="btn btn-success" style="width: 100%; padding: 0.75rem;" <?php echo ($order['status'] === 'accepted') ? 'disabled' : ''; ?>>✓ Accept Order</button>
      </form>
      <form method="POST" style="flex: 1;">
        <input type="hidden" name="status" value="declined">
        <button type="submit" class="btn btn-danger" style="width: 100%; padding: 0.75rem;" <?php echo ($order['status'] === 'declined') ? 'disabled' : ''; ?>>✗ Decline Order</button>
      </form>
      <form method="POST" style="flex: 1;">
        <input type="hidden" name="status" value="pending">
        <button type="submit" class="btn btn-secondary" style="width: 100%; padding: 0.75rem;" <?php echo ($order['status'] === 'pending') ? 'disabled' : ''; ?>>↺ Set Pending</button>
      </form>
    </div>
  </div>
</body>
</html>
