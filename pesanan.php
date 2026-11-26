<?php
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/koneksi.php';
require_once '../config/midtrans.php';

$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true) ?? [];

$id_pelanggan      = intval($input['id_pelanggan'] ?? 1);
$metode_pembayaran = strtoupper(trim($input['metode_pembayaran'] ?? 'MIDTRANS'));
$nama_penerima     = trim($input['nama_penerima'] ?? 'Pelanggan Dhira');
$telepon_penerima  = trim($input['telepon_penerima'] ?? '081234567890');
$alamat            = trim($input['alamat_pengiriman'] ?? 'Makassar');
$jadwal            = $input['jadwal_pengiriman'] ?? date('Y-m-d H:i:s', strtotime('+1 day'));
$catatan           = trim($input['catatan_pelanggan'] ?? '');
$total_produk      = floatval($input['total_harga_produk'] ?? 0);
$ongkir            = floatval($input['biaya_pengiriman'] ?? 0);
$total_bayar       = intval(round($input['total_pembayaran'] ?? ($total_produk + $ongkir)));
$items             = $input['items'] ?? [];

$kode_pesanan = 'DHIRA-' . date('YmdHis') . '-' . rand(100, 999);

try {
    $pdo->beginTransaction();

    // 1. Simpan header pesanan ke database
    $stmt = $pdo->prepare("INSERT INTO pesanan 
        (kode_pesanan, id_pelanggan, jadwal_pengiriman, total_harga_produk, biaya_pengiriman, total_pembayaran, nama_penerima, telepon_penerima, alamat_pengiriman, catatan_pelanggan, status_pembayaran, status_pesanan) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'menunggu_konfirmasi')");
    $stmt->execute([
        $kode_pesanan,
        $id_pelanggan,
        $jadwal,
        $total_produk,
        $ongkir,
        $total_bayar,
        $nama_penerima,
        $telepon_penerima,
        $alamat,
        $catatan
    ]);

    $id_pesanan = $pdo->lastInsertId();

    // 2. Simpan detail item produk
    if (!empty($items)) {
        $stmtDetail = $pdo->prepare("INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
        foreach ($items as $item) {
            $qty      = intval($item['jumlah']);
            $harga    = floatval($item['harga_satuan']);
            $subtotal = $qty * $harga;
            $stmtDetail->execute([$id_pesanan, intval($item['id_produk']), $qty, $harga, $subtotal]);
        }
    }

    // 3A. JIKA BUKAN MIDTRANS (COD / BAYAR DI TOKO / TRANSFER MANUAL)
    if ($metode_pembayaran !== 'MIDTRANS') {
        $pdo->commit();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['cart']);

        ob_clean();
        echo json_encode([
            'status'            => 'success',
            'kode_pesanan'      => $kode_pesanan,
            'metode_pembayaran' => $metode_pembayaran,
            'payment_url'       => null
        ]);
        exit;
    }

    // 3B. JIKA MEMILIH MIDTRANS PAYMENT GATEWAY
    $midtransPayload = [
        'transaction_details' => [
            'order_id'     => $kode_pesanan,
            'gross_amount' => $total_bayar,
        ],
        'customer_details' => [
            'first_name' => $nama_penerima,
            'phone'      => $telepon_penerima,
            'shipping_address' => [
                'first_name' => $nama_penerima,
                'phone'      => $telepon_penerima,
                'address'    => $alamat,
            ]
        ]
    ];

    $midtransRes = buatTransaksiMidtrans($midtransPayload);

    if (!isset($midtransRes['redirect_url']) || empty($midtransRes['redirect_url'])) {
        $pdo->rollBack();
        ob_clean();
        echo json_encode([
            'status'  => 'error',
            'message' => 'Gagal membuat transaksi Midtrans. Periksa kembali Server Key Anda.',
            'detail'  => $midtransRes
        ]);
        exit;
    }

    $upd = $pdo->prepare("UPDATE pesanan SET snap_token = ?, payment_url = ? WHERE id_pesanan = ?");
    $upd->execute([
        $midtransRes['token'],
        $midtransRes['redirect_url'],
        $id_pesanan
    ]);

    $pdo->commit();

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    unset($_SESSION['cart']);

    ob_clean();
    echo json_encode([
        'status'            => 'success',
        'kode_pesanan'      => $kode_pesanan,
        'metode_pembayaran' => 'MIDTRANS',
        'snap_token'        => $midtransRes['token'],
        'payment_url'       => $midtransRes['redirect_url']
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    ob_clean();
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$finish_url = $protocol . $_SERVER['HTTP_HOST'] . '/dhirabakery/pesanan_saya.php';

$midtransPayload = [
    'transaction_details' => [
        'order_id'     => $kode_pesanan,
        'gross_amount' => $total_bayar,
    ],
    'callbacks' => [
        'finish' => $finish_url
    ],
    'customer_details' => [
        'first_name' => $nama_penerima,
        'phone'      => $telepon_penerima,
        'shipping_address' => [
            'first_name' => $nama_penerima,
            'phone'      => $telepon_penerima,
            'address'    => $alamat,
        ]
    ]
];

?>