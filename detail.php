<?php
session_start();
require_once 'config/koneksi.php';

$id_produk = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_produk <= 0) {
    header("Location: index.php");
    exit();
}

// 1. Ambil data produk utama
$stmt = $pdo->prepare("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori_produk k ON p.id_kategori = k.id_kategori WHERE p.id_produk = ? AND p.status_aktif = 1");
$stmt->execute([$id_produk]);
$produk = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produk) {
    die("Produk tidak ditemukan atau tidak aktif.");
}

// 2. Ambil semua foto dari galeri produk_foto
$stmt_foto = $pdo->prepare("SELECT nama_file FROM produk_foto WHERE id_produk = ? ORDER BY id_foto ASC");
$stmt_foto->execute([$id_produk]);
$semua_foto = $stmt_foto->fetchAll(PDO::FETCH_COLUMN);

// Pastikan foto sampul utama selalu berada di urutan pertama dan tidak duplikat
if (!empty($produk['foto_produk'])) {
    $semua_foto = array_values(array_unique(array_merge([$produk['foto_produk']], $semua_foto)));
}

// 3. Ambil daftar ulasan untuk produk ini
$stmt_ulasan = $pdo->prepare("SELECT * FROM ulasan_produk WHERE id_produk = ? ORDER BY id_ulasan DESC");
$stmt_ulasan->execute([$id_produk]);
$daftar_ulasan = $stmt_ulasan->fetchAll(PDO::FETCH_ASSOC);

// Proses Tambah ke Keranjang ($_SESSION['cart'])
$pesan_sukses = '';
if (isset($_POST['add_to_cart'])) {
    $qty = intval($_POST['qty'] ?? 1);
    if ($qty < 1) $qty = 1;

    $cart_key = $produk['id_produk'];

    if (isset($_SESSION['cart'][$cart_key])) {
        $_SESSION['cart'][$cart_key]['qty'] += $qty;
    } else {
        $_SESSION['cart'][$cart_key] = [
            'id_produk'    => $produk['id_produk'],
            'nama_produk'  => $produk['nama_produk'],
            'harga_total'  => $produk['harga'],
            'jenis_produk' => $produk['jenis_produk'],
            'foto'         => $produk['foto_produk'],
            'qty'          => $qty
        ];
    }
    $pesan_sukses = "Produk berhasil ditambahkan ke keranjang!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($produk['nama_produk']); ?> — Dhira Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background-color: #f5efe6; color: #23120b; }
        .card-detail { background-color: #ffffff; border: none; border-radius: 20px; box-shadow: 0 10px 25px rgba(35,18,11,0.08); }
        .btn-chocolate { background-color: #23120b; color: #f5efe6; font-weight: 600; }
        .btn-chocolate:hover { background-color: #7c3626; color: #ffffff; }
    </style>
</head>
<body>

    <div class="container py-5">
        <header class="d-flex justify-content-between align-items-center mb-4">
            <a href="index.php" class="fs-2 text-dark text-decoration-none fw-bold">DHIRA BAKERY</a>
            <a href="index.php" class="btn btn-outline-dark rounded-pill px-4">
                <i class="bi bi-arrow-left me-1"></i>  BACK TO CATALOG 
            </a>
        </header>

        <?php if ($pesan_sukses): ?>
            <div class="alert alert-success rounded-4 shadow-sm mb-4">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($pesan_sukses); ?> 
                <a href="checkout.php" class="alert-link fw-bold ms-2">Lanjut ke Checkout &rarr;</a>
            </div>
        <?php endif; ?>

        <div class="row g-4 align-items-start">
            <!-- KOLOM KIRI: CAROUSEL / SLIDER FOTO PRODUK -->
            <div class="col-lg-6">
                <div class="card card-detail p-3">
                    <?php if (!empty($semua_foto)): ?>
                        <!-- Bootstrap Carousel -->
                        <div id="carouselProduk" class="carousel slide" data-bs-ride="false">
                            <div class="carousel-inner rounded-4 shadow-sm overflow-hidden" style="background-color: #faf6f0;">
                                <?php foreach ($semua_foto as $idx => $nama_file): ?>
                                    <div class="carousel-item <?= $idx === 0 ? 'active' : ''; ?>">
                                        <img src="assets/uploads/<?= htmlspecialchars($nama_file); ?>" 
                                             class="d-block w-100" 
                                             style="height: 420px; object-fit: cover;" 
                                             alt="<?= htmlspecialchars($produk['nama_produk']); ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Tombol Panah Slider (Muncul jika foto > 1) -->
                            <?php if (count($semua_foto) > 1): ?>
                                <button class="carousel-control-prev" type="button" data-bs-target="#carouselProduk" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon bg-dark rounded-circle p-3" aria-hidden="true"></span>
                                    <span class="visually-hidden">Sebelumnya</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#carouselProduk" data-bs-slide="next">
                                    <span class="carousel-control-next-icon bg-dark rounded-circle p-3" aria-hidden="true"></span>
                                    <span class="visually-hidden">Selanjutnya</span>
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Thumbnail Kecil di Bawah untuk Klik Langsung -->
                        <?php if (count($semua_foto) > 1): ?>
                            <div class="d-flex gap-2 mt-3 overflow-auto pb-1">
                                <?php foreach ($semua_foto as $idx => $nama_file): ?>
                                    <img src="assets/uploads/<?= htmlspecialchars($nama_file); ?>" 
                                         data-bs-target="#carouselProduk" 
                                         data-bs-slide-to="<?= $idx; ?>" 
                                         class="rounded-3 border border-2 <?= $idx === 0 ? 'border-dark' : 'border-light'; ?>" 
                                         style="width: 75px; height: 75px; object-fit: cover; cursor: pointer;" 
                                         alt="Thumbnail <?= $idx + 1; ?>">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <img src="https://via.placeholder.com/500x400/23120b/f5efe6?text=No+Image" class="img-fluid rounded-4" alt="No Image">
                    <?php endif; ?>
                </div>
            </div>

            <!-- KOLOM KANAN: INFORMASI & TOMBOL BELI -->
            <div class="col-lg-6">
                <div class="card card-detail p-4">
                    <div class="mb-2">
                        <span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($produk['jenis_produk'] === 'pre_order' ? 'Pre-Order' : 'Ready Stock'); ?></span>
                        <span class="badge bg-dark ms-1"><?= htmlspecialchars($produk['nama_kategori'] ?? 'Umum'); ?></span>
                    </div>

                    <h2 class="fw-bold mb-3"><?= htmlspecialchars($produk['nama_produk']); ?></h2>
                    <h3 class="fw-bold text-danger mb-4">Rp <?= number_format($produk['harga'], 0, ',', '.'); ?></h3>

                    <div class="mb-4">
                        <h6 class="fw-semibold text-muted mb-1">Deskripsi Produk:</h6>
                        <p class="text-secondary" style="white-space: pre-line;"><?= htmlspecialchars($produk['deskripsi'] ?? 'Tidak ada deskripsi.'); ?></p>
                    </div>

                    <form method="POST">
                        <div class="mb-3" style="max-width: 150px;">
                            <label class="form-label fw-semibold">Jumlah (Qty)</label>
                            <input type="number" name="qty" class="form-control" value="1" min="1" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="add_to_cart" class="btn btn-chocolate btn-lg rounded-pill">
                                <i class="bi bi-cart-plus me-2"></i> ADD TO CART
                            </button>
                            <a href="checkout.php" class="btn btn-outline-dark btn-lg rounded-pill">
                                <i class="bi bi-bag-check me-2"></i> Lanjut ke Checkout
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- BAGIAN ULASAN PELANGGAN -->
        <div class="card card-detail p-4 mt-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-chat-square-quote-fill text-warning me-2"></i> Ulasan Pelanggan (<?= count($daftar_ulasan); ?>)
                </h5>
                <a href="pesanan_saya.php" class="btn btn-sm btn-outline-dark rounded-pill px-3">
                    <i class="bi bi-pencil-square me-1"></i> Tulis Ulasan Pesanan
                </a>
            </div>

            <?php if (!empty($daftar_ulasan)): ?>
                <div class="row g-3">
                    <?php foreach ($daftar_ulasan as $ul): ?>
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 border" style="background-color: #faf6f0;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-dark"><?= htmlspecialchars($ul['nama_pelanggan']); ?></strong>
                                    <span class="text-warning small">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi <?= $i <= $ul['rating'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                        <?php endfor; ?>
                                    </span>
                                </div>
                                <p class="small text-secondary mb-1"><?= htmlspecialchars($ul['komentar']); ?></p>
                                <small class="text-muted" style="font-size: 11px;"><?= date('d M Y, H:i', strtotime($ul['tanggal_ulasan'])); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted small mb-0">Belum ada ulasan untuk produk ini. Jadilah yang pertama mengulas setelah memesan!</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS Bundle (Wajib untuk Carousel & Tombol Geser) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>