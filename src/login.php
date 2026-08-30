<?php
session_start();
require 'config.php';

$error = '';

if (isset($_SESSION['host_logged_in'])) {
    header("Location: host");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Hanya gunakan password_verify dari database
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['host_logged_in'] = true;
        $_SESSION['host_name'] = $user['name'];
        header("Location: host");
        exit;
    } else {
        $error = 'Username atau Password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Login Host / Pengajar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-white d-flex align-items-center vh-100">
    <div class="container" style="max-width: 400px;">
        <div class="card bg-secondary text-white shadow border-0 p-4">
            <h3 class="fw-bold text-center mb-3">Login Host</h3>
            <?php if ($error): ?>
                <div class="alert alert-danger p-2 fs-6 text-center"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required placeholder="Username">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="Password">
                </div>
                <button type="submit" class="btn btn-warning w-100 fw-bold">Masuk</button>
            </form>
            <div class="text-center mt-3">
                <a href="/" class="text-light text-decoration-none">← Kembali ke Halaman Peserta</a>
            </div>
        </div>
    </div>
</body>

</html>