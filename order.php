<?php
session_start();
require 'db.php';
class OrderManager {
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

    public function getCleanerInfo(int $cleanerId) {
        $sql = "SELECT fullName, hourlyRate FROM homecleaners WHERE homeCleanerID = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $cleanerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getCleanerServices(int $cleanerId) {
        $sql = "SELECT s.serviceID, s.serviceName
                FROM services s
                INNER JOIN cleanerServices cs ON s.serviceID = cs.serviceID
                WHERE cs.homeCleanerID = :id
                ORDER BY s.serviceName";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $cleanerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getServiceNameById(int $serviceId): ?string {
        $stmt = $this->pdo->prepare("SELECT serviceName FROM services WHERE serviceID = :id LIMIT 1");
        $stmt->execute(['id' => $serviceId]);
        $name = $stmt->fetchColumn();
        return $name ?: null;
    }

    public function createOrder(array $data): bool {
        $sql = "INSERT INTO orders 
            (homeOwnerId, homeCleanerID, serviceName, userAddress, orderDate, startTime, durationHours,  totalPrice, paymentMethod, status)
            VALUES 
            (:userId, :cleanerId, :serviceName, :address, :orderDate, :startTime, :duration, :totalPrice, :paymentMethod, 'pending')";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'userId' => $data['userId'],
            'cleanerId' => $data['cleanerId'],
            'serviceName' => $data['serviceName'],
            'address' => $data['address'],
            'orderDate' => $data['orderDate'],
            'startTime' => $data['startTime'],
            'duration' => $data['duration'],
            'totalPrice' => $data['totalPrice'],
            'paymentMethod' => $data['paymentMethod'],
        ]);
    }
}

if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['userid'];


$orderManager = new OrderManager($dbHost, $dbName, $dbUser, $dbPass);

$cleanerId = isset($_GET['cleaner_id']) && ctype_digit($_GET['cleaner_id']) ? intval($_GET['cleaner_id']) : 0;
if($cleanerId <= 0) {
    die("Invalid cleaner ID.");
}

$cleaner = $orderManager->getCleanerInfo($cleanerId);
if (!$cleaner) {
    die("Cleaner not found.");
}
$services = $orderManager->getCleanerServices($cleanerId);

$error = '';
$success = '';

$validPaymentMethods = ['visa', 'mastercard', 'paynow', 'cash_on_site'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceId = $_POST['service_id'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $orderDate = $_POST['order_date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $paymentMethod = strtolower(trim($_POST['payment_method'] ?? ''));

    if ($serviceId === '' || $address === '' || $orderDate === '' || $startTime === '' || $duration === '' || $paymentMethod === '') {
        $error = "Please fill in all fields.";
    } elseif (!ctype_digit($serviceId)) {
        $error = "Invalid service selected.";
    } elseif (!in_array(intval($serviceId), array_column($services, 'serviceID'))) {
        $error = "Selected service is not available for this cleaner.";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $orderDate)) {
        $error = "Invalid date format.";
    } elseif (!preg_match('/^\d{2}:\d{2}$/', $startTime)) {
        $error = "Invalid time format.";
    } elseif (!is_numeric($duration) || $duration <= 0) {
        $error = "Duration must be a positive number.";
    } elseif (!in_array($paymentMethod, $validPaymentMethods)) {
        $error = "Please select a valid payment method.";
    } else {
        $serviceName = $orderManager->getServiceNameById(intval($serviceId));
        if (!$serviceName) {
            $error = "Selected service is invalid.";
        } else {
            $hourlyRate = $cleaner['hourlyRate'];
            $totalPrice = $hourlyRate * $duration;

            $orderData = [
                'userId' => $userId,
                'cleanerId' => $cleanerId,
                'serviceName' => $serviceName,
                'address' => $address,
                'hourlyRate' => $hourlyRate,
                'orderDate' => $orderDate,
                'startTime' => $startTime,
                'duration' => $duration,
                'totalPrice' => $totalPrice,
                'paymentMethod' => $paymentMethod,
            ];

            if ($orderManager->createOrder($orderData)) {
                $success = "Your order has been placed successfully!";
            } else {
                $error = "Failed to place order. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Place Order</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f6f8;
    color: #333;
    margin: 20px;
  }
  .container {
    max-width: 480px;
    margin: 0 auto;
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }
  h1 {
    margin-top:0;
    margin-bottom: 16px;
    color: #4a4a4a;
    text-align: center;
  }
  label {
    font-weight: bold;
    display: block;
    margin: 12px 0 6px;
  }
  input[type="text"],
  select,
  input[type="date"],
  input[type="time"],
  input[type="number"] {
    width: 100%;
    padding: 8px 10px;
    font-size: 1rem;
    border: 1px solid #ccc;
    border-radius: 6px;
    box-sizing: border-box;
  }
  input[readonly] {
    background-color: #e9ecef;
  }
  .readonly-field {
    padding: 10px 12px;
    background: #e9ecef;
    border-radius: 6px;
    font-size: 1rem;
  }
  .total-price {
    font-weight: bold;
    margin-top: 8px;
    font-size: 1.2rem;
  }
  button {
    margin-top: 24px;
    width: 100%;
    padding: 12px;
    font-size: 1.1rem;
    background-color: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
  }
  button:hover {
    background-color: #5a67d8;
  }
  .message {
    margin: 10px 0;
    padding: 10px;
    border-radius: 6px;
  }
  .error {
    background-color: #fed7d7;
    border: 1px solid #c53030;
    color: #c53030;
  }
  .success {
    background-color: #d4f4dd;
    border: 1px solid #27632a;
    color: #27632a;
  }
</style>
<script>
window.addEventListener('DOMContentLoaded', function() {
  const durationInput = document.getElementById('duration');
  const totalPriceSpan = document.getElementById('totalPrice');
  const hourlyRate = parseFloat('<?php echo addslashes($cleaner['hourlyRate']) ?>');

  function updateTotal() {
    let hrs = parseFloat(durationInput.value);
    if (isNaN(hrs) || hrs < 0) hrs = 0;
    const total = hrs * hourlyRate;
    totalPriceSpan.textContent = total.toFixed(2) + ' $';
  }

  durationInput.addEventListener('input', updateTotal);

  updateTotal();
});
</script>
</head>
<body>
  <div class="container" role="main" aria-labelledby="pageTitle">
    <h1 id="pageTitle">Place Order with <?php echo htmlspecialchars($cleaner['fullName']); ?></h1>
    <?php if ($error): ?>
      <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($success): ?>
      <div class="message success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form method="post" novalidate>
      <label>Cleaner Name</label>
      <div class="readonly-field"><?php echo htmlspecialchars($cleaner['fullName']); ?></div>

      <label for="service_id">Select Service</label>
      <select name="service_id" id="service_id" required>
        <option value="">-- Select Service --</option>
        <?php foreach ($services as $service): ?>
          <option value="<?php echo $service['serviceID']; ?>" <?php echo (isset($_POST['service_id']) && $_POST['service_id'] == $service['serviceID']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($service['serviceName']); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label for="address">Your Address</label>
      <input type="text" name="address" id="address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" required />

      <label>Hourly Rate</label>
      <div class="readonly-field"><?php echo htmlspecialchars($cleaner['hourlyRate']); ?> $</div>

      <label for="order_date">Order Date</label>
      <input type="date" name="order_date" id="order_date" value="<?php echo htmlspecialchars($_POST['order_date'] ?? ''); ?>" required />

      <label for="start_time">Start Time</label>
      <input type="time" name="start_time" id="start_time" value="<?php echo htmlspecialchars($_POST['start_time'] ?? ''); ?>" required />

      <label for="duration">Duration (hours)</label>
      <input type="number" name="duration" id="duration" min="0.5" step="0.5" value="<?php echo htmlspecialchars($_POST['duration'] ?? ''); ?>" required />

      <label for="payment_method">Payment Method</label>
      <select id="payment_method" name="payment_method" required>
          <option value="">-- Select Payment Method --</option>
          <option value="visa" <?php if(isset($_POST['payment_method']) && $_POST['payment_method'] === 'visa') echo 'selected'; ?>>Visa</option>
          <option value="mastercard" <?php if(isset($_POST['payment_method']) && $_POST['payment_method'] === 'mastercard') echo 'selected'; ?>>MasterCard</option>
          <option value="paynow" <?php if(isset($_POST['payment_method']) && $_POST['payment_method'] === 'paynow') echo 'selected'; ?>>PayNow</option>
          <option value="cash_on_site" <?php if(isset($_POST['payment_method']) && $_POST['payment_method'] === 'cash_on_site') echo 'selected'; ?>>Cash on Site</option>
      </select>

      <div class="total-price">Total Price: <span id="totalPrice">0.00 $</span></div>

      <button type="submit">Place Order</button>
      <a href="homeOwner.php" class="back-button">⬅️ Back to Home</a>
    </form>
  </div>
</body>
</html>
