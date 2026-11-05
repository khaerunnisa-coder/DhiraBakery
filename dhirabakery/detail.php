<?php
session_start();
require_once 'config/koneksi.php';

$id_produk = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $pdo->prepare("SELECT p.*, k.nama_kategori, s.jumlah_stok FROM produk p LEFT JOIN kategori_produk k ON p.id_kategori = k.id_kategori LEFT JOIN stok_produk s ON p.id_produk = s.id_produk WHERE p.id_produk = ? AND p.status_aktif = 1");
$stmt->execute([$id_produk]);
$produk = $stmt->fetch();

if (!$produk) {
    header("Location: index.php");
    exit();
}

$stmt_rasa = $pdo->prepare("SELECT * FROM varian_rasa WHERE id_produk = ? AND tersedia = 1");
$stmt_rasa->execute([$id_produk]);
$varian_rasa = $stmt_rasa->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $qty = intval($_POST['jumlah']);
    $id_rasa = isset($_POST['id_rasa']) ? intval($_POST['id_rasa']) : null;
    $nama_rasa = 'Original';
    $harga_tambahan = 0;

    if ($id_rasa) {
        $stmt_r = $pdo->prepare("SELECT * FROM varian_rasa WHERE id_rasa = ?");
        $stmt_r->execute([$id_rasa]);
        $r = $stmt_r->fetch();
        if ($r) {
            $nama_rasa = $r['nama_rasa'];
            $harga_tambahan = $r['harga_tambahan'];
        }
    }

    $cart_item = [
        'id_produk'    => $produk['id_produk'],
        'nama_produk'  => $produk['nama_produk'],
        'jenis_produk' => $produk['jenis_produk'],
        'harga_dasar'  => $produk['harga'],
        'id_rasa'      => $id_rasa,
        'nama_rasa'    => $nama_rasa,
        'harga_total'  => $produk['harga'] + $harga_tambahan,
        'qty'          => $qty,
        'foto'         => $produk['foto_produk']
    ];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    $_SESSION['cart'][] = $cart_item;

    header("Location: keranjang.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($produk['nama_produk']); ?> — DHIRA BAKERY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="container py-4">
        <header class="d-flex justify-content-between align-items-center mb-4 navbar-tech">
            <a href="index.php" class="brand-title fs-2 text-white text-decoration-none">DHIRA BAKERY™</a>
            <a href="index.php" class="nav-link">[ BACK TO CATALOG ]</a>
        </header>

        <div class="row g-4 align-items-center">
            <div class="col-md-6 text-center">
                <div class="card-ice-product p-3">
                    <img src="assets/uploads/<?= !empty($produk['foto_produk']) ? $produk['foto_produk'] : 'default.jpg'; ?>" 
                         class="img-fluid rounded-4 shadow" style="height: 380px; object-fit: cover; width: 100%;"
                         onerror="this.src='https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=800&q=80'">
                </div>
            </div>

            <div class="col-md-6">
                <div class="futuristic-card">
                    <span class="badge <?= $produk['jenis_produk'] == 'pre_order' ? 'bg-primary' : 'bg-dark'; ?> px-3 py-2 tech-tag mb-2">
                        <?= $produk['jenis_produk'] == 'pre_order' ? '[ PRE-ORDER CAKE ]' : '[ READY STOCK ]'; ?>
                    </span>
                    <h1 class="display-6 fw-bold text-white mb-2"><?= htmlspecialchars($produk['nama_produk']); ?></h1>
                    <p class="tech-tag text-info mb-3">KATEGORI: <?= htmlspecialchars($produk['nama_kategori']); ?></p>

                    <h3 class="card-ice-price fs-2 text-warning mb-3">
                        Rp <?= number_format($produk['harga'], 0, ',', '.'); ?>
                    </h3>

                    <p class="text-white-50 mb-4"><?= htmlspecialchars($produk['deskripsi']); ?></p>

                    <form action="" method="POST">
                        <?php if (count($varian_rasa) > 0): ?>
                            <div class="mb-3">
                                <label class="tech-tag mb-1">PILIH VARIAN RASA:</label>
                                <select name="id_rasa" class="form-select form-select-tech" required>
                                    <?php foreach ($varian_rasa as $vr): ?>
                                        <option value="<?= $vr['id_rasa']; ?>">
                                            <?= htmlspecialchars($vr['nama_rasa']); ?> 
                                            <?= $vr['harga_tambahan'] > 0 ? '(+ Rp '.number_format($vr['harga_tambahan'],0,',','.').')' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <label class="tech-tag mb-1">JUMLAH (QTY):</label>
                            <input type="number" name="jumlah" value="1" min="1" max="<?= $produk['jumlah_stok'] ? $produk['jumlah_stok'] : 50; ?>" class="form-control form-control-tech" style="width: 120px;" required>
                        </div>

                        <button type="submit" name="add_to_cart" class="btn btn-light btn-lg rounded-pill fw-bold text-dark px-4 me-2">
                            <i class="bi bi-cart-plus me-1"></i> ADD TO CART
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
