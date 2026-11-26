<?php
session_start();
require_once 'config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['cart'])) {
    $nama_penerima     = trim($_POST['nama_penerima']);
    $telepon_penerima  = trim($_POST['telepon_penerima']);
    $metode_pengiriman = trim($_POST['metode_pengiriman']);
    $alamat_pengiriman = trim($_POST['alamat_pengiriman']);
    $jadwal_pengiriman = trim($_POST['jadwal_pengiriman']);
    $catatan_pelanggan = trim($_POST['catatan_pelanggan']);
    $metode_pembayaran = trim($_POST['metode_pembayaran']);

    $cart = $_SESSION['cart'];
    $total_harga = 0;
    foreach ($cart as $item) {
        $total_harga += ($item['harga_total'] * $item['qty']);
    }

    $kode_pesanan = "DHR-" . date("Ymd") . "-" . rand(1000, 9999);
    
    // Check if user is logged in
    $id_pelanggan = null;
    if (isset($_SESSION['user_id'])) {
        $stmt_pel = $pdo->prepare("SELECT id_pelanggan FROM pelanggan WHERE id_user = ?");
        $stmt_pel->execute([$_SESSION['user_id']]);
        $pel = $stmt_pel->fetch();
        if ($pel) { $id_pelanggan = $pel['id_pelanggan']; }
    }

    try {
        $pdo->beginTransaction();

        $stmt_order = $pdo->prepare("INSERT INTO pesanan 
            (kode_pesanan, id_pelanggan, jadwal_pengiriman, status_pesanan, status_pembayaran, total_harga_produk, total_pembayaran, nama_penerima, telepon_penerima, alamat_pengiriman, catatan_pelanggan) 
            VALUES (?, ?, ?, 'menunggu_konfirmasi', 'belum_dibayar', ?, ?, ?, ?, ?, ?)");
        
        $stmt_order->execute([
            $kode_pesanan, $id_pelanggan, $jadwal_pengiriman, $total_harga, $total_harga,
            $nama_penerima, $telepon_penerima, $alamat_pengiriman, $catatan_pelanggan
        ]);
        
        $id_pesanan = $pdo->lastInsertId();

        $stmt_detail = $pdo->prepare("INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, harga_satuan, subtotal, catatan_produk) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($cart as $item) {
            $subtotal = $item['harga_total'] * $item['qty'];
            $catatan = "Varian: " . $item['nama_rasa'];
            $stmt_detail->execute([$id_pesanan, $item['id_produk'], $item['qty'], $item['harga_total'], $subtotal, $catatan]);
        }

        $stmt_pay = $pdo->prepare("INSERT INTO pembayaran (id_pesanan, metode_pembayaran, status_pembayaran, jumlah_bayar) VALUES (?, ?, 'pending', ?)");
        $stmt_pay->execute([$id_pesanan, $metode_pembayaran, $total_harga]);

        $pdo->commit();
        unset($_SESSION['cart']);

        // Format Pesan WhatsApp
        $wa_msg = "*PEMESANAN BARU - DHIRA BAKERY*%0A";
        $wa_msg .= "------------------------------------%0A";
        $wa_msg .= "*Kode Nota:* #" . $kode_pesanan . "%0A";
        $wa_msg .= "*Nama Pemesan:* " . urlencode($nama_penerima) . "%0A";
        $wa_msg .= "*Nomor WA:* " . $telepon_penerima . "%0A";
        $wa_msg .= "*Jadwal Kirim:* " . urlencode($jadwal_pengiriman) . "%0A";
        $wa_msg .= "*Metode Bayar:* " . urlencode($metode_pembayaran) . "%0A";
        $wa_msg .= "------------------------------------%0A";
        $wa_msg .= "*Daftar Produk:*%0A";
        foreach ($cart as $item) {
            $wa_msg .= "• " . urlencode($item['nama_produk']) . " (" . urlencode($item['nama_rasa']) . ") x" . $item['qty'] . " = Rp " . number_format($item['harga_total'] * $item['qty'], 0, ',', '.') . "%0A";
        }
        $wa_msg .= "------------------------------------%0A";
        $wa_msg .= "*Total Pembayaran:* Rp " . number_format($total_harga, 0, ',', '.') . "%0A%0A";
        $wa_msg .= "_Silakan kirimkan bukti pembayaran ke WhatsApp ini untuk konfirmasi pesanan Anda._";

        $target_wa = "6281234567890";
        header("Location: https://api.whatsapp.com/send?phone=" . $target_wa . "&text=" . $wa_msg);
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Gagal memproses pesanan: " . $e->getMessage());
    }
}
?>