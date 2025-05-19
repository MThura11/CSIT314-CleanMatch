<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Contact | CleanMatch</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>

  <header>
    <div class="logo">CleanMatch</div>
    <nav>
      <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="contact.php" class="active">Contact</a></li>

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
  
      <div class="contact-hero">
        <h1>Contact CleanMatch</h1>
        <p>We're here to help with any questions or concerns you may have</p>
    </div>

    <div class="container">
        <div class="contact-container">
            <div class="contact-info">
                <h2>Get in Touch</h2>
                <div class="contact-method">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <h3>Email Us</h3>
                        <p>support@cleanmatch.com</p>
                    </div>
                </div>
                <div class="contact-method">
                    <i class="fas fa-phone"></i>
                    <div>
                        <h3>Call Us</h3>
                        <p>+65 6248 9393</p>
                        <p>Monday-Friday, 9am-5pm EST</p>
                    </div>
                </div>
                <div class="contact-method">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <h3>Visit Us</h3>
                        <p>463 Clementi Rd</p>
                        <p>Singapore 599494</p>
                    </div>
                </div>
                <div class="contact-method">
                    <i class="fas fa-comments"></i>
                    <div>
                        <h3>Live Chat</h3>
                        <p>Available 24/7 through our app</p>
                    </div>
                </div>
            </div>

            <div class="contact-form">
                <h2>Send Us a Message</h2>
                <form>
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" required>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <select id="subject">
                            <option>General Inquiry</option>
                            <option>Account Support</option>
                            <option>Billing Question</option>
                            <option>Technical Issue</option>
                            <option>Feedback</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" required></textarea>
                    </div>
                    <button type="submit" class="submit-btn">Send Message</button>
                </form>
            </div>
        </div>

        <div class="faq-section">
            <h2 style="text-align: center; color: #4CAF50;">Frequently Asked Questions</h2>
            <div class="faq-item">
                <div class="faq-question">How do I sign up as a cleaner?</div>
                <p>Signing up is easy! Click on the "Register" button at the top of the page and select "I'm a Cleaner" during the registration process. You'll need to provide some basic information about yourself and your services, and then our team will review your application (usually within 1-2 business days).</p>
            </div>
            <div class="faq-item">
                <div class="faq-question">What areas do you serve?</div>
                <p>We currently serve most major metropolitan areas in the United States and Canada. During the registration process, you'll be able to see if your area is covered. We're expanding rapidly, so if we're not in your area yet, please let us know and we'll notify you when we arrive!</p>
            </div>
            <div class="faq-item">
                <div class="faq-question">How are cleaners vetted?</div>
                <p>All cleaners on our platform go through a rigorous vetting process that includes identity verification, background checks, and in-person or video interviews. We also require professional references and verify their cleaning experience. Only about 30% of applicants are accepted onto the platform.</p>
            </div>
            <div class="faq-item">
                <div class="faq-question">What's your cancellation policy?</div>
                <p>Homeowners can cancel without penalty up to 24 hours before the scheduled cleaning. Cleaners can set their own cancellation policies (visible on their profiles) but we require at least 48 hours notice for cancellations unless there's an emergency.</p>
            </div>
        </div>
    </div>


          
  <div class="page-title">
    <h1>Contact Us</h1>
  </div>

  <main class="container">
    <section class="contact-form">
      <form method="POST" action="">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required />

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required />

        <label for="message">Message:</label>
        <textarea id="message" name="message" required></textarea>

        <button type="submit">Send Message</button>
      </form>
    </section>
  </main>

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