<?php
session_start();
require_once '../config/koneksi.php';

$upload_dir   = '../assets/uploads/';
$pesan_sukses = '';
$pesan_error  = '';

// PROSES SIMPAN PRODUK BARU & MULTI-UPLOAD FOTO
if (isset($_POST['simpan_produk'])) {
    $nama_produk  = trim($_POST['nama_produk']);
    $id_kategori  = intval($_POST['id_kategori']);
    $jenis_produk = $_POST['jenis_produk'];
    $harga        = floatval($_POST['harga']);
    $deskripsi    = trim($_POST['deskripsi']);
    $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;

    if (empty($nama_produk) || $id_kategori <= 0) {
        $pesan_error = "Nama produk dan kategori wajib diisi.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Simpan data utama produk terlebih dahulu
            $sql = "INSERT INTO produk (id_kategori, nama_produk, jenis_produk, harga, deskripsi, foto_produk, status_aktif) 
                    VALUES (?, ?, ?, ?, ?, NULL, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $id_kategori,
                $nama_produk,
                $jenis_produk,
                $harga,
                $deskripsi,
                $status_aktif
            ]);

            $id_produk_baru = $pdo->lastInsertId();
            $sampul_utama   = null;
            $ekstensi_diizinkan = ['jpg', 'jpeg', 'png', 'webp'];

            // 2. Proses upload banyak foto jika ada file yang dipilih
            if (!empty($_FILES['foto_produk']['name'][0])) {
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $total_files = count($_FILES['foto_produk']['name']);
                $stmtFoto    = $pdo->prepare("INSERT INTO produk_foto (id_produk, nama_file) VALUES (?, ?)");

                for ($i = 0; $i < $total_files; $i++) {
                    if ($_FILES['foto_produk']['error'][$i] === UPLOAD_ERR_OK) {
                        $file_tmp  = $_FILES['foto_produk']['tmp_name'][$i];
                        $file_name = $_FILES['foto_produk']['name'][$i];
                        $file_size = $_FILES['foto_produk']['size'][$i];
                        $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                  if (!in_array($ext, $ekstensi_diizinkan) || $file_size > 10 * 1024 * 1024) {
                    continue;
                    }

                        $filename_unik = 'produk_' . $id_produk_baru . '_' . time() . '_' . $i . '_' . rand(100, 999) . '.' . $ext;

                        if (move_uploaded_file($file_tmp, $upload_dir . $filename_unik)) {
                            $stmtFoto->execute([$id_produk_baru, $filename_unik]);

                            // Jadikan foto pertama yang berhasil diupload sebagai sampul utama
                            if ($sampul_utama === null) {
                                $sampul_utama = $filename_unik;
                            }
                        }
                    }
                }

                // Update kolom foto_produk (sampul utama) di tabel produk
                if ($sampul_utama !== null) {
                    $pdo->prepare("UPDATE produk SET foto_produk = ? WHERE id_produk = ?")
                        ->execute([$sampul_utama, $id_produk_baru]);
                }
            }

            $pdo->commit();
            header("Location: produk.php");
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $pesan_error = "Gagal menyimpan produk: " . $e->getMessage();
        }
    }
}

// Ambil daftar kategori aktif
$kategori_list = $pdo->query("SELECT * FROM kategori_produk WHERE status_aktif = 1")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk — Admin Dhira Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f5efe6; color: #23120b; }
        .card-bakery { background-color: #ffffff; border: none; border-radius: 20px; box-shadow: 0 10px 25px rgba(35,18,11,0.08); }
        .btn-chocolate { background-color: #23120b; color: #f5efe6; font-weight: 600; }
        .btn-chocolate:hover { background-color: #7c3626; color: #ffffff; }
        .preview-new-img {
            width: 95px;
            height: 95px;
            object-fit: cover;
            border-radius: 12px;
            border: 2px dashed #7c3626;
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0"><i class="bi bi-plus-circle me-2"></i>Tambah Produk Baru</h3>
        <a href="produk.php" class="btn btn-outline-dark rounded-pill px-4">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>

    <?php if ($pesan_error): ?>
        <div class="alert alert-danger rounded-4 shadow-sm"><?= htmlspecialchars($pesan_error); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="row g-4">
            <!-- KOLOM KIRI: UPLOAD FOTO PRODUK -->
            <div class="col-lg-5">
                <div class="card card-bakery p-4 h-100">
                    <h5 class="fw-bold mb-3">Foto Produk</h5>

                    <div class="mb-2">
                        <label for="inputFoto" class="form-label fw-semibold">
                            Pilih Foto Produk (Bisa Lebih dari 1 File)
                        </label>
                        <input type="file" 
                               class="form-control" 
                               id="inputFoto" 
                               name="foto_produk[]" 
                               accept="image/jpeg, image/png, image/webp"
                               multiple
                               onchange="previewMultipleFiles(this)">
                        <div class="form-text">
                            Foto pertama yang dipilih otomatis menjadi <strong>Sampul Utama</strong>. Format: JPG, PNG, WEBP (Maks. 2 MB/foto).
                        </div>
                    </div>

                    <!-- Area Live Preview Foto -->
                    <div id="previewContainer" class="d-flex flex-wrap gap-2 mt-3"></div>
                </div>
            </div>

            <!-- KOLOM KANAN: DETAIL INFORMASI PRODUK -->
            <div class="col-lg-7">
                <div class="card card-bakery p-4">
                    <h5 class="fw-bold mb-3">Informasi Produk</h5>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Produk</label>
                        <input type="text" name="nama_produk" class="form-control" required 
                               placeholder="Contoh: Croissant Almond / Custom Birthday Cake">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kategori</label>
                            <select name="id_kategori" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($kategori_list as $kat): ?>
                                    <option value="<?= $kat['id_kategori']; ?>">
                                        <?= htmlspecialchars($kat['nama_kategori']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jenis Pemesanan</label>
                            <select name="jenis_produk" class="form-select" required>
                                <option value="ready_stock">Ready Stock</option>
                                <option value="pre_order">Pre-Order (PO)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Harga (Rp)</label>
                        <input type="number" name="harga" class="form-control" required min="0" placeholder="Contoh: 25000">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi Produk</label>
                        <textarea name="deskripsi" rows="4" class="form-control" placeholder="Tuliskan komposisi, ukuran, atau keterangan pre-order..."></textarea>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="statusAktif" name="status_aktif" checked>
                        <label class="form-check-label fw-semibold" for="statusAktif">Tampilkan produk di katalog</label>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="simpan_produk" class="btn btn-chocolate btn-lg rounded-pill">
                            <i class="bi bi-save me-2"></i> Simpan Produk Baru
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function previewMultipleFiles(input) {
    const container = document.getElementById('previewContainer');
    container.innerHTML = '';

    if (input.files) {
        Array.from(input.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'preview-new-img';
                container.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    }
}
</script>

</body>
</html>