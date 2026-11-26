<?php
// Ganti dengan Server Key Sandbox dari Dashboard Midtrans Anda
define('MIDTRANS_SERVER_KEY', 'SB-Mid-server-3admsb~.yWD8t6NwuArTRdBv');
define('MIDTRANS_IS_PRODUCTION', false);

define(
    'MIDTRANS_SNAP_URL',
    MIDTRANS_IS_PRODUCTION
        ? 'https://app.midtrans.com/snap/v1/transactions'
        : 'https://app.sandbox.midtrans.com/snap/v1/transactions'
);

function buatTransaksiMidtrans(array $payload): array
{
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL            => MIDTRANS_SNAP_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_SSL_VERIFYPEER => false, // Menghindari error sertifikat SSL bawaan XAMPP di Windows
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':')
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return ['error' => 'cURL Error: ' . $curlErr];
    }

    $result = json_decode($response, true);
    $result['http_code'] = $httpCode;
    return $result;
}
function cekStatusMidtrans($order_id) {
    $server_key = defined('MIDTRANS_SERVER_KEY') ? MIDTRANS_SERVER_KEY : ($GLOBALS['midtrans_server_key'] ?? '');
    $is_prod    = defined('MIDTRANS_IS_PRODUCTION') ? MIDTRANS_IS_PRODUCTION : false;

    $base_url = $is_prod 
        ? "https://api.midtrans.com/v2/" 
        : "https://api.sandbox.midtrans.com/v2/";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $base_url . urlencode($order_id) . "/status");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($server_key . ':')
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true) ?? [];
}
?>