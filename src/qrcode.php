<?php
require 'config.php';

$pin = $_GET['pin'] ?? null;
$error_type = null;
$session_data = null;

// 1. Validasi Keberadaan Parameter PIN
if (!$pin) {
    $error_type = 'missing_param';
} else {
    // 2. Cek PIN di Database
    $stmt = $pdo->prepare("SELECT * FROM game_sessions WHERE pin = ?");
    $stmt->execute([$pin]);
    $session_data = $stmt->fetch();

    if (!$session_data) {
        $error_type = 'not_found';
    } elseif ($session_data['status'] !== 'active') {
        $error_type = 'inactive';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Generator<?= $pin ? ' - PIN ' . htmlspecialchars($pin) : '' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Library QR Code Styling -->
    <script type="text/javascript" src="https://unpkg.com/qr-code-styling@1.5.0/lib/qr-code-styling.js"></script>
    <link rel="icon" type="image/x-icon" href="logo.png" />
    <link rel="apple-touch-icon" href="logo.png">
    <style>
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

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body>
    <div class="container text-center" style="max-width: 500px;">
        <div class="card glass-card border-0 p-4 align-items-center">

            <?php if ($error_type === 'missing_param'): ?>
                <!-- Tampilan Error Parameter Hilang -->
                <div class="py-3">
                    <div class="display-1 text-warning mb-2">⚠️</div>
                    <h4 class="fw-bold text-danger mb-2">Parameter PIN Tidak Ditemukan</h4>
                    <p class="text-muted small mb-4">Halaman ini memerlukan parameter PIN. Silakan buka QR Code melalui Dashboard Host.</p>
                    <!-- <a href="host" class="btn btn-outline-primary fw-bold rounded-pill px-4">Kembali ke Host</a> -->
                </div>

            <?php elseif ($error_type === 'not_found'): ?>
                <!-- Tampilan Error PIN Tidak Ditemukan -->
                <div class="py-3">
                    <div class="display-1 text-danger mb-2">❌</div>
                    <h4 class="fw-bold text-danger mb-2">PIN Key Tidak Valid</h4>
                    <p class="text-muted small mb-1">PIN Key <strong class="font-monospace text-dark"><?= htmlspecialchars($pin) ?></strong> tidak terdaftar dalam sistem.</p>
                    <p class="text-muted small mb-4">Pastikan Anda memasukkan nomor PIN yang benar.</p>
                    <!-- <a href="host" class="btn btn-outline-primary fw-bold rounded-pill px-4">Kembali ke Host</a> -->
                </div>

            <?php elseif ($error_type === 'inactive'): ?>
                <!-- Tampilan Error PIN Tidak Aktif -->
                <div class="py-3">
                    <div class="display-1 text-secondary mb-2">🚫</div>
                    <h4 class="fw-bold text-secondary mb-2">PIN Key Nonaktif</h4>
                    <p class="text-muted small mb-1">PIN Key <strong class="font-monospace text-dark"><?= htmlspecialchars($pin) ?></strong> sedang dalam status nonaktif.</p>
                    <p class="text-muted small mb-4">Aktifkan kembali sesi pada Dashboard Host untuk menampilkan QR Code.</p>
                    <!-- <a href="host" class="btn btn-outline-primary fw-bold rounded-pill px-4">Kembali ke Host</a> -->
                </div>

            <?php else: ?>
                <!-- Tampilan QR Code jika PIN Valid dan Aktif -->
                <h4 class="fw-bold mb-1" style="color: #4a00e0;"><?= htmlspecialchars($session_data['session_name']) ?></h4>

                <div class="mb-3">
                    <span class="text-secondary small d-block mb-1">PIN KEY SESI</span>
                    <span class="badge bg-warning text-dark fs-3 font-monospace px-3 py-2 shadow-sm"><?= htmlspecialchars($pin) ?></span>
                </div>

                <!-- Container QR Code -->
                <div id="canvas" class="p-2 bg-white rounded-3 shadow-sm mb-3"></div>

                <p class="font-monospace fw-bold text-secondary mb-3">http://36.93.42.26:8080/</p>

                <!-- Tombol Unduh QR Code -->
                <!-- <button onclick="downloadQR()" class="btn btn-primary w-100 fw-bold rounded-pill">Unduh QR Code (PNG)</button> -->
            <?php endif; ?>

        </div>
    </div>

    <?php if (!$error_type): ?>
        <script type="text/javascript">
            const currentPin = <?= json_encode($pin) ?>;

            // Konfigurasi QR Code dengan Warna dan Logo di Tengah
            const qrCode = new QRCodeStyling({
                width: 350,
                height: 350,
                type: "canvas",
                data: "http://36.93.42.26:8080/",
                image: "logo.png", // Path file logo
                imageOptions: {
                    hideBackgroundDots: true, // Menyembunyikan titik QR di belakang logo
                    imageSize: 0.60, // Ukuran proporsional logo (25%)
                    margin: 0 // Jarak margin di sekeliling logo
                },
                backgroundOptions: {
                    color: "#ffffff", // Background White
                },
                dotsOptions: {
                    color: "#0072ff", // Pixels Blue
                    type: "rounded"
                },
                cornersSquareOptions: {
                    color: "#8e2de2", // Squares Purple
                    type: "extra-rounded"
                },
                cornersDotOptions: {
                    color: "#8e2de2", // Square dots Purple
                    type: "dot"
                }
            });

            // Render QR Code ke dalam elemen HTML
            qrCode.append(document.getElementById("canvas"));

            // Fungsi untuk mengunduh QR Code sebagai gambar PNG
            function downloadQR() {
                const fileName = `QR_Lembar_Kerja_PID_PIN_${currentPin}`;
                qrCode.download({
                    name: fileName,
                    extension: "png"
                });
            }
        </script>
    <?php endif; ?>
</body>

</html>