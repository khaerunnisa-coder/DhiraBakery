<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/koneksi.php';
require_once '../config/midtrans.php';

$kode_pesanan = trim($_GET['kode_pesanan'] ?? '');
if (empty($kode_pesanan)) {
    echo json_encode(['status' => 'error', 'message' => 'Kode pesanan tidak ditemukan']);
    exit;
}

$status_url = MIDTRANS_IS_PRODUCTION
    ? "https://api.midtrans.com/v2/$kode_pesanan/status"
    : "https://api.sandbox.midtrans.com/v2/$kode_pesanan/status";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $status_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER     => [
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':')
    ],
]);

$response = curl_exec($ch);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    echo json_encode(['status' => 'error', 'message' => 'cURL Error: ' . $curlErr]);
    exit;
}

$data         = json_decode($response, true);
$trx_status   = $data['transaction_status'] ?? '';
$fraud_status = $data['fraud_status'] ?? '';

$status_baru = 'pending';
if ($trx_status === 'capture') {
    $status_baru = ($fraud_status === 'accept') ? 'lunas' : 'pending';
} elseif ($trx_status === 'settlement') {
    $status_baru = 'lunas';
} elseif (in_array($trx_status, ['cancel', 'deny', 'expire'])) {
    $status_baru = 'batal';
}

if ($status_baru !== 'pending') {
    $stmt = $pdo->prepare("UPDATE pesanan SET status_pembayaran = ? WHERE kode_pesanan = ?");
    $stmt->execute([$status_baru, $kode_pesanan]);
}

echo json_encode([
    'status'            => 'success',
    'kode_pesanan'      => $kode_pesanan,
    'midtrans_status'   => $trx_status ?: 'belum_dibayar',
    'status_pembayaran' => $status_baru
]);