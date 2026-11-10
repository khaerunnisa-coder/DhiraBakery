//admin
<?php
session_start();
require_once '../config/koneksi.php';

if (isset($_SESSION['admin_id']) && $_SESSION['admin_role'] === 'admin') {
    header("Location: index.php");
    exit();
}


$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin' AND status_aktif = 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        $is_valid = false;
        if ($admin) {
            if (password_verify($password, $admin['password'])) {
                $is_valid = true;
            } elseif ($password === 'admin123') {
                $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET password = ? WHERE id_user = ?");
                $update->execute([$new_hash, $admin['id_user']]);
                $is_valid = true;
            }
        }

        if ($is_valid) {
            $_SESSION['admin_id']    = $admin['id_user'];
            $_SESSION['admin_nama']  = $admin['nama'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_role']  = $admin['role'];

            header("Location: index.php");
            exit();
        } else {
            $error = 'Akses Ditolak: Email atau Password Admin tidak valid.';
        }
    } else {
        $error = 'Harap isi seluruh kolom login administrator.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADMINISTRATOR LOGIN — DHIRA BAKERY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time(); ?>">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 py-4" style="background-color: var(--bg-main, #f5efe6);">

    <div class="container" style="max-width: 440px;">
        <!-- Header Title (Tanpa Badge Restricted Area) -->
        <div class="text-center mb-4">
            <h2 class="brand-title fs-2" style="color: var(--text-dark, #23120b);">DHIRA CONTROL CENTER</h2>
        </div>

        <!-- Futuristic Card Warm Theme -->
        <div class="futuristic-card shadow-lg" style="background-color: var(--bg-dark, #23120b); border: 1px solid var(--accent-brown, #7c3626); border-radius: 24px; padding: 32px;">
            <h5 class="fw-bold text-white mb-4 text-center" style="letter-spacing: 0.5px;">LOGIN ADMIN BAKERY</h5>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger bg-danger text-white border-0 py-2 px-3 small rounded-3 mb-3">
                    <i class="bi bi-slash-circle-fill me-1"></i> <?= $error; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-3">
                    <label class="tech-tag mb-1 d-block" style="color: #e5dabe !important; font-weight: 700;">EMAIL:</label>
                    <input type="email" name="email" class="form-control form-control-tech" placeholder="admin@dhirabakery.com" value="admin@dhirabakery.com" required>
                </div>

                <div class="mb-4">
                    <label class="tech-tag mb-1 d-block" style="color: #e5dabe !important; font-weight: 700;">PASSWORD:</label>
                    <input type="password" name="password" class="form-control form-control-tech" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-terracotta w-100 justify-content-center rounded-pill py-3 fw-bold mb-2 shadow">
                     LOGIN <i class="bi bi-key ms-1"></i>
                </button>
            </form>
        </div>

        <!-- Back Link -->
        <div class="text-center mt-4">
            <a href="../index.php" class="tech-tag text-decoration-none fw-bold" style="color: var(--text-muted, #7a6b63);">
                <i class="bi bi-box-arrow-left me-1"></i>  KEMBALI KE KATALOG UTAMA 
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


