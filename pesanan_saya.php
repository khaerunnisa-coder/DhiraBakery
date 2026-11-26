<?php
session_start();
require_once 'config/koneksi.php';
require_once 'config/midtrans.php';

// Nomor WhatsApp Admin Dhirabakery (Format 628xxx tanpa + atau 0 di depan)
$nomor_wa_toko = "6282159170705";

// Ambil kode pesanan dari URL (?order_id= dari Midtrans atau ?kode= dari checkout) atau input pencarian
$kode_pesanan = trim($_GET['order_id'] ?? $_GET['kode'] ?? $_POST['cari_kode'] ?? '');
$pesan_notif  = '';
$error_notif  = '';

// 1. PROSES SIMPAN ULASAN PRODUK
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_ulasan'])) {
    $kode_pesanan   = trim($_POST['kode_pesanan'] ?? '');
    $id_produk      = intval($_POST['id_produk'] ?? 0);
    $nama_pelanggan = trim($_POST['nama_pelanggan'] ?? 'Pelanggan');
    $rating         = max(1, min(5, intval($_POST['rating'] ?? 5)));
    $komentar       = trim($_POST['komentar'] ?? '');

    if ($id_produk > 0 && !empty($kode_pesanan)) {
        $cek = $pdo->prepare("SELECT COUNT(*) FROM ulasan_produk WHERE kode_pesanan = ? AND id_produk = ?");
        $cek->execute([$kode_pesanan, $id_produk]);

        if ($cek->fetchColumn() == 0) {
            $ins = $pdo->prepare("INSERT INTO ulasan_produk (id_produk, kode_pesanan, nama_pelanggan, rating, komentar) VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$id_produk, $kode_pesanan, $nama_pelanggan, $rating, $komentar]);
            $pesan_notif = "Terima kasih! Ulasan Anda berhasil dikirim dan tampil di halaman produk.";
        } else {
            $error_notif = "Anda sudah memberikan ulasan untuk produk ini pada pesanan tersebut.";
        }
    }
}

$pesanan = null;
$detail_items = [];
$ulasan_sudah_ada = [];
$link_whatsapp = '';

if (!empty($kode_pesanan)) {
    // 2. AMBIL DATA PESANAN
    $stmt = $pdo->prepare("SELECT * FROM pesanan WHERE kode_pesanan = ?");
    $stmt->execute([$kode_pesanan]);
    $pesanan = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pesanan) {
        // Jika memakai Midtrans & status masih pending, cek status terbaru ke server Midtrans
        if (!empty($pesanan['snap_token']) && $pesanan['status_pembayaran'] === 'pending') {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $apiUrl = $protocol . $_SERVER['HTTP_HOST'] . '/dhirabakery/api/cek_status_midtrans.php?order_id=' . urlencode($kode_pesanan);
            $jsonStatus = @file_get_contents($apiUrl);
            $statusMidtrans = json_decode($jsonStatus, true) ?? [];

            $txStatus = $statusMidtrans['transaction_status'] ?? ($statusMidtrans['status_midtrans'] ?? '');

            if (in_array($txStatus, ['capture', 'settlement', 'lunas'])) {
                $pdo->prepare("UPDATE pesanan SET status_pembayaran = 'lunas', status_pesanan = 'diproses' WHERE kode_pesanan = ?")
                    ->execute([$kode_pesanan]);
                $pesanan['status_pembayaran'] = 'lunas';
                $pesanan['status_pesanan']    = 'diproses';
            } elseif (in_array($txStatus, ['cancel', 'deny', 'expire', 'gagal'])) {
                $pdo->prepare("UPDATE pesanan SET status_pembayaran = 'gagal', status_pesanan = 'dibatalkan' WHERE kode_pesanan = ?")
                    ->execute([$kode_pesanan]);
                $pesanan['status_pembayaran'] = 'gagal';
                $pesanan['status_pesanan']    = 'dibatalkan';
            }
        }

        // 3. DETEKSI METODE PEMBAYARAN & PENGIRIMAN DARI CATATAN PESANAN
        $catatan_raw = $pesanan['catatan_pelanggan'] ?? '';
        $metode_bayar_terdeteksi = !empty($pesanan['payment_url']) ? 'MIDTRANS' : 'BELUM_TERDETEKSI';
        $metode_kirim_terdeteksi = '-';

        if (preg_match('/^\[(.*?)\s*\|\s*(.*?)\]/s', $catatan_raw, $matches)) {
            $metode_kirim_terdeteksi = trim($matches[1]);
            $metode_bayar_terdeteksi = strtoupper(trim($matches[2]));
        }

        if (preg_match('/^\[(.*?)\s*\|\s*(.*?)\]\s*(.*)$/s', $catatan_raw, $matches)) {
            $metode_kirim_terdeteksi = trim($matches[1]);
            $metode_bayar_terdeteksi = strtoupper(trim($matches[2]));
            $catatan_bersih          = trim($matches[3]);
        }

        // 4. AMBIL DAFTAR PRODUK DALAM PESANAN INI
        $stmtItems = $pdo->prepare("
            SELECT dp.*, p.nama_produk, p.foto_produk 
            FROM detail_pesanan dp 
            LEFT JOIN produk p ON dp.id_produk = p.id_produk 
            WHERE dp.id_pesanan = ?
        ");
        $stmtItems->execute([$pesanan['id_pesanan']]);
        $detail_items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // Susun rincian item untuk pesan otomatis WhatsApp
        $daftar_teks_item = "";
        foreach ($detail_items as $itm) {
            $nama_p = $itm['nama_produk'] ?? 'Produk';
            $daftar_teks_item .= "- {$nama_p} ({$itm['jumlah']}x)\n";
        }

        $teks_wa = "Halo Admin Dhirabakery, saya ingin konfirmasi pesanan:\n\n"
                 . "*Kode Pesanan:* {$pesanan['kode_pesanan']}\n"
                 . "*Nama Pemesan:* {$pesanan['nama_penerima']}\n"
                 . "*Metode Bayar:* " . str_replace('_', ' ', $metode_bayar_terdeteksi) . "\n"
                 . "*Status Bayar:* " . strtoupper($pesanan['status_pembayaran']) . "\n"
                 . "*Total Tagihan:* Rp " . number_format($pesanan['total_pembayaran'], 0, ',', '.') . "\n\n"
                 . "*Rincian Pesanan:*\n{$daftar_teks_item}\n"
                 . "Terima kasih!";

        $link_whatsapp = "https://wa.me/" . $nomor_wa_toko . "?text=" . rawurlencode($teks_wa);

        // 5. CEK ULASAN YANG SUDAH DIKIRIM UNTUK PESANAN INI
        $stmtUlasan = $pdo->prepare("SELECT id_produk, rating, komentar FROM ulasan_produk WHERE kode_pesanan = ?");
        $stmtUlasan->execute([$kode_pesanan]);
        foreach ($stmtUlasan->fetchAll(PDO::FETCH_ASSOC) as $u) {
            $ulasan_sudah_ada[$u['id_produk']] = $u;
        }
    } else {
        $error_notif = "Kode pesanan '$kode_pesanan' tidak ditemukan.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pesanan & Ulasan — DHIRABAKERY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
</head>
<body>

    <div class="container py-4">
        <!-- NAVBAR -->
        <header class="d-flex flex-wrap justify-content-between align-items-center navbar-tech px-4 py-3 rounded-4 shadow mb-4 gap-2">
            <a href="index.php" class="brand-title fs-4 text-decoration-none">DHIRA BAKERY™</a>
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-outline-light rounded-pill px-3 py-2">
                    <i class="bi bi-house-door me-1"></i> BERANDA
                </a>
                <a href="tentang.php" class="btn btn-outline-light rounded-pill px-3 py-2">
                    <i class="bi bi-shop me-1"></i> TENTANG TOKO
                </a>
            </div>
        </header>

        <!-- FORM CEK KODE PESANAN -->
        <div class="futuristic-card p-4 rounded-4 shadow mb-4">
            <h5 class="fw-bold text-white mb-3"><i class="bi bi-receipt-cutoff text-warning me-2"></i> LACAK PESANAN & BERI ULASAN</h5>
            <form method="GET" class="row g-2">
                <div class="col-md-9">
                    <input type="text" name="kode" class="form-control form-control-tech" 
                           placeholder="Masukkan Kode Pesanan Anda (contoh: DHIRA-2026...)" 
                           value="<?= htmlspecialchars($kode_pesanan); ?>" required>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-warning rounded-pill fw-bold text-dark">
                        <i class="bi bi-search me-1"></i> CEK PESANAN
                    </button>
                </div>
            </form>
        </div>

        <?php if ($pesan_notif): ?>
            <div class="alert alert-success rounded-4 shadow-sm">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($pesan_notif); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_notif): ?>
            <div class="alert alert-danger rounded-4 shadow-sm">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error_notif); ?>
            </div>
        <?php endif; ?>

        <?php if ($pesanan): ?>
            <!-- KARTU STATUS PESANAN & RINCIAN PEMBAYARAN -->
            <div class="futuristic-card p-4 rounded-4 shadow mb-4 text-white">
                <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom border-secondary pb-3 mb-3 gap-2">
                    <div>
                        <span class="tech-tag text-warning d-block">KODE PESANAN</span>
                        <h4 class="fw-bold mb-0"><?= htmlspecialchars($pesanan['kode_pesanan']); ?></h4>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-secondary px-3 py-2 rounded-pill fs-6">
                            PESANAN: <?= strtoupper(str_replace('_', ' ', htmlspecialchars($pesanan['status_pesanan']))); ?>
                        </span>
                        <?php
                        $badgeBayar = 'bg-warning text-dark';
                        if ($pesanan['status_pembayaran'] === 'lunas') $badgeBayar = 'bg-success text-white';
                        if ($pesanan['status_pembayaran'] === 'gagal') $badgeBayar = 'bg-danger text-white';
                        ?>
                        <span class="badge <?= $badgeBayar; ?> px-3 py-2 rounded-pill fs-6">
                            PEMBAYARAN: <?= strtoupper(htmlspecialchars($pesanan['status_pembayaran'])); ?>
                        </span>
                    </div>
                </div>

                <!-- Ringkasan Penerima & Pengiriman -->
                <div class="row g-3 small text-white-50 mb-4">
                    <div class="col-md-6">
                        <div class="mb-1"><strong class="text-white">Pemesan:</strong> <?= htmlspecialchars($pesanan['nama_penerima']); ?> (<?= htmlspecialchars($pesanan['telepon_penerima']); ?>)</div>
                        <div class="mb-1"><strong class="text-white">Alamat:</strong> <?= htmlspecialchars($pesanan['alamat_pengiriman']); ?></div>
                        <?php if ($metode_kirim_terdeteksi !== '-'): ?>
                            <div><strong class="text-white">Pengiriman:</strong> <?= htmlspecialchars(str_replace('_', ' ', $metode_kirim_terdeteksi)); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="mb-1"><strong class="text-white">Jadwal:</strong> <?= htmlspecialchars($pesanan['jadwal_pengiriman']); ?></div>
                        <div class="mb-1"><strong class="text-white">Metode Bayar:</strong> <span class="text-info fw-bold"><?= htmlspecialchars(str_replace('_', ' ', $metode_bayar_terdeteksi)); ?></span></div>
                        <div><strong class="text-white">Total Tagihan:</strong> <span class="text-warning fw-bold fs-5">Rp <?= number_format($pesanan['total_pembayaran'], 0, ',', '.'); ?></span></div>
                    </div>
                </div>

                <!-- KOTAK DETAIL REKENING / INSTRUKSI PEMBAYARAN -->
                <div class="p-3 p-md-4 rounded-4 bg-dark border border-warning mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="tech-tag text-warning mb-0">
                            <i class="bi bi-wallet2 me-1"></i> DETAIL & TUJUAN PEMBAYARAN
                        </span>
                        <span class="badge border border-warning text-warning">
                            <?= htmlspecialchars(str_replace('_', ' ', $metode_bayar_terdeteksi)); ?>
                        </span>
                    </div>

                    <?php if ($metode_bayar_terdeteksi === 'TRANSFER_BCA'): ?>
                        <div class="row align-items-center g-3 mt-1">
                            <div class="col-md-8">
                                <small class="text-white-50 d-block">Bank Tujuan: <strong class="text-white">BANK BCA</strong></small>
                                <div class="d-flex align-items-center gap-2 my-1">
                                    <h3 class="fw-bold text-warning mb-0" id="norekText">1580849080</h3>
                                    <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3" onclick="salinRekening('1580849080', this)">
                                        <i class="bi bi-clipboard me-1"></i> Salin No. Rek
                                    </button>
                                </div>
                                <small class="text-white-50 d-block">Atas Nama: <strong class="text-white">Dhira Bakery Official</strong></small>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <small class="text-white-50 d-block">Nominal Transfer:</small>
                                <span class="fs-5 fw-bold text-white">Rp <?= number_format($pesanan['total_pembayaran'], 0, ',', '.'); ?></span>
                            </div>
                        </div>

                    <?php elseif ($metode_bayar_terdeteksi === 'TRANSFER_BRI'): ?>
                        <div class="row align-items-center g-3 mt-1">
                            <div class="col-md-8">
                                <small class="text-white-50 d-block">Bank Tujuan: <strong class="text-white">BANK BRI</strong></small>
                                <div class="d-flex align-items-center gap-2 my-1">
                                    <h3 class="fw-bold text-warning mb-0" id="norekText">009101082910503</h3>
                                    <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3" onclick="salinRekening('009101082910503', this)">
                                        <i class="bi bi-clipboard me-1"></i> Salin No. Rek
                                    </button>
                                </div>
                                <small class="text-white-50 d-block">No. Rekening: <span class="text-white">0091-01-082910-50-3</span> | A/N: <strong class="text-white">Dhira Bakery Official</strong></small>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <small class="text-white-50 d-block">Nominal Transfer:</small>
                                <span class="fs-5 fw-bold text-white">Rp <?= number_format($pesanan['total_pembayaran'], 0, ',', '.'); ?></span>
                            </div>
                        </div>

                    <?php elseif ($metode_bayar_terdeteksi === 'COD'): ?>
                        <p class="small text-white-50 mb-0 mt-1">
                            Pembayaran dilakukan secara <strong class="text-white">Tunai di Tempat (COD)</strong> sebesar <strong class="text-warning">Rp <?= number_format($pesanan['total_pembayaran'], 0, ',', '.'); ?></strong> kepada kurir saat pesanan tiba di alamat tujuan.
                        </p>

                    <?php elseif ($metode_bayar_terdeteksi === 'BAYAR_DI_TOKO'): ?>
                        <p class="small text-white-50 mb-0 mt-1">
                            Pembayaran dilakukan langsung di kasir <strong class="text-white">Dhirabakery (BTN Minasaupa Blok L1 No. 4)</strong> sebesar <strong class="text-warning">Rp <?= number_format($pesanan['total_pembayaran'], 0, ',', '.'); ?></strong> saat pengambilan pesanan.
                        </p>

                    <?php else: ?>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
                            <div>
                                <small class="text-white-50 d-block">ID Transaksi / Order ID Midtrans: <strong class="text-white"><?= htmlspecialchars($pesanan['kode_pesanan']); ?></strong></small>
                                <small class="text-white-50">
                                    <?= $pesanan['status_pembayaran'] === 'lunas' 
                                        ? 'Pembayaran online Anda telah terverifikasi otomatis oleh Midtrans.' 
                                        : 'Selesaikan pembayaran via QRIS / Virtual Account (BCA, BRI, BNI, Mandiri) / E-Wallet melalui tautan Midtrans.'; ?>
                                </small>
                            </div>
                            <?php if (!empty($pesanan['payment_url']) && $pesanan['status_pembayaran'] === 'pending'): ?>
                                <div class="d-flex gap-2">
                                    <a href="<?= htmlspecialchars($pesanan['payment_url']); ?>" class="btn btn-warning btn-sm rounded-pill fw-bold text-dark px-3">
                                        Lanjutkan Bayar Midtrans
                                    </a>
                                    <a href="pesanan_saya.php?kode=<?= urlencode($pesanan['kode_pesanan']); ?>" class="btn btn-outline-light btn-sm rounded-pill px-3">
                                        Refresh Status
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- TOMBOL LANGSUNG KE HALAMAN WHATSAPP -->
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-2">
                    <small class="text-white-50">Butuh konfirmasi pembayaran atau kirim bukti transfer?</small>
                    <a href="<?= htmlspecialchars($link_whatsapp); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-success rounded-pill px-4 py-2 fw-bold">
                        <i class="bi bi-whatsapp me-2"></i> KONFIRMASI VIA WHATSAPP
                    </a>
                </div>
            </div>

            <!-- DAFTAR ITEM & FORM ULASAN PRODUK -->
            <div class="futuristic-card p-4 rounded-4 shadow text-white">
                <h5 class="fw-bold mb-1"><i class="bi bi-star-fill text-warning me-2"></i> ULASAN PRODUK PESANAN ANDA</h5>
                <p class="small text-white-50 mb-4">Bagikan penilaian rasa dan kualitas roti/kue Dhirabakery yang Anda pesan.</p>

                <div class="row g-4">
                    <?php foreach ($detail_items as $item): ?>
                        <?php $id_prod = $item['id_produk']; ?>
                        <div class="col-12">
                            <div class="p-3 rounded-4 border border-secondary" style="background: rgba(255,255,255,0.03);">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <img src="assets/uploads/<?= !empty($item['foto_produk']) ? htmlspecialchars($item['foto_produk']) : 'default.jpg'; ?>" 
                                         class="rounded-3" 
                                         style="width: 70px; height: 70px; object-fit: cover;"
                                         onerror="this.src='https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=200&q=80'">
                                    <div>
                                        <h6 class="fw-bold text-white mb-1"><?= htmlspecialchars($item['nama_produk'] ?? 'Produk Dhirabakery'); ?></h6>
                                        <small class="text-white-50"><?= $item['jumlah']; ?> x Rp <?= number_format($item['harga_satuan'], 0, ',', '.'); ?></small>
                                    </div>
                                </div>

                                <?php if (isset($ulasan_sudah_ada[$id_prod])): ?>
                                    <div class="p-3 rounded-3 bg-dark border border-success">
                                        <div class="text-warning mb-1">
                                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                                <i class="bi <?= $s <= $ulasan_sudah_ada[$id_prod]['rating'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                            <?php endfor; ?>
                                            <span class="badge bg-success ms-2">Sudah Diulas</span>
                                        </div>
                                        <p class="small text-white-50 mb-0">"<?= htmlspecialchars($ulasan_sudah_ada[$id_prod]['komentar']); ?>"</p>
                                    </div>
                                <?php else: ?>
                                    <form method="POST" class="row g-2 align-items-end">
                                        <input type="hidden" name="kode_pesanan" value="<?= htmlspecialchars($pesanan['kode_pesanan']); ?>">
                                        <input type="hidden" name="id_produk" value="<?= $id_prod; ?>">
                                        <input type="hidden" name="nama_pelanggan" value="<?= htmlspecialchars($pesanan['nama_penerima']); ?>">

                                        <div class="col-md-3">
                                            <label class="small text-warning fw-bold mb-1">Rating Bintang</label>
                                            <select name="rating" class="form-select form-select-tech" required>
                                                <option value="5">★★★★★ (5 - Sangat Enak)</option>
                                                <option value="4">★★★★☆ (4 - Enak)</option>
                                                <option value="3">★★★☆☆ (3 - Cukup)</option>
                                                <option value="2">★★☆☆☆ (2 - Kurang)</option>
                                                <option value="1">★☆☆☆☆ (1 - Tidak Puas)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="small text-warning fw-bold mb-1">Komentar Ulasan</label>
                                            <input type="text" name="komentar" class="form-control form-control-tech" 
                                                   placeholder="Tulis kesan Anda tentang rasa & tekstur produk..." required>
                                        </div>
                                        <div class="col-md-3 d-grid">
                                            <button type="submit" name="kirim_ulasan" class="btn btn-warning rounded-pill fw-bold text-dark">
                                                <i class="bi bi-send-fill me-1"></i> Kirim Ulasan
                                            </button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function salinRekening(nomor, btn) {
        navigator.clipboard.writeText(nomor).then(() => {
            const teksAsli = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Tersalin!';
            btn.classList.replace('btn-outline-warning', 'btn-success');
            setTimeout(() => {
                btn.innerHTML = teksAsli;
                btn.classList.replace('btn-success', 'btn-outline-warning');
            }, 2000);
        });
    }
    </script>
</body>
</html>