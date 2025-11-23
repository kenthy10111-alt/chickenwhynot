<?php
require_once __DIR__ . '/includes/auth.php';
?>
<!doctype html>
<html lang="en">
  <head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>CHICKEN WHY NOT?</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/shop.css">
  <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
  </head>
  <body>
  <header class="site-header">
    <div class="container header-row">
  <a class="brand" href="#">
    <img src="/web/LOGO/Gemini_Generated_Image_n3a5yen3a5yen3a5-removebg-preview.png" alt="CHICKEN WHY NOT? logo" style="height:88px;vertical-align:middle;margin-right:15px;border-radius:6px;">
    <span>CHICKEN WHY NOT?</span>
  </a>
    <nav class="main-nav">
      <a href="#products">Products</a>
      <a href="#about">About</a>
      <a href="#contact">Contact</a>
    </nav>
    <div class="header-actions" style="margin-left:auto;display:flex;align-items:center;gap:0.6rem;">
  <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
    <a href="/web/auth/login.php" onclick="logout()" class="btn" style="text-decoration:none;padding:.45rem .9rem;">Logout</a>
  <?php else: ?>
    <a href="/web/auth/login.php" class="btn" style="text-decoration:none;padding:.45rem .9rem;">Login</a>
  <?php endif; ?>
      <div class="cart-wrap">
        <button id="cartBtn" class="cart-btn" aria-label="View cart">
          <i class="fas fa-shopping-cart"></i>
          <span id="cartCount" class="cart-count">0</span>
        </button>
      </div>
    </div>
    </div>
  </header>

  <main>
    <section class="hero">
    <div class="container hero-inner">
      <div>
      <h1>Fresh eggs, delivered locally</h1>
      <p>We sell high-quality farm eggs — free-range, organic, and duck eggs. Buy by the dozen or in bulk. Fast local pickup.</p>
      <div class="hero-actions">
        <a href="#products" class="btn">Shop Eggs</a>
        <a href="#about" class="btn btn-outline">Learn More</a>
      </div>
      </div>
          <div class="hero-image">
            <img src="farm/istockphoto-540736440-612x612 (1).jpg" alt="Hero eggs image" style="width: 100%; max-width: 600px; height: auto; border-radius: 8px;">
          </div>
    </div>
    </section>

    <section id="products" class="products container">
    <h2 class="section-title">Our Eggs</h2>
    <div id="productsGrid" class="products-grid">
      <!-- products rendered by JS -->
    </div>
    </section>

    <section id="about" class="about container">
    <h2 class="section-title">About Our Farm</h2>
    <p>Family-run farm focused on animal welfare and sustainable practices. Our hens are raised on pastures with fresh feed and clean water. We collect and pack daily to ensure freshness.</p>
    </section>

    <section id="contact" class="contact container">
    <h2 class="section-title">Contact & Pickup</h2>
    <p>Email: hello@kentheggs.local • Phone: 09120706881</p>
    <p>Pickup available at 123 Farm Lane, open Mon–Sat 9am–4pm.</p>
    </section>
  </main>

  <!-- Cart drawer -->
  <aside id="cartDrawer" class="cart-drawer" aria-hidden="true">
    <div class="cart-header">
    <h3>Your Cart</h3>
    <button id="closeCart" class="close-btn" aria-label="Close cart">×</button>
    </div>
    <div id="cartItems" class="cart-items">
    <!-- cart items injected by JS -->
    </div>
    <div class="cart-footer">
    <div class="cart-total">Total: P<span id="cartTotal">0.00</span></div>
    <button id="checkoutBtn" class="btn">Checkout</button>
    </div>
  </aside>

  <!-- Checkout modal (demo) -->
  <div id="checkoutModal" class="modal" aria-hidden="true">
    <div class="modal-content">
    <button id="closeModal" class="close-btn">×</button>
    <div id="checkoutForm-wrapper">
      <h3>Checkout</h3>
      <form id="checkoutForm">
        <label>Name<input type="text" name="name" required></label>
        <label>Address<input type="text" name="address" required></label>
        <label>Phone<input type="tel" name="phone" required></label>
        <button type="submit" class="btn btn-checkout">Place Order</button>
      </form>
    </div>
    <div id="successMessage" class="success-message" style="display:none;text-align:center;padding:2rem;">
      <h3 style="color:#06b6d4;margin-bottom:1rem;">✓ Thank you for your order!</h3>
      <p style="color:#8b95a6;margin-bottom:1.5rem;">Your order has been received. We'll process it shortly and contact you with pickup details.</p>
      <button id="successClose" class="btn btn-checkout" style="background:#06b6d4;">Close</button>
    </div>
    </div>
  </div>

  <footer class="site-footer">
      <div class="container">
        <p>© <span id="year"></span> CHICKEN WHY NOT? — Fresh local eggs</p>
      </div>
  </footer>

  <script src="js/shop.js" defer></script>
  <script type="module">
  // Import the functions you need from the SDKs you need
  import { initializeApp } from "https://www.gstatic.com/firebasejs/12.6.0/firebase-app.js";
  import { getAnalytics } from "https://www.gstatic.com/firebasejs/12.6.0/firebase-analytics.js";
  // TODO: Add SDKs for Firebase products that you want to use
  // https://firebase.google.com/docs/web/setup#available-libraries

  // Your web app's Firebase configuration
  // For Firebase JS SDK v7.20.0 and later, measurementId is optional
  const firebaseConfig = {
    apiKey: "AIzaSyAxzu6qPNqQr4FSvB-qfMblsq7JC1Iqriw",
    authDomain: "chickenwhynot-5dd74.firebaseapp.com",
    projectId: "chickenwhynot-5dd74",
    storageBucket: "chickenwhynot-5dd74.firebasestorage.app",
    messagingSenderId: "641568929032",
    appId: "1:641568929032:web:b129e5f579907caf8968bd",
    measurementId: "G-XNC0CNDG7V"
  };

  // Initialize Firebase
  const app = initializeApp(firebaseConfig);
  const analytics = getAnalytics(app);
</script>
  </body>
</html>
