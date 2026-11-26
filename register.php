<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/koneksi.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['nama']) && !empty($data['email']) && !empty($data['nomor_whatsapp']) && !empty($data['password'])) {
    try {
        // Cek apakah email sudah terdaftar
        $cek = $pdo->prepare("SELECT id_user FROM users WHERE email = ?");
        $cek->execute([trim($data['email'])]);
        if ($cek->fetch()) {
            echo json_encode(["status" => "error", "message" => "Email sudah terdaftar"]);
            exit();
        }

        $pdo->beginTransaction();

        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmtUser = $pdo->prepare("INSERT INTO users (nama, email, nomor_whatsapp, password, role) VALUES (?, ?, ?, ?, 'pelanggan')");
        $stmtUser->execute([
            trim($data['nama']),
            trim($data['email']),
            trim($data['nomor_whatsapp']),
            $hash
        ]);
        $id_user = $pdo->lastInsertId();

        $stmtPelanggan = $pdo->prepare("INSERT INTO pelanggan (id_user, nama_pelanggan, nomor_whatsapp, email) VALUES (?, ?, ?, ?)");
        $stmtPelanggan->execute([
            $id_user,
            trim($data['nama']),
            trim($data['nomor_whatsapp']),
            trim($data['email'])
        ]);

        $pdo->commit();

        echo json_encode([
            "status" => "success",
            "message" => "Registrasi berhasil, silakan login"
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["status" => "error", "message" => "Gagal registrasi: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Semua kolom wajib diisi"]);
}
?>