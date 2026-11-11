<?php
session_start();
require_once 'config/koneksi.php';

$stmt_kat = $pdo->query("SELECT * FROM kategori_produk WHERE status_aktif = 1");
$kategori_list = $stmt_kat->fetchAll();

$kategori_id = isset($_GET['kategori']) ? $_GET['kategori'] : '';
if (!empty($kategori_id)) {
    $stmt_prod = $pdo->prepare("SELECT p.*, s.jumlah_stok FROM produk p LEFT JOIN stok_produk s ON p.id_produk = s.id_produk WHERE p.id_kategori = ? AND p.status_aktif = 1 ORDER BY p.id_produk DESC");
    $stmt_prod->execute([$kategori_id]);
} else {
    $stmt_prod = $pdo->query("SELECT p.*, s.jumlah_stok FROM produk p LEFT JOIN stok_produk s ON p.id_produk = s.id_produk WHERE p.status_aktif = 1 ORDER BY p.id_produk DESC");
}
$produk_list = $stmt_prod->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DHIRA BAKERY — Artisan Bread & Pastry Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="container py-2">
       <header class="d-flex justify-content-between align-items-center navbar-tech px-4 py-3">
    <a href="index.php" class="brand-title fs-4 text-decoration-none">DHIRA BAKERY</a>

    <div class="d-flex align-items-center gap-2">
        <a href="keranjang.php" class="btn btn-outline-light position-relative rounded-pill px-3 py-2 me-1">
            <i class="bi bi-bag me-1"></i> CART
            <?php if (!empty($_SESSION['cart'])): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?= count($_SESSION['cart']); ?>
                </span>
            <?php endif; ?>
        </a>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="dropdown">
                <button class="btn btn-warning rounded-pill px-3 py-2 dropdown-toggle fw-bold" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-fill me-1"></i> <?= htmlspecialchars($_SESSION['user_nama']); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-dark">
                    <li><a class="dropdown-item text-white px-3 py-2" href="logout.php"><i class="bi bi-box-arrow-right me-2 text-danger"></i> Logout Pelanggan</a></li>
                </ul>
            </div>
        <?php else: ?>
            <a href="login.php" class="btn btn-light rounded-pill px-3 py-2 text-dark fw-bold"><i class="bi bi-person me-1"></i> LOGIN</a>
        <?php endif; ?>

        <a href="admin/login.php" class="btn btn-sm btn-dark rounded-pill px-3 py-2 border border-secondary text-white-50" title="Login Admin Toko"><i class="bi bi-shield-lock me-1"></i> ADMIN</a>
    </div>
</header>


<section class="hero-container position-relative overflow-hidden mt-4 mb-5 shadow-lg">
    <div class="row align-items-center">
        <div class="col-lg-6 mb-4 mb-lg-0 pe-lg-4">          
            <h1 class="hero-title">LEZATNYA PAGI</h1>
            <div class="hero-subtitle-italic">dimulai di sini</div>
            <h1 class="hero-title">ARTISAN BREAD</h1>

            <p class="hero-desc mt-3">
                Resep autentik dari bahan-bahan segar pilihan tanpa pengawet. Melayani pesanan harian & kue khusus perayaan Anda.
            </p>

            <div>
                <a href="#katalog-section" class="btn-cream">
                    LIHAT KATALOG <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>

       <div class="col-lg-6 text-center hero-logo-wrapper">
    <div class="hero-logo-badge">
        <img src="assets/img/logo.png" 
             alt="Logo Dhira Bakery" 
             onerror="this.src='https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=800&q=80'">
    </div>
</div>
</section>

        <div class="row g-3 mb-5">
            <div class="col-md-3 col-6">
                <div class="p-3 bg-white rounded-4 border border-secondary-subtle d-flex align-items-center gap-3 shadow-sm">
                    <i class="bi bi-patch-check fs-2 text-danger"></i>
                    <div>
                        <h6 class="fw-bold mb-0">100% ALAMI</h6>
                        <small class="text-muted">Bahan Premium</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3 bg-white rounded-4 border border-secondary-subtle d-flex align-items-center gap-3 shadow-sm">
                    <i class="bi bi-lightning-charge fs-2 text-warning"></i>
                    <div>
                        <h6 class="fw-bold mb-0">FRESH EVERYDAY</h6>
                        <small class="text-muted">Panggang Harian</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3 bg-white rounded-4 border border-secondary-subtle d-flex align-items-center gap-3 shadow-sm">
                    <i class="bi bi-heart-pulse fs-2 text-danger"></i>
                    <div>
                        <h6 class="fw-bold mb-0">TANPA PENGAWET</h6>
                        <small class="text-muted">Sehat & Bergizi</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3 bg-white rounded-4 border border-secondary-subtle d-flex align-items-center gap-3 shadow-sm">
                    <i class="bi bi-truck fs-2 text-success"></i>
                    <div>
                        <h6 class="fw-bold mb-0">KURIR INSTAN</h6>
                        <small class="text-muted">Kirim via GoSend</small>
                    </div>
                </div>
            </div>
        </div>

    <div id="katalog-section" class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3 border-top border-secondary-subtle pt-5">
        <div>
            <h2 class="display-6 fw-bold mb-0 text-dark">KATALOG PILIHAN</h2>
        <p class="tech-tag mb-0" style="color: var(--text-muted, #7a6b63);">[ EXCLUSIVE BREAD & CAKES ]</p>
    </div>
    
    <div class="d-flex flex-wrap gap-2">
        <a href="index.php#katalog-section" class="filter-pill <?= empty($kategori_id) ? 'active' : ''; ?>">SEMUA PRODUK</a>
        <?php foreach ($kategori_list as $kat): ?>
            <a href="index.php?kategori=<?= $kat['id_kategori']; ?>#katalog-section" 
               class="filter-pill <?= $kategori_id == $kat['id_kategori'] ? 'active' : ''; ?>">
                <?= strtoupper(htmlspecialchars($kat['nama_kategori'])); ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

        <div class="row g-4 mb-5">
            <?php if (count($produk_list) > 0): ?>
                <?php foreach ($produk_list as $p): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card-ice-product">
                            <div class="position-relative">
                                <span class="position-absolute top-0 start-0 badge <?= $p['jenis_produk'] == 'pre_order' ? 'bg-primary' : 'bg-dark'; ?> m-2 px-2 py-1 rounded tech-tag">
                                    <?= $p['jenis_produk'] == 'pre_order' ? '[ PRE-ORDER ]' : '[ READY STOCK ]'; ?>
                                </span>

                                <img src="assets/uploads/<?= !empty($p['foto_produk']) ? $p['foto_produk'] : 'default.jpg'; ?>" 
                                     alt="<?= htmlspecialchars($p['nama_produk']); ?>"
                                     onerror="this.src='https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=400&q=80'">
                            </div>
                            
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
                    <p class="tech-tag fs-5">TIDAK ADA PRODUK PADA KATEGORI INI.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="futuristic-card text-center py-5 mb-5">
            <h2 class="display-6 fw-bold mb-2">SARAPAN LEZAT HARI INI</h2>
            <p class="text-white-50 mb-4" style="max-width: 600px; margin: 0 auto;">Pesan sekarang untuk pengiriman instan langsung ke rumah Anda atau jadwalkan kue perayaan ulang tahun.</p>
            <a href="#katalog-section" class="btn btn-light rounded-pill px-4 py-2 fw-bold text-dark">PESAN SEKARANG</a>
        </div>

        <footer class="border-top border-secondary-subtle pt-4 pb-5 text-center text-muted">
            <p class="tech-tag mb-1">&copy; <?= date('Y'); ?> DHIRA BAKERY™ OFFICIAL. ALL RIGHTS RESERVED.</p>
            <small class="small">PEMBAYARAN QRIS (DANA/GOPAY/OVO) • TRANSFER BANK • WHATSAPP INSTANT</small>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>