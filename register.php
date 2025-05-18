<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Map full userType to letter
    $inputType = $_POST['userType'];
    switch ($inputType) {
        case 'PM': $userType = 'P'; break;
        case 'Cleaner': $userType = 'C'; break;
        case 'Homeowner': $userType = 'U'; break;
        default:
            echo "❌ Invalid user type selected.";
            exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, userType) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password, $userType]);
        echo "✅ User registered successfully! <a href='login.php'>Login here</a>";

        // Clear input values after success
        $username = $email = $inputType = '';
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate entry')) {
            echo "❌ Username or email already exists.";
        } else {
            echo "❌ Registration failed: " . $e->getMessage();
        }
    }
} else {
    $username = $email = $inputType = ''; // Set default empty values
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Register</title>
<link rel="stylesheet" href="style.css"/>
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
   

<!-- Registration form -->
<div class="register-page">
  <div class="register-box">
    <form method="POST" autocomplete="off">

      <h2>Register</h2>

      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" required />
      </div>

      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required />
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required />
      </div>

      <div class="form-group">
        <label>User Type</label>
        <select name="userType" required>
          <option value="">Select Role</option>
          <option value="PM">PM</option>
          <option value="Cleaner">Cleaner</option>
          <option value="Homeowner">Homeowner</option>
        </select>
      </div>

      <button type="submit" class="btn">Register</button>
    </form>
  </div>
</div>



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
