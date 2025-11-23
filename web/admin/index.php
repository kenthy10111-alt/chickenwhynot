<?php
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/admin_auth.php';

require_admin();

// Get order statistics
$stats = [];

// Total orders
$result = $conn->query("SELECT COUNT(*) as total FROM orders");
$row = $result->fetch_assoc();
$stats['total_orders'] = $row['total'];

// Pending orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
$row = $result->fetch_assoc();
$stats['pending_orders'] = $row['count'];

// Accepted orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'accepted'");
$row = $result->fetch_assoc();
$stats['accepted_orders'] = $row['count'];

// Declined orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'declined'");
$row = $result->fetch_assoc();
$stats['declined_orders'] = $row['count'];

// Total revenue (from accepted orders)
$result = $conn->query("SELECT SUM(total_amount) as revenue FROM orders WHERE status = 'accepted'");
$row = $result->fetch_assoc();
$stats['total_revenue'] = $row['revenue'] ?? 0;

// Get recent orders
$recent_orders = $conn->query("
  SELECT o.*, u.name, u.phone, u.email 
  FROM orders o 
  LEFT JOIN users u ON o.user_id = u.id 
  ORDER BY o.created_at DESC 
  LIMIT 10
");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Dashboard — CHICKEN WHY NOT?</title>
  <link rel="stylesheet" href="/web/css/admin.css">
</head>
<body>
  <div class="admin-container">
    <!-- Header -->
    <div class="admin-header">
      <h1>🐔 Admin Dashboard</h1>
      <div class="admin-header-actions">
        <div class="admin-header-user">
          Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
        </div>
        <a href="/web/auth/logout.php" class="btn btn-secondary btn-small">Logout</a>
      </div>
    </div>

    <!-- Navigation -->
    <div class="admin-nav">
      <a href="/web/admin/" class="active">Dashboard</a>
      <a href="/web/admin/orders.php">All Orders</a>
      <a href="/web/">Back to Shop</a>
    </div>

    <!-- Statistics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
      <div class="info-box">
        <strong style="color: var(--primary-coral); font-size: 1.5rem;"><?php echo $stats['total_orders']; ?></strong>
        <p>Total Orders</p>
      </div>
      <div class="info-box">
        <strong style="color: var(--yellow); font-size: 1.5rem;"><?php echo $stats['pending_orders']; ?></strong>
        <p>Pending Orders</p>
      </div>
      <div class="info-box">
        <strong style="color: var(--green); font-size: 1.5rem;"><?php echo $stats['accepted_orders']; ?></strong>
        <p>Accepted Orders</p>
      </div>
      <div class="info-box">
        <strong style="color: var(--red); font-size: 1.5rem;"><?php echo $stats['declined_orders']; ?></strong>
        <p>Declined Orders</p>
      </div>
      <div class="info-box">
        <strong style="color: var(--primary-coral); font-size: 1.5rem;">₱<?php echo number_format($stats['total_revenue'], 2); ?></strong>
        <p>Total Revenue</p>
      </div>
    </div>

    <!-- Recent Orders -->
    <h2>Recent Orders</h2>
    <div class="admin-table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Order #</th>
            <th>Customer</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($order = $recent_orders->fetch_assoc()): ?>
            <tr>
              <td><strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong></td>
              <td>
                <div><?php echo htmlspecialchars($order['name'] ?? 'N/A'); ?></div>
                <small style="color: var(--text-light);"><?php echo htmlspecialchars($order['phone'] ?? ''); ?></small>
              </td>
              <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
              <td>
                <span class="status-badge status-<?php echo htmlspecialchars($order['status']); ?>">
                  <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                </span>
              </td>
              <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
              <td>
                <div class="actions">
                  <a href="/web/admin/order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-primary btn-small">View</a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
