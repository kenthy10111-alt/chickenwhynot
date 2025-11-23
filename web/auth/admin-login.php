<?php
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/auth.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$identifier) $errors[] = 'Phone or email is required.';
    if (!$password) $errors[] = 'Password is required.';

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE (phone = ? OR email = ?) AND is_admin = 1 LIMIT 1");
        $stmt->bind_param('ss', $identifier, $identifier);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows === 0) {
            $errors[] = 'Admin account not found.';
        } else {
            $user = $res->fetch_assoc();
            if (empty($user['password']) || !password_verify($password, $user['password'])) {
                $errors[] = 'Invalid credentials.';
            } else {
                // Login as admin
                login_user($user);
                header('Location: /web/admin/');
                exit;
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Login — CHICKEN WHY NOT?</title>
  <link rel="stylesheet" href="/web/css/auth.css">
</head>
<body>
  <div class="auth-container">
    <!-- Left Panel -->
    <div class="auth-left">
      <div class="auth-left-content">
        <img src="/web/LOGO/Gemini_Generated_Image_n3a5yen3a5yen3a5-removebg-preview.png" alt="CHICKEN WHY NOT?" class="auth-logo-watermark">
      </div>
    </div>

    <!-- Right Panel -->
    <div class="auth-right">
      <div class="auth-form-wrapper">
        <div class="auth-form-header">
          <h2>Admin Login</h2>
          <a href="/web/auth/login.php" class="auth-form-toggle">Regular Login</a>
        </div>

        <?php if (!empty($errors)): ?>
          <div class="auth-errors">
            <?php foreach ($errors as $e): ?>
              <div><?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['registered']) && $_GET['registered'] === '1'): ?>
          <div style="background:#d4edda;color:#155724;padding:1rem;border-radius:6px;margin-bottom:1rem;border-left:4px solid #28a745;font-size:0.9rem;">
            ✓ Admin account created successfully! Please log in below.
          </div>
        <?php endif; ?>

        <form method="post" novalidate>
          <label>
            Email or Phone
            <input type="text" name="identifier" value="<?php echo htmlspecialchars($identifier ?? ''); ?>" required>
          </label>

          <label>
            Password
            <input type="password" name="password" required>
          </label>

          <button type="submit" class="auth-button">Sign In as Admin</button>
        </form>

        <div class="auth-footer">
          By signing in, you agree to our <a href="#">Terms & Conditions</a> and <a href="#">Privacy Policy</a>.
        </div>

        <div class="auth-link">
          Not an admin? <a href="/web/auth/login.php">Login as customer</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
