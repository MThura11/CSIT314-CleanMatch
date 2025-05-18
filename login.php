<?php
session_start();
require 'db.php';

class UserAuth {
    private $pdo;

    public function __construct($host, $db, $user, $pass) {
        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];
        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            die("DB Connection failed: " . $e->getMessage());
        }
    }

    public function login($username, $password) {
        $stmt = $this->pdo->prepare("SELECT userId, username, password, userType FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['userid'] = $user['userId'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['userType'] = $user['userType'];
            return $user['userType'];
        }
        return false;
    }

    public function isCleanerExists($cleanerId) {
        $stmt = $this->pdo->prepare("SELECT 1 FROM homecleaners WHERE homeCleanerID = ?");
        $stmt->execute([$cleanerId]);
        return (bool)$stmt->fetchColumn();
    }
}

$auth = new UserAuth($dbHost, $dbName, $dbUser , $dbPass);

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uname = $_POST['username'] ?? '';
    $pwd = $_POST['password'] ?? '';

    $userType = $auth->login($uname, $pwd);

    if ($userType !== false) {
        switch ($userType) {
            case 'C':
                // Check if the cleaner exists in the database
                if ($auth->isCleanerExists($_SESSION['userid'])) {
                    header("Location: cleaner.php");
                } else {
                    header("Location: cleanerDetail.php");
                }
                exit();
            case 'A':
                header("Location: admin.php");
                exit();
            case 'U':
                header("Location: homeOwner.php");
                exit();
            case 'P':
                header("Location: platformOwner.php");
                exit();
            default:
                $error = "Invalid user type.";
                session_destroy();
        }
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Login</title>
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
              case 'A': echo 'admin.php'; break;
              case 'C': echo 'cleaner.php'; break;
              case 'U': echo 'homeowner.php'; break;
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
    
  <div class="login-container" role="main">
    <h2>Login</h2>
    <?php if ($error): ?>
      <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" novalidate>
      <input type="text" name="username" placeholder="Username" autocomplete="username" required autofocus />
      <input type="password" name="password" placeholder="Password" autocomplete="current-password" required />
      <button type="submit">Log In</button>
    </form>
    <div class="footer">Enter your credentials to access your account.</div>
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
  input{
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