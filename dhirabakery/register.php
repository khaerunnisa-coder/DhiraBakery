<?php
session_start();
require_once 'config/koneksi.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']);
    $email    = trim($_POST['email']);
    $wa       = trim($_POST['nomor_whatsapp']);
    $password = trim($_POST['password']);

    if (!empty($nama) && !empty($email) && !empty($wa) && !empty($password)) {
        $stmt_check = $pdo->prepare("SELECT id_user FROM users WHERE email = ?");
        $stmt_check->execute([$email]);
        if ($stmt_check->fetch()) {
            $error = 'Email tersebut sudah terdaftar. Silakan login.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            
            try {
                $pdo->beginTransaction();
                
                $stmt_u = $pdo->prepare("INSERT INTO users (nama, email, nomor_whatsapp, password, role) VALUES (?, ?, ?, ?, 'pelanggan')");
                $stmt_u->execute([$nama, $email, $wa, $hash]);
                $id_user = $pdo->lastInsertId();

                $stmt_p = $pdo->prepare("INSERT INTO pelanggan (id_user, nama_pelanggan, nomor_whatsapp, email) VALUES (?, ?, ?, ?)");
                $stmt_p->execute([$id_user, $nama, $wa, $email]);

                $pdo->commit();
                $success = 'Pendaftaran akun berhasil! Silakan login.';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Harap isi seluruh formulir pendaftaran.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REGISTRASI PELANGGAN — DHIRA BAKERY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 py-5">

    <div class="container" style="max-width: 500px;">
        <div class="text-center mb-4">
            <a href="index.php" class="brand-title fs-2 text-white text-decoration-none">DHIRA BAKERY™</a>
            <p class="tech-tag text-white-50 mt-1">[ NEW MEMBER REGISTRATION ]</p>
        </div>

        <div class="futuristic-card shadow-lg">
            <h4 class="fw-bold text-white mb-3 text-center">DAFTAR AKUN PELANGGAN</h4>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger bg-danger text-white border-0 py-2 px-3 small rounded-3 mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= $error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success bg-success text-white border-0 py-2 px-3 small rounded-3 mb-3">
                    <i class="bi bi-check-circle-fill me-1"></i> <?= $success; ?>
                    <div class="mt-2"><a href="login.php" class="btn btn-sm btn-light rounded-pill fw-bold text-dark">Lanjut Login</a></div>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-3">
                    <label class="tech-tag mb-1">NAMA LENGKAP:</label>
                    <input type="text" name="nama" class="form-control form-control-tech" placeholder="Contoh: Rina Amalia" required>
                </div>

                <div class="mb-3">
                    <label class="tech-tag mb-1">EMAIL ADDRESS:</label>
                    <input type="email" name="email" class="form-control form-control-tech" placeholder="user@gmail.com" required>
                </div>

                <div class="mb-3">
                    <label class="tech-tag mb-1">NOMOR WHATSAPP:</label>
                    <input type="tel" name="nomor_whatsapp" class="form-control form-control-tech" placeholder="081234567890" required>
                </div>

                <div class="mb-4">
                    <label class="tech-tag mb-1">PASSWORD:</label>
                    <input type="password" name="password" class="form-control form-control-tech" placeholder="Buat password..." required>
                </div>

                <button type="submit" class="btn btn-light btn-lg w-100 rounded-pill fw-bold text-dark mb-3">
                    BUAT AKUN BARU <i class="bi bi-person-plus ms-1"></i>
                </button>

                <div class="text-center border-top border-secondary pt-3 mt-3">
                    <span class="small text-white-50">Sudah punya akun?</span>
                    <a href="login.php" class="tech-tag text-warning text-decoration-none ms-1">LOGIN DISINI</a>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
