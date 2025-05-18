<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>About | CleanMatch</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>

  <header>
    <div class="logo">CleanMatch</div>
    <nav>
      <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="about.php" class="active">About</a></li>
        <li><a href="contact.php">Contact</a></li>

        <?php if (isset($_SESSION['username'])): ?>
          <li><a href=<?php
            switch ($_SESSION['userType']) {
              case 'P': echo 'platformOwner.php'; break;
              case 'C': echo 'cleaner.php'; break;
              case 'U': echo 'homeOwner.php'; break;
            }
          ?>><?php echo htmlspecialchars($_SESSION['username']); ?></a></li>
          <li><form action="logout.php" method="POST" style="display:inline;">
            <button type="submit" style="color: black; background: none; border: none; width: 100%; text-align: left;">
            Logout
            </button>

            </form></li>
        <?php else: ?>
          <li><a href="login.php">Login</a></li>
          <li><a href="register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

<div class="about-hero">
        <h1>About CleanMatch</h1>
        <p>Connecting homeowners with trusted, professional cleaners since 2025</p>
    </div>

    <div class="container">
        <div class="about-section">
            <h2>This project for CSCI314 was done by</h2>
            <ul>
            <li>Ng Xi Wen</li>
            <li>Yap Hao Feng</li>
            <li>Muhammad Luthfi Bin Azahar</li>
            <li>Tan Jia Tian </li>
            <li>Nang Moon Moon Seng</li>
            <li>Zay Lin Htet</li>
            <li>Myat Thura Soe</li>
            </ul>
            <h2>Our Story</h2>
            <p>CleanMatch was founded in 2024 with a simple mission: to make finding reliable home cleaning services as easy as possible while providing cleaners with fair opportunities to grow their businesses.</p>
            <p>After experiencing the frustrations of traditional cleaning services firsthand - inconsistent quality, inflexible scheduling, and opaque pricing - our founders set out to create a better solution. CleanMatch leverages technology to connect homeowners directly with vetted cleaning professionals, cutting out the middleman and creating better experiences for both sides.</p>
        </div>

        <div class="about-section">
            <h2>Our Mission</h2>
            <p>We believe everyone deserves a clean, comfortable home without the hassle. At the same time, we're committed to supporting independent cleaning professionals by helping them build sustainable businesses on their terms.</p>
            <p>Our platform is designed to:</p>
            <ul>
                <li>Provide homeowners with transparent pricing, verified reviews, and quality guarantees</li>
                <li>Give cleaners control over their schedules, rates, and client relationships</li>
                <li>Create a community based on trust, respect, and mutual benefit</li>
            </ul>
        </div>

        <div class="stats">
            <div class="stat-item">
                <h3>500+</h3>
                <p>Professional Cleaners</p>
            </div>
            <div class="stat-item">
                <h3>10,000+</h3>
                <p>Satisfied Homeowners</p>
            </div>
            <div class="stat-item">
                <h3>50,000+</h3>
                <p>Completed Jobs</p>
            </div>
            <div class="stat-item">
                <h3>95%</h3>
                <p>Positive Ratings</p>
            </div>
        </div>

        <div class="about-section">
            <h2>Our Team</h2>
            <p>CleanMatch is built by a diverse team of technologists, operations experts, and cleaning industry veterans who are passionate about improving the service experience for everyone involved.</p>
            


        <div class="about-section" style="text-align: center;">
            <h2>Join Our Community</h2>
            <p>Whether you're looking for cleaning services or offering them, we'd love to have you as part of the CleanMatch family.</p>
            
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