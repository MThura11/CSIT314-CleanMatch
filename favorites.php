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

    // Get all favorite cleaners for the user with ratings
    public function getFavoriteCleaners(int $userId): array {
        $sql = "SELECT c.homeCleanerID, c.fullName, c.location, c.experienceYears, c.availability,
                       AVG(o.rating) AS avgRating, COUNT(o.rating) AS ratingCount
                FROM favorites f
                INNER JOIN homecleaners c ON f.homeCleanerID = c.homeCleanerID
                LEFT JOIN orders o ON c.homeCleanerID = o.homeCleanerID
                WHERE f.userId = :userId
                GROUP BY c.homeCleanerID
                ORDER BY c.fullName";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function removeFavorite(int $userId, int $cleanerId): bool {
        $sql = "DELETE FROM favorites WHERE userId = :userId AND homeCleanerID = :cleanerId";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['userId' => $userId, 'cleanerId' => $cleanerId]);
    }
}

if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['userid'];


$manager = new CleanerManager($dbHost, $dbName, $dbUser , $dbPass);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_favorite'])) {
    $cleanerId = intval($_POST['cleaner_id']);
    if ($manager->removeFavorite($userId, $cleanerId)) {
        $message = "Cleaner removed from your favorites.";
    } else {
        $message = "Failed to remove cleaner from favorites.";
    }
}

$favorites = $manager->getFavoriteCleaners($userId);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>My Favorite Cleaners</title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 20px;
    background: #f4f6f8;
    color: #333;
    min-height: 100vh;
    position: relative;
    padding-bottom: 70px; /* space for fixed back button */
  }
  h1 {
    color: #4a4a4a;
    margin-bottom: 0.5rem;
  }

  button, .view-btn {
    padding: 8px 16px;
    font-size: 1rem;
    background-color: #808080; /* red for remove */
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
  button:hover {
    background-color: #c53030;
  }
  .view-btn:hover{
    background-color: #808080;
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
  .back-btn {
    background-color: #718096;
    position: fixed;
    bottom: 20px;
    left: 20px;
    z-index: 100;
  }
  .back-btn:hover {
    background-color: #4a5568;
  }
</style>
</head>
<body>
  <h1>My Favorite Cleaners</h1>

  <?php if ($message): ?>
    <div class="message"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <?php if (count($favorites) > 0): ?>
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
      <?php foreach ($favorites as $cleaner): ?>
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
              <input type="hidden" name="remove_favorite" value="1" />
              <input type="hidden" name="cleaner_id" value="<?php echo htmlspecialchars($cleaner['homeCleanerID']); ?>" />
              <button type="submit">Remove</button>
          </form>
          <a class="view-btn" href="viewCleaner.php?id=<?php echo urlencode($cleaner['homeCleanerID']); ?>">View</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
  <div class="no-results">You have no favorite cleaners yet.</div>
  <?php endif; ?>

  <a href="homeOwner.php" class="view-btn back-btn" aria-label="Back to Cleaner List">Back to Cleaners List</a>
</body>
</html>