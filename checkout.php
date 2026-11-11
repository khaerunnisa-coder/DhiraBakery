<?php
session_start();
require_once 'config/koneksi.php';

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
if (empty($cart)) {
    header("Location: index.php");
    exit();
}

$total_harga = 0;
$ada_po = false;
foreach ($cart as $item) {
    $total_harga += ($item['harga_total'] * $item['qty']);
    if ($item['jenis_produk'] == 'pre_order') { $ada_po = true; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title> CHECKOUT — DHIRA BAKERY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="container py-4">
        <header class="d-flex justify-content-between align-items-center mb-4 navbar-tech">
            <a href="index.php" class="brand-title fs-2 text-white text-decoration-none">DHIRA BAKERY™</a>
        </header>

        <h3 class="fw-bold mb-4 text-black">[ CHECKOUT & PAYMENT METHOD ]</h3>

        <form action="proses_checkout.php" method="POST">
            <div class="row g-4">
                <div class="col-md-7">
                    <div class="futuristic-card mb-4">
                        <h5 class="fw-bold text-white mb-3 border-bottom border-secondary pb-2">1. CUSTOMER & DELIVERY INFO</h5>

                        <div class="mb-3">
                            <label class="tech-tag mb-1">FULL NAME:</label>
                            <input type="text" name="nama_penerima" class="form-control form-control-tech" required placeholder="Nama Lengkap Pemesan">
                        </div>

                        <div class="mb-3">
                            <label class="tech-tag mb-1">WHATSAPP NUMBER:</label>
                            <input type="tel" name="telepon_penerima" class="form-control form-control-tech" placeholder="081234567890" required>
                        </div>

                        <div class="mb-3">
                            <label class="tech-tag mb-1">DELIVERY METHOD:</label>
                            <select name="metode_pengiriman" class="form-select form-select-tech" required>
                                <option value="diantar">Dikirim Kurir (GoSend / Instant)</option>
                                <option value="ambil_sendiri">Ambil Sendiri di Toko (Self-Pickup)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="tech-tag mb-1">DELIVERY ADDRESS:</label>
                            <textarea name="alamat_pengiriman" class="form-control form-control-tech" rows="3" required placeholder="Alamat lengkap penerima..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="tech-tag mb-1">SCHEDULE DATE & TIME:</label>
                            <input type="datetime-local" name="jadwal_pengiriman" class="form-control form-control-tech" required min="<?= date('Y-m-d\TH:i'); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="tech-tag mb-1">ADDITIONAL NOTES / GREETINGS:</label>
                            <textarea name="catatan_pelanggan" class="form-control form-control-tech" rows="2" placeholder="Catatan ucapan atau instruksi khusus..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="futuristic-card mb-4">
                        <h5 class="fw-bold text-white mb-3 border-bottom border-secondary pb-2">2. PAYMENT METHOD</h5>

                        <div class="mb-3">
                            <label class="tech-tag mb-1">SELECT METHOD:</label>
                            <select name="metode_pembayaran" id="metode_pembayaran" class="form-select form-select-tech" onchange="tampilkanInstruksi()" required>
                                <option value="">-- Choose Payment --</option>
                                <option value="QRIS (DANA/GoPay/OVO)">QRIS Static (DANA / GoPay / OVO)</option>
                                <option value="Transfer BCA form-select-tech text-black">Transfer Bank BCA</option>
                                <option value="Transfer BRI form-select-tech text-black">Transfer Bank BRI</option>
                            </select>
                        </div>

                        <div id="instruksi-qris" class="p-3 rounded-3 bg-dark border border-secondary d-none text-center mb-3">
                            <span class="tech-tag d-block text-warning mb-2">[ SCAN QRIS ALL PAYMENT ]</span>
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=DHIRABAKERY_QRIS_STATIC" class="img-thumbnail my-2" alt="QRIS">
                            <small class="tech-tag d-block text-black">DANA / GOPAY / OVO / SHOPEEPAY</small>
                        </div>

                        <div id="instruksi-bca" class="p-3 rounded-3 bg-dark border border-secondary d-none mb-3">
                            <span class="tech-tag text-info">[ BCA BANK TRANSFER ]</span>
                            <h4 class="fw-bold text-white mt-1">1580849080</h4>
                            <small class="text-white-50">A/N: Dhira Bakery Official</small>
                        </div>

                        <div id="instruksi-bri" class="p-3 rounded-3 bg-dark border border-secondary d-none mb-3">
                            <span class="tech-tag text-info">[ BRI BANK TRANSFER ]</span>
                            <h4 class="fw-bold text-white mt-1">0091-01-082910-50-3</h4>
                            <small class="text-white-50">A/N: Dhira Bakery Official</small>
                        </div>

                        <hr class="border-secondary">

                        <div class="d-flex justify-content-between mb-2">
                            <span class="tech-tag">SUBTOTAL</span>
                            <span>Rp <?= number_format($total_harga, 0, ',', '.'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="tech-tag">TOTAL PAYABLE</span>
                            <span class="fs-4 fw-bold text-warning">Rp <?= number_format($total_harga, 0, ',', '.'); ?></span>
                        </div>

                        <button type="submit" class="btn btn-light btn-lg w-100 rounded-pill fw-bold text-dark">
                            <i class="bi bi-whatsapp me-2"></i> SEND ORDER VIA WHATSAPP
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
    function tampilkanInstruksi() {
        const val = document.getElementById('metode_pembayaran').value;
        document.getElementById('instruksi-qris').classList.add('d-none');
        document.getElementById('instruksi-bca').classList.add('d-none');
        document.getElementById('instruksi-bri').classList.add('d-none');

        if (val.includes('QRIS')) document.getElementById('instruksi-qris').classList.remove('d-none');
        if (val.includes('BCA')) document.getElementById('instruksi-bca').classList.remove('d-none');
        if (val.includes('BRI')) document.getElementById('instruksi-bri').classList.remove('d-none');
    }
    </script>
</body>
</html>
