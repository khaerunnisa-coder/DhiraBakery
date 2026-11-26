<?php
session_start();
require_once 'config/koneksi.php';

$link_google_maps = "https://maps.app.goo.gl/yzHtoYd55hhUmwfQ6";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Toko — DHIRABAKERY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
</head>
<body>

    <div class="container py-3">
        <!-- HEADER NAVBAR UTAMA -->
        <header class="d-flex flex-wrap justify-content-between align-items-center navbar-tech px-4 py-3 rounded-4 shadow gap-2">
            <!-- Logo & Nama Toko -->
            <a href="index.php" class="brand-title fs-4 text-decoration-none d-flex align-items-center gap-2">
                <img src="assets/img/logo.jpeg" 
                     alt="Logo Dhira Bakery" 
                     class="navbar-logo" 
                     onerror="this.style.display='none'">
                <span>DHIRA BAKERY</span>
            </a>

            <!-- Tombol Navigasi Kanan (BERANDA, TENTANG TOKO, CART & LOGIN) -->
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="index.php" class="btn btn-outline-light rounded-pill px-3 py-2">
                    <i class="bi bi-house-door me-1"></i> BERANDA
                </a>

                <a href="tentang.php" class="btn btn-warning rounded-pill px-3 py-2 text-dark fw-bold">
                    <i class="bi bi-shop me-1"></i> TENTANG TOKO
                </a>

                <a href="keranjang.php" class="btn btn-outline-light position-relative rounded-pill px-3 py-2">
                    <i class="bi bi-bag me-1"></i> CART
                    <?php if (!empty($_SESSION['cart'])): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= count($_SESSION['cart']); ?>
                        </span>
                    <?php endif; ?>
                </a>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <button class="btn btn-warning rounded-pill px-3 py-2 dropdown-toggle fw-bold" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-fill me-1"></i> <?= htmlspecialchars($_SESSION['user_nama']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-bakery shadow-lg mt-2">
                            <li>
                                <a class="dropdown-item dropdown-item-bakery" href="admin/login.php">
                                    <i class="bi bi-shield-lock me-2" style="color: #e5a93c;"></i> Login Admin
                                </a>
                            </li>
                            <li><hr class="dropdown-divider border-secondary my-1"></li>
                            <li>
                                <a class="dropdown-item dropdown-item-bakery text-danger" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout Pelanggan
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="dropdown">
                        <button class="btn btn-light rounded-pill px-4 py-2 text-dark fw-bold dropdown-toggle d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person"></i> LOGIN
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-bakery shadow-lg mt-2">
                            <li>
                                <a class="dropdown-item dropdown-item-bakery" href="login.php">
                                    <i class="bi bi-person-circle me-2" style="color: #e5a93c;"></i> Login Pelanggan
                                </a>
                            </li>
                            <li><hr class="dropdown-divider border-secondary my-1"></li>
                            <li>
                                <a class="dropdown-item dropdown-item-bakery" href="admin/login.php">
                                    <i class="bi bi-shield-lock me-2" style="color: #e5a93c;"></i> Login Admin
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <!-- KONTEN PROFIL USAHA / TENTANG TOKO -->
        <section class="my-4">
            <div class="futuristic-card p-4 p-lg-5 rounded-4 shadow">
                <div class="row g-4 g-lg-5 align-items-start">
                    <!-- Kolom Kiri: Foto Etalase & Identitas Usaha -->
                    <div class="col-lg-5">
                        <div class="position-relative overflow-hidden rounded-4 shadow mb-4" style="background-color: #190c07;">
                            <img src="assets/img/banner-toko.jpeg?v=<?= time(); ?>"
                                 alt="Etalase Dhirabakery BTN Minasaupa"
                                 class="w-100 d-block"
                                 style="height: 310px; object-fit: cover;">
                        </div>

                        <div class="p-4 rounded-4 border border-secondary text-white" style="background: rgba(255,255,255,0.03);">
                            <h5 class="fw-bold text-warning mb-3 border-bottom border-secondary pb-2">
                                <i class="bi bi-card-list me-2"></i> IDENTITAS USAHA
                            </h5>
                            <ul class="list-unstyled mb-0 small">
                                <li class="mb-2 d-flex justify-content-between border-bottom border-secondary-subtle pb-2">
                                    <span class="text-white-50">Nama Usaha</span>
                                    <span class="fw-bold text-white">Dhirabakery</span>
                                </li>
                                <li class="mb-2 d-flex justify-content-between border-bottom border-secondary-subtle pb-2">
                                    <span class="text-white-50">Bidang Usaha</span>
                                    <span class="fw-bold text-white">Kuliner (Roti dan Bakery)</span>
                                </li>
                                <li class="mb-2 d-flex justify-content-between border-bottom border-secondary-subtle pb-2">
                                    <span class="text-white-50">Nama Pemilik</span>
                                    <span class="fw-bold text-white">Siti Hafirah Tukimin</span>
                                </li>
                                <li class="mb-2 d-flex justify-content-between border-bottom border-secondary-subtle pb-2">
                                    <span class="text-white-50">Tahun Berdiri</span>
                                    <span class="fw-bold text-warning">2017</span>
                                </li>
                                <li class="d-flex justify-content-between gap-3">
                                    <span class="text-white-50">Alamat</span>
                                    <a href="<?= $link_google_maps; ?>" target="_blank" rel="noopener noreferrer" class="fw-bold text-warning text-end text-decoration-none">
                                        BTN Minasaupa Blok L1 No. 4 <i class="bi bi-box-arrow-up-right ms-1"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Sejarah & Komitmen Usaha -->
                    <div class="col-lg-7 text-white">
                        <span class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill mb-2">
                            <i class="bi bi-shop me-1"></i> PROFIL USAHA
                        </span>
                        <h1 class="display-6 fw-bold mb-3">DHIRABAKERY</h1>

                        <p class="text-white-50 lh-lg mb-3">
                            <strong class="text-white">Dhirabakery</strong> merupakan usaha yang bergerak di bidang kuliner, khususnya produksi dan penjualan berbagai jenis roti dan produk bakery. Usaha ini didirikan pada tahun <strong class="text-warning">2017</strong> oleh <strong class="text-white">Siti Hafirah Tukimin</strong> dengan tujuan menghadirkan produk roti yang lezat dan berkualitas untuk memenuhi kebutuhan serta selera masyarakat.
                        </p>

                        <p class="text-white-50 lh-lg mb-3">
                            Sejak awal berdiri, Dhirabakery terus berupaya mengembangkan usahanya dengan mengutamakan kualitas produk, cita rasa, dan pelayanan kepada pelanggan. Dengan mengedepankan kreativitas dalam pengolahan produk bakery, Dhirabakery berkomitmen untuk memberikan pilihan roti yang dapat dinikmati oleh berbagai kalangan masyarakat.
                        </p>

                        <p class="text-white-50 lh-lg mb-4">
                            Berlokasi di kawasan <strong class="text-white">BTN Minasaupa</strong>, Dhirabakery menjalankan kegiatan usahanya dengan harapan dapat terus berkembang, memperluas jangkauan pemasaran, serta mempertahankan kepercayaan pelanggan.
                        </p>

                        <div class="p-4 rounded-4 border border-warning mb-4" style="background: rgba(229, 169, 60, 0.08);">
                            <h5 class="fw-bold text-warning mb-2">
                                <i class="bi bi-patch-check-fill me-2"></i> KOMITMEN USAHA
                            </h5>
                            <p class="small text-white-50 lh-lg mb-0">
                                Dhirabakery berkomitmen untuk terus menjaga kualitas produk, menghadirkan cita rasa yang memuaskan, serta memberikan pelayanan terbaik kepada pelanggan. Dengan pengalaman sejak tahun 2017, Dhirabakery berharap dapat menjadi salah satu pilihan masyarakat dalam memenuhi kebutuhan produk roti dan bakery.
                            </p>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <a href="index.php#katalog-section" class="btn btn-warning rounded-pill px-4 py-2 fw-bold text-dark">
                                <i class="bi bi-grid me-1"></i> Lihat Katalog Produk
                            </a>
                            <a href="<?= $link_google_maps; ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-warning rounded-pill px-4 py-2">
                                <i class="bi bi-geo-alt-fill me-1"></i> Buka Google Maps
                            </a>
                            <a href="index.php" class="btn btn-outline-light rounded-pill px-4 py-2">
                                <i class="bi bi-arrow-left me-1"></i> Beranda
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="mt-5 pt-4 border-top border-secondary-subtle text-center text-muted">
            <a href="<?= $link_google_maps; ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-muted">
                <small class="tech-tag">
                    <i class="bi bi-geo-alt-fill text-danger me-1"></i> &copy; <?= date('Y'); ?> DHIRABAKERY — BTN MINASAUPA BLOK L1 NO. 4
                </small>
            </a>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>