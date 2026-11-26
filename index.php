<?php
session_start();
require_once 'config/koneksi.php';

// Filter Kategori
$kategori_id = isset($_GET['kategori']) ? intval($_GET['kategori']) : 0;

// Ambil Data Kategori
$kategori_list = $pdo->query("SELECT * FROM kategori_produk WHERE status_aktif = 1")->fetchAll();

// Ambil Data Produk
if ($kategori_id > 0) {
    $stmt = $pdo->prepare("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori_produk k ON p.id_kategori = k.id_kategori WHERE p.status_aktif = 1 AND p.id_kategori = ? ORDER BY p.id_produk DESC");
    $stmt->execute([$kategori_id]);
} else {
    $stmt = $pdo->prepare("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori_produk k ON p.id_kategori = k.id_kategori WHERE p.status_aktif = 1 ORDER BY p.id_produk DESC");
    $stmt->execute();
}
$produk_list = $stmt->fetchAll();

// Link titik pin Google Maps Dhirabakery
$link_google_maps = "https://maps.app.goo.gl/JhqRQD8VAw4o4oMx7";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DHIRA BAKERY — Artisan Bread & Cakes</title>
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

            <!-- Tombol Navigasi Kanan (TENTANG TOKO, CART & LOGIN) -->
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="tentang.php" class="btn btn-outline-light rounded-pill px-3 py-2">
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

        <!-- HERO SECTION -->
        <section class="hero-container my-4">
            <div class="row align-items-center g-4 g-lg-5">
                <!-- Kolom Teks Kiri -->
                <div class="col-lg-6 col-xl-7">
                    <div class="hero-tagline">LEZATNYA HARIMU</div>
                    <div class="hero-subtitle-italic">dimulai di sini</div>
                    <h1 class="hero-title-main">ARTISAN BREAD</h1>
                    <p class="hero-desc">
                        Resep autentik dari bahan-bahan segar pilihan tanpa pengawet. Melayani pesanan harian & kue khusus perayaan Anda.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="#katalog-section" class="btn-hero-pill">
                            LIHAT KATALOG <i class="bi bi-arrow-right fs-6"></i>
                        </a>
                        <a href="<?= $link_google_maps; ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light rounded-pill px-4 py-2 d-inline-flex align-items-center fw-semibold">
                            <i class="bi bi-geo-alt-fill text-warning me-2"></i> BUKA GOOGLE MAPS
                        </a>
                    </div>
                </div>

                <!-- Kolom Foto Banner Kanan -->
                <div class="col-lg-6 col-xl-5">
                    <div class="position-relative overflow-hidden rounded-4 shadow-lg" style="background-color: #23120b;">
                        <img src="assets/img/banner-toko.jpeg?v=<?= time(); ?>"
                             alt="Etalase Toko Dhirabakery"
                             class="w-100 d-block"
                             style="height: 360px; object-fit: cover;">
                    </div>
                </div>
            </div>
        </section>

        <!-- KATALOG PRODUK SECTION -->
        <div id="katalog-section" class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3 border-top border-secondary-subtle pt-4 mt-4">
            <div>
                <h2 class="display-6 fw-bold mb-0 text-dark">KATALOG PILIHAN</h2>
                <p class="tech-tag mb-0" style="color: var(--text-muted, #7a6b63);"> EXCLUSIVE BREAD & CAKES </p>
            </div>
            
            <div class="category-filter-wrapper gap-2">
                <a href="index.php#katalog-section" class="filter-pill <?= empty($kategori_id) ? 'active' : ''; ?>">SEMUA PRODUK</a>
                <?php foreach ($kategori_list as $kat): ?>
                    <a href="index.php?kategori=<?= $kat['id_kategori']; ?>#katalog-section" 
                       class="filter-pill <?= $kategori_id == $kat['id_kategori'] ? 'active' : ''; ?>">
                        <?= strtoupper(htmlspecialchars($kat['nama_kategori'])); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- GRID KARTU PRODUK -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4 mb-5">
            <?php if (count($produk_list) > 0): ?>
                <?php foreach ($produk_list as $p): ?>
                    <div class="col">
                        <div class="futuristic-card h-100 d-flex flex-column p-3">
                            <div class="position-relative mb-3 overflow-hidden rounded-3">
                                <img src="assets/uploads/<?= !empty($p['foto_produk']) ? $p['foto_produk'] : 'default.jpg'; ?>" 
                                     class="card-img-top w-100" 
                                     alt="<?= htmlspecialchars($p['nama_produk']); ?>"
                                     style="height: 220px; object-fit: cover;"
                                     onerror="this.src='https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=600&q=80'">
                                <span class="position-absolute top-0 start-0 m-2 badge <?= $p['jenis_produk'] == 'pre_order' ? 'bg-primary' : 'bg-success'; ?> tech-tag">
                                    <?= strtoupper($p['jenis_produk']); ?>
                                </span>
                            </div>
                            
                            <h5 class="fw-bold text-white mb-1"><?= htmlspecialchars($p['nama_produk']); ?></h5>
                            <small class="text-white-50 mb-2 d-block"><?= htmlspecialchars($p['nama_kategori']); ?></small>
                            <p class="small text-white-50 flex-grow-1"><?= htmlspecialchars(mb_strimwidth($p['deskripsi'], 0, 80, '...')); ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-secondary">
                                <span class="fs-5 fw-bold" style="color: #e5a93c;">Rp <?= number_format($p['harga'], 0, ',', '.'); ?></span>
                                <a href="detail.php?id=<?= $p['id_produk']; ?>" class="btn btn-sm btn-terracotta rounded-pill px-3 fw-bold">
                                    DETAIL <i class="bi bi-chevron-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted fs-5">[ Belum ada produk dalam kategori ini ]</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- KARTU LOKASI TOKO (KLIK LANGSUNG KE GOOGLE MAPS) -->
        <section class="my-5">
            <a href="<?= $link_google_maps; ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none d-block">
                <div class="futuristic-card p-4 rounded-4 shadow d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 54px; height: 54px; background: rgba(229, 169, 60, 0.15); border: 1px solid #e5a93c;">
                            <i class="bi bi-geo-alt-fill fs-4" style="color: #e5a93c;"></i>
                        </div>
                        <div>
                            <span class="tech-tag text-warning d-block mb-1">Buka dari pukul 08:00 sampai 22.00 WITA</span>
                            <h5 class="fw-bold text-white mb-1">BTN Minasaupa Blok L1 No. 4, Makassar</h5>
                            <small class="text-white-50">Klik untuk membuka rute dan titik lokasi langsung di Google Maps</small>
                        </div>
                    </div>
                    <div>
                        <span class="btn btn-warning rounded-pill px-4 py-2 fw-bold text-dark">
                            <i class="bi bi-map me-1"></i> BUKA MAPS <i class="bi bi-box-arrow-up-right ms-1"></i>
                        </span>
                    </div>
                </div>
            </a>
        </section>

        <!-- FOOTER -->
        <footer class="mt-4 pt-4 border-top border-secondary-subtle text-center text-muted">
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