<?php
require_once '../config/koneksi.php';

if (isset($_POST['update_status'])) {
    $id_pesanan = intval($_POST['id_pesanan']);
    $status_bayar = htmlspecialchars($_POST['status_pembayaran']);
    $status_pesan = htmlspecialchars($_POST['status_pesanan']);

    $stmt = $pdo->prepare("UPDATE pesanan SET status_pembayaran = ?, status_pesanan = ? WHERE id_pesanan = ?");
    $stmt->execute([$status_bayar, $status_pesan, $id_pesanan]);

    $stmt_p = $pdo->prepare("UPDATE pembayaran SET status_pembayaran = ? WHERE id_pesanan = ?");
    $stmt_p->execute([$status_bayar == 'lunas' ? 'success' : 'pending', $id_pesanan]);

    header("Location: pesanan.php");
    exit();
}

$stmt = $pdo->query("SELECT * FROM pesanan ORDER BY id_pesanan DESC");
$pesanan_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Pesanan — DHIRA BAKERY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <div class="d-flex">
        <div class="bg-dark text-white p-3 min-vh-100" style="width: 250px;">
            <h4 class="brand-title text-center text-white mb-4">DHIRA ADMIN</h4>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item mb-1"><a href="index.php" class="nav-link text-white-50"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                <li class="nav-item mb-1"><a href="pesanan.php" class="nav-link active bg-light text-dark fw-bold"><i class="bi bi-receipt me-2"></i> Data Pesanan</a></li>
                <li class="nav-item mb-1"><a href="produk.php" class="nav-link text-white-50"><i class="bi bi-box-seam me-2"></i> Kelola Produk</a></li>
                <li class="nav-item mb-1"><a href="laporan.php" class="nav-link text-white-50"><i class="bi bi-file-earmark-bar-graph me-2"></i> Laporan</a></li>
                <li class="nav-item mt-4"><a href="../index.php" class="nav-link text-warning"><i class="bi bi-box-arrow-left me-2"></i> Lihat Toko</a></li>
            </ul>
        </div>

        <div class="flex-fill p-4">
            <h3 class="fw-bold text-white mb-4">[ KELOLA DATA PESANAN ]</h3>

            <div class="futuristic-card p-0 overflow-hidden">
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
                            <?php foreach($pesanan_list as $p): ?>
                                <tr>
                                    <td><strong>#<?= $p['kode_pesanan']; ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($p['nama_penerima']); ?></strong><br>
                                        <small class="text-white-50"><i class="bi bi-whatsapp text-success me-1"></i><?= $p['telepon_penerima']; ?></small>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($p['alamat_pengiriman']); ?></small>
                                        <?php if(!empty($p['catatan_pelanggan'])): ?>
                                            <br><small class="text-info"><em>Note: "<?= htmlspecialchars($p['catatan_pelanggan']); ?>"</em></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?= date('d/m/Y H:i', strtotime($p['jadwal_pengiriman'])); ?></small></td>
                                    <td class="fw-bold text-warning">Rp <?= number_format($p['total_pembayaran'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge <?= $p['status_pembayaran'] == 'lunas' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                            <?= ucfirst($p['status_pembayaran']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#modalStatus<?= $p['id_pesanan']; ?>">
                                            Edit Status
                                        </button>
                                    </td>
                                </tr>

                                <div class="modal fade" id="modalStatus<?= $p['id_pesanan']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form action="" method="POST" class="modal-content bg-dark text-white border border-secondary">
                                            <input type="hidden" name="id_pesanan" value="<?= $p['id_pesanan']; ?>">
                                            <div class="modal-header border-secondary">
                                                <h5 class="modal-title fw-bold">Update Status #<?= $p['kode_pesanan']; ?></h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="tech-tag">STATUS PEMBAYARAN</label>
                                                    <select name="status_pembayaran" class="form-select form-select-tech">
                                                        <option value="belum_dibayar" <?= $p['status_pembayaran'] == 'belum_dibayar' ? 'selected' : ''; ?>>Belum Dibayar / Menunggu Bukti</option>
                                                        <option value="lunas" <?= $p['status_pembayaran'] == 'lunas' ? 'selected' : ''; ?>>Lunas (Terverifikasi)</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="tech-tag">STATUS PESANAN</label>
                                                    <select name="status_pesanan" class="form-select form-select-tech">
                                                        <option value="menunggu_konfirmasi" <?= $p['status_pesanan'] == 'menunggu_konfirmasi' ? 'selected' : ''; ?>>Menunggu Konfirmasi</option>
                                                        <option value="diproses_dapur" <?= $p['status_pesanan'] == 'diproses_dapur' ? 'selected' : ''; ?>>Diproses Dapur</option>
                                                        <option value="siap_dikirim" <?= $p['status_pesanan'] == 'siap_dikirim' ? 'selected' : ''; ?>>Siap Dikirim / Ambil</option>
                                                        <option value="selesai" <?= $p['status_pesanan'] == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-secondary">
                                                <button type="submit" name="update_status" class="btn btn-light rounded-pill fw-bold">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
