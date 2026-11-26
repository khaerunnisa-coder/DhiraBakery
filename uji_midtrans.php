<?php
require_once 'config/koneksi.php';

$produk = [];
$riwayat = [];
$db_error = '';

try {
    // Ambil produk aktif untuk simulasi keranjang
    $produk = $pdo->query("SELECT * FROM produk WHERE status_aktif = 1 LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    // Ambil 5 transaksi terbaru dari tabel pesanan
    $riwayat = $pdo->query("SELECT * FROM pesanan ORDER BY id_pesanan DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $db_error = "Pastikan tabel 'pesanan' dan 'detail_pesanan' sudah dibuat di database. Detail: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uji Pembayaran Midtrans — Dhira Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f5efe6; color: #23120b; }
        .card-bakery { background: #ffffff; border: none; border-radius: 20px; box-shadow: 0 10px 25px rgba(35,18,11,0.08); }
        .btn-chocolate { background-color: #23120b; color: #f5efe6; font-weight: 600; }
        .btn-chocolate:hover { background-color: #7c3626; color: #ffffff; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-credit-card-2-front me-2"></i>Simulasi Checkout Midtrans</h3>
            <p class="text-muted mb-0">Pengujian pembuatan transaksi Snap dan verifikasi status pembayaran Dhira Bakery.</p>
        </div>
        <a href="admin/produk.php" class="btn btn-outline-dark rounded-pill px-4">
            <i class="bi bi-box-seam me-1"></i> Kelola Produk
        </a>
    </div>

    <?php if ($db_error): ?>
        <div class="alert alert-danger rounded-4 shadow-sm"><?= htmlspecialchars($db_error); ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- FORM SIMULASI CHECKOUT -->
        <div class="col-lg-5">
            <div class="card card-bakery p-4">
                <h5 class="fw-bold mb-3">1. Buat Transaksi Baru</h5>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pilih Produk Uji Coba</label>
                    <select id="pilihProduk" class="form-select">
                        <?php if (!empty($produk)): ?>
                            <?php foreach ($produk as $p): ?>
                                <option value="<?= $p['id_produk']; ?>" data-harga="<?= intval($p['harga']); ?>">
                                    <?= htmlspecialchars($p['nama_produk']); ?> — Rp <?= number_format($p['harga'], 0, ',', '.'); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="1" data-harga="35000">Croissant Butter (Contoh) — Rp 35.000</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Penerima</label>
                    <input type="text" id="namaPenerima" class="form-control" value="Muh. Zulhilman">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nomor WhatsApp</label>
                    <input type="text" id="telpPenerima" class="form-control" value="081234567890">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Alamat Pengiriman</label>
                    <input type="text" id="alamatPenerima" class="form-control" value="Jl. AP Pettarani, Makassar">
                </div>

                <div class="p-3 rounded-4 mb-3" style="background-color: #faf6f0;">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>Biaya Pengiriman (Flat)</span>
                        <span class="fw-semibold">Rp 10.000</span>
                    </div>
                    <div class="d-flex justify-content-between fw-bold" id="labelTotal">
                        <span>Total Pembayaran</span>
                        <span>Rp 45.000</span>
                    </div>
                </div>

                <button id="btnBayar" onclick="prosesCheckout()" class="btn btn-chocolate btn-lg w-100 rounded-pill">
                    Buat Pesanan & Bayar Midtrans
                </button>
                <div id="pesanError" class="text-danger small mt-2"></div>
            </div>
        </div>

        <!-- TABEL STATUS TRANSAKSI -->
        <div class="col-lg-7">
            <div class="card card-bakery p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">2. Riwayat & Verifikasi Status</h5>
                    <button onclick="location.reload()" class="btn btn-sm btn-outline-dark rounded-pill px-3">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kode Order</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($riwayat)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        Belum ada transaksi. Klik tombol di sebelah kiri untuk membuat pesanan uji coba.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($riwayat as $r): ?>
                                    <tr>
                                        <td class="small fw-bold"><?= htmlspecialchars($r['kode_pesanan']); ?></td>
                                        <td>Rp <?= number_format($r['total_pembayaran'], 0, ',', '.'); ?></td>
                                        <td>
                                            <?php if ($r['status_pembayaran'] === 'lunas'): ?>
                                                <span class="badge bg-success">LUNAS</span>
                                            <?php elseif ($r['status_pembayaran'] === 'batal'): ?>
                                                <span class="badge bg-danger">BATAL</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">PENDING</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($r['status_pembayaran'] === 'pending' && !empty($r['payment_url'])): ?>
                                                <a href="<?= htmlspecialchars($r['payment_url']); ?>" target="_blank" class="btn btn-sm btn-primary rounded-pill px-3">
                                                    Bayar
                                                </a>
                                                <button onclick="cekStatus('<?= htmlspecialchars($r['kode_pesanan']); ?>')" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                                    Cek Status
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted small">Selesai</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const selectProduk = document.getElementById('pilihProduk');
const labelTotal = document.getElementById('labelTotal');

function updateTotal() {
    const harga = parseFloat(selectProduk.options[selectProduk.selectedIndex].dataset.harga);
    const total = harga + 10000;
    labelTotal.innerHTML = '<span>Total Pembayaran</span><span>Rp ' + total.toLocaleString('id-ID') + '</span>';
}
selectProduk.addEventListener('change', updateTotal);
updateTotal();

async function prosesCheckout() {
    const btn = document.getElementById('btnBayar');
    const errBox = document.getElementById('pesanError');
    const harga = parseFloat(selectProduk.options[selectProduk.selectedIndex].dataset.harga);
    const ongkir = 10000;

    btn.disabled = true;
    btn.innerText = 'Menghubungkan ke Midtrans...';
    errBox.innerText = '';

    try {
        const res = await fetch('api/pesanan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_pelanggan: 1,
                nama_penerima: document.getElementById('namaPenerima').value,
                telepon_penerima: document.getElementById('telpPenerima').value,
                alamat_pengiriman: document.getElementById('alamatPenerima').value,
                total_harga_produk: harga,
                biaya_pengiriman: ongkir,
                total_pembayaran: harga + ongkir,
                items: [{ id_produk: selectProduk.value, jumlah: 1, harga_satuan: harga }]
            })
        });

        const data = await res.json();
        if (data.status === 'success' && data.payment_url) {
            window.open(data.payment_url, '_blank');
            location.reload();
        } else {
            errBox.innerText = 'Gagal: ' + JSON.stringify(data);
        }
    } catch (e) {
        errBox.innerText = 'Error jaringan/server: ' + e;
    } finally {
        btn.disabled = false;
        btn.innerText = 'Buat Pesanan & Bayar Midtrans';
    }
}

async function cekStatus(kode) {
    const res = await fetch('api/cek_status_midtrans.php?kode_pesanan=' + encodeURIComponent(kode));
    const data = await res.json();
    alert('Status Midtrans: ' + data.midtrans_status + '\nStatus Database: ' + data.status_pembayaran);
    location.reload();
}
</script>
</body>
</html>