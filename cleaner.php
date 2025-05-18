<?php
session_start();
require 'db.php';

if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['userid'];





try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}





// Fetch distinct order types
$stmt = $conn->prepare("
    SELECT * FROM orders 
    WHERE status = 'pending'
");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);



//get homeCleanerID
$stmt = $pdo->prepare("SELECT homeCleanerID FROM homecleaners WHERE userId = ?");
$stmt->execute([$userId]);
$cleaner = $stmt->fetch(PDO::FETCH_ASSOC);

$homeCleanerID = $cleaner['homeCleanerID'];
// Handle "Accept" POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_order_id'])) {
    $orderId = $_POST['accept_order_id'];

    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = 'accepted', homeCleanerID = ?
        WHERE orderId = ? AND status = 'pending'
    ");
    $stmt->execute([$homeCleanerID, $orderId]);
}


$pendingFilter = $_GET['pending_service'] ?? 'all';
// Get distinct service names from pending orders
$stmt = $pdo->query("
    SELECT DISTINCT serviceName 
    FROM orders 
    WHERE status = 'pending' AND (homeCleanerID IS NULL OR homeCleanerID = 0)
      AND serviceName IS NOT NULL AND serviceName != ''
");
$pendingServices = $stmt->fetchAll(PDO::FETCH_COLUMN);
//get pending orders
if ($pendingFilter === 'all') {
    $stmt = $pdo->prepare("
        SELECT * FROM orders 
        WHERE status = 'pending' AND (homeCleanerID IS NULL OR homeCleanerID = 0)
    ");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("
        SELECT * FROM orders 
        WHERE status = 'pending' AND (homeCleanerID IS NULL OR homeCleanerID = 0)
          AND serviceName = ?
    ");
    $stmt->execute([$pendingFilter]);
}
$pendingOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Example display
//foreach ($orders as $order) {
//    echo "<p>Order ID: {$order['orderId']} - Status: {$order['status']}</p>";
//}

// Handle Complete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order_id'])) {
    $orderId = $_POST['complete_order_id'];

    $stmt = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE orderId = ? AND homeCleanerID = ?");
    $stmt->execute([$orderId, $homeCleanerID]);
    // ✅ Redirect to refresh the page
    header("Location: cleaner.php");
    exit;
}

// Handle Decline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decline_order_id'])) {
    $orderId = $_POST['decline_order_id'];

    //$stmt = $pdo->prepare("UPDATE orders SET status = 'pending', homeCleanerID = 0 WHERE orderId = ? AND homeCleanerID = ?");
    $stmt = $pdo->prepare("
    UPDATE orders 
    SET status = 'pending', homeCleanerID = NULL 
    WHERE orderId = ? AND homeCleanerID = ?");
    
    $stmt->execute([$orderId, $homeCleanerID]);

    // ✅ Redirect to refresh the page
    header("Location: cleaner.php");
    exit;
}





//ORDER MANAGER CLASS
class OrderManager {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    //Get AVG RATING
    public function getAverageRating(int $cleanerId): ?float {
        $stmt = $this->pdo->prepare("
            SELECT AVG(rating) FROM orders 
            WHERE status = 'completed' AND homeCleanerID = ?
        ");
        $stmt->execute([$cleanerId]);
        $avg = $stmt->fetchColumn();
        return $avg ? (float)$avg : null;
    }


    // ✅ Count orders by status
    public function countByStatus(string $status, int $homeCleanerID): int {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM orders 
            WHERE status = ? AND homeCleanerID = ?
        ");
        $stmt->execute([$status, $homeCleanerID]);
        return (int) $stmt->fetchColumn();
    }

    // ✅ Get completed orders (with optional service filter)
    public function getCompletedOrders(int $homeCleanerID, string $serviceFilter = 'all'): array {
        if ($serviceFilter === 'all') {
            $stmt = $this->pdo->prepare("
                SELECT * FROM orders 
                WHERE status = 'completed' AND homeCleanerID = ?
                ORDER BY orderDate DESC
            ");
            $stmt->execute([$homeCleanerID]);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT * FROM orders 
                WHERE status = 'completed' AND homeCleanerID = ? AND serviceName = ?
                ORDER BY orderDate DESC
            ");
            $stmt->execute([$homeCleanerID, $serviceFilter]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ Get all unique service names from orders
    public function getServiceNames(): array {
        $stmt = $this->pdo->query("
            SELECT DISTINCT serviceName 
            FROM orders 
            WHERE serviceName IS NOT NULL AND serviceName != ''
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}


//COMPLETED ORDER
$orderManager = new OrderManager($pdo);

// Get filter value
$completedFilter = $_GET['completed_service'] ?? 'all';

// Get list of services
$services = $orderManager->getServiceNames();

// Get completed orders based on filter
$completedOrders = $orderManager->getCompletedOrders($homeCleanerID, $completedFilter);




//get accepted orders

$stmt = $pdo->prepare("SELECT * FROM orders WHERE homeCleanerID = ? AND status = 'accepted'");
$stmt->execute([$homeCleanerID]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);




//count completed
/*
$stmt = $pdo->prepare("
  SELECT COUNT(*) FROM orders 
  WHERE status = 'completed' AND homeCleanerID = ?
");
$stmt->execute([$homeCleanerID]);
$completedCount = $stmt->fetchColumn();

//count accepted
$stmt = $pdo->prepare("
  SELECT COUNT(*) FROM orders 
  WHERE status = 'accepted' AND homeCleanerID = ?
");
$stmt->execute([$homeCleanerID]);
$acceptedCount = $stmt->fetchColumn();
*/

//COUNTING COMPLETED AND ACCEPTED



$orderManager = new OrderManager($pdo);

$completedCount = $orderManager->countByStatus('completed', $homeCleanerID);
$acceptedCount  = $orderManager->countByStatus('accepted', $homeCleanerID);




$orderManager = new OrderManager($pdo);
$avgRating = $orderManager->getAverageRating($homeCleanerID);



?>





<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Cleaners List</title>
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


<h1>Cleaner's  Page Placeholder</h1>
<form method="GET" action="cleanerDetail.php">
  
  <button type="submit">View My Details</button>
</form>

<h3>📦 Your Order Summary</h3>
<ul>
  <li>✅ Completed Orders: <?= $completedCount ?></li>
  <li>🟡 Accepted Orders: <?= $acceptedCount ?></li>
</ul>
<h3>⭐ My Average Rating</h3>
<p>
    <?= $avgRating ? number_format($avgRating, 2) . ' / 5' : 'No ratings yet.' ?>
</p>

<h2>Orders</h2>

<h2>Pending Orders</h2>

<form method="GET" style="margin-bottom: 1rem;">
  <label for="pending_service">Filter Pending Orders by Service:</label>
  <select name="pending_service" id="pending_service" onchange="this.form.submit()">
    <option value="all">All Services</option>
    <?php foreach ($pendingServices as $service): ?>
      <option value="<?= htmlspecialchars($service) ?>" <?= $pendingFilter === $service ? 'selected' : '' ?>>
        <?= htmlspecialchars($service) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <!-- Preserve other filters (optional) -->
  <input type="hidden" name="accepted_service" value="<?= htmlspecialchars($_GET['accepted_service'] ?? 'all') ?>">
  <input type="hidden" name="completed_service" value="<?= htmlspecialchars($_GET['completed_service'] ?? 'all') ?>">
</form>



<?php if (count($pendingOrders) > 0): ?>
  <table border="1" cellpadding="8" cellspacing="0">
    <tr>
      <th>Order ID</th>
      <th>Date</th>
      <th>Start Time</th>
      <th>Duration</th>
      <th>Service</th>
      <th>Price</th>
      <th>Address</th>
      <th>Action</th>
    </tr>
    <?php foreach ($pendingOrders as $order): ?>
      <tr>
        <td><?= $order['orderId'] ?></td>
        <td><?= $order['orderDate'] ?></td>
        <td><?= $order['startTime'] ?></td>
        <td><?= $order['durationHours'] ?> hrs</td>
        <td><?= htmlspecialchars($order['serviceName']) ?></td>
        <td>$<?= number_format($order['totalPrice'], 2) ?></td>
        <td><?= htmlspecialchars($order['userAddress']) ?></td>
        <td>
          <form method="POST">
            <input type="hidden" name="accept_order_id" value="<?= $order['orderId'] ?>">
            <button type="submit">Accept</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php else: ?>
  <p>No pending orders available.</p>
<?php endif; ?>





    
    
      <h2>Accepted Orders</h2>





  <?php if (count($orders) > 0): ?>
    <table border="1" cellpadding="8" cellspacing="0">
      <tr>
        <th>Order ID</th>
        <th>Date</th>
        <th>Start Time</th>
        <th>Duration</th>
        <th>Service</th>
        <th>Price</th>
        <th>Address</th>
        <th>Action</th>
      </tr>
      <?php foreach ($orders as $order): ?>
        <tr>
          <td><?= $order['orderId'] ?></td>
          <td><?= $order['orderDate'] ?></td>
          <td><?= $order['startTime'] ?></td>
          <td><?= $order['durationHours'] ?> hrs</td>
          <td><?= htmlspecialchars($order['serviceName']) ?></td>
          <td>$<?= number_format($order['totalPrice'], 2) ?></td>
          <td><?= htmlspecialchars($order['userAddress']) ?></td>
          <td>
          <form method="POST" style="display: inline;">
            <input type="hidden" name="complete_order_id" value="<?= $order['orderId'] ?>">
            <button type="submit">✅ Complete</button>
          </form>
          <form method="POST" style="display: inline;">
            <input type="hidden" name="decline_order_id" value="<?= $order['orderId'] ?>">
            <button type="submit" onclick="return confirm('Are you sure you want to decline this order?')">❌ Decline</button>
          </form>
        </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p>No accepted orders yet.</p>
  <?php endif; ?>

    <h2>Completed Orders</h2>
    
    <form method="GET" style="margin-bottom: 1rem;">
      <label for="completed_service">Filter Completed by Service:</label>
      <select name="completed_service" id="completed_service" onchange="this.form.submit()">
        <option value="all">All Services</option>
        <?php foreach ($services as $service): ?>
          <option value="<?= htmlspecialchars($service) ?>" <?= $completedFilter === $service ? 'selected' : '' ?>>
            <?= htmlspecialchars($service) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <!-- Preserve accepted_service in the URL -->
      <input type="hidden" name="accepted_service" value="<?= htmlspecialchars($acceptedFilter) ?>">
    </form>




    
    <?php if (count($completedOrders) > 0): ?>
      <table border="1" cellpadding="8" cellspacing="0">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Date</th>
            <th>Start Time</th>
            <th>Duration</th>
            <th>Service</th>
            <th>Price</th>
            <th>Address</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($completedOrders as $order): ?>
            <tr>
              <td><?= $order['orderId'] ?></td>
              <td><?= $order['orderDate'] ?></td>
              <td><?= $order['startTime'] ?></td>
              <td><?= $order['durationHours'] ?> hrs</td>
              <td><?= htmlspecialchars($order['serviceName']) ?></td>
              <td>$<?= number_format($order['totalPrice'], 2) ?></td>
              <td><?= htmlspecialchars($order['userAddress']) ?></td>
              <td><?= ucfirst($order['status']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No completed orders yet.</p>
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

