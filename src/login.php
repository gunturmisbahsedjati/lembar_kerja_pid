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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Host / Pengajar</title>
    <meta name="author" content="Arghavan Barra Al Misbah" />
    <meta name="language" content="Indonesia" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="logo.png" />
    <link rel="apple-touch-icon" href="logo.png">
    <style>
        /* Animasi Background Gradient Purple to Blue seperti index.php */
        body {
            background: linear-gradient(-45deg, #4a00e0, #8e2de2, #00c6ff, #0072ff);
            background-size: 400% 400%;
            animation: gradientBG 12s ease infinite;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes gradientBG {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        /* Styling Kartu Efek Transparan / Glassmorphism */
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .btn-purple {
            background: linear-gradient(135deg, #8e2de2, #4a00e0);
            border: none;
            color: white;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-purple:hover {
            background: linear-gradient(135deg, #4a00e0, #8e2de2);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 0, 224, 0.4);
        }
    </style>
</head>

<body>
    <div class="container" style="max-width: 400px;">
        <div class="card glass-card border-0 p-4">
            <h3 class="fw-bold text-center mb-3" style="color: #4a00e0;">Login Host</h3>
            <?php if ($error): ?>
                <div class="alert alert-danger p-2 fs-6 text-center rounded-3"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary">Username</label>
                    <input type="text" name="username" class="form-control" required placeholder="Masukkan Username">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary">Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="Masukkan Password">
                </div>
                <button type="submit" class="btn btn-purple w-100 btn-lg fw-bold py-2">Masuk</button>
                <a href="panduan" target="_blank" class="btn btn-outline-secondary w-100 fw-bold mt-2">Panduan</a>
            </form>
            <div class="text-center mt-3">
                <a href="/" class="text-secondary text-decoration-none small">← Kembali ke Halaman Peserta</a>
            </div>
        </div>
    </div>
    <noscript>
        <div style="background:#333;opacity:0.8;filter:alpha(opacity=80);width:100%;height:100%;position:fixed;top:0px;z-index:1099;"></div>
        <div style="background:#000;width:70%;margin:0% 15%;;position:fixed;top:20%;z-index:1100;text-align:center;padding:4%;color:#fff;">
            <p>We're sorry but this apps doesn't work properly without JavaScript enabled. Please enable it to continue.</p>
        </div>
    </noscript>
</body>

</html>