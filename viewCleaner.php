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

    public function getCleanerById(int $id): ?array {
        $sql = "SELECT fullName, phoneNumber, email, location, experienceYears, hourlyRate
                FROM homecleaners 
                WHERE homeCleanerID = :id 
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $cleaner = $stmt->fetch(PDO::FETCH_ASSOC);
        return $cleaner ?: null;
    }
}

if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    die("Invalid cleaner ID.");
}

$cleanerId = intval($_GET['id']);


$manager = new CleanerManager($dbHost, $dbName, $dbUser, $dbPass);

$cleaner = $manager->getCleanerById($cleanerId);

if (!$cleaner) {
    die("Cleaner not found.");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Cleaner Details - <?php echo htmlspecialchars($cleaner['fullName']); ?></title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 20px;
    background: #f4f6f8;
    color: #333;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
  }
  .container {
    background: white;
    padding: 24px 32px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    max-width: 600px;
    margin: 0 auto;
    flex-grow: 1;
  }
  h1 {
    margin-top: 0;
    color: #4a4a4a;
    margin-bottom: 24px;
    text-align: center;
  }
  dl {
    display: grid;
    grid-template-columns: max-content 1fr;
    row-gap: 12px;
    column-gap: 16px;
  }
  dt {
    font-weight: bold;
    color: #555;
  }
  dd {
    margin: 0;
    color: #222;
  }
  .buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 32px;
  }
  .btn {
    padding: 12px 24px;
    font-size: 1rem;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    color: white;
    display: inline-block;
    text-align: center;
    transition: background-color 0.3s ease;
    min-width: 140px;
  }
  .btn-order {
    background-color: #48bb78; /* green */
  }
  .btn-order:hover {
    background-color: #38a169;
  }
  .btn-back {
    background-color: #718096; /* gray */
  }
  .btn-back:hover {
    background-color: #4a5568;
  }
  @media (max-width: 480px) {
    .container {
      padding: 16px 20px;
      max-width: 100%;
    }
    .buttons {
      flex-direction: column;
      gap: 12px;
    }
    .btn {
      min-width: auto;
      width: 100%;
    }
  }
</style>
</head>
<body>
  <div class="container" role="main" aria-labelledby="pageTitle">
    <h1 id="pageTitle">Cleaner Details</h1>
    <dl>
      <dt>Full Name:</dt>
      <dd><?php echo htmlspecialchars($cleaner['fullName']); ?></dd>

      <dt>Phone Number:</dt>
      <dd><a href="tel:<?php echo htmlspecialchars($cleaner['phoneNumber']); ?>"><?php echo htmlspecialchars($cleaner['phoneNumber']); ?></a></dd>

      <dt>Email:</dt>
      <dd><a href="mailto:<?php echo htmlspecialchars($cleaner['email']); ?>"><?php echo htmlspecialchars($cleaner['email']); ?></a></dd>

      <dt>Location:</dt>
      <dd><?php echo htmlspecialchars($cleaner['location']); ?></dd>

      <dt>Years of Experience:</dt>
      <dd><?php echo htmlspecialchars($cleaner['experienceYears']); ?></dd>

      <dt>Hourly Rating:</dt>
      <dd><?php echo htmlspecialchars($cleaner['hourlyRate']); ?> $ / hour</dd>
    </dl>

    <div class="buttons">
        <button type="button" class="btn btn-back" aria-label="Go back to previous page" onclick="history.back();">Go Back</button>
        <a href="order.php?cleaner_id=<?php echo $cleanerId; ?>" class="btn btn-order" aria-label="Proceed to order this cleaner">Proceed to Order</a>
    </div>
  </div>
</body>
</html>

