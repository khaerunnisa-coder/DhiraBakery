<?php
require_once 'auth_check.php';
require_once '../config/koneksi.php';

// 1. HANDLER UPDATE STATUS PESANAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id_pesanan        = intval($_POST['id_pesanan']);
    $status_pesanan    = htmlspecialchars($_POST['status_pesanan']);
    $status_pembayaran = htmlspecialchars($_POST['status_pembayaran']);

    $stmt = $pdo->prepare("UPDATE pesanan SET status_pesanan = ?, status_pembayaran = ? WHERE id_pesanan = ?");
    $stmt->execute([$status_pesanan, $status_pembayaran, $id_pesanan]);

    header("Location: pesanan.php");
    exit();
}

// 2. HANDLER HAPUS PESANAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_pesanan'])) {
    $id_pesanan = intval($_POST['id_pesanan']);
    
    $stmt_del = $pdo->prepare("DELETE FROM pesanan WHERE id_pesanan = ?");
    $stmt_del->execute([$id_pesanan]);

    header("Location: pesanan.php");
    exit();
}

// GET DATA PESANAN
$query = "SELECT p.*, pl.nama_pelanggan, pl.nomor_whatsapp 
          FROM pesanan p 
          LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan 
          ORDER BY p.id_pesanan DESC";
$pesanan_list = $pdo->query($query)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pesanan — DHIRA BAKERY</title>
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
                <a href="index.php" class="admin-nav-btn">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="pesanan.php" class="admin-nav-btn active">
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

        <!-- KONTEN PESANAN -->
        <div class="mb-4">
            <h3 class="display-6 fw-bold text-dark">KELOLA DATA PESANAN</h3>
        </div>

        <div class="futuristic-card p-4">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr class="tech-tag border-secondary">
                            <th>KODE NOTA</th>
                            <th>PELANGGAN</th>
                            <th>ALAMAT & CATATAN</th>
                            <th>JADWAL KIRIM</th>
                            <th>TOTAL</th>
                            <th>STATUS BAYAR</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pesanan_list) > 0): ?>
                            <?php foreach($pesanan_list as $p): ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($p['kode_pesanan']); ?></strong></td>
                                    <td>
                                        <div><strong><?= htmlspecialchars($p['nama_pelanggan'] ?? $p['nama_penerima']); ?></strong></div>
                                        <small class="text-success"><i class="bi bi-whatsapp me-1"></i><?= htmlspecialchars($p['telepon_penerima']); ?></small>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($p['alamat_pengiriman']); ?></div>
                                        <?php if (!empty($p['catatan_pelanggan'])): ?>
                                            <small class="text-info italic">Note: "<?= htmlspecialchars($p['catatan_pelanggan']); ?>"</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($p['jadwal_pengiriman'])); ?></td>
                                    <td class="fw-bold" style="color: #e5a93c;">Rp <?= number_format($p['total_pembayaran'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge <?= $p['status_pembayaran'] == 'sudah_dibayar' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                            <?= htmlspecialchars($p['status_pembayaran']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <!-- Tombol Edit Status -->
                                            <button class="btn btn-sm btn-outline-light rounded-pill px-2" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $p['id_pesanan']; ?>">
                                                Edit Status
                                            </button>

                                            <!-- Tombol Hapus -->
                                            <button class="btn btn-sm btn-outline-danger rounded-circle p-2" data-bs-toggle="modal" data-bs-target="#modalHapus<?= $p['id_pesanan']; ?>" title="Hapus Pesanan">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>

                                        <!-- Modal Edit Status -->
                                        <div class="modal fade" id="modalEdit<?= $p['id_pesanan']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <form action="" method="POST" class="modal-content bg-dark text-white border border-secondary text-start">
                                                    <div class="modal-header border-secondary">
                                                        <h5 class="modal-title fw-bold">Update Status Pesanan</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id_pesanan" value="<?= $p['id_pesanan']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="tech-tag mb-1 d-block" style="color: #e5dabe !important; font-weight: 700;">STATUS PESANAN</label>
                                                            <select name="status_pesanan" class="form-select form-select-tech">
                                                                <option value="menunggu_konfirmasi" <?= $p['status_pesanan'] == 'menunggu_konfirmasi' ? 'selected' : ''; ?>>Menunggu Konfirmasi</option>
                                                                <option value="diproses" <?= $p['status_pesanan'] == 'diproses' ? 'selected' : ''; ?>>Diproses / Dipanggang</option>
                                                                <option value="dikirim" <?= $p['status_pesanan'] == 'dikirim' ? 'selected' : ''; ?>>Dalam Pengiriman</option>
                                                                <option value="selesai" <?= $p['status_pesanan'] == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                                                                <option value="dibatalkan" <?= $p['status_pesanan'] == 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="tech-tag mb-1 d-block" style="color: #e5dabe !important; font-weight: 700;">STATUS PEMBAYARAN</label>
                                                            <select name="status_pembayaran" class="form-select form-select-tech">
                                                                <option value="belum_dibayar" <?= $p['status_pembayaran'] == 'belum_dibayar' ? 'selected' : ''; ?>>Belum Dibayar</option>
                                                                <option value="sudah_dibayar" <?= $p['status_pembayaran'] == 'sudah_dibayar' ? 'selected' : ''; ?>>Sudah Dibayar</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-secondary">
                                                        <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="update_status" class="btn btn-warning rounded-pill fw-bold">Simpan Status</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        <!-- Modal Konfirmasi Hapus -->
                                        <div class="modal fade" id="modalHapus<?= $p['id_pesanan']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content bg-dark text-white border border-danger text-start">
                                                    <div class="modal-header border-secondary">
                                                        <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus Pesanan</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Apakah Anda yakin ingin menghapus data pesanan <strong>"#<?= htmlspecialchars($p['kode_pesanan']); ?>"</strong> atas nama <strong><?= htmlspecialchars($p['nama_pelanggan'] ?? $p['nama_penerima']); ?></strong>?
                                                        <br><small class="text-white-50">Riwayat pembayaran dan rincian item pesanan ini akan terhapus secara permanen dari database.</small>
                                                    </div>
                                                    <div class="modal-footer border-secondary">
                                                        <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                                                        <form action="" method="POST" class="d-inline">
                                                            <input type="hidden" name="id_pesanan" value="<?= $p['id_pesanan']; ?>">
                                                            <button type="submit" name="hapus_pesanan" class="btn btn-danger rounded-pill fw-bold">Ya, Hapus</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-white-50">Belum ada data pesanan masuk.</td>
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