<?php
session_start();
require 'db.php';
class ServiceManager {
    private $pdo;
    public function __construct($host, $db, $user, $pass) {
        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            die("DB Connection failed: " . $e->getMessage());
        }
    }

    // Service methods
    public function getAllServices(): array {
        $stmt = $this->pdo->query("SELECT serviceID, serviceName FROM services ORDER BY serviceName");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getServiceById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT serviceID, serviceName FROM services WHERE serviceID = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        return $service ?: null;
    }

    public function addService(string $name): bool {
        $name = trim($name);
        if ($name === '') return false;
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM services WHERE serviceName = :name");
        $stmt->execute(['name' => $name]);
        if ($stmt->fetchColumn() > 0) return false;  // Duplicate name

        $stmt = $this->pdo->prepare("INSERT INTO services (serviceName) VALUES (:name)");
        return $stmt->execute(['name' => $name]);
    }

    public function updateService(int $id, string $name): bool {
        $name = trim($name);
        if ($name === '') return false;
        // Check duplicate for other services
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM services WHERE serviceName = :name AND serviceID != :id");
        $stmt->execute(['name' => $name, 'id' => $id]);
        if ($stmt->fetchColumn() > 0) return false;

        $stmt = $this->pdo->prepare("UPDATE services SET serviceName = :name WHERE serviceID = :id");
        return $stmt->execute(['name' => $name, 'id' => $id]);
    }

    public function deleteService(int $id): bool {
        // Optional: You might want to check dependencies before deleting
        $stmt = $this->pdo->prepare("DELETE FROM services WHERE serviceID = :id");
        return $stmt->execute(['id' => $id]);
    }

    // User methods
    public function getAllUsers(): array {
        $stmt = $this->pdo->query("SELECT userID, username, userType FROM users ORDER BY username");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT userID, username, userType FROM users WHERE userID = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function deleteUser(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE userID = :id");
        return $stmt->execute(['id' => $id]);
    }
}



$serviceManager = new ServiceManager($dbHost, $dbName, $dbUser, $dbPass);

$error = '';
$success = '';
$editService = null;
$editUser = null;

// Handle add/edit submit for services only (as example)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['serviceName'] ?? '';
    $id = $_POST['serviceID'] ?? '';

    if (trim($name) === '') {
        $error = "Service name cannot be empty.";
    } else {
        if ($id === '') {
            // Add new service
            if ($serviceManager->addService($name)) {
                $success = "Service added successfully.";
            } else {
                $error = "Failed to add service (duplicate name?).";
            }
        } else {
            // Update existing service
            if ($serviceManager->updateService((int)$id, $name)) {
                $success = "Service updated successfully.";
            } else {
                $error = "Failed to update service (duplicate name?).";
            }
        }
    }
} elseif (isset($_GET['edit'])) {
    // Detect if editing service or user by parameter presence
    if (isset($_GET['type']) && $_GET['type'] === 'user') {
        $editId = (int)$_GET['edit'];
        $editUser = $serviceManager->getUserById($editId);
        if (!$editUser) {
            $error = "User not found for editing.";
        }
    } else {
        // Load service data for edit
        $editId = (int)$_GET['edit'];
        $editService = $serviceManager->getServiceById($editId);
        if (!$editService) {
            $error = "Service not found for editing.";
        }
    }
} elseif (isset($_GET['delete'])) {
    // Detect if deleting service or user by parameter presence
    if (isset($_GET['type']) && $_GET['type'] === 'user') {
        $delId = (int)$_GET['delete'];
        if ($serviceManager->deleteUser($delId)) {
            $success = "User deleted successfully.";
        } else {
            $error = "Failed to delete user.";
        }
    } else {
        $delId = (int)$_GET['delete'];
        if ($serviceManager->deleteService($delId)) {
            $success = "Service deleted successfully.";
        } else {
            $error = "Failed to delete service.";
        }
    }
}

// Load all services and users
$services = $serviceManager->getAllServices();
$users = $serviceManager->getAllUsers();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Manage Services and Users</title>
<link rel="stylesheet" href="style.css" />
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background:#f4f6f8;
    margin:20px;
    color:#333;
  }
  .container {
    max-width: 900px;
    margin: auto;
    background: white;
    padding: 25px 30px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    margin-bottom: 40px;
  }
  h1 {
    text-align: center;
    margin-bottom: 1rem;
    color: #4a4a4a;
  }
  form {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
  }
  input[type=text] {
    flex-grow: 1;
    padding: 10px 12px;
    font-size: 1rem;
    border-radius: 6px;
    border: 1px solid #ccc;
  }
  button {
    padding: 10px 20px;
    border: none;
    background: #667eea;
    color: white;
    font-size: 1rem;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }
  button:hover {
    background: #5a67d8;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1rem;
  }
  th, td {
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
    text-align: left;
  }
  th {
    background: #667eea;
    color: white;
  }
  a.action-btn {
    padding: 6px 12px;
    border-radius: 5px;
    color: white;
    text-decoration: none;
    font-size: 0.9rem;
  }
  a.edit {
    background: #48bb78;
  }
  a.edit:hover {
    background: #38a169;
  }
  a.delete {
    background: #e53e3e;
  }
  a.delete:hover {
    background: #c53030;
  }
  .message {
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
    max-width: 900px;
  }
  .error {
    background: #fed7d7;
    color: #c53030;
    border: 1px solid #fc8181;
  }
  .success {
    background: #d4f4dd;
    color: #27632a;
    border: 1px solid #74b06f;
  }
  .section-title {
    margin-top: 0;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #667eea;
  }
  .cancel-link {
    padding: 10px 20px; 
    background: #ccc; 
    border-radius: 6px; 
    text-decoration: none; 
    color: #333; 
    margin-left: 10px;
    height: 40px;
    display: inline-flex;
    align-items: center;
  }
  #user-search {
    width: 100%;
    padding: 10px 12px;
    font-size: 1rem;
    border-radius: 6px;
    border: 1px solid #ccc;
    margin-bottom: 1rem;
  }
</style>
<script>
  function confirmDelete(event, name, type) {
    if (!confirm("Are you sure you want to delete " + type + ": " + name + "?")) {
      event.preventDefault();
    }
  }

  // User table search filter
  function filterUsers() {
    const input = document.getElementById('user-search');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('user-table');
    const trs = table.tBodies[0].getElementsByTagName('tr');

    for (let i = 0; i < trs.length; i++) {
      const tdUsername = trs[i].getElementsByTagName('td')[0];
      const tdType = trs[i].getElementsByTagName('td')[1];
      if (tdUsername && tdType) {
        const txtUsername = tdUsername.textContent || tdUsername.innerText;
        const txtType = tdType.textContent || tdType.innerText;

        if (txtUsername.toLowerCase().indexOf(filter) > -1 || txtType.toLowerCase().indexOf(filter) > -1) {
          trs[i].style.display = '';
        } else {
          trs[i].style.display = 'none';
        }
      }
    }
  }
</script>
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



  <div class="container">
    <h1 class="section-title">Manage Services</h1>

    <?php if($error): ?>
      <div class="message error"><?=htmlspecialchars($error)?></div>
    <?php elseif($success): ?>
      <div class="message success"><?=htmlspecialchars($success)?></div>
    <?php endif; ?>

    <form method="post" action="">
      <input type="hidden" name="serviceID" value="<?=htmlspecialchars($editService['serviceID'] ?? '')?>" />
      <input type="text" name="serviceName" placeholder="Enter service name" value="<?=htmlspecialchars($editService['serviceName'] ?? '')?>" required />
      <button type="submit"><?= $editService ? 'Update' : 'Add' ?> Service</button>
      <?php if($editService): ?>
      <a href="manage_services_and_users.php" class="cancel-link">Cancel</a>
      <?php endif; ?>
    </form>

    <?php if(count($services) > 0): ?>
      <table>
        <thead>
          <tr>
            <th>Service Name</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($services as $service): ?>
            <tr>
              <td><?=htmlspecialchars($service['serviceName'])?></td>
              <td>
                <a class="action-btn edit" href="?edit=<?= $service['serviceID'] ?>">Edit</a>
                <a class="action-btn delete" href="?delete=<?= $service['serviceID'] ?>" onclick="confirmDelete(event, '<?= addslashes(htmlspecialchars($service['serviceName'])) ?>', 'service')">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No services available.</p>
    <?php endif; ?>
  </div>

  <div class="container">
    <h1 class="section-title">Manage Users</h1>

    <input type="text" id="user-search" onkeyup="filterUsers()" placeholder="Search users..." aria-label="Search users"/>

    <?php if(count($users) > 0): ?>
      <table id="user-table">
        <thead>
          <tr>
            <th>Username</th>
            <th>User Type</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($users as $user): ?>
            <tr>
              <td><?=htmlspecialchars($user['username'])?></td>
              <td>
                <?php
                  // Translate userType codes to friendly names
                  switch($user['userType']) {
                    case 'A': echo 'Admin'; break;
                    case 'C': echo 'Cleaner'; break;
                    case 'U': echo 'Homeowner'; break;
                    default: echo htmlspecialchars($user['userType']);
                  }
                ?>
              </td>
              <td>
                <a class="action-btn edit" href="?edit=<?= $user['userID'] ?>&type=user">Edit</a>
                <a class="action-btn delete" href="?delete=<?= $user['userID'] ?>&type=user" onclick="confirmDelete(event, '<?= addslashes(htmlspecialchars($user['username'])) ?>', 'user')">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No users available.</p>
    <?php endif; ?>
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


