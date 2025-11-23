<?php
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/auth.php';

$errors = [];
$success = false;

// Ensure password column exists (if someone already imported the old schema without password)
$checkCol = $conn->query("SHOW COLUMNS FROM users LIKE 'password'");
if ($checkCol->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN password VARCHAR(255) NULL AFTER email");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    if (!$name) $errors[] = 'Name is required.';
    if (!$phone) $errors[] = 'Phone is required.';
    if (!$password || strlen($password) < 6) $errors[] = 'Password required (min 6 chars).';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    // Check if phone or email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ? OR (email != '' AND email = ?) LIMIT 1");
        $stmt->bind_param('ss', $phone, $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $errors[] = 'An account with this phone or email already exists.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Check if is_admin column exists, if not add it
        $checkCol = $conn->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
        if ($checkCol->num_rows === 0) {
            $conn->query("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0");
        }
        
        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, address, password, is_admin) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssi', $name, $email, $phone, $address, $hash, $is_admin);
        if ($stmt->execute()) {
            // After successful registration, redirect based on admin checkbox
            if ($is_admin) {
                header('Location: /web/auth/admin-login.php?registered=1');
            } else {
                header('Location: /web/auth/login.php?registered=1');
            }
            exit;
        } else {
            $errors[] = 'Failed to create account. Try again.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Register — CHICKEN WHY NOT?</title>
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
          <h2>Create Account</h2>
          <a href="/web/auth/login.php" class="auth-form-toggle">Sign In</a>
        </div>

        <?php if (!empty($errors)): ?>
          <div class="auth-errors">
            <?php foreach ($errors as $e): ?>
              <div><?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post" novalidate>
          <label>
            Name
            <input type="text" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
          </label>

          <label>
            Email
            <input type="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>">
          </label>

          <label>
            Phone
            <input type="tel" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>" required>
          </label>

          <label>
            Address
            <input type="text" name="address" value="<?php echo htmlspecialchars($address ?? ''); ?>">
          </label>

          <label>
            Password
            <input type="password" name="password" required>
          </label>

          <label>
            Confirm Password
            <input type="password" name="confirm" required>
          </label>

          <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem;">
            <input type="checkbox" name="is_admin" style="width: 18px; height: 18px; cursor: pointer;">
            <span>Register as Admin</span>
          </label>

          <button type="submit" class="auth-button">Sign Up</button>
        </form>

        <div class="auth-footer">
          By signing up, you agree to our <a href="#">Terms & Conditions</a> and <a href="#">Privacy Policy</a>.
        </div>

        <div class="auth-link">
          Already have an account? <a href="/web/auth/login.php">Sign in here</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>