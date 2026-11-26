<?php
session_start();
require_once '../config/koneksi.php';

$id_produk = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_produk <= 0) {
    header("Location: produk.php");
    exit;
}

$upload_dir   = '../assets/uploads/';
$pesan_sukses = '';
$pesan_error  = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'foto_dihapus') $pesan_sukses = "Foto berhasil dihapus dari galeri.";
    if ($_GET['msg'] === 'sampul_diubah') $pesan_sukses = "Foto sampul utama berhasil diperbarui.";
}

// 1. AMBIL DATA PRODUK SAAT INI
$stmt = $pdo->prepare("SELECT * FROM produk WHERE id_produk = ?");
$stmt->execute([$id_produk]);
$produk = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produk) {
    die("Produk tidak ditemukan.");
}

// 2. PROSES HAPUS SALAH SATU FOTO GALERI
if (isset($_GET['hapus_foto_id'])) {
    $id_foto = intval($_GET['hapus_foto_id']);
    $stmtCek = $pdo->prepare("SELECT * FROM produk_foto WHERE id_foto = ? AND id_produk = ?");
    $stmtCek->execute([$id_foto, $id_produk]);
    $fotoHapus = $stmtCek->fetch(PDO::FETCH_ASSOC);

    if ($fotoHapus) {
        $path_file = $upload_dir . $fotoHapus['nama_file'];
        if (file_exists($path_file)) {
            unlink($path_file);
        }

        $pdo->prepare("DELETE FROM produk_foto WHERE id_foto = ?")->execute([$id_foto]);

        if ($produk['foto_produk'] === $fotoHapus['nama_file']) {
            $stmtSisa = $pdo->prepare("SELECT nama_file FROM produk_foto WHERE id_produk = ? ORDER BY id_foto ASC LIMIT 1");
            $stmtSisa->execute([$id_produk]);
            $sisa = $stmtSisa->fetch(PDO::FETCH_ASSOC);
            $sampulBaru = $sisa ? $sisa['nama_file'] : null;

            $pdo->prepare("UPDATE produk SET foto_produk = ? WHERE id_produk = ?")->execute([$sampulBaru, $id_produk]);
        }

        header("Location: edit_produk.php?id=$id_produk&msg=foto_dihapus");
        exit;
    }
}

// 3. PROSES JADIKAN FOTO SEBAGAI SAMPUL UTAMA
if (isset($_GET['jadikan_sampul'])) {
    $id_foto = intval($_GET['jadikan_sampul']);
    $stmtCek = $pdo->prepare("SELECT nama_file FROM produk_foto WHERE id_foto = ? AND id_produk = ?");
    $stmtCek->execute([$id_foto, $id_produk]);
    $fotoPilih = $stmtCek->fetch(PDO::FETCH_ASSOC);

    if ($fotoPilih) {
        $pdo->prepare("UPDATE produk SET foto_produk = ? WHERE id_produk = ?")->execute([$fotoPilih['nama_file'], $id_produk]);
        header("Location: edit_produk.php?id=$id_produk&msg=sampul_diubah");
        exit;
    }
}

// 4. PROSES SIMPAN DATA PRODUK & UPLOAD BANYAK FOTO (MAKSIMAL 10 MB)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cek jika POST kosong akibat ukuran file melebihi post_max_size di php.ini
    if (empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $pesan_error = "Total ukuran file melebihi batas post_max_size di php.ini (" . ini_get('post_max_size') . ").";
    } else {
        $nama_produk  = trim($_POST['nama_produk'] ?? '');
        $id_kategori  = intval($_POST['id_kategori'] ?? 0);
        $jenis_produk = $_POST['jenis_produk'] ?? 'ready_stock';
        $harga        = floatval($_POST['harga'] ?? 0);
        $deskripsi    = trim($_POST['deskripsi'] ?? '');
        $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;

        $sampul_utama       = $produk['foto_produk'];
        $ekstensi_diizinkan = ['jpg', 'jpeg', 'png', 'webp'];
        $jumlah_terupload   = 0;
        $batas_10mb         = 10 * 1024 * 1024; // 10 MB

        try {
            if (!empty($_FILES['foto_produk']['name'][0])) {
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                if (!empty($sampul_utama)) {
                    $cekAda = $pdo->prepare("SELECT COUNT(*) FROM produk_foto WHERE id_produk = ? AND nama_file = ?");
                    $cekAda->execute([$id_produk, $sampul_utama]);
                    if ($cekAda->fetchColumn() == 0 && file_exists($upload_dir . $sampul_utama)) {
                        $pdo->prepare("INSERT INTO produk_foto (id_produk, nama_file) VALUES (?, ?)")->execute([$id_produk, $sampul_utama]);
                    }
                }

                $total_files    = count($_FILES['foto_produk']['name']);
                $stmtInsertFoto = $pdo->prepare("INSERT INTO produk_foto (id_produk, nama_file) VALUES (?, ?)");

                for ($i = 0; $i < $total_files; $i++) {
                    $err_code  = $_FILES['foto_produk']['error'][$i];
                    $file_name = $_FILES['foto_produk']['name'][$i];

                    if ($err_code !== UPLOAD_ERR_OK) {
                        if ($err_code === UPLOAD_ERR_INI_SIZE) {
                            $pesan_error .= "File '$file_name' ditolak server karena melebihi batas upload_max_filesize di php.ini (saat ini: " . ini_get('upload_max_filesize') . "). ";
                        } else {
                            $pesan_error .= "Gagal upload '$file_name' (Kode Error PHP: $err_code). ";
                        }
                        continue;
                    }

                    $file_tmp  = $_FILES['foto_produk']['tmp_name'][$i];
                    $file_size = $_FILES['foto_produk']['size'][$i];
                    $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                    if (!in_array($ext, $ekstensi_diizinkan)) {
                        $pesan_error .= "File '$file_name' ditolak (hanya JPG, PNG, WEBP). ";
                        continue;
                    }
                    if ($file_size > $batas_10mb) {
                        $pesan_error .= "File '$file_name' (" . round($file_size / 1048576, 2) . " MB) melebihi batas 10 MB. ";
                        continue;
                    }

                    $filename_unik = 'produk_' . $id_produk . '_' . time() . '_' . $i . '_' . rand(100, 999) . '.' . $ext;

                    if (move_uploaded_file($file_tmp, $upload_dir . $filename_unik)) {
                        $stmtInsertFoto->execute([$id_produk, $filename_unik]);
                        $jumlah_terupload++;

                        if (empty($sampul_utama)) {
                            $sampul_utama = $filename_unik;
                        }
                    } else {
                        $pesan_error .= "Gagal memindahkan file '$file_name' ke folder $upload_dir. ";
                    }
                }
            }

            $sql = "UPDATE produk SET 
                        id_kategori = ?, 
                        nama_produk = ?, 
                        jenis_produk = ?, 
                        harga = ?, 
                        deskripsi = ?, 
                        foto_produk = ?, 
                        status_aktif = ? 
                    WHERE id_produk = ?";
            $pdo->prepare($sql)->execute([
                $id_kategori,
                $nama_produk,
                $jenis_produk,
                $harga,
                $deskripsi,
                $sampul_utama,
                $status_aktif,
                $id_produk
            ]);

            $produk['id_kategori']  = $id_kategori;
            $produk['nama_produk']  = $nama_produk;
            $produk['jenis_produk'] = $jenis_produk;
            $produk['harga']        = $harga;
            $produk['deskripsi']    = $deskripsi;
            $produk['foto_produk']  = $sampul_utama;
            $produk['status_aktif'] = $status_aktif;

            if (empty($pesan_error)) {
                $pesan_sukses = "Data produk disimpan" . ($jumlah_terupload > 0 ? " & $jumlah_terupload foto baru berhasil diunggah." : ".");
            }
        } catch (PDOException $e) {
            $pesan_error = "Error Database: " . $e->getMessage();
        }
    }
}

$stmtGaleri = $pdo->prepare("SELECT * FROM produk_foto WHERE id_produk = ? ORDER BY id_foto ASC");
$stmtGaleri->execute([$id_produk]);
$galeri_foto = $stmtGaleri->fetchAll(PDO::FETCH_ASSOC);

$kategori_list = $pdo->query("SELECT * FROM kategori_produk WHERE status_aktif = 1")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk — Admin Dhira Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f5efe6; color: #23120b; }
        .card-bakery { background-color: #ffffff; border: none; border-radius: 20px; box-shadow: 0 10px 25px rgba(35,18,11,0.08); }
        .btn-chocolate { background-color: #23120b; color: #f5efe6; font-weight: 600; }
        .btn-chocolate:hover { background-color: #7c3626; color: #ffffff; }
        .foto-card { position: relative; border-radius: 14px; overflow: hidden; border: 2px solid #e5dabe; background: #faf6f0; }
        .foto-card.is-cover { border-color: #7c3626; }
        .foto-card img { width: 100%; height: 135px; object-fit: cover; }
        .preview-new-img { width: 90px; height: 90px; object-fit: cover; border-radius: 12px; border: 2px dashed #7c3626; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Produk</h3>
        <a href="produk.php" class="btn btn-outline-dark rounded-pill px-4">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>

    <?php if ($pesan_sukses): ?>
        <div class="alert alert-success rounded-4 shadow-sm"><?= htmlspecialchars($pesan_sukses); ?></div>
    <?php endif; ?>

    <?php if ($pesan_error): ?>
        <div class="alert alert-danger rounded-4 shadow-sm"><?= htmlspecialchars($pesan_error); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card card-bakery p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Galeri Foto Produk</h5>
                        <span class="badge bg-dark rounded-pill"><?= count($galeri_foto); ?> Foto</span>
                    </div>

                    <div class="row g-3 mb-4">
                        <?php if (!empty($galeri_foto)): ?>
                            <?php foreach ($galeri_foto as $gf): ?>
                                <?php $is_cover = ($produk['foto_produk'] === $gf['nama_file']); ?>
                                <div class="col-6">
                                    <div class="foto-card <?= $is_cover ? 'is-cover' : ''; ?>">
                                        <img src="<?= $upload_dir . htmlspecialchars($gf['nama_file']); ?>" alt="Foto Produk">
                                        <?php if ($is_cover): ?>
                                            <span class="badge bg-warning text-dark position-absolute top-0 start-0 m-2">
                                                <i class="bi bi-star-fill"></i> Sampul
                                            </span>
                                        <?php endif; ?>
                                        <div class="p-2 d-flex justify-content-between gap-1 bg-white">
                                            <?php if (!$is_cover): ?>
                                                <a href="edit_produk.php?id=<?= $id_produk; ?>&jadikan_sampul=<?= $gf['id_foto']; ?>" 
                                                   class="btn btn-xs btn-outline-dark py-0 px-2" style="font-size: 11px;">Sampul</a>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size: 11px;">Utama</span>
                                            <?php endif; ?>
                                            <a href="edit_produk.php?id=<?= $id_produk; ?>&hapus_foto_id=<?= $gf['id_foto']; ?>" 
                                               class="btn btn-xs btn-outline-danger py-0 px-2" style="font-size: 11px;"
                                               onclick="return confirm('Hapus foto ini?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="p-4 text-center rounded-4 border border-dashed text-muted" style="background:#faf6f0;">
                                    <i class="bi bi-images fs-2 d-block mb-1"></i>
                                    Belum ada foto terpasang.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-2">
                        <label for="inputFoto" class="form-label fw-semibold">
                            Tambah Foto Baru (Bisa Pilih Banyak Sekaligus)
                        </label>
                        <input type="file" 
                               class="form-control" 
                               id="inputFoto" 
                               name="foto_produk[]" 
                               accept="image/jpeg, image/png, image/webp"
                               multiple
                               onchange="previewMultipleFiles(this)">
                        <div class="form-text">Tahan tombol <strong>Ctrl</strong> untuk pilih banyak foto (Maks. <strong>10 MB/foto</strong> | Limit server saat ini: <?= ini_get('upload_max_filesize'); ?>).</div>
                    </div>

                    <div id="previewContainer" class="d-flex flex-wrap gap-2 mt-3"></div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card card-bakery p-4">
                    <h5 class="fw-bold mb-3">Informasi Produk</h5>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Produk</label>
                        <input type="text" name="nama_produk" class="form-control" required 
                               value="<?= htmlspecialchars($produk['nama_produk']); ?>">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kategori</label>
                            <select name="id_kategori" class="form-select" required>
                                <?php foreach ($kategori_list as $kat): ?>
                                    <option value="<?= $kat['id_kategori']; ?>" 
                                        <?= $kat['id_kategori'] == $produk['id_kategori'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($kat['nama_kategori']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jenis Pemesanan</label>
                            <select name="jenis_produk" class="form-select" required>
                                <option value="ready_stock" <?= $produk['jenis_produk'] == 'ready_stock' ? 'selected' : ''; ?>>Ready Stock</option>
                                <option value="pre_order" <?= $produk['jenis_produk'] == 'pre_order' ? 'selected' : ''; ?>>Pre-Order (PO)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Harga (Rp)</label>
                        <input type="number" name="harga" class="form-control" required min="0"
                               value="<?= intval($produk['harga']); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi Produk</label>
                        <textarea name="deskripsi" rows="4" class="form-control"><?= htmlspecialchars($produk['deskripsi']); ?></textarea>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="statusAktif" name="status_aktif" 
                               <?= $produk['status_aktif'] == 1 ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-semibold" for="statusAktif">Tampilkan produk di katalog</label>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="simpan_produk" class="btn btn-chocolate btn-lg rounded-pill">
                            <i class="bi bi-check-circle me-2"></i> Simpan Perubahan & Unggah Foto
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