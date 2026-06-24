<?php
require_once 'config/database.php';

// Fetch all services from database
try {
    $stmt = $pdo->query("SELECT * FROM layanan ORDER BY id ASC");
    $layanan_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $layanan_list = [];
}

include 'templates/header.php';
?>

<!-- Hero Section -->
<section class="hero-section text-center">
    <div class="container">
        <span class="hero-subtitle mb-2 d-inline-block text-uppercase">Welcome to Luxury & Style</span>
        <h1 class="display-3 mb-4 font-outfit text-white">Define Your Beauty, Refine Your Soul</h1>
        <p class="lead mb-5 mx-auto text-white-50" style="max-width: 600px;">
            Dapatkan pengalaman perawatan rambut, wajah, dan tubuh premium bersama hair stylist dan terapis kecantikan terbaik kami.
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="index.php#layanan" class="btn btn-salon px-4 py-2 fs-5">
                <i class="fa-solid fa-list me-2"></i>Lihat Layanan
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="customer/booking.php" class="btn btn-salon-outline text-white border-white px-4 py-2 fs-5">
                    <i class="fa-solid fa-calendar-check me-2"></i>Booking Sekarang
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-salon-outline text-white border-white px-4 py-2 fs-5">
                    <i class="fa-solid fa-calendar-check me-2"></i>Booking Sekarang
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <img src="https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=600&q=80" alt="About Salon" class="img-fluid rounded-4 shadow-sm">
            </div>
            <div class="col-lg-6 ps-lg-5">
                <span class="text-primary fw-semibold text-uppercase font-outfit">Tentang Glowing Grace</span>
                <h2 class="mb-4 mt-2">Tempat Terbaik untuk Memanjakan Diri Anda</h2>
                <p class="text-muted">
                    Kami percaya bahwa setiap individu memiliki pesona uniknya sendiri. Di Glowing Grace Salon, kami berkomitmen untuk menonjolkan kecantikan alami Anda melalui layanan profesional dengan standar kualitas tertinggi.
                </p>
                <div class="row mt-4">
                    <div class="col-sm-6 mb-3">
                        <div class="d-flex align-items-center">
                            <span class="bg-light p-3 rounded-circle text-primary me-3"><i class="fa-solid fa-award fa-xl"></i></span>
                            <div>
                                <h6 class="mb-0 fw-bold font-outfit">Terapis Ahli</h6>
                                <small class="text-muted">Profesional berpengalaman</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="d-flex align-items-center">
                            <span class="bg-light p-3 rounded-circle text-primary me-3"><i class="fa-solid fa-wand-magic-sparkles fa-xl"></i></span>
                            <div>
                                <h6 class="mb-0 fw-bold font-outfit">Bahan Premium</h6>
                                <small class="text-muted">Produk berkualitas tinggi</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Catalog Section -->
<section id="layanan" class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <span class="text-primary fw-semibold text-uppercase font-outfit">Katalog Layanan</span>
            <h2 class="display-5 mt-2 mb-3">Layanan Spesial Kami</h2>
            <div class="mx-auto" style="width: 80px; height: 3px; background-color: var(--primary-color);"></div>
            <p class="text-muted mt-3">Silakan pilih layanan kecantikan premium kami di bawah ini untuk membuat janji temu.</p>
        </div>

        <div class="row">
            <?php if (empty($layanan_list)): ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">Belum ada data layanan tersedia.</p>
                </div>
            <?php else: ?>
                <?php foreach ($layanan_list as $lay): ?>
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card card-salon h-100">
                            <!-- Service Image with fallback -->
                            <?php 
                            $image_src = 'https://images.unsplash.com/photo-1562322140-8baeececf3df?auto=format&fit=crop&w=400&q=80'; // Default styling image
                            if ($lay['id'] == 1) $image_src = 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=400&q=80';
                            if ($lay['id'] == 2) $image_src = 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=400&q=80';
                            if ($lay['id'] == 3) $image_src = 'https://images.unsplash.com/photo-1519014816548-bf5fe059798b?auto=format&fit=crop&w=400&q=80';
                            if ($lay['id'] == 4) $image_src = 'https://images.unsplash.com/photo-1562322140-8baeececf3df?auto=format&fit=crop&w=400&q=80';
                            ?>
                            <img src="<?= $image_src; ?>" class="card-img-top" alt="<?= htmlspecialchars($lay['nama_layanan']); ?>" style="height: 180px; object-fit: cover;">
                            
                            <div class="card-body d-flex flex-column p-4">
                                <h5 class="card-title mb-2"><?= htmlspecialchars($lay['nama_layanan']); ?></h5>
                                <div class="d-flex align-items-center mb-3">
                                    <span class="text-primary fw-bold fs-5 font-outfit me-3">Rp <?= number_format($lay['harga'], 0, ',', '.'); ?></span>
                                    <span class="badge bg-secondary-subtle text-secondary small"><i class="fa-regular fa-clock me-1"></i><?= $lay['durasi']; ?> Menit</span>
                                </div>
                                <p class="card-text text-muted small flex-grow-1">
                                    <?= htmlspecialchars(mb_strimwidth($lay['deskripsi'], 0, 120, "...")); ?>
                                </p>
                                <div class="mt-3">
                                    <a href="customer/booking.php?layanan_id=<?= $lay['id']; ?>" class="btn btn-salon w-100">
                                        <i class="fa-solid fa-calendar-check me-2"></i>Booking
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <span class="text-primary fw-semibold text-uppercase font-outfit">Testimoni</span>
            <h2 class="display-6 mt-2 mb-3">Apa Kata Pelanggan Kami</h2>
            <div class="mx-auto" style="width: 80px; height: 3px; background-color: var(--primary-color);"></div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card-salon p-4 h-100">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary-subtle text-primary p-3 rounded-circle me-3 fw-bold">AS</div>
                        <div>
                            <h6 class="mb-0 fw-bold">Amanda Shinta</h6>
                            <small class="text-muted">Customer Setia</small>
                        </div>
                    </div>
                    <p class="text-muted small italic">"Layanan gunting rambut di sini juara banget! Stylist-nya mengerti apa yang saya inginkan dan hasilnya awet bagus bahkan setelah keramas sendiri."</p>
                    <div class="text-warning small">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card-salon p-4 h-100">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary-subtle text-primary p-3 rounded-circle me-3 fw-bold">KP</div>
                        <div>
                            <h6 class="mb-0 fw-bold">Kartika Putri</h6>
                            <small class="text-muted">Customer Setia</small>
                        </div>
                    </div>
                    <p class="text-muted small">"Facial-nya bikin rileks banget, pijatan wajahnya enak dan bahan-bahannya dingin di kulit. Setelah treatment wajah langsung kelihatan cerah bersinar."</p>
                    <div class="text-warning small">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card-salon p-4 h-100">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary-subtle text-primary p-3 rounded-circle me-3 fw-bold">R</div>
                        <div>
                            <h6 class="mb-0 fw-bold">Riana</h6>
                            <small class="text-muted">Customer Baru</small>
                        </div>
                    </div>
                    <p class="text-muted small">"Suka banget sama manicure-pedicure di sini. Kebersihannya terjaga, peralatan steril, terapisnya ramah, dan pijatannya bikin pegel hilang."</p>
                    <div class="text-warning small">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'templates/footer.php'; ?>
