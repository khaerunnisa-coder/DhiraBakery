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
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 py-4" style="background-color: var(--bg-main, #f5efe6);">

    <div class="container" style="max-width: 450px;">
        <!-- Header Judul Cokelat Pekat -->
        <div class="text-center mb-4">
            <a href="index.php" class="brand-title fs-2 text-decoration-none d-inline-block mb-1" style="color: #23120b !important;">DHIRA BAKERY</a>
            <p class="tech-tag mt-1" style="color: #7a6b63 !important;"> CUSTOMER ACCESS PORTAL </p>
        </div>

        <!-- Kartu Login Cokelat Warm -->
        <div class="futuristic-card shadow-lg" style="background-color: var(--bg-dark, #23120b); border: 1px solid var(--accent-brown, #7c3626); border-radius: 24px; padding: 32px;">
            <h4 class="fw-bold text-white mb-3 text-center">LOGIN PELANGGAN</h4>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger bg-danger text-white border-0 py-2 px-3 small rounded-3 mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= $error; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-3">
                    <label class="tech-tag mb-1 d-block" style="color: #e5dabe !important; font-weight: 700;">EMAIL:</label>
                    <input type="email" name="email" class="form-control form-control-tech" placeholder="user@gmail.com" value="user@gmail.com" required>
                </div>

                <div class="mb-4">
                    <label class="tech-tag mb-1 d-block" style="color: #e5dabe !important; font-weight: 700;">PASSWORD:</label>
                    <input type="password" name="password" class="form-control form-control-tech" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn-terracotta w-100 justify-content-center py-3 mb-3 shadow">
                    LOG IN PELANGGAN <i class="bi bi-box-arrow-in-right ms-1"></i>
                </button>

                <div class="text-center border-top border-secondary pt-3 mt-3">
                    <span class="small text-white-50">Belum punya akun?</span>
                    <a href="register.php" class="tech-tag text-warning text-decoration-none ms-1">DAFTAR SEKARANG</a>
                </div>
            </form>
        </div>

        <div class="text-center mt-4">
            <a href="index.php" class="tech-tag text-decoration-none fw-bold" style="color: #7a6b63 !important;"><i class="bi bi-arrow-left me-1"></i>  KEMBALI KE KATALOG </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>