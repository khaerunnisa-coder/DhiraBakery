<?php
require_once 'auth_check.php';
require_once '../config/koneksi.php';

// Hitung Ringkasan Data
$total_pesanan  = $pdo->query("SELECT COUNT(*) FROM pesanan")->fetchColumn();
$belum_dibayar  = $pdo->query("SELECT COUNT(*) FROM pesanan WHERE status_pembayaran = 'belum_dibayar'")->fetchColumn();
$total_produk   = $pdo->query("SELECT COUNT(*) FROM produk WHERE status_aktif = 1")->fetchColumn();
$total_omset    = $pdo->query("SELECT SUM(total_pembayaran) FROM pesanan WHERE status_pembayaran = 'sudah_dibayar'")->fetchColumn() ?: 0;

// Ambil 5 Pesanan Terbaru
$stmt_recent = $pdo->query("SELECT p.*, pl.nama_pelanggan FROM pesanan p LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan ORDER BY p.id_pesanan DESC LIMIT 5");
$recent_orders = $stmt_recent->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — DHIRA BAKERY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time(); ?>">
</head>
<body>

    <div class="container py-2">
        <!-- HEADER TOP NAVBAR ADMIN -->
        <header class="d-flex flex-column flex-md-row justify-content-between align-items-center admin-navbar gap-3">
            <div class="d-flex align-items-center gap-2">
                <a href="index.php" class="brand-title fs-4 text-white text-decoration-none me-2">DHIRA ADMIN</a>
            </div>

            <!-- Menu Pilihan Atas -->
            <nav class="d-flex flex-wrap gap-2 justify-content-center">
                <a href="index.php" class="admin-nav-btn active">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="pesanan.php" class="admin-nav-btn">
                    <i class="bi bi-receipt"></i> Data Pesanan
                </a>
                <a href="produk.php" class="admin-nav-btn">
                    <i class="bi bi-box-seam"></i> Kelola Produk
                </a>
                <a href="laporan.php" class="admin-nav-btn">
                    <i class="bi bi-file-earmark-bar-graph"></i> Laporan
                </a>
            </nav>

            <!-- Action Buttons -->
            <div class="d-flex align-items-center gap-2">
                <a href="../index.php" class="btn btn-sm btn-outline-light rounded-pill px-3 py-2 text-warning fw-bold" target="_blank" title="Lihat Toko Utama">
                    <i class="bi bi-shop me-1"></i> Lihat Toko
                </a>
                <a href="logout.php" class="admin-btn-logout">
                    <i class="bi bi-power me-1"></i> Logout Admin
                </a>
            </div>
        </header>

        <!-- KONTEN DASHBOARD -->
        <div class="mb-4">
            <h3 class="display-6 fw-bold text-dark"> DASHBOARD OPERASIONAL TOKO </h3>
        </div>

        <!-- 4 Kotak Ringkasan Status -->
        <div class="row g-3 mb-5">
            <div class="col-md-3 col-6">
                <div class="futuristic-card p-3">
                    <small class="tech-tag d-block mb-1" style="color: #e5dabe !important;">TOTAL ORDERS</small>
                    <h2 class="fw-bold text-white mb-0"><?= number_format($total_pesanan); ?></h2>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="futuristic-card p-3">
                    <small class="tech-tag d-block mb-1" style="color: #e5dabe !important;">UNPAID ORDERS</small>
                    <h2 class="fw-bold mb-0" style="color: #e5a93c;"><?= number_format($belum_dibayar); ?></h2>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="futuristic-card p-3">
                    <small class="tech-tag d-block mb-1" style="color: #e5dabe !important;">TOTAL PRODUCTS</small>
                    <h2 class="fw-bold text-white mb-0"><?= number_format($total_produk); ?></h2>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="futuristic-card p-3">
                    <small class="tech-tag d-block mb-1" style="color: #e5dabe !important;">TOTAL OMSET (LUNAS)</small>
                    <h2 class="fw-bold mb-0" style="color: #e5a93c;">Rp <?= number_format($total_omset, 0, ',', '.'); ?></h2>
                </div>
            </div>
        </div>

        <!-- Tabel Pesanan Terbaru -->
        <div class="futuristic-card p-4">
            <h5 class="fw-bold text-white mb-3" style="letter-spacing: 0.5px;"><i class="bi bi-clock-history me-2"></i>Pesanan Masuk Terbaru</h5>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr class="tech-tag border-secondary">
                            <th>KODE NOTA</th>
                            <th>PEMESAN</th>
                            <th>JADWAL KIRIM</th>
                            <th>TOTAL</th>
                            <th>STATUS BAYAR</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_orders) > 0): ?>
                            <?php foreach ($recent_orders as $ro): ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($ro['kode_pesanan']); ?></strong></td>
                                    <td><?= htmlspecialchars($ro['nama_pelanggan'] ?? $ro['nama_penerima']); ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($ro['jadwal_pengiriman'])); ?></td>
                                    <td class="fw-bold" style="color: #e5a93c;">Rp <?= number_format($ro['total_pembayaran'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge <?= $ro['status_pembayaran'] == 'sudah_dibayar' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                            <?= htmlspecialchars($ro['status_pembayaran']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="pesanan.php" class="btn btn-sm btn-outline-light rounded-pill">Detail</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-white-50">Belum ada transaksi.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-5 pt-4 border-top border-secondary-subtle text-center text-muted">
            <small class="tech-tag">&copy; <?= date('Y'); ?> DHIRA BAKERY CONTROL CENTER</small>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>