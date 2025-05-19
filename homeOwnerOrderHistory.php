<?php session_start();
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

    // Fetch order history filtered by service name and order date
    public function getOrderHistory(int $userId, string $service = '', string $orderDate = ''): array {
        $sql = "SELECT o.*, c.fullName AS cleanerName
                FROM orders o
                JOIN homeCleaners c ON o.homeCleanerID = c.homeCleanerID
                WHERE o.homeOwnerId = :userId AND o.status = 'completed'";

        if ($service) {
            $sql .= " AND o.serviceName LIKE :service";
        }
        if ($orderDate) {
            $sql .= " AND o.orderDate = :orderDate";
        }

        $sql .= " ORDER BY o.orderDate DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        if ($service) {
            $likeService = "%$service%";
            $stmt->bindParam(':service', $likeService, PDO::PARAM_STR);
        }
        if ($orderDate) {
            $stmt->bindParam(':orderDate', $orderDate);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Submit a rating for an order
    public function submitRating(int $orderId, int $rating): bool {
        $sql = "UPDATE orders SET rating = :rating WHERE orderId = :orderId AND rating IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
        $stmt->bindParam(':orderId', $orderId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}

if (!isset($_SESSION['userid'])) { header("Location: login.php"); exit(); }

$userId = $_SESSION['userid'];



$manager = new CleanerManager($dbHost, $dbName, $dbUser , $dbPass);

$serviceFilter = $_GET['service'] ?? '';
$orderDateFilter = $_GET['order_date'] ?? '';

$orderHistory = $manager->getOrderHistory($userId, $serviceFilter, $orderDateFilter);

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['orderId'], $_POST['rating'])) {
    $orderId = (int)$_POST['orderId'];
    $rating = (int)$_POST['rating'];
    if ($rating >= 1 && $rating <= 5) {
        $manager->submitRating($orderId, $rating);
        // redirect to prevent resubmission and clear POST
        header("Location: " . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
        exit();
    }
}

function renderStars($rating) {
    $fullStar = '★';
    $emptyStar = '☆';
    return str_repeat($fullStar, $rating) . str_repeat($emptyStar, 5 - $rating);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="stylesheet" href="style.css" />
<title>Order History</title>
<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background: #f4f6f8; color: #333; }
    h1 { color: #4a4a4a; margin-bottom: 1rem; }
    form.filter-form { margin-bottom: 20px; display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
    label { font-weight: bold; margin-right: 8px; white-space: nowrap; }
    input[type="text"], input[type="date"] { padding: 6px 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 1rem; }
    button { padding: 8px 16px; background-color: #667eea; border: none; border-radius: 6px; color: white; font-weight: bold; cursor: pointer; transition: background-color 0.3s ease; }
    button:hover { background-color: #5a67d8; }

    table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
    th { background-color: #667eea; color: white; }
    tr:hover { background-color: #f9fafb; }
    .no-results { text-align: center; padding: 20px; color: #777; }
    .rating-form { margin: 0; }
    select.rating-select {
        font-size: 1rem;
        padding: 4px 8px;
        border-radius: 6px;
        border: 1px solid #ccc;
        background: white;
        cursor: pointer;
    }
    input[type="submit"] {
        margin-left: 6px;
        padding: 6px 12px;
        border-radius: 6px;
        background-color: #667eea;
        color: white;
        border: none;
        cursor: pointer;
        font-weight: bold;
        transition: background-color 0.3s ease;
    }
    input[type="submit"]:hover {
        background-color: #5a67d8;
    }
    .rated-stars {
        font-size: 1.2rem;
        color: #f5a623; /* star gold */
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

<h1>Confirmed Matches History</h1>

<form method="get" aria-label="Filter order history" class="filter-form">
    <label for="service">Service:</label>
    <input type="text" name="service" id="service" placeholder="Service name" value="<?php echo htmlspecialchars($serviceFilter); ?>" />

    <label for="order_date">Order Date:</label>
    <input type="date" name="order_date" id="order_date" value="<?php echo htmlspecialchars($orderDateFilter); ?>" />

    <button type="submit">Filter</button>
</form>

<?php if (count($orderHistory) > 0): ?>
<table>
    <thead>
        <tr>
            <th>Cleaner Name</th>
            <th>Service</th>
            <th>Order Date</th>
            <th>Start Time</th>
            <th>Duration (hours)</th>
            <th>Total Price</th>
            <th>Payment Method</th>
            <th>Status</th>
            <th>Rating</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($orderHistory as $order): ?>
        <tr>
            <td><?php echo htmlspecialchars($order['cleanerName']); ?></td>
            <td><?php echo htmlspecialchars($order['serviceName']); ?></td>
            <td><?php echo htmlspecialchars($order['orderDate']); ?></td>
            <td><?php echo htmlspecialchars($order['startTime']); ?></td>
            <td><?php echo htmlspecialchars($order['durationHours']); ?></td>
            <td>$<?php echo number_format((float)$order['totalPrice'], 2); ?></td>
            <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $order['paymentMethod']))); ?></td>
            <td><?php echo htmlspecialchars($order['status']); ?></td>
            <td>
                <?php if (isset($order['rating']) && $order['rating'] !== null): ?>
                    <span class="rated-stars" title="Rated <?php echo (int)$order['rating']; ?> out of 5">
                        <?php echo renderStars((int)$order['rating']); ?>
                    </span>
                <?php else: ?>
                    <form method="post" class="rating-form" style="display:inline;">
                        <input type="hidden" name="orderId" value="<?php echo (int)$order['orderId']; ?>" />
                        <select name="rating" class="rating-select" required aria-label="Select rating">
                            <option value="" disabled selected>Rate</option>
                            <option value="1">1 ★</option>
                            <option value="2">2 ★★</option>
                            <option value="3">3 ★★★</option>
                            <option value="4">4 ★★★★</option>
                            <option value="5">5 ★★★★★</option>
                        </select>
                        <input type="submit" value="Submit" />
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<div class="no-results">No confirmed matches found for the selected filters.</div>
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

