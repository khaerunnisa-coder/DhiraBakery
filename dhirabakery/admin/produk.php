<?php
require_once '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_produk'])) {
    $nama        = htmlspecialchars($_POST['nama_produk']);
    $id_kategori = intval($_POST['id_kategori']);
    $jenis       = htmlspecialchars($_POST['jenis_produk']);
    $harga       = floatval($_POST['harga']);
    $stok        = intval($_POST['stok']);
    $deskripsi   = htmlspecialchars($_POST['deskripsi']);

    $stmt = $pdo->prepare("INSERT INTO produk (id_kategori, nama_produk, deskripsi, jenis_produk, harga, status_aktif) VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->execute([$id_kategori, $nama, $deskripsi, $jenis, $harga]);
    $id_prod = $pdo->lastInsertId();

    $stmt_s = $pdo->prepare("INSERT INTO stok_produk (id_produk, jumlah_stok) VALUES (?, ?)");
    $stmt_s->execute([$id_prod, $stok]);

    header("Location: produk.php");
    exit();
}

$stmt = $pdo->query("SELECT p.*, k.nama_kategori, s.jumlah_stok FROM produk p LEFT JOIN kategori_produk k ON p.id_kategori = k.id_kategori LEFT JOIN stok_produk s ON p.id_produk = s.id_produk ORDER BY p.id_produk DESC");
$produk_list = $stmt->fetchAll();
$kategori_list = $pdo->query("SELECT * FROM kategori_produk WHERE status_aktif = 1")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Produk — DHIRA BAKERY</title>
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
                <li class="nav-item mb-1"><a href="pesanan.php" class="nav-link text-white-50"><i class="bi bi-receipt me-2"></i> Data Pesanan</a></li>
                <li class="nav-item mb-1"><a href="produk.php" class="nav-link active bg-light text-dark fw-bold"><i class="bi bi-box-seam me-2"></i> Kelola Produk</a></li>
                <li class="nav-item mb-1"><a href="laporan.php" class="nav-link text-white-50"><i class="bi bi-file-earmark-bar-graph me-2"></i> Laporan</a></li>
                <li class="nav-item mt-4"><a href="../index.php" class="nav-link text-warning"><i class="bi bi-box-arrow-left me-2"></i> Lihat Toko</a></li>
            </ul>
        </div>

        <div class="flex-fill p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold text-white mb-0">[ KELOLA PRODUK BAKERY ]</h3>
                <button class="btn btn-light rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="bi bi-plus-circle me-1"></i> Tambah Produk</button>
            </div>

            <div class="futuristic-card p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0">
                        <thead>
                            <tr class="tech-tag border-secondary">
                                <th>PRODUK</th>
                                <th>KATEGORI</th>
                                <th>TIPE</th>
                                <th>HARGA</th>
                                <th>STOK</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($produk_list as $p): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($p['nama_produk']); ?></strong></td>
                                    <td><?= htmlspecialchars($p['nama_kategori']); ?></td>
                                    <td>
                                        <span class="badge <?= $p['jenis_produk'] == 'pre_order' ? 'bg-primary' : 'bg-success'; ?>">
                                            <?= strtoupper($p['jenis_produk']); ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold text-warning">Rp <?= number_format($p['harga'], 0, ',', '.'); ?></td>
                                    <td><?= $p['jumlah_stok'] !== null ? $p['jumlah_stok'] : 0; ?></td>
                                    <td><span class="badge bg-success">AKTIF</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah -->
    <div class="modal fade" id="modalTambah" tabindex="-1">
        <div class="modal-dialog">
            <form action="" method="POST" class="modal-content bg-dark text-white border border-secondary">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title fw-bold">Tambah Produk Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="tech-tag">NAMA PRODUK</label>
                        <input type="text" name="nama_produk" class="form-control form-control-tech" required>
                    </div>
                    <div class="mb-3">
                        <label class="tech-tag">KATEGORI</label>
                        <select name="id_kategori" class="form-select form-select-tech" required>
                            <?php foreach($kategori_list as $k): ?>
                                <option value="<?= $k['id_kategori']; ?>"><?= htmlspecialchars($k['nama_kategori']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="tech-tag">JENIS PENJUALAN</label>
                        <select name="jenis_produk" class="form-select form-select-tech" required>
                            <option value="ready_stock">Ready Stock (Harian)</option>
                            <option value="pre_order">Pre-Order (PO)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="tech-tag">HARGA (RP)</label>
                        <input type="number" name="harga" class="form-control form-control-tech" required>
                    </div>
                    <div class="mb-3">
                        <label class="tech-tag">STOK INITIAL</label>
                        <input type="number" name="stok" class="form-control form-control-tech" value="10" required>
                    </div>
                    <div class="mb-3">
                        <label class="tech-tag">DESKRIPSI</label>
                        <textarea name="deskripsi" class="form-control form-control-tech" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="submit" name="tambah_produk" class="btn btn-light rounded-pill fw-bold">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
