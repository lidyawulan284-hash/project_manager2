<?php
// Tampilkan error agar halaman tidak putih kosong jika ada salah
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = (float) $_POST['price'];
    $stock = (int) $_POST['stock'];

    // Validasi data sesuai instruksi soal
    if (strlen($name) < 3) $errors[] = "Nama produk minimal 3 karakter.";
    if ($price <= 0) $errors[] = "Harga harus lebih dari 0.";
    if ($stock < 0) $errors[] = "Stok tidak boleh negatif.";

    // Validasi Nama Unik (tidak boleh ganda)
    $stmt = $pdo->prepare("SELECT id FROM products WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        $errors[] = "Nama produk sudah terdaftar (harus unik).";
    }

    // Jika tidak ada error, masukkan ke database
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO products (name, category, price, stock) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $category, $price, $stock]);
        
        // Redirect kembali ke index
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Produk</title>
    <!-- Gunakan Bootstrap agar tampilan bagus -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5" style="max-width: 600px;">
        <h2 class="mb-4">Tambah Produk Baru</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error) echo "<li>$error</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="card p-4 shadow-sm">
            <div class="mb-3">
                <label class="form-label">Nama Produk</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Kategori</label>
                <input type="text" name="category" class="form-control" required value="<?= htmlspecialchars($_POST['category'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Harga (Rp)</label>
                <input type="number" name="price" class="form-control" required value="<?= htmlspecialchars($_POST['price'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Stok</label>
                <input type="number" name="stock" class="form-control" required value="<?= htmlspecialchars($_POST['stock'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Simpan Produk</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</body>
</html>