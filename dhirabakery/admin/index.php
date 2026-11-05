<?php
require_once '../config/koneksi.php';

$total_pesanan = $pdo->query("SELECT COUNT(*) FROM pesanan")->fetchColumn();
$pesanan_pending = $pdo->query("SELECT COUNT(*) FROM pesanan WHERE status_pembayaran = 'belum_dibayar'")->fetchColumn();
$total_produk = $pdo->query("SELECT COUNT(*) FROM produk WHERE status_aktif = 1")->fetchColumn();
$omset = $pdo->query("SELECT SUM(total_pembayaran) FROM pesanan WHERE status_pembayaran = 'lunas'")->fetchColumn();

$stmt_recent = $pdo->query("SELECT * FROM pesanan ORDER BY id_pesanan DESC LIMIT 5");
$pesanan_terbaru = $stmt_recent->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard — DHIRA BAKERY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <div class="d-flex">
        <!-- Sidebar -->
        <div class="bg-dark text-white p-3 min-vh-100" style="width: 250px;">
            <h4 class="brand-title text-center text-white mb-4">DHIRA ADMIN</h4>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item mb-1"><a href="index.php" class="nav-link active bg-light text-dark fw-bold"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                <li class="nav-item mb-1"><a href="pesanan.php" class="nav-link text-white-50"><i class="bi bi-receipt me-2"></i> Data Pesanan</a></li>
                <li class="nav-item mb-1"><a href="produk.php" class="nav-link text-white-50"><i class="bi bi-box-seam me-2"></i> Kelola Produk</a></li>
                <li class="nav-item mb-1"><a href="laporan.php" class="nav-link text-white-50"><i class="bi bi-file-earmark-bar-graph me-2"></i> Laporan</a></li>
                <li class="nav-item mt-4"><a href="../index.php" class="nav-link text-warning"><i class="bi bi-box-arrow-left me-2"></i> Lihat Toko</a></li>
            </ul>
        </div>

        <div class="flex-fill p-4">
            <h3 class="fw-bold mb-4 text-white">[ DASHBOARD OPERASIONAL TOKO ]</h3>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="futuristic-card p-3">
                        <span class="tech-tag">TOTAL ORDERS</span>
                        <h2 class="fw-bold text-white mb-0 mt-1"><?= $total_pesanan; ?></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="futuristic-card p-3">
                        <span class="tech-tag text-warning">UNPAID ORDERS</span>
                        <h2 class="fw-bold text-warning mb-0 mt-1"><?= $pesanan_pending; ?></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="futuristic-card p-3">
                        <span class="tech-tag text-info">TOTAL PRODUCTS</span>
                        <h2 class="fw-bold text-info mb-0 mt-1"><?= $total_produk; ?></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="futuristic-card p-3">
                        <span class="tech-tag text-success">TOTAL OMSET (LUNAS)</span>
                        <h4 class="fw-bold text-success mb-0 mt-1">Rp <?= number_format($omset ? $omset : 0, 0, ',', '.'); ?></h4>
                    </div>
                </div>
            </div>

            <div class="futuristic-card p-0 overflow-hidden">
                <div class="p-3 border-bottom border-secondary">
                    <h5 class="fw-bold text-white mb-0">Pesanan Masuk Terbaru</h5>
                </div>
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
                            <?php if(count($pesanan_terbaru) > 0): ?>
                                <?php foreach($pesanan_terbaru as $p): ?>
                                    <tr>
                                        <td><strong>#<?= $p['kode_pesanan']; ?></strong></td>
                                        <td><?= htmlspecialchars($p['nama_penerima']); ?><br><small class="text-white-50"><?= $p['telepon_penerima']; ?></small></td>
                                        <td><?= date('d-m-Y H:i', strtotime($p['jadwal_pengiriman'])); ?></td>
                                        <td class="fw-bold text-warning">Rp <?= number_format($p['total_pembayaran'], 0, ',', '.'); ?></td>
                                        <td>
                                            <span class="badge <?= $p['status_pembayaran'] == 'lunas' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                                <?= ucfirst($p['status_pembayaran']); ?>
                                            </span>
                                        </td>
                                        <td><a href="pesanan.php" class="btn btn-sm btn-outline-light">Detail</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-4 text-white-50">Belum ada transaksi.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
