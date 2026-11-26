<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/koneksi.php';

$id_pelanggan = isset($_GET['id_pelanggan']) ? intval($_GET['id_pelanggan']) : 0;

if ($id_pelanggan > 0) {
    $stmt = $pdo->prepare("SELECT * FROM pesanan WHERE id_pelanggan = ? ORDER BY tanggal_pesan DESC");
    $stmt->execute([$id_pelanggan]);
    $pesanan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $pesanan
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "ID Pelanggan tidak valid"]);
}
?>