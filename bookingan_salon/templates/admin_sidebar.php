<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<nav id="sidebar">
    <div class="sidebar-header text-center">
        <h3><i class="fa-solid fa-gem me-2"></i>Grace Admin</h3>
    </div>

    <ul class="list-unstyled components">
        <li class="<?= $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <a href="http://localhost/bookingan_salon/admin/dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="<?= $current_page == 'layanan.php' ? 'active' : ''; ?>">
            <a href="http://localhost/bookingan_salon/admin/layanan.php">
                <i class="fas fa-scissors"></i> Kelola Layanan
            </a>
        </li>
        <li class="<?= $current_page == 'booking.php' ? 'active' : ''; ?>">
            <a href="http://localhost/bookingan_salon/admin/booking.php">
                <i class="fas fa-calendar-check"></i> Kelola Booking
            </a>
        </li>
        <li class="<?= $current_page == 'users.php' ? 'active' : ''; ?>">
            <a href="http://localhost/bookingan_salon/admin/users.php">
                <i class="fas fa-users"></i> Kelola User
            </a>
        </li>
        <li class="<?= $current_page == 'laporan.php' ? 'active' : ''; ?>">
            <a href="http://localhost/bookingan_salon/admin/laporan.php">
                <i class="fas fa-chart-pie"></i> Laporan Booking
            </a>
        </li>
        <li class="<?= $current_page == 'profil.php' ? 'active' : ''; ?>">
            <a href="http://localhost/bookingan_salon/admin/profil.php">
                <i class="fas fa-user-gear"></i> Profil Admin
            </a>
        </li>
        <hr class="mx-3 my-4 border-secondary">
        <li>
            <a href="http://localhost/bookingan_salon/index.php" target="_blank">
                <i class="fas fa-globe"></i> Lihat Website
            </a>
        </li>
        <li>
            <a href="http://localhost/bookingan_salon/logout.php" class="text-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</nav>
