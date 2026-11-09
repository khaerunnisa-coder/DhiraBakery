<?php
require_once 'auth_check.php';
require_once '../config/koneksi.php';

$upload_dir = '../assets/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// 1. HANDLER TAMBAH PRODUK (DENGAN UPLOAD GAMBAR)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_produk'])) {
    $nama        = htmlspecialchars($_POST['nama_produk']);
    $id_kategori = intval($_POST['id_kategori']);
    $jenis       = htmlspecialchars($_POST['jenis_produk']);
    $harga       = floatval($_POST['harga']);
    $stok        = intval($_POST['stok']);
    $deskripsi   = htmlspecialchars($_POST['deskripsi']);
    $foto_name   = 'default.jpg';

    if (isset($_FILES['foto_produk']) && $_FILES['foto_produk']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['foto_produk']['tmp_name'];
        $fileName = $_FILES['foto_produk']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = time() . '_' . uniqid() . '.' . $fileExtension;
            if (move_uploaded_file($fileTmpPath, $upload_dir . $newFileName)) {
                $foto_name = $newFileName;
            }
        }
    }

    $stmt = $pdo->prepare("INSERT INTO produk (id_kategori, nama_produk, deskripsi, jenis_produk, harga, foto_produk, status_aktif) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([$id_kategori, $nama, $deskripsi, $jenis, $harga, $foto_name]);
    $id_prod = $pdo->lastInsertId();

    $stmt_s = $pdo->prepare("INSERT INTO stok_produk (id_produk, jumlah_stok) VALUES (?, ?)");
    $stmt_s->execute([$id_prod, $stok]);

    header("Location: produk.php");
    exit();
}

// 2. HANDLER EDIT PRODUK & UBAH GAMBAR
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_produk'])) {
    $id_produk   = intval($_POST['id_produk']);
    $nama        = htmlspecialchars($_POST['nama_produk']);
    $id_kategori = intval($_POST['id_kategori']);
    $jenis       = htmlspecialchars($_POST['jenis_produk']);
    $harga       = floatval($_POST['harga']);
    $stok        = intval($_POST['stok']);
    $deskripsi   = htmlspecialchars($_POST['deskripsi']);
    $foto_lama   = htmlspecialchars($_POST['foto_lama']);
    $foto_name   = $foto_lama;

    if (isset($_FILES['foto_produk']) && $_FILES['foto_produk']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['foto_produk']['tmp_name'];
        $fileName = $_FILES['foto_produk']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = time() . '_' . uniqid() . '.' . $fileExtension;
            if (move_uploaded_file($fileTmpPath, $upload_dir . $newFileName)) {
                $foto_name = $newFileName;
                if ($foto_lama !== 'default.jpg' && file_exists($upload_dir . $foto_lama)) {
                    unlink($upload_dir . $foto_lama);
                }
            }
        }
    }

    $stmt = $pdo->prepare("UPDATE produk SET id_kategori = ?, nama_produk = ?, deskripsi = ?, jenis_produk = ?, harga = ?, foto_produk = ? WHERE id_produk = ?");
    $stmt->execute([$id_kategori, $nama, $deskripsi, $jenis, $harga, $foto_name, $id_produk]);

    $stmt_s = $pdo->prepare("UPDATE stok_produk SET jumlah_stok = ? WHERE id_produk = ?");
    $stmt_s->execute([$stok, $id_produk]);

    header("Location: produk.php");
    exit();
}

// 3. HANDLER HAPUS PRODUK
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_produk'])) {
    $id_produk = intval($_POST['id_produk']);
    
    $stmt_foto = $pdo->prepare("SELECT foto_produk FROM produk WHERE id_produk = ?");
    $stmt_foto->execute([$id_produk]);
    $prod = $stmt_foto->fetch();

    if ($prod && $prod['foto_produk'] !== 'default.jpg' && file_exists($upload_dir . $prod['foto_produk'])) {
        unlink($upload_dir . $prod['foto_produk']);
    }

    $stmt_del = $pdo->prepare("DELETE FROM produk WHERE id_produk = ?");
    $stmt_del->execute([$id_produk]);

    header("Location: produk.php");
    exit();
}

// GET DATA PRODUK & KATEGORI
$stmt = $pdo->query("SELECT p.*, k.nama_kategori, s.jumlah_stok FROM produk p LEFT JOIN kategori_produk k ON p.id_kategori = k.id_kategori LEFT JOIN stok_produk s ON p.id_produk = s.id_produk ORDER BY p.id_produk DESC");
$produk_list = $stmt->fetchAll();
$kategori_list = $pdo->query("SELECT * FROM kategori_produk WHERE status_aktif = 1")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk — DHIRA BAKERY</title>
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
                <a href="pesanan.php" class="admin-nav-btn">
                    <i class="bi bi-receipt"></i> Data Pesanan
                </a>
                <a href="produk.php" class="admin-nav-btn active">
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

        <!-- KONTEN KELOLA PRODUK -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <h3 class="display-6 fw-bold text-dark mb-0">KELOLA PRODUK BAKERY</h3>
            <button class="btn-terracotta" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="bi bi-plus-circle me-1"></i> TAMBAH PRODUK BARU
            </button>
        </div>

        <div class="futuristic-card p-4">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr class="tech-tag border-secondary">
                            <th>FOTO</th>
                            <th>PRODUK</th>
                            <th>KATEGORI</th>
                            <th>TIPE</th>
                            <th>HARGA</th>
                            <th>STOK</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($produk_list) > 0): ?>
                            <?php foreach($produk_list as $p): ?>
                                <tr>
                                    <td>
                                        <img src="../assets/uploads/<?= !empty($p['foto_produk']) ? $p['foto_produk'] : 'default.jpg'; ?>" 
                                             alt="<?= htmlspecialchars($p['nama_produk']); ?>" 
                                             class="rounded-3" 
                                             style="width: 50px; height: 50px; object-fit: cover;"
                                             onerror="this.src='https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=200&q=80'">
                                    </td>
                                    <td><strong><?= htmlspecialchars($p['nama_produk']); ?></strong></td>
                                    <td><?= htmlspecialchars($p['nama_kategori']); ?></td>
                                    <td>
                                        <span class="badge <?= $p['jenis_produk'] == 'pre_order' ? 'bg-primary' : 'bg-success'; ?>">
                                            <?= strtoupper($p['jenis_produk']); ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold" style="color: #e5a93c;">Rp <?= number_format($p['harga'], 0, ',', '.'); ?></td>
                                    <td><?= $p['jumlah_stok'] !== null ? $p['jumlah_stok'] : 0; ?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <!-- Tombol Edit -->
                                            <button class="btn btn-sm btn-outline-warning rounded-pill px-2" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $p['id_produk']; ?>">
                                                <i class="bi bi-pencil-square me-1"></i> Edit
                                            </button>

                                            <!-- Tombol Hapus -->
                                            <button class="btn btn-sm btn-outline-danger rounded-circle p-2" data-bs-toggle="modal" data-bs-target="#modalHapus<?= $p['id_produk']; ?>" title="Hapus Produk">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>

                                        <!-- Modal Edit Produk -->
                                        <div class="modal fade" id="modalEdit<?= $p['id_produk']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <form action="" method="POST" enctype="multipart/form-data" class="modal-content bg-dark text-white border border-secondary text-start">
                                                    <div class="modal-header border-secondary">
                                                        <h5 class="modal-title fw-bold">Edit Produk & Gambar</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id_produk" value="<?= $p['id_produk']; ?>">
                                                        <input type="hidden" name="foto_lama" value="<?= htmlspecialchars($p['foto_produk']); ?>">

                                                        <div class="mb-3 text-center">
                                                            <label class="tech-tag d-block mb-2" style="color: #e5dabe !important; font-weight: 700;">GAMBAR SAAT INI</label>
                                                            <img src="../assets/uploads/<?= !empty($p['foto_produk']) ? $p['foto_produk'] : 'default.jpg'; ?>" 
                                                                 class="rounded-3 border border-secondary" style="width: 110px; height: 110px; object-fit: cover;"
                                                                 onerror="this.src='https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=200&q=80'">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="tech-tag mb-1 d-block" style="color: #e5dabe !important; font-weight: 700;">GANTI GAMBAR (JPG/PNG/WEBP)</label>
                                                            <input type="file" name="foto_produk" class="form-control form-control-tech" accept="image/*">
                                                            <small class="text-white-50">Biarkan kosong jika tidak ingin mengubah gambar.</small>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="tech-tag mb-1 d-block" style="color: #e5dabe !important; font-weight: 700;">NAMA PRODUK</label>
                                                            <input type="text" name="nama_produk" class="form-control form-control-tech" value="<?= htmlspecialchars($p['nama_produk']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="tech-tag mb-1 d-block" style="color: #e5dabe !important; font-weight: 700;">KATEGORI</label>
                                                            <select name="id_kategori" class="form-select form-select-tech" required>
                                                                <?php foreach($kategori_list as $k): ?>
                                                                    <option value="<?= $k['id_kategori']; ?>" <?= $k['id_kategori'] == $p['id_kategori'] ? 'selected' : ''; ?>>
                                                                        <?= htmlspecialchars($k['nama_kategori']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="tech-tag mb-1 d-block" style="color: #e5dabe !important; font-weight: 700;">JENIS PENJUALAN</label>
                                                            <select name="jenis_produk" class="form-select form-select-tech" required>
                                                                <option value="ready_stock" <?= $p['jenis_produk'] == 'ready_stock' ? 'selected' : ''; ?>>Ready Stock (Harian)</option>
                                                                <option value="pre_order" <?= $p['jenis_produk'] == 'pre_order' ? 'selected' : ''; ?>>Pre-Order (PO)</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="tech-tag mb-1 d-block" style="color: #e5dabe !important; font-weight: 700;">HARGA (RP)</label>
                                                            <input type="number" name="harga" class="form-control form-control-tech" value="<?= intval($p['harga']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="tech-tag mb-1 d-block" style="color: #e5dabe !important; font-weight: 700;">STOK</label>
                                                            <input type="number" name="stok" class="form-control form-control-tech" value="<?= intval($p['jumlah_stok']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="tech-tag mb-1 d-block" style="color: #e5dabe !important; font-weight: 700;">DESKRIPSI</label>
                                                            <textarea name="deskripsi" class="form-control form-control-tech" rows="2"><?= htmlspecialchars($p['deskripsi']); ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-secondary">
                                                        <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="edit_produk" class="btn btn-warning rounded-pill fw-bold">Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        <!-- Modal Konfirmasi Hapus -->
                                        <div class="modal fade" id="modalHapus<?= $p['id_produk']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content bg-dark text-white border border-danger text-start">
                                                    <div class="modal-header border-secondary">
                                                        <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Apakah Anda yakin ingin menghapus produk <strong>"<?= htmlspecialchars($p['nama_produk']); ?>"</strong>?
                                                    </div>
                                                    <div class="modal-footer border-secondary">
                                                        <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                                                        <form action="" method="POST" class="d-inline">
                                                            <input type="hidden" name="id_produk" value="<?= $p['id_produk']; ?>">
                                                            <button type="submit" name="hapus_produk" class="btn btn-danger rounded-pill fw-bold">Ya, Hapus</button>
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
                                <td colspan="7" class="text-center py-4 text-white-50">Belum ada data produk.</td>
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

    <!-- Modal Tambah Produk -->
    <div class="modal fade" id="modalTambah" tabindex="-1">
        <div class="modal-dialog">
            <form action="" method="POST" enctype="multipart/form-data" class="modal-content bg-dark text-white border border-secondary text-start">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title fw-bold">Tambah Produk Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="tech-tag mb-1 d-block" style="color: #e5dabe !important; font-weight: 700;">FOTO PRODUK (JPG/PNG/WEBP)</label>
                        <input type="file" name="foto_produk" class="form-control form-control-tech" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="tech-tag mb-1 d-block" style="color: #e5dabe !important; font-weight: 700;">NAMA PRODUK</label>
                        <input type="text" name="nama_produk" class="form-control form-control-tech" required>
                    </div>
                    <div class="mb-3">
                        <label class="tech-tag mb-1 d-block" style="color: #e5dabe !important; font-weight: 700;">KATEGORI</label>
                        <select name="id_kategori" class="form-select form-select-tech" required>
                            <?php foreach($kategori_list as $k): ?>
                                <option value="<?= $k['id_kategori']; ?>"><?= htmlspecialchars($k['nama_kategori']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="tech-tag mb-1 d-block" style="color: #e5dabe !important; font-weight: 700;">JENIS PENJUALAN</label>
                        <select name="jenis_produk" class="form-select form-select-tech" required>
                            <option value="ready_stock">Ready Stock (Harian)</option>
                            <option value="pre_order">Pre-Order (PO)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="tech-tag mb-1 d-block" style="color: #e5dabe !important; font-weight: 700;">HARGA (RP)</label>
                        <input type="number" name="harga" class="form-control form-control-tech" required>
                    </div>
                    <div class="mb-3">
                        <label class="tech-tag mb-1 d-block" style="color: #e5dabe !important; font-weight: 700;">STOK INITIAL</label>
                        <input type="number" name="stok" class="form-control form-control-tech" value="10" required>
                    </div>
                    <div class="mb-3">
                        <label class="tech-tag mb-1 d-block" style="color: #e5dabe !important; font-weight: 700;">DESKRIPSI</label>
                        <textarea name="deskripsi" class="form-control form-control-tech" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_produk" class="btn btn-light rounded-pill fw-bold">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>