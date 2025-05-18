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

    // Get cleaner details
    public function getCleanerById(int $id): ?array {
        $sql = "SELECT fullName, phoneNumber, email, location, experienceYears, hourlyRate 
                FROM homecleaners WHERE homeCleanerID = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $cleaner = $stmt->fetch(PDO::FETCH_ASSOC);
        return $cleaner ?: null;
    }

    // Get all available services
    public function getAllServices(): array {
        $stmt = $this->pdo->query("SELECT serviceID, serviceName FROM services ORDER BY serviceName");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get cleaner's selected services
    public function getcleanerservices(int $cleanerId): array {
        $stmt = $this->pdo->prepare("SELECT serviceID FROM cleanerservices WHERE homeCleanerID = :id");
        $stmt->execute(['id' => $cleanerId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    // Save cleaner's selected services (replace existing)
    public function setcleanerservices(int $cleanerId, array $serviceIds): bool {
        try {
            $this->pdo->beginTransaction();

            // Delete old services
            $delStmt = $this->pdo->prepare("DELETE FROM cleanerservices WHERE homeCleanerID = :id");
            $delStmt->execute(['id' => $cleanerId]);

            // Insert new ones
            $insStmt = $this->pdo->prepare("INSERT INTO cleanerservices (homeCleanerID, serviceID) VALUES (:cleanerID, :serviceID)");
            foreach ($serviceIds as $serviceID) {
                $insStmt->execute([
                    'cleanerID' => $cleanerId,
                    'serviceID' => $serviceID,
                ]);
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    // Insert or update cleaner details
    public function upsertCleaner(int $id, array $data): bool {
        $existsStmt = $this->pdo->prepare("SELECT 1 FROM homecleaners WHERE homeCleanerID = :id");
        $existsStmt->execute(['id' => $id]);
        $exists = (bool)$existsStmt->fetchColumn();

        if ($exists) {
            $sql = "UPDATE homecleaners SET 
                        fullName = :fullName,
                        phoneNumber = :phoneNumber,
                        email = :email,
                        location = :location,
                        experienceYears = :experienceYears,
                        hourlyRate = :hourlyRate
                    WHERE homeCleanerID = :id";
        } else {
            $sql = "INSERT INTO homecleaners 
                        (userId, fullName, phoneNumber, email, location, experienceYears, hourlyRate) 
                    VALUES 
                        (:id, :fullName, :phoneNumber, :email, :location, :experienceYears, :hourlyRate)";
        }
        $stmt = $this->pdo->prepare($sql);
        $params = [
            'id' => $id,
            'fullName' => $data['fullName'],
            'phoneNumber' => $data['phoneNumber'],
            'email' => $data['email'],
            'location' => $data['location'],
            'experienceYears' => $data['experienceYears'],
            'hourlyRate' => $data['hourlyRate'],
        ];
        return $stmt->execute($params);
    }
}

if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['userid'];



$manager = new CleanerManager($dbHost, $dbName, $dbUser, $dbPass);

$services = $manager->getAllServices();
$cleanerservices = $manager->getcleanerservices($userId);

$error = '';
$success = '';

$cleaner = $manager->getCleanerById($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['fullName'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $experienceYears = trim($_POST['experienceYears'] ?? '');
    $hourlyRate = trim($_POST['hourlyRate'] ?? '');
    $selectedServices = $_POST['services'] ?? [];

    if ($fullName === '' || $phoneNumber === '' || $email === '' || $location === '' 
        || $experienceYears === '' || $hourlyRate === '') {
        $error = "Please fill out all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!is_numeric($experienceYears) || (int)$experienceYears < 0) {
        $error = "Experience years must be a non-negative number.";
    } elseif (!is_numeric($hourlyRate) || (float)$hourlyRate < 0) {
        $error = "Hourly rate must be a non-negative number.";
    } else {
        // Validate services IDs if any
        $availableServiceIDs = array_column($services, 'serviceID');
        foreach ($selectedServices as $serviceID) {
            if (!in_array($serviceID, $availableServiceIDs)) {
                $error = "Invalid service selected.";
                break;
            }
        }
        if (!$error) {
            $data = [
                'fullName' => $fullName,
                'phoneNumber' => $phoneNumber,
                'email' => $email,
                'location' => $location,
                'experienceYears' => (int)$experienceYears,
                'hourlyRate' => (float)$hourlyRate,
            ];
            if ($manager->upsertCleaner($userId, $data)) {
                if ($manager->setcleanerservices($userId, $selectedServices)) {
                    $success = "Your details and services have been saved successfully.";
                    $cleanerservices = $selectedServices;
                    $cleaner = $manager->getCleanerById($userId);
                } else {
                    $error = "Failed to save services. Please try again.";
                }
            } else {
                $error = "Failed to save your details. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Edit Cleaner Details</title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f6f8;
    margin: 20px;
    color: #333;
  }
  .container {
    background: white;
    max-width: 600px;
    margin: 0 auto;
    padding: 24px 32px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }
  h1 {
    margin-top: 0;
    text-align: center;
    color: #4a4a4a;
    margin-bottom: 16px;
  }
  form {
    display: flex;
    flex-direction: column;
  }
  label {
    margin: 12px 0 4px;
    font-weight: bold;
    color: #555;
  }
  input[type="text"],
  input[type="email"],
  input[type="number"] {
    padding: 10px 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1rem;
  }
  input[type="number"] {
    -moz-appearance: textfield;
  }
  input[type="number"]::-webkit-outer-spin-button,
  input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }
  .checkbox-group {
    margin: 12px 0 16px 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  .checkbox-item {
    color: #333;
    font-size: 1rem;
  }
  button {
    margin-top: 24px;
    padding: 12px;
    font-size: 1rem;
    background-color: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }
  button:hover {
    background-color: #5a67d8;
  }
  .message {
    margin-bottom: 16px;
    padding: 12px;
    border-radius: 6px;
  }
  .error {
    background-color: #fed7d7;
    color: #c53030;
    border: 1px solid #fc8181;
  }
  .success {
    background-color: #d4f4dd;
    color: #27632a;
    border: 1px solid #74b06f;
  }
</style>
</head>
<body>
  <div class="container" role="main" aria-labelledby="pageTitle">
    <h1 id="pageTitle">Add/Edit Your Cleaner Details</h1>

    <?php if ($error): ?>
      <div class="message error" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($success): ?>
      <div class="message success" role="alert"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <label for="fullName">Full Name</label>
      <input type="text" id="fullName" name="fullName" value="<?php echo htmlspecialchars($cleaner['fullName'] ?? ''); ?>" required />

      <label for="phoneNumber">Phone Number</label>
      <input type="text" id="phoneNumber" name="phoneNumber" value="<?php echo htmlspecialchars($cleaner['phoneNumber'] ?? ''); ?>" required />

      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($cleaner['email'] ?? ''); ?>" required />

      <label for="location">Location</label>
      <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($cleaner['location'] ?? ''); ?>" required />

      <label for="experienceYears">Years of Experience</label>
      <input type="number" id="experienceYears" name="experienceYears" min="0" value="<?php echo htmlspecialchars($cleaner['experienceYears'] ?? '0'); ?>" required />

      <label for="hourlyRate">Hourly Rate ($)</label>
      <input type="number" id="hourlyRate" name="hourlyRate" min="0" step="0.01" value="<?php echo htmlspecialchars($cleaner['hourlyRate'] ?? '0.00'); ?>" required />

      <label>Services</label>
      <div class="checkbox-group" role="group" aria-labelledby="servicesLabel">
        <?php foreach ($services as $service): ?>
            <div class="checkbox-item">
              <input type="checkbox" id="service_<?php echo htmlspecialchars($service['serviceID']); ?>" name="services[]" value="<?php echo htmlspecialchars($service['serviceID']); ?>"
                <?php echo in_array($service['serviceID'], $cleanerservices) ? 'checked' : ''; ?> />
              <label for="service_<?php echo htmlspecialchars($service['serviceID']); ?>"><?php echo htmlspecialchars($service['serviceName']); ?></label>
            </div>
        <?php endforeach; ?>
      </div>

      <button type="submit">Save Details</button>
      <a href="cleaner.php" class="btn" style="margin-top: 10px; display: inline-block;">
  Back to Cleaner Homepage
    </form>
  </div>
</body>
</html>
