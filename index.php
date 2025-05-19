<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start(); ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Welcome to CleanMatch</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>

  <header>
    <div class="logo">CleanMatch</div>
    <nav>
      <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="contact.php">Contact</a></li>

        <?php if (isset($_SESSION['username'])): ?>
          <li><a href=<?php
            switch ($_SESSION['userType']) {
              case 'P': echo 'platformOwner.php'; break;
              case 'C': echo 'cleaner.php'; break;
              case 'U': echo 'homeOwner.php'; break;
            }
          ?>><?php echo htmlspecialchars($_SESSION['username']); ?></a></li>
            <li>
            <form action="logout.php" method="POST" style="display:inline;">
            <button type="submit" style="color: black; background: none; border: none; width: 100%; text-align: left;">
            Logout
            </button>

            </form>

            </li>

        <?php else: ?>
          <li><a href="login.php">Login</a></li>
          <li><a href="register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <div class="hero">
    <h1>Welcome to CleanMatch</h1>
    <h1>This is for CSCI314 Coursework Not A REAL CLEANING SERVICE</h1>
    <p>Your trusted platform for connecting homeowners with professional cleaners.</p>
    <a href="register.php" class="btn" style="margin-top: 1rem;">Get Started</a>
  </div>

  <section class="features container">
    <div class="feature-card">
      <h3>For Homeowners</h3>
      <p>Post your cleaning job and get matched with verified, reliable cleaners.</p>
    </div>
    <div class="feature-card">
      <h3>For Cleaners</h3>
      <p>Browse jobs and grow your business by helping local clients.</p>
    </div>
    <div class="feature-card">
      <h3>Admin Panel</h3>
      <p>Monitor job activity, manage users, and ensure service quality.</p>
    </div>
  </section>

  <footer>
    <div class="footer-content">
      <div class="footer-column">
        <h3>CleanMatch</h3>
        <p>Connecting homeowners with professional cleaners since 2025.</p>
      </div>
      <div class="footer-column">
        <h3>Quick Links</h3>
        <ul>
          <li><a href="index.php">Home</a></li>
          <li><a href="about.php">About</a></li>
          <li><a href="contact.php">Contact</a></li>
        </ul>
      </div>
    </div>
    <div class="copyright">
      <p>&copy; 2025 CleanMatch. All rights reserved.</p>
    </div>
  </footer>

</body>
</html>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 20px;
    background: #f4f6f8;
    color: #333;
  }
  h1 {
    color: #4a4a4a;
    margin-bottom: 0.5rem;
  }
  form {
    margin-bottom: 20px;
  }
  input[type="text"] {
    padding: 8px 12px;
    font-size: 1rem;
    width: 250px;
    border: 1px solid #ccc;
    border-radius: 6px;
  }
  button {
    padding: 8px 16px;
    font-size: 1rem;
    background-color: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    margin-left: 8px;
    transition: background-color 0.3s ease;
  }
  button:hover {
    background-color: #5a67d8;
  }
  table {
    border-collapse: collapse;
    width: 100%;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }
  th, td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
  }
  th {
    background-color: #667eea;
    color: white;
  }
  tr:hover {
    background-color: #f9fafb;
  }
  .no-results {
    text-align: center;
    padding: 20px;
    color: #777;
  }


</style>