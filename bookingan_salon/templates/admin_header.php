<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Access Control Middleware for Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = 'Akses ditolak. Silakan login sebagai administrator.';
    header('Location: http://localhost/bookingan_salon/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Glowing Grace Salon</title>
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

<div class="wrapper">
    <!-- Include Sidebar -->
    <?php include __DIR__ . '/admin_sidebar.php'; ?>

    <!-- Page Content -->
    <div id="content" class="d-flex flex-column">
        <!-- Top Navbar inside admin area -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white py-3 px-4 rounded shadow-sm mb-4 border border-light">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-salon btn-sm me-3">
                    <i class="fas fa-align-left"></i>
                </button>
                <span class="navbar-text ms-auto font-outfit fw-semibold text-dark">
                    <i class="fa-solid fa-user-tie text-primary me-1"></i>Administrator: <?= htmlspecialchars($_SESSION['nama']); ?>
                </span>
            </div>
        </nav>
        
        <!-- Main Content Holder -->
        <div class="flex-grow-1">
