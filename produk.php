<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/koneksi.php';

$kategori_id = isset($_GET['kategori_id']) ? intval($_GET['kategori_id']) : 0;

try {
    if ($kategori_id > 0) {
        $stmt = $pdo->prepare("SELECT p.*, k.nama_kategori, COALESCE(s.jumlah_stok, 0) AS jumlah_stok 
                               FROM produk p 
                               LEFT JOIN kategori_produk k ON p.id_kategori = k.id_kategori 
                               LEFT JOIN stok_produk s ON p.id_produk = s.id_produk 
                               WHERE p.status_aktif = 1 AND p.id_kategori = ? 
                               ORDER BY p.id_produk DESC");
        $stmt->execute([$kategori_id]);
    } else {
        $stmt = $pdo->prepare("SELECT p.*, k.nama_kategori, COALESCE(s.jumlah_stok, 0) AS jumlah_stok 
                               FROM produk p 
                               LEFT JOIN kategori_produk k ON p.id_kategori = k.id_kategori 
                               LEFT JOIN stok_produk s ON p.id_produk = s.id_produk 
                               WHERE p.status_aktif = 1 
                               ORDER BY p.id_produk DESC");
        $stmt->execute();
    }

    $produk_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Sisipkan varian rasa pada masing-masing produk
    $stmtRasa = $pdo->prepare("SELECT id_rasa, nama_rasa, harga_tambahan FROM varian_rasa WHERE id_produk = ? AND tersedia = 1");
    foreach ($produk_list as &$p) {
        $stmtRasa->execute([$p['id_produk']]);
        $p['varian_rasa'] = $stmtRasa->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        "status" => "success",
        "data" => $produk_list
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>