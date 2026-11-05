<?php
session_start();
require_once 'config/koneksi.php';

if (isset($_GET['action']) && $_GET['action'] == 'remove') {
    $index = intval($_GET['index']);
    if (isset($_SESSION['cart'][$index])) {
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }
    header("Location: keranjang.php");
    exit();
}

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$grand_total = 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>KERANJANG — DHIRA BAKERY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="container py-4">
        <header class="d-flex justify-content-between align-items-center mb-4 navbar-tech">
            <a href="index.php" class="brand-title fs-2 text-white text-decoration-none">DHIRA BAKERY™</a>
            <a href="index.php" class="nav-link">[ CONTINUE SHOPPING ]</a>
        </header>

        <h3 class="fw-bold mb-4 text-white">[ SHOPPING BAG CART ]</h3>

        <?php if (count($cart) > 0): ?>
            <div class="futuristic-card mb-4 p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0">
                        <thead>
                            <tr class="tech-tag border-secondary">
                                <th class="ps-4">PRODUK</th>
                                <th>VARIAN</th>
                                <th>HARGA</th>
                                <th>QTY</th>
                                <th>SUBTOTAL</th>
                                <th class="text-end pe-4">HAPUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart as $idx => $item): 
                                $subtotal = $item['harga_total'] * $item['qty'];
                                $grand_total += $subtotal;
                            ?>
                                <tr>
                                    <td class="ps-4">
                                        <strong><?= htmlspecialchars($item['nama_produk']); ?></strong>
                                        <br><span class="badge bg-secondary tech-tag"><?= strtoupper($item['jenis_produk']); ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($item['nama_rasa']); ?></td>
                                    <td>Rp <?= number_format($item['harga_total'], 0, ',', '.'); ?></td>
                                    <td><?= $item['qty']; ?></td>
                                    <td class="fw-bold text-warning">Rp <?= number_format($subtotal, 0, ',', '.'); ?></td>
                                    <td class="text-end pe-4">
                                        <a href="keranjang.php?action=remove&index=<?= $idx; ?>" class="btn btn-sm btn-outline-danger rounded-circle">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center p-4 futuristic-card">
                <div>
                    <span class="tech-tag d-block">TOTAL ESTIMATION</span>
                    <span class="fs-2 fw-bold text-warning">Rp <?= number_format($grand_total, 0, ',', '.'); ?></span>
                </div>
                <a href="checkout.php" class="btn btn-light btn-lg rounded-pill fw-bold text-dark px-4">
                    PROCEED TO CHECKOUT <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        <?php else: ?>
            <div class="futuristic-card text-center py-5">
                <i class="bi bi-bag-x display-1 text-white-50 mb-3 d-block"></i>
                <h4 class="text-white mb-2">KERANJANG MASIH KOSONG</h4>
                <p class="tech-tag mb-4">Silakan pilih roti atau kue favorit Anda terlebih dahulu.</p>
                <a href="index.php" class="btn btn-light rounded-pill fw-bold text-dark px-4">KEMBALI KE KATALOG</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
