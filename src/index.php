<?php
session_start();
require 'config.php';

$error = '';

if (isset($_SESSION['error_msg'])) {
    $error = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = trim($_POST['pin']);
    $name = trim($_POST['name']);
    $institution = trim($_POST['institution']);

    $stmt = $pdo->prepare("SELECT * FROM game_sessions WHERE pin = ? AND status = 'active'");
    $stmt->execute([$pin]);
    $session = $stmt->fetch();

    if ($session) {
        $stmt_resp = $pdo->prepare("INSERT INTO respondents (session_id, name, institution) VALUES (?, ?, ?)");
        $stmt_resp->execute([$session['id'], $name, $institution]);
        $respondent_id = $pdo->lastInsertId();

        header("Location: play?respondent_id=$respondent_id&level=1");
        exit;
    } else {
        $_SESSION['error_msg'] = "PIN Key tidak valid atau sedang tidak aktif!";
        header("Location: index");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lembar Kerja PID | BBPMP Provinsi Jawa Timur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="logo.png" />
    <link rel="apple-touch-icon" href="logo.png">
    <style>
        /* Animasi Background Gradient Purple to Blue */
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
    <div class="container" style="max-width: 450px;">
        <div class="card glass-card border-0 p-4">
            <img src="logo.png" alt="Logo" class="mx-auto d-block mb-3" style="width: 100px;">
            <h3 class="fw-bold text-center mb-1" style="color: #4a00e0;">LEMBAR KERJA PESERTA</h3>
            <p class="text-center text-muted mb-4">PEMANFAATAN FITUR PAPAN INTERAKTIF DIGITAL</p>

            <?php if ($error): ?>
                <div class="alert alert-danger p-2 fs-6 text-center rounded-3"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary">Nama Lengkap</label>
                    <input type="text" name="name" class="form-control" required placeholder="Masukkan nama Anda">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold text-secondary">Instansi / Sekolah</label>
                    <input type="text" name="institution" class="form-control" required placeholder="Nama Sekolah/Instansi">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary">PIN Key</label>
                    <input type="text" name="pin" class="form-control form-control-lg text-center font-monospace fw-bold border-2" required placeholder="123456" style="letter-spacing: 2px;">
                </div>
                <button type="submit" class="btn btn-purple w-100 btn-lg fw-bold py-2">Mulai Eksplorasi</button>
            </form>
            <hr class="my-4 text-secondary">
            <footer class="text-center">BBPMP Provinsi Jawa Timur | <?= date('Y') ?></footer>
        </div>
    </div>
</body>

</html>