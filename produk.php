<?php
session_start();
require_once '../config/koneksi.php';

// Catatan: Pastikan nama session di bawah ini SAMA PERSIS dengan yang dibuat saat login admin.
// Jika halaman login belum siap atau masih testing, biarkan baris di bawah ini dikomentari dulu:
// if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit; }

$upload_dir = '../assets/uploads/';
$pesan_sukses = '';
$pesan_error  = '';

// PROSES HAPUS PRODUK
if (isset($_GET['hapus'])) {
    $id_hapus = intval($_GET['hapus']);
    if ($id_hapus > 0) {
        // Ambil nama foto dulu agar file fisiknya ikut terhapus
        $stmtFoto = $pdo->prepare("SELECT foto_produk FROM produk WHERE id_produk = ?");
        $stmtFoto->execute([$id_hapus]);
        $dataFoto = $stmtFoto->fetch(PDO::FETCH_ASSOC);

        if ($dataFoto && !empty($dataFoto['foto_produk'])) {
            $path_foto = $upload_dir . $dataFoto['foto_produk'];
            if (file_exists($path_foto)) {
                unlink($path_foto);
            }
        }

        $stmtDel = $pdo->prepare("DELETE FROM produk WHERE id_produk = ?");
        if ($stmtDel->execute([$id_hapus])) {
            $pesan_sukses = "Produk berhasil dihapus.";
        } else {
            $pesan_error = "Gagal menghapus produk.";
        }
    }
}

// AMBIL SEMUA DATA PRODUK + NAMA KATEGORI
$sql = "SELECT p.*, k.nama_kategori 
        FROM produk p 
        LEFT JOIN kategori_produk k ON p.id_kategori = k.id_kategori 
        ORDER BY p.id_produk DESC";
$produk_list = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk — Admin Dhira Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f5efe6; color: #23120b; }
        .card-bakery { background-color: #ffffff; border: none; border-radius: 20px; box-shadow: 0 10px 25px rgba(35,18,11,0.08); }
        .btn-chocolate { background-color: #23120b; color: #f5efe6; font-weight: 600; }
        .btn-chocolate:hover { background-color: #7c3626; color: #ffffff; }
        .thumb-produk { width: 64px; height: 64px; object-fit: cover; border-radius: 12px; border: 1px solid #e5dabe; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-box-seam me-2"></i>Daftar Produk Dhira Bakery</h3>
            <p class="text-muted mb-0">Kelola katalog roti, kue, harga, dan foto produk.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-dark rounded-pill px-3">
                <i class="bi bi-speedometer2 me-1"></i> Dashboard
            </a>
            <a href="tambah_produk.php" class="btn btn-chocolate rounded-pill px-4">
                <i class="bi bi-plus-circle me-1"></i> Tambah Produk
            </a>
        </div>
    </div>

    <?php if ($pesan_sukses): ?>
        <div class="alert alert-success rounded-4 shadow-sm"><?= htmlspecialchars($pesan_sukses); ?></div>
    <?php endif; ?>

    <?php if ($pesan_error): ?>
        <div class="alert alert-danger rounded-4 shadow-sm"><?= htmlspecialchars($pesan_error); ?></div>
    <?php endif; ?>

    <div class="card card-bakery p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Foto</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Jenis</th>
                        <th>Harga</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($produk_list)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                Belum ada data produk. Klik <strong>Tambah Produk</strong> untuk mulai mengisi katalog.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($produk_list as $row): ?>
                            <?php
                                $ada_foto = !empty($row['foto_produk']) && file_exists($upload_dir . $row['foto_produk']);
                                $src_foto = $ada_foto 
                                    ? $upload_dir . htmlspecialchars($row['foto_produk']) 
                                    : 'https://via.placeholder.com/100x100/23120b/f5efe6?text=No+Img';
                            ?>
                            <tr>
                                <td>
                                    <img src="<?= $src_foto; ?>" alt="Foto" class="thumb-produk">
                                </td>
                                <td class="fw-bold"><?= htmlspecialchars($row['nama_produk']); ?></td>
                                <td><?= htmlspecialchars($row['nama_kategori'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($row['jenis_produk'] === 'pre_order'): ?>
                                        <span class="badge bg-warning text-dark">Pre-Order</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark">Ready Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold">Rp <?= number_format($row['harga'], 0, ',', '.'); ?></td>
                                <td>
                                    <?php if ($row['status_aktif'] == 1): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="edit_produk.php?id=<?= $row['id_produk']; ?>" 
                                       class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="produk.php?hapus=<?= $row['id_produk']; ?>" 
                                       class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                       onclick="return confirm('Yakin ingin menghapus produk <?= htmlspecialchars(addslashes($row['nama_produk'])); ?>?');">
                                        <i class="bi bi-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>