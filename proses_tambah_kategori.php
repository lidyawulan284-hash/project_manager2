<?php
// Mulai sesi dan koneksi database di sini
$pdo = new PDO("mysql:host=localhost;dbname=ruang_rindu_db", "root", "");

// Tangkap datanya
$nama_kategori = $_POST['nama_kategori'];

// Taruh PREPARED STATEMENT di sini
$stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (:nama)");
$stmt->execute(['nama' => $nama_kategori]);

// Kalau sukses, kembalikan user ke halaman master data
header("Location: master_data.php?status=sukses");
exit();
?>