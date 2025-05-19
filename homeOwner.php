<?php
session_start();
require 'db.php';
class CleanerManager {
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

    public function searchCleaners(string $term): array {
        $sql = "SELECT c.homeCleanerID, c.fullName, c.location, c.experienceYears, c.availability,
                       AVG(o.rating) AS avgRating, COUNT(o.rating) AS ratingCount
                FROM homeCleaners c
                LEFT JOIN orders o ON c.homeCleanerID = o.homeCleanerID AND o.status = 'completed'  -- Only include completed orders
                WHERE c.fullName LIKE :term OR c.location LIKE :term
                GROUP BY c.homeCleanerID
                ORDER BY c.fullName";
        $stmt = $this->pdo->prepare($sql);
        $likeTerm = "%$term%";
        $stmt->bindParam(':term', $likeTerm, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllCleaners(): array {
        $sql = "SELECT c.homeCleanerID, c.fullName, c.location, c.experienceYears, c.availability,
                       AVG(o.rating) AS avgRating, COUNT(o.rating) AS ratingCount
                FROM homeCleaners c
                LEFT JOIN orders o ON c.homeCleanerID = o.homeCleanerID AND o.status = 'completed'  -- Only include completed orders
                GROUP BY c.homeCleanerID
                ORDER BY c.fullName";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addFavorite(int $userId, int $cleanerId): bool {
        $sql = "INSERT INTO favorites (userId, homeCleanerID) VALUES (:userId, :cleanerId)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['userId' => $userId, 'cleanerId' => $cleanerId]);
    }

    public function isFavorite(int $userId, int $cleanerId): bool {
        $sql = "SELECT 1 FROM favorites WHERE userId = :userId AND homeCleanerID = :cleanerId LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['userId' => $userId, 'cleanerId' => $cleanerId]);
        return (bool)$stmt->fetchColumn();
    }

    public function getPendingOrders(int $userId): array {
        $sql = "SELECT o.*, c.fullName AS cleanerName
                FROM orders o
                JOIN homeCleaners c ON o.homeCleanerID = c.homeCleanerID
                WHERE o.homeOwnerId = :userId AND o.status != 'completed'
                ORDER BY o.orderDate DESC, o.startTime DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['userid'];



$manager = new CleanerManager($dbHost, $dbName, $dbUser , $dbPass);

$search = $_GET['search'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_favorite'])) {
    $cleanerId = intval($_POST['cleaner_id']);
    if (!$manager->isFavorite($userId, $cleanerId)) {
        $manager->addFavorite($userId, $cleanerId);
        $message = "Cleaner added to your favorites.";
    } else {
        $message = "Cleaner is already in your favorites.";
    }
}

if (strlen(trim($search)) > 0) {
    $cleaners = $manager->searchCleaners($search);
} else {
    $cleaners = $manager->getAllCleaners();
}

$pendingOrders = $manager->getPendingOrders($userId);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Cleaners List</title>
<link rel="stylesheet" href="style.css" />
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 20px;
    background: #f4f6f8;
    color: #333;
    position: relative;
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
  button, .view-btn {
    padding: 8px 16px;
    font-size: 1rem;
    background-color: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    margin-left: 8px;
    transition: background-color 0.3s ease;
    text-decoration: none;
    display: inline-block;
    text-align: center;
  }
  button:hover, .view-btn:hover {
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
  .rating {
    font-weight: bold;
    color: #f6c90e;
  }
  .message {
    padding: 12px;
    background-color: #def1d8;
    color: #27632a;
    border: 1px solid #74b06f;
    margin-bottom: 20px;
    border-radius: 6px;
    width: fit-content;
  }
  /* Top right favorites button */
  .favorites-button {
    position: absolute;
    top: 0;
    right: 0;
    margin: 20px;
    background-color: #48bb78; /* green */
    border-radius: 6px;
  }
  .favorites-button:hover {
    background-color: #38a169;
  }
  .favorites-button {
  /* Remove these if they exist */
  position: static;
  float: none;

  /* Add desired layout styles */
  display: inline-block;
  margin: 0 auto;
  padding: 8px 16px;
  background-color: #3498db;
  color: white;
  border-radius: 6px;
  text-decoration: none;
}

</style>
</head>
<body>

  <header>
    <div class="logo">CleanMatch</div>
    <nav>
      <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="contact.php">Contact</a></li>
        <li><a href="homeOwnerOrderHistory.php">Order History</a></li>

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





  <h1>Cleaner List</h1>
  <div style="text-align: right; margin-bottom: 1rem;">
  <a href="favorites.php" class="favorites-button view-btn">My Favorites</a>
</div>
  
  
  <form method="get">
    <input 
      type="text" 
      name="search" 
      placeholder="Search by name or location" 
      value="<?php echo htmlspecialchars($search); ?>"
      aria-label="Search cleaners by name or location"
    />
    <button type="submit">Search</button>
  </form>
          
  <?php if ($message): ?>
    <div class="message"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <?php if (count($cleaners) > 0): ?>
  <table>
    <thead>
      <tr>
        <th>Full Name</th>
        <th>Location</th>
        <th>Years of Experience</th>
        <th>Availability</th>
        <th>Rating</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($cleaners as $cleaner): ?>
      <tr>
        <td><?php echo htmlspecialchars($cleaner['fullName']); ?></td>
        <td><?php echo htmlspecialchars($cleaner['location']); ?></td>
        <td><?php echo htmlspecialchars($cleaner['experienceYears']); ?></td>
        <td><?php echo htmlspecialchars($cleaner['availability']); ?></td>
        <td class="rating">
          <?php 
          if ($cleaner['ratingCount'] > 0) {
              echo round($cleaner['avgRating'], 2) . ' / 5 (' . $cleaner['ratingCount'] . ' ratings)';
          } else {
              echo 'No ratings';
          }
          ?>
        </td>
        <td>
          <form method="post" style="display:inline;">
              <input type="hidden" name="add_favorite" value="1">
              <input type="hidden" name="cleaner_id" value="<?php echo htmlspecialchars($cleaner['homeCleanerID']); ?>">
              <button type="submit" class="view-btn" <?php echo $manager->isFavorite($userId, $cleaner['homeCleanerID']) ? 'disabled' : ''; ?>>
                  <?php echo $manager->isFavorite($userId, $cleaner['homeCleanerID']) ? 'Favorited' : 'Add to Favorites'; ?>
              </button>
          </form>
          <a class="view-btn" href="viewCleaner.php?id=<?php echo urlencode($cleaner['homeCleanerID']); ?>">View</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
  <div class="no-results">No cleaners found matching your search.</div>
  <?php endif; ?>
  
  <h2 class="section-title">Pending Orders</h2>
  <?php if (count($pendingOrders) > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Cleaner Name</th>
          <th>Service</th>
          <th>Order Date</th>
          <th>Start Time</th>
          <th>Duration (hours)</th>
          <th>Total Price</th>
          <th>Payment Method</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pendingOrders as $order): ?>
          <tr>
            <td><?php echo htmlspecialchars($order['orderId']); ?></td>
            <td><?php echo htmlspecialchars($order['cleanerName']); ?></td>
            <td><?php echo htmlspecialchars($order['serviceName']); ?></td>
            <td><?php echo htmlspecialchars($order['orderDate']); ?></td>
            <td><?php echo htmlspecialchars($order['startTime']); ?></td>
            <td><?php echo htmlspecialchars($order['durationHours']); ?></td>
            <td>$<?php echo number_format((float)$order['totalPrice'], 2); ?></td>
            <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $order['paymentMethod']))); ?></td>
            <td><?php echo htmlspecialchars($order['status']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="no-results">You have no pending orders.</div>
  <?php endif; ?>

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


