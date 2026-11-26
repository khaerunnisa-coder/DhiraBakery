<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/koneksi.php';

try {
    $stmt = $pdo->query("SELECT id_kategori, nama_kategori, deskripsi FROM kategori_produk WHERE status_aktif = 1");
    $kategori = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $kategori
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>