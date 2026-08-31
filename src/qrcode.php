<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Generator - Lembar Kerja PID</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Library QR Code Styling -->
    <script type="text/javascript" src="https://unpkg.com/qr-code-styling@1.5.0/lib/qr-code-styling.js"></script>
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
    <div class="container text-center" style="max-width: 420px;">
        <div class="card glass-card border-0 p-4 align-items-center">
            <h4 class="fw-bold mb-1" style="color: #4a00e0;">QR Code Aplikasi</h4>
            <p class="text-muted small mb-3">Scan untuk membuka Lembar Kerja PID</p>

            <!-- Container QR Code -->
            <div id="canvas" class="p-2 bg-white rounded-3 shadow-sm mb-3"></div>

            <p class="font-monospace fw-bold text-secondary mb-3">http://36.93.42.26:8080/</p>

            <!-- Tombol Unduh QR Code -->
            <button onclick="downloadQR()" class="btn btn-primary w-100 fw-bold rounded-pill">Unduh QR Code
                (PNG)</button>
        </div>
    </div>

    <script type="text/javascript">
        // Konfigurasi QR Code dengan Warna Pilihan Anda
        const qrCode = new QRCodeStyling({
            width: 280,
            height: 280,
            type: "canvas",
            data: "http://36.93.42.26:8080/",
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
            qrCode.download({ name: "QR_Lembar_Kerja_PID", extension: "png" });
        }
    </script>
</body>

</html>