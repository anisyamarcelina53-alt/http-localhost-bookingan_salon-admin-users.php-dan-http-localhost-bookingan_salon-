<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Access Control Middleware for Customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    $_SESSION['error'] = 'Akses ditolak. Silakan login sebagai customer.';
    header('Location: http://localhost/bookingan_salon/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Customer - Glowing Grace</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="http://localhost/bookingan_salon/assets/css/style.css">
</head>
<body>

<!-- Customer Navbar -->
<nav class="navbar navbar-expand-lg navbar-salon sticky-top">
    <div class="container">
        <a class="navbar-brand" href="http://localhost/bookingan_salon/customer/dashboard.php">
            <i class="fa-solid fa-gem me-2"></i>Grace Customer
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#customerNav" aria-controls="customerNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="customerNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="http://localhost/bookingan_salon/customer/dashboard.php">
                        <i class="fa-solid fa-house me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="http://localhost/bookingan_salon/customer/booking.php">
                        <i class="fa-solid fa-calendar-plus me-1"></i>Booking Layanan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="http://localhost/bookingan_salon/customer/riwayat.php">
                        <i class="fa-solid fa-clock-rotate-left me-1"></i>Riwayat Booking
                    </a>
                </li>
            </ul>
            <div class="d-flex align-items-center">
                <span class="navbar-text me-3 font-outfit text-dark fw-semibold">
                    <i class="fa-regular fa-user me-1 text-primary"></i>Hai, <?= htmlspecialchars($_SESSION['nama']); ?>
                </span>
                <a href="http://localhost/bookingan_salon/logout.php" class="btn btn-salon btn-sm">
                    <i class="fa-solid fa-right-from-bracket me-1"></i>Logout
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="container py-4 flex-grow-1">
