<?php
require_once '../config/koneksi.php';

$stmt = $pdo->query("SELECT p.*, pm.metode_pembayaran FROM pesanan p LEFT JOIN pembayaran pm ON p.id_pesanan = pm.id_pesanan WHERE p.status_pembayaran = 'lunas' ORDER BY p.id_pesanan DESC");
$laporan_list = $stmt->fetchAll();

$total_omset = 0;
foreach($laporan_list as $l) {
    $total_omset += $l['total_pembayaran'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan — DHIRA BAKERY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <div class="d-flex">
        <div class="bg-dark text-white p-3 min-vh-100 no-print" style="width: 250px;">
            <h4 class="brand-title text-center text-white mb-4">DHIRA ADMIN</h4>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item mb-1"><a href="index.php" class="nav-link text-white-50"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                <li class="nav-item mb-1"><a href="pesanan.php" class="nav-link text-white-50"><i class="bi bi-receipt me-2"></i> Data Pesanan</a></li>
                <li class="nav-item mb-1"><a href="produk.php" class="nav-link text-white-50"><i class="bi bi-box-seam me-2"></i> Kelola Produk</a></li>
                <li class="nav-item mb-1"><a href="laporan.php" class="nav-link active bg-light text-dark fw-bold"><i class="bi bi-file-earmark-bar-graph me-2"></i> Laporan</a></li>
                <li class="nav-item mt-4"><a href="../index.php" class="nav-link text-warning"><i class="bi bi-box-arrow-left me-2"></i> Lihat Toko</a></li>
            </ul>
        </div>

        <div class="flex-fill p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                <h3 class="fw-bold text-white mb-0">[ LAPORAN OMSET LUNAS ]</h3>
                <button onclick="window.print()" class="btn btn-light rounded-pill fw-bold"><i class="bi bi-printer me-1"></i> Cetak Laporan (PDF)</button>
            </div>

            <div class="futuristic-card p-4 bg-white text-dark">
                <div class="text-center mb-4">
                    <h2 class="fw-bold mb-1">DHIRA BAKERY™</h2>
                    <p class="text-muted mb-0">Laporan Transaksi Penjualan Terverifikasi Lunas</p>
                    <small class="tech-tag text-muted">TANGGAL CETAK: <?= date('d F Y H:i'); ?></small>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Kode Nota</th>
                                <th>Tanggal Pesan</th>
                                <th>Nama Pemesan</th>
                                <th>Metode Bayar</th>
                                <th>Total Transaksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($laporan_list) > 0): $no=1; foreach($laporan_list as $row): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td>#<?= $row['kode_pesanan']; ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($row['tanggal_pesan'])); ?></td>
                                    <td><?= htmlspecialchars($row['nama_penerima']); ?></td>
                                    <td><?= htmlspecialchars($row['metode_pembayaran']); ?></td>
                                    <td class="fw-bold">Rp <?= number_format($row['total_pembayaran'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="6" class="text-center text-muted">Belum ada transaksi lunas.</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5" class="text-end fw-bold">TOTAL OMSET TERJUAL:</th>
                                <th class="text-danger fs-5 fw-bold">Rp <?= number_format($total_omset, 0, ',', '.'); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="d-flex justify-content-between mt-5 text-center">
                    <div>
                        <p class="mb-5">Diperiksa Oleh,</p>
                        <p class="fw-bold mb-0">( Admin Dhira Bakery )</p>
                    </div>
                    <div>
                        <p class="mb-5">Disetujui Oleh,</p>
                        <p class="fw-bold mb-0">( Pemilik Toko )</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
