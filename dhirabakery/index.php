<?php
session_start();
require_once 'config/koneksi.php';

$stmt_kat = $pdo->query("SELECT * FROM kategori_produk WHERE status_aktif = 1");
$kategori_list = $stmt_kat->fetchAll();

$kategori_id = isset($_GET['kategori']) ? $_GET['kategori'] : '';
if (!empty($kategori_id)) {
    $stmt_prod = $pdo->prepare("SELECT p.*, s.jumlah_stok FROM produk p LEFT JOIN stok_produk s ON p.id_produk = s.id_produk WHERE p.id_kategori = ? AND p.status_aktif = 1");
    $stmt_prod->execute([$kategori_id]);
} else {
    $stmt_prod = $pdo->query("SELECT p.*, s.jumlah_stok FROM produk p LEFT JOIN stok_produk s ON p.id_produk = s.id_produk WHERE p.status_aktif = 1");
}
$produk_list = $stmt_prod->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DHIRA BAKERY — Modern High-Fashion Artisan Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="container py-3">
        <!-- Header / Navigation -->
        <header class="d-flex justify-content-between align-items-center mb-4 navbar-tech">
            <a href="index.php" class="brand-title fs-2 text-white text-decoration-none">DHIRA BAKERY™</a>
            
            <nav class="d-none d-md-flex align-items-center">
                <a href="index.php" class="nav-link <?= empty($kategori_id) ? 'active' : ''; ?>">[ CATALOG ]</a>
                <a href="index.php?jenis=ready" class="nav-link">[ READY STOCK ]</a>
                <a href="index.php?jenis=po" class="nav-link">[ PRE-ORDER ]</a>
            </nav>

            <div class="d-flex align-items-center gap-2">
                <a href="keranjang.php" class="btn btn-outline-light position-relative font-weight-bold rounded-pill px-3 py-2 me-1">
                    <i class="bi bi-bag-check me-1"></i> CART
                    <?php if (!empty($_SESSION['cart'])): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= count($_SESSION['cart']); ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- Autentikasi User / Admin Navigation -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <button class="btn btn-warning rounded-pill px-3 py-2 dropdown-toggle fw-bold" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-check-fill me-1"></i> <?= htmlspecialchars($_SESSION['user_nama']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item text-white px-3 py-2" href="logout.php"><i class="bi bi-box-arrow-right me-2 text-danger"></i> Logout Pelanggan</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-light rounded-pill px-3 py-2 text-dark font-weight-bold"><i class="bi bi-person me-1"></i> LOGIN USER</a>
                <?php endif; ?>

                <a href="admin/login.php" class="btn btn-sm btn-dark rounded-pill px-3 py-2 border border-secondary text-white-50" title="Login Admin Toko"><i class="bi bi-shield-lock me-1"></i> ADMIN</a>
            </div>
        </header>

        <!-- Hero Showcase -->
        <section class="hero-container position-relative overflow-hidden">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <p class="tech-tag mb-2">[ BAKERY COLLECTION: EDITION 2026 ]</p>
                    <h1 class="hero-title mb-3">ARTISAN BREAD & CAKES™</h1>
                    <p class="text-white-50 mb-4">Pengalaman rasa eksklusif dari adonan premium harian. Melayani pemesanan instan & custom Pre-Order perayaan Anda.</p>

                    <div class="d-flex align-items-center gap-3">
                        <a href="#katalog-section" class="btn-circle-action">↗</a>
                        <div>
                            <span class="tech-tag d-block">EXPLORE PRODUCTS</span>
                            <span class="fs-5 fw-bold text-white">FRESH EVERYDAY</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 text-center">
                    <img src="https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=800&q=80" 
                         class="img-fluid rounded-4 shadow-lg" alt="Bakery Hero" style="max-height: 380px; width: 100%; object-fit: cover;">
                </div>
            </div>
        </section>

        <!-- Section Title & Category Filters -->
        <div id="katalog-section" class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3">
            <div>
                <h2 class="display-6 fw-bold mb-0">CATALOG COLLECTION</h2>
                <p class="tech-tag mb-0">[ EXCLUSIVE SELECTION ]</p>
            </div>
            
            <div class="d-flex flex-wrap gap-2">
                <a href="index.php#katalog-section" class="filter-pill <?= empty($kategori_id) ? 'active' : ''; ?>">ALL ITEMS</a>
                <?php foreach ($kategori_list as $kat): ?>
                    <a href="index.php?kategori=<?= $kat['id_kategori']; ?>#katalog-section" 
                       class="filter-pill <?= $kategori_id == $kat['id_kategori'] ? 'active' : ''; ?>">
                        <?= strtoupper(htmlspecialchars($kat['nama_kategori'])); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="row g-4 mb-5">
            <?php if (count($produk_list) > 0): ?>
                <?php foreach ($produk_list as $p): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card-ice-product">
                            <span class="position-absolute top-0 start-0 badge <?= $p['jenis_produk'] == 'pre_order' ? 'bg-primary' : 'bg-dark'; ?> m-3 px-2 py-1 rounded tech-tag">
                                <?= $p['jenis_produk'] == 'pre_order' ? '[ PRE-ORDER ]' : '[ READY STOCK ]'; ?>
                            </span>

                            <img src="assets/uploads/<?= !empty($p['foto_produk']) ? $p['foto_produk'] : 'default.jpg'; ?>" 
                                 alt="<?= htmlspecialchars($p['nama_produk']); ?>"
                                 onerror="this.src='https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=400&q=80'">
                            
                            <div>
                                <div class="card-ice-title"><?= htmlspecialchars($p['nama_produk']); ?></div>
                                <div class="card-ice-desc text-truncate mb-2"><?= htmlspecialchars($p['deskripsi']); ?></div>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="card-ice-price">Rp <?= number_format($p['harga'], 0, ',', '.'); ?></span>
                                    <a href="detail.php?id=<?= $p['id_produk']; ?>" class="btn btn-dark btn-sm rounded-circle p-2" title="Detail & Order">
                                        <i class="bi bi-arrow-up-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <p class="tech-tag fs-5">NO PRODUCTS FOUND IN THIS CATEGORY.</p>
                </div>
            <?php endif; ?>
        </div>

        <footer class="border-top border-secondary pt-4 pb-5 text-center text-white-50">
            <p class="tech-tag mb-1">&copy; <?= date('Y'); ?> DHIRA BAKERY™ OFFICIAL. ALL RIGHTS RESERVED.</p>
            <small class="small">ORDER VIA DANA • GOPAY • TRANSFER BANK • WHATSAPP INSTANT</small>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
