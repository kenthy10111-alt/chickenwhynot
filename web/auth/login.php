<?php
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/auth.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? ''); // phone or email
    $password = $_POST['password'] ?? '';
    $login_as_admin = isset($_POST['login_as_admin']) ? 1 : 0;

    if (!$identifier) $errors[] = 'Phone or email is required.';
    if (!$password) $errors[] = 'Password is required.';

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE phone = ? OR email = ? LIMIT 1");
        $stmt->bind_param('ss', $identifier, $identifier);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            $errors[] = 'Account not found.';
        } else {
            $user = $res->fetch_assoc();
            if (empty($user['password']) || !password_verify($password, $user['password'])) {
                $errors[] = 'Invalid credentials.';
            } else {
                // Check if trying to login as admin
                if ($login_as_admin) {
                    // Check if is_admin column exists
                    $checkCol = $conn->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
                    $has_is_admin = $checkCol->num_rows > 0;
                    
                    if (!$has_is_admin || $user['is_admin'] != 1) {
                        $errors[] = 'You do not have admin privileges.';
                    } else {
                        // Login as admin
                        login_user($user);
                        header('Location: /web/admin/');
                        exit;
                    }
                } else {
                    // Regular login
                    login_user($user);
                    header('Location: /web/');
                    exit;
                }
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
  <title>Login — CHICKEN WHY NOT?</title>
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
          <h2>Sign In</h2>
          <a href="/web/auth/register.php" class="auth-form-toggle">Create Account</a>
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
            ✓ Account created successfully! Please log in below.
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

          <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem;">
            <input type="checkbox" name="login_as_admin" style="width: 18px; height: 18px; cursor: pointer;">
            <span>Login as Admin</span>
          </label>

          <button type="submit" class="auth-button">Sign In</button>
        </form>

        <div class="auth-footer">
          By signing in, you agree to our <a href="#">Terms & Conditions</a> and <a href="#">Privacy Policy</a>.
        </div>

        <div class="auth-link">
          Don't have an account? <a href="/web/auth/register.php">Create one here</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>