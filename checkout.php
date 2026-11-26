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
$items_midtrans = [];

foreach ($cart as $key => $item) {
    $qty = intval($item['qty'] ?? 1);
    $harga_satuan = floatval($item['harga_total'] ?? 0);
    $total_harga += ($harga_satuan * $qty);

    if (($item['jenis_produk'] ?? '') == 'pre_order') {
        $ada_po = true;
    }

    $items_midtrans[] = [
        'id_produk'    => intval($item['id_produk'] ?? (is_numeric($key) ? $key : 1)),
        'jumlah'       => $qty,
        'harga_satuan' => $harga_satuan
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>CHECKOUT — DHIRA BAKERY</title>
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

        <form id="formCheckout" method="POST">
            <div class="row g-4">
                <!-- KOLOM KIRI: DATA PELANGGAN & PENGIRIMAN -->
                <div class="col-md-7">
                    <div class="futuristic-card mb-4">
                        <h5 class="fw-bold text-white mb-3 border-bottom border-secondary pb-2">1. CUSTOMER & DELIVERY INFO</h5>

                        <div class="mb-3">
                            <label class="tech-tag mb-1">FULL NAME:</label>
                            <input type="text" name="nama_penerima" id="nama_penerima" class="form-control form-control-tech" required placeholder="Nama Lengkap Pemesan">
                        </div>

                        <div class="mb-3">
                            <label class="tech-tag mb-1">WHATSAPP NUMBER:</label>
                            <input type="tel" name="telepon_penerima" id="telepon_penerima" class="form-control form-control-tech" placeholder="081234567890" required>
                        </div>

                        <div class="mb-3">
                            <label class="tech-tag mb-1">DELIVERY METHOD:</label>
                            <select name="metode_pengiriman" id="metode_pengiriman" class="form-select form-select-tech" required>
                                <option value="diantar">Dikirim Kurir (GoSend / Instant)</option>
                                <option value="ambil_sendiri">Ambil Sendiri di Toko (Self-Pickup)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="tech-tag mb-1">DELIVERY ADDRESS:</label>
                            <textarea name="alamat_pengiriman" id="alamat_pengiriman" class="form-control form-control-tech" rows="3" required placeholder="Alamat lengkap penerima..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="tech-tag mb-1">SCHEDULE DATE & TIME:</label>
                            <input type="datetime-local" name="jadwal_pengiriman" id="jadwal_pengiriman" class="form-control form-control-tech" required min="<?= date('Y-m-d\TH:i'); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="tech-tag mb-1">ADDITIONAL NOTES / GREETINGS:</label>
                            <textarea name="catatan_pelanggan" id="catatan_pelanggan" class="form-control form-control-tech" rows="2" placeholder="Catatan ucapan atau instruksi khusus..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- KOLOM KANAN: PILIHAN METODE PEMBAYARAN -->
                <div class="col-md-5">
                    <div class="futuristic-card mb-4">
                        <h5 class="fw-bold text-white mb-3 border-bottom border-secondary pb-2">2. PAYMENT METHOD</h5>

                        <div class="mb-3">
                            <label class="tech-tag mb-1">SELECT PAYMENT METHOD:</label>
                            <select name="metode_pembayaran" id="metode_pembayaran" class="form-select form-select-tech" onchange="tampilkanInstruksi()" required>
                                <option value="MIDTRANS">Pembayaran Online Otomatis (Midtrans: QRIS / VA / GoPay)</option>
                                <option value="COD">Bayar di Tempat / COD (Cash on Delivery)</option>
                                <option value="BAYAR_DI_TOKO">Bayar Tunai di Toko (Saat Pengambilan)</option>
                                <option value="TRANSFER_BCA">Transfer Bank Manual — BCA</option>
                                <option value="TRANSFER_BRI">Transfer Bank Manual — BRI</option>
                            </select>
                        </div>

                        <!-- Info Midtrans -->
                        <div id="info-MIDTRANS" class="p-3 rounded-3 bg-dark border border-warning mb-3 info-payment">
                            <div class="d-flex align-items-center mb-1">
                                <i class="bi bi-shield-check text-warning fs-5 me-2"></i>
                                <span class="tech-tag text-warning mb-0">[ MIDTRANS SECURE PAYMENT ]</span>
                            </div>
                            <small class="text-white-50 d-block">
                                Terverifikasi otomatis melalui QRIS, BCA/BRI/BNI/Mandiri VA, GoPay, atau ShopeePay.
                            </small>
                        </div>

                        <!-- Info COD -->
                        <div id="info-COD" class="p-3 rounded-3 bg-dark border border-info mb-3 d-none info-payment">
                            <span class="tech-tag text-info d-block mb-1">[ CASH ON DELIVERY (COD) ]</span>
                            <small class="text-white-50 d-block">
                                Siapkan uang tunai pas sebesar <strong class="text-white">Rp <?= number_format($total_harga, 0, ',', '.'); ?></strong> untuk dibayarkan langsung kepada kurir saat pesanan tiba di alamat Anda.
                            </small>
                        </div>

                        <!-- Info Bayar di Toko -->
                        <div id="info-BAYAR_DI_TOKO" class="p-3 rounded-3 bg-dark border border-success mb-3 d-none info-payment">
                            <span class="tech-tag text-success d-block mb-1">[ BAYAR TUNAI DI TOKO ]</span>
                            <small class="text-white-50 d-block">
                                Pembayaran dilakukan langsung di kasir <strong class="text-white">Dhirabakery (BTN Minasaupa Blok L1 No. 4)</strong> saat mengambil pesanan sesuai jadwal.
                            </small>
                        </div>

                        <!-- Info Transfer BCA -->
                        <div id="info-TRANSFER_BCA" class="p-3 rounded-3 bg-dark border border-secondary mb-3 d-none info-payment">
                            <span class="tech-tag text-warning d-block mb-1">[ TRANSFER BANK BCA ]</span>
                            <h4 class="fw-bold text-white mb-0">1580849080</h4>
                            <small class="text-white-50 d-block mb-2">A/N: Dhira Bakery Official</small>
                            <small class="text-warning d-block">Simpan bukti transfer Anda untuk konfirmasi dengan admin.</small>
                        </div>

                        <!-- Info Transfer BRI -->
                        <div id="info-TRANSFER_BRI" class="p-3 rounded-3 bg-dark border border-secondary mb-3 d-none info-payment">
                            <span class="tech-tag text-warning d-block mb-1">[ TRANSFER BANK BRI ]</span>
                            <h4 class="fw-bold text-white mb-0">0091-01-082910-50-3</h4>
                            <small class="text-white-50 d-block mb-2">A/N: Dhira Bakery Official</small>
                            <small class="text-warning d-block">Simpan bukti transfer Anda untuk konfirmasi dengan admin.</small>
                        </div>

                        <hr class="border-secondary">

                        <div class="d-flex justify-content-between mb-2">
                            <span class="tech-tag">SUBTOTAL</span>
                            <span>Rp <?= number_format($total_harga, 0, ',', '.'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="tech-tag">TOTAL PAYABLE</span>
                            <span class="fs-4 fw-bold text-warning">Rp <?= number_format($total_harga, 0, ',', '.'); ?></span>
                        </div>

                        <button type="submit" id="btnSubmitCheckout" class="btn btn-light btn-lg w-100 rounded-pill fw-bold text-dark">
                            <i class="bi bi-credit-card-2-front me-2"></i> PAY NOW WITH MIDTRANS
                        </button>
                        <div id="checkoutFeedbackBox" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
    const cartItems = <?= json_encode($items_midtrans); ?>;
    const totalBayar = <?= floatval($total_harga); ?>;
    const idPelanggan = <?= intval($_SESSION['id_pelanggan'] ?? 1); ?>;

    function tampilkanInstruksi() {
        const metode = document.getElementById('metode_pembayaran').value;
        const btn = document.getElementById('btnSubmitCheckout');

        document.querySelectorAll('.info-payment').forEach(el => el.classList.add('d-none'));
        const aktif = document.getElementById('info-' + metode);
        if (aktif) aktif.classList.remove('d-none');

        if (metode === 'MIDTRANS') {
            btn.innerHTML = '<i class="bi bi-credit-card-2-front me-2"></i> PAY NOW WITH MIDTRANS';
        } else {
            btn.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i> BUAT PESANAN SEKARANG';
        }
    }

    document.getElementById('formCheckout').addEventListener('submit', async function(e) {
        e.preventDefault();

        const metode = document.getElementById('metode_pembayaran').value;
        const btn = document.getElementById('btnSubmitCheckout');
        const feedbackBox = document.getElementById('checkoutFeedbackBox');
        feedbackBox.innerHTML = '';

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> MEMPROSES PESANAN...';

        const metodeKirim = document.getElementById('metode_pengiriman').value;
        const catatanAsli = document.getElementById('catatan_pelanggan').value.trim();
        const catatanGabung = `[${metodeKirim.toUpperCase()} | ${metode}] ` + catatanAsli;

        try {
            const response = await fetch('api/pesanan.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_pelanggan: idPelanggan,
                    metode_pembayaran: metode,
                    nama_penerima: document.getElementById('nama_penerima').value,
                    telepon_penerima: document.getElementById('telepon_penerima').value,
                    alamat_pengiriman: document.getElementById('alamat_pengiriman').value,
                    jadwal_pengiriman: document.getElementById('jadwal_pengiriman').value.replace('T', ' ') + ':00',
                    catatan_pelanggan: catatanGabung,
                    total_harga_produk: totalBayar,
                    biaya_pengiriman: 0,
                    total_pembayaran: totalBayar,
                    items: cartItems
                })
            });

            const result = await response.json();

            if (result.status === 'success') {
                if (result.payment_url) {
                    // Alihkan langsung ke Midtrans jika memakai payment gateway
                    window.location.href = result.payment_url;
                } else {
                    // Langsung antar ke halaman status pesanan & ulasan produk
                    window.location.href = 'pesanan_saya.php?kode=' + encodeURIComponent(result.kode_pesanan);
                }
            } else {
                feedbackBox.innerHTML = `<div class="text-warning small">Gagal memproses pesanan: ${result.message || JSON.stringify(result)}</div>`;
                btn.disabled = false;
                tampilkanInstruksi();
            }
        } catch (err) {
            feedbackBox.innerHTML = `<div class="text-warning small">Terjadi kesalahan koneksi ke server: ${err.message}</div>`;
            btn.disabled = false;
            tampilkanInstruksi();
        }
    });
    </script>
</body>
</html>