<?php
require 'config.php';

// Generate hash baru
$new_hash1 = password_hash('admin123', PASSWORD_BCRYPT);
$new_hash = password_hash('host123', PASSWORD_BCRYPT);
echo $new_hash1 . "<br>";
echo $new_hash;

// Hapus user lama & masukkan user admin baru
$pdo->exec("DELETE FROM users WHERE username = 'host_a'");
$stmt = $pdo->prepare("INSERT INTO users (username, password, name) VALUES ('host_a', ?, 'Host A')");

if ($stmt->execute([$new_hash])) {
    echo "<h2 style='color:green;'>Password Host Berhasil Direset!</h2>";
    echo "<p>Username: <b>host_a</b></p>";
    echo "<p>Password: <b>host123</b></p>";
    echo "<a href='login'>Klik di sini untuk Login</a>";
} else {
    echo "<h2 style='color:red;'>Gagal mengupdate database.</h2>";
}
