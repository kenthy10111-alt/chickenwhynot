<?php
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/db_helper.php';

require_admin();

// Filter by status
$status_filter = $_GET['status'] ?? 'all';
$valid_statuses = ['all', 'pending', 'accepted', 'declined'];
if (!in_array($status_filter, $valid_statuses)) {
    $status_filter = 'all';
}

// Handle status change actions from the orders list (Accept / Decline / Set Pending)
$success_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['new_status'])) {
  $order_id = (int) $_POST['order_id'];
  $new_status = $_POST['new_status'];
  if (!in_array($new_status, ['pending', 'accepted', 'declined'])) {
    $error_message = 'Invalid status.';
  } else {
    // record who changed it (admin)
    $admin_id = $_SESSION['user_id'] ?? null;
    $res = update_order_status_admin($order_id, $new_status, $admin_id, 'Changed via orders list');
    if ($res['success']) {
      // redirect back to avoid re-submission
      header('Location: ' . $_SERVER['REQUEST_URI']);
      exit;
    } else {
      $error_message = $res['message'] ?? 'Failed to update order status.';
    }
  }
}

// Use helper to fetch orders with optional filter
$error_message = '';
$filter_status = $status_filter === 'all' ? null : $status_filter;
$result = get_orders($filter_status, null, 0, 200);
if ($result === null) {
    $error_message = "Database Error: unable to fetch orders.";
    $orders_list = [];
} else {
    $orders_list = $result['orders'];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Orders — Admin Panel — CHICKEN WHY NOT?</title>
  <link rel="stylesheet" href="/web/css/admin.css">
</head>
<body>
  <div class="admin-container">
    <!-- Header -->
    <div class="admin-header">
      <h1>📦 Orders Management</h1>
      <div class="admin-header-actions">
        <a href="/web/auth/logout.php" class="btn btn-secondary btn-small">Logout</a>
      </div>
    </div>

    <!-- Navigation -->
    <div class="admin-nav">
      <a href="/web/admin/">Dashboard</a>
      <a href="/web/admin/orders.php" class="active">All Orders</a>
      <a href="/web/">Back to Shop</a>
    </div>

    <!-- Filters -->
      <!-- Error Message -->
      <?php if ($error_message): ?>
        <div class="alert alert-error" style="margin-bottom: 2rem;">
          <strong>Error:</strong> <?php echo $error_message; ?><br>
          <small>Make sure the database is imported and the orders table exists.</small>
        </div>
      <?php endif; ?>

    <div style="background: white; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; display: flex; gap: 1rem;">
      <a href="/web/admin/orders.php?status=all" class="btn <?php echo $status_filter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">
        All Orders
      </a>
      <a href="/web/admin/orders.php?status=pending" class="btn <?php echo $status_filter === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">
        Pending
      </a>
      <a href="/web/admin/orders.php?status=accepted" class="btn <?php echo $status_filter === 'accepted' ? 'btn-primary' : 'btn-secondary'; ?>">
        Accepted
      </a>
      <a href="/web/admin/orders.php?status=declined" class="btn <?php echo $status_filter === 'declined' ? 'btn-primary' : 'btn-secondary'; ?>">
        Declined
      </a>
    </div>

    <!-- Orders Table -->
    <div class="admin-table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Order #</th>
            <th>Customer</th>
            <th>Phone</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
            <?php if (!$error_message && empty($orders_list)): ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-light);">
                No orders found.
              </td>
            </tr>
            <?php elseif (!$error_message && !empty($orders_list)): ?>
              <?php foreach ($orders_list as $order): ?>
              <tr>
                  <td><strong>#<?php echo htmlspecialchars($order['order_number'] ?? 'N/A'); ?></strong></td>
                <td><?php echo htmlspecialchars($order['name'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?></td>
                  <td>₱<?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                <td>
                    <span class="status-badge status-<?php echo htmlspecialchars($order['status'] ?? 'pending'); ?>">
                      <?php echo ucfirst(htmlspecialchars($order['status'] ?? 'pending')); ?>
                  </span>
                </td>
                  <td><?php echo isset($order['created_at']) ? date('M d, Y H:i', strtotime($order['created_at'])) : 'N/A'; ?></td>
                <td>
                  <div class="actions">
                    <a href="/web/admin/order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-primary btn-small">View</a>
                      <?php if (($order['status'] ?? 'pending') !== 'accepted'): ?>
                        <form method="post" style="display:inline;margin:0 0 0 0.4rem;">
                          <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                          <input type="hidden" name="new_status" value="accepted">
                          <button type="submit" class="btn btn-success btn-small">Accept</button>
                        </form>
                      <?php endif; ?>
                      <?php if (($order['status'] ?? 'pending') !== 'declined'): ?>
                        <form method="post" style="display:inline;margin:0 0 0 0.4rem;">
                          <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                          <input type="hidden" name="new_status" value="declined">
                          <button type="submit" class="btn btn-danger btn-small">Decline</button>
                        </form>
                      <?php endif; ?>
                      <?php if (($order['status'] ?? 'pending') !== 'pending'): ?>
                        <form method="post" style="display:inline;margin:0 0 0 0.4rem;">
                          <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                          <input type="hidden" name="new_status" value="pending">
                          <button type="submit" class="btn btn-secondary btn-small">Set Pending</button>
                        </form>
                      <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php elseif ($error_message): ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 2rem; color: var(--red);">
                Unable to load orders. Please check database connection.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    
    <!-- Help Section -->
    <?php if (empty($orders_list)): ?>
      <div style="background: var(--white); padding: 2rem; border-radius: 8px; margin-top: 2rem; border-left: 4px solid var(--primary-coral);">
        <h3>Setup Instructions</h3>
        <p>To view orders, follow these steps:</p>
        <ol>
          <li>Make sure MySQL is running in WAMP</li>
          <li>Import the database: <code>database/kenth_eggs.sql</code> into MySQL</li>
          <li>Go to the shop and place some test orders: <a href="/web/" style="color: var(--primary-coral);">Click here</a></li>
          <li>Refresh this page to see your orders</li>
        </ol>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
