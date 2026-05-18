<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  echo "<script>alert('❌ Please log in first.'); window.location.href='login_page.html';</script>";
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Museum Ticket Booking</title>
  <link rel="stylesheet" href="homepage.css" />
</head>

<body>
  <!-- Navbar -->
  <header class="navbar">
    <div class="logo">
      <div class="icon"></div>
      <span class="name"> The National Museum
        <div class="india">of India</div>
    </div>
    </div>
    <nav>
      <ul>
        <li><a href="#home">Home</a></li>
        <li><a href="exhibitions.php">Exhibitions</a></li>
        <li><a href="backend/history.php">Reports</a></li>
        <li><a href="contact.html">ContactUs</a></li>
      </ul>
    </nav>
    <a href="backend/logout.php" class="btn">Log Out</a>
  </header>
  <div class="shadow"></div>

  <!-- Hero Section -->
  <section id="home" class="hero">
    <div class="hero-content">
      <h1>Discover History & Art at the National Museum</h1>
      <p>Explore our world-class exhibits and experience the wonders of the past. Book your tickets online and skip the
        queue.</p>
      <div class="hero-buttons">
        <a href='http://localhost/minor/backend/index.php' class="btn-primary"> Continue Booking Tickets</a>
      </div>
    </div>
  </section>

  <!-- Features -->
  <section class="features">
    <div class="feature">
      <img src="https://cdn-icons-png.flaticon.com/512/684/684908.png" alt="Ticket Icon" />
      <h3>Easy Booking</h3>
      <p>Book your museum tickets quickly and securely online.</p>
    </div>
    <div class="feature">
      <img src="https://cdn-icons-png.flaticon.com/512/3069/3069184.png" alt="Clock Icon" />
      <h3>Save Time</h3>
      <p>Skip the waiting lines and enjoy your visit stress-free.</p>
    </div>
    <div class="feature">
      <img src="https://cdn-icons-png.flaticon.com/512/1828/1828884.png" alt="Info Icon" />
      <h3>Visitor Info</h3>
      <p>Find all details about our timings, events, and facilities.</p>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    <p>&copy; 2025 National Museum | Designed for Museum Ticket Booking Project</p>
  </footer>
</body>

</html>