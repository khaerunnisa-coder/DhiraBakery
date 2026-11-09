<?php
require_once 'auth_check.php';
require_once '../config/koneksi.php';

// Filter Periode Tanggal (Default: Bulan Ini)
$tgl_mulai   = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-01');
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : date('Y-m-d');

// Query Ringkasan Laporan Omset (Status Sudah Dibayar)
$stmt_sum = $pdo->prepare("SELECT 
    COUNT(*) AS total_transaksi,
    IFNULL(SUM(total_pembayaran), 0) AS total_omset,
    IFNULL(SUM(total_harga_produk), 0) AS total_produk_nominal
FROM pesanan 
WHERE status_pembayaran = 'sudah_dibayar' 
  AND DATE(tanggal_pesan) BETWEEN ? AND ?");
$stmt_sum->execute([$tgl_mulai, $tgl_selesai]);
$summary = $stmt_sum->fetch();

// Query Rincian Pesanan Lunas
$stmt_list = $pdo->prepare("SELECT p.*, pl.nama_pelanggan 
FROM pesanan p 
LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan 
WHERE p.status_pembayaran = 'sudah_dibayar' 
  AND DATE(p.tanggal_pesan) BETWEEN ? AND ?
ORDER BY p.tanggal_pesan DESC");
$stmt_list->execute([$tgl_mulai, $tgl_selesai]);
$laporan_list = $stmt_list->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan — DHIRA BAKERY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time(); ?>">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background-color: #ffffff !important; color: #000000 !important; }
            .futuristic-card { background: none !important; border: 1px solid #ddd !important; color: #000 !important; }
            .table-dark { --bs-table-bg: transparent !important; --bs-table-color: #000 !important; }
        }
    </style>
</head>
<body>

    <div class="container py-2">
        <!-- HEADER TOP NAVBAR ADMIN -->
        <header class="d-flex flex-column flex-md-row justify-content-between align-items-center admin-navbar gap-3 no-print">
            <div class="d-flex align-items-center gap-2">
                <a href="index.php" class="brand-title fs-4 text-white text-decoration-none me-2">DHIRA ADMIN</a>
            </div>

            <!-- Menu Pilihan Atas -->
            <nav class="d-flex flex-wrap gap-2 justify-content-center">
                <a href="index.php" class="admin-nav-btn">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="pesanan.php" class="admin-nav-btn">
                    <i class="bi bi-receipt"></i> Data Pesanan
                </a>
                <a href="produk.php" class="admin-nav-btn">
                    <i class="bi bi-box-seam"></i> Kelola Produk
                </a>
                <a href="laporan.php" class="admin-nav-btn active">
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

        <!-- TITLE & FILTER SECTION -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h3 class="display-6 fw-bold text-dark mb-0">LAPORAN PENJUALAN TOKO</h3>
                <small class="tech-tag text-muted">PERIODE: <?= date('d/m/Y', strtotime($tgl_mulai)); ?> S.D. <?= date('d/m/Y', strtotime($tgl_selesai)); ?></small>
            </div>
            
            <div class="d-flex gap-2 no-print">
                <button onclick="window.print()" class="btn-cream">
                    <i class="bi bi-printer me-1"></i> CETAK LAPORAN
                </button>
            </div>
        </div>

        <!-- FORM FILTER PERIODE TANGGAL -->
        <div class="futuristic-card mb-4 p-4 no-print">
            <form action="" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="tech-tag mb-1 d-block" style="color: #e5dabe !important; font-weight: 700;">TANGGAL MULAI</label>
                    <input type="date" name="tgl_mulai" class="form-control form-control-tech" value="<?= $tgl_mulai; ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="tech-tag mb-1 d-block" style="color: #e5dabe !important; font-weight: 700;">TANGGAL SELESAI</label>
                    <input type="date" name="tgl_selesai" class="form-control form-control-tech" value="<?= $tgl_selesai; ?>" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn-terracotta w-100 justify-content-center py-2" style="border-radius: 12px !important;">
                        <i class="bi bi-filter me-1"></i> TAMPILKAN LAPORAN
                    </button>
                </div>
            </form>
        </div>

        <!-- KOTAK RINGKASAN REKAPITULASI -->
        <div class="row g-3 mb-4">
            <div class="col-md-4 col-6">
                <div class="futuristic-card p-3">
                    <small class="tech-tag d-block mb-1" style="color: #e5dabe !important;">TRANSAKSI LUNAS</small>
                    <h3 class="fw-bold text-white mb-0" style="font-family: 'Plus Jakarta Sans', sans-serif;"><?= number_format($summary['total_transaksi']); ?> Transaksi</h3>
                </div>
            </div>
            <div class="col-md-4 col-6">
                <div class="futuristic-card p-3">
                    <small class="tech-tag d-block mb-1" style="color: #e5dabe !important;">TOTAL NOMINAL PRODUK</small>
                    <h3 class="fw-bold mb-0" style="color: #e5a93c; font-family: 'Plus Jakarta Sans', sans-serif;">Rp <?= number_format($summary['total_produk_nominal'], 0, ',', '.'); ?></h3>
                </div>
            </div>
            <div class="col-md-4 col-12">
                <div class="futuristic-card p-3">
                    <small class="tech-tag d-block mb-1" style="color: #e5dabe !important;">TOTAL OMSET BERSIH (LUNAS)</small>
                    <h3 class="fw-bold mb-0" style="color: #e5a93c; font-family: 'Plus Jakarta Sans', sans-serif;">Rp <?= number_format($summary['total_omset'], 0, ',', '.'); ?></h3>
                </div>
            </div>
        </div>

        <!-- TABEL RINCIAN TRANSAKSI LAPORAN -->
        <div class="futuristic-card p-4">
            <h5 class="fw-bold text-white mb-3" style="letter-spacing: 0.5px;"><i class="bi bi-list-check me-2"></i>Rincian Transaksi Terbayar</h5>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr class="tech-tag border-secondary">
                            <th>TANGGAL</th>
                            <th>KODE NOTA</th>
                            <th>PELANGGAN</th>
                            <th>PENERIMA</th>
                            <th>HARGA PRODUK</th>
                            <th>ONGKIR</th>
                            <th>TOTAL BAYAR</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($laporan_list) > 0): ?>
                            <?php foreach($laporan_list as $l): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($l['tanggal_pesan'])); ?></td>
                                    <td><strong>#<?= htmlspecialchars($l['kode_pesanan']); ?></strong></td>
                                    <td><?= htmlspecialchars($l['nama_pelanggan'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($l['nama_penerima']); ?></td>
                                    <td>Rp <?= number_format($l['total_harga_produk'], 0, ',', '.'); ?></td>
                                    <td>Rp <?= number_format($l['biaya_pengiriman'], 0, ',', '.'); ?></td>
                                    <td class="fw-bold text-warning">Rp <?= number_format($l['total_pembayaran'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-white-50">Tidak ada transaksi lunas pada periode ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-5 pt-4 border-top border-secondary-subtle text-center text-muted no-print">
            <small class="tech-tag">&copy; <?= date('Y'); ?> DHIRA BAKERY CONTROL CENTER</small>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>