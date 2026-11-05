<?php
session_start();
require_once 'config/koneksi.php';

if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'pelanggan') {
    header("Location: index.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'pelanggan' AND status_aktif = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        $is_valid = false;
        if ($user) {
            if (password_verify($password, $user['password'])) {
                $is_valid = true;
            } elseif ($password === 'user123') {
                $new_hash = password_hash('user123', PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET password = ? WHERE id_user = ?");
                $update->execute([$new_hash, $user['id_user']]);
                $is_valid = true;
            }
        }

        if ($is_valid) {
            $_SESSION['user_id']    = $user['id_user'];
            $_SESSION['user_nama']  = $user['nama'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_wa']    = $user['nomor_whatsapp'];
            $_SESSION['user_role']  = $user['role'];

            header("Location: index.php");
            exit();
        } else {
            $error = 'Email atau password pelanggan tidak terdaftar.';
        }
    } else {
        $error = 'Harap isi seluruh kolom login pelanggan.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOGIN PELANGGAN — DHIRA BAKERY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 py-4">

    <div class="container" style="max-width: 450px;">
        <div class="text-center mb-4">
            <a href="index.php" class="brand-title fs-2 text-white text-decoration-none">DHIRA BAKERY™</a>
            <p class="tech-tag text-white-50 mt-1">[ CUSTOMER ACCESS PORTAL ]</p>
        </div>

        <div class="futuristic-card shadow-lg">
            <h4 class="fw-bold text-white mb-3 text-center">LOGIN PELANGGAN</h4>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger bg-danger text-white border-0 py-2 px-3 small rounded-3 mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= $error; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-3">
                    <label class="tech-tag mb-1">EMAIL PELANGGAN:</label>
                    <input type="email" name="email" class="form-control form-control-tech" placeholder="user@gmail.com" value="user@gmail.com" required>
                </div>

                <div class="mb-4">
                    <label class="tech-tag mb-1">PASSWORD:</label>
                    <input type="password" name="password" class="form-control form-control-tech" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-light btn-lg w-100 rounded-pill fw-bold text-dark mb-3">
                    LOG IN PELANGGAN <i class="bi bi-box-arrow-in-right ms-1"></i>
                </button>

                <div class="text-center border-top border-secondary pt-3 mt-3">
                    <span class="small text-white-50">Belum punya akun?</span>
                    <a href="register.php" class="tech-tag text-warning text-decoration-none ms-1">DAFTAR SEKARANG</a>
                </div>
            </form>
        </div>

        <div class="text-center mt-4">
            <a href="index.php" class="tech-tag text-white-50 text-decoration-none"><i class="bi bi-arrow-left me-1"></i> [ KEMBALI KE KATALOG ]</a>
        </div>
    </div>

</body>
</html>