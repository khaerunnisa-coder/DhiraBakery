<?php
require_once '../config/koneksi.php';

// Tempel Server Key Sandbox yang sama dengan api/pesanan.php
$server_key = 'SB-Mid-server-XXXXXXXXXXXXXXXXXXXX';

$json_result = file_get_contents('php://input');
$notif = json_decode($json_result, true);

if (!$notif) exit();

$order_id           = $notif['order_id'];
$status_code        = $notif['status_code'];
$gross_amount       = $notif['gross_amount'];
$transaction_status = $notif['transaction_status'];
$payment_type       = $notif['payment_type'];
$transaction_id     = $notif['transaction_id'];
$signature_key      = $notif['signature_key'];

$valid_signature = hash("sha512", $order_id . $status_code . $gross_amount . $server_key);
if ($signature_key !== $valid_signature) {
    http_response_code(403);
    exit("Invalid Signature");
}

$stmt = $pdo->prepare("SELECT id_pesanan FROM pesanan WHERE kode_pesanan = ?");
$stmt->execute([$order_id]);
$pesanan = $stmt->fetch();

if ($pesanan) {
    $id_pesanan = $pesanan['id_pesanan'];

    if ($transaction_status == 'capture' || $transaction_status == 'settlement') {
        $pdo->prepare("UPDATE pesanan SET status_pembayaran = 'lunas', status_pesanan = 'diproses' WHERE id_pesanan = ?")
            ->execute([$id_pesanan]);

        $pdo->prepare("INSERT INTO pembayaran (id_pesanan, metode_pembayaran, status_pembayaran, jumlah_bayar, nomor_referensi) VALUES (?, ?, 'lunas', ?, ?)")
            ->execute([$id_pesanan, strtoupper($payment_type), $gross_amount, $transaction_id]);

    } elseif ($transaction_status == 'cancel' || $transaction_status == 'deny' || $transaction_status == 'expire') {
        $pdo->prepare("UPDATE pesanan SET status_pembayaran = 'gagal', status_pesanan = 'dibatalkan' WHERE id_pesanan = ?")
            ->execute([$id_pesanan]);
    }
}

http_response_code(200);
echo "OK";
?>