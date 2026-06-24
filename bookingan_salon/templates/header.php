<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glowing Grace Salon - Booking Online</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="http://localhost/bookingan_salon/assets/css/style.css">
</head>
<body>

<!-- Custom Navbar -->
<nav class="navbar navbar-expand-lg navbar-salon sticky-top">
    <div class="container">
        <a class="navbar-brand" href="http://localhost/bookingan_salon/index.php">
            <i class="fa-solid fa-gem me-2"></i>Glowing Grace
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link" href="http://localhost/bookingan_salon/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="http://localhost/bookingan_salon/index.php#layanan">Layanan</a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a class="nav-link" href="http://localhost/bookingan_salon/admin/dashboard.php">Dashboard Admin</a>
                        <?php else: ?>
                            <a class="nav-link" href="http://localhost/bookingan_salon/customer/dashboard.php">Dashboard Saya</a>
                        <?php endif; ?>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-salon btn-sm" href="http://localhost/bookingan_salon/logout.php">
                            <i class="fa-solid fa-right-from-bracket me-1"></i>Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="http://localhost/bookingan_salon/login.php">Login</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-salon btn-sm" href="http://localhost/bookingan_salon/register.php">
                            <i class="fa-solid fa-user-plus me-1"></i>Daftar
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
