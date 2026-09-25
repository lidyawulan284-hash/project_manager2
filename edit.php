<?php
require 'config.php';

// Ambil ID dari URL
$id = $_GET['id'] ?? 0;

// Cari data produk berdasarkan ID
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

// Jika produk tidak ada, hentikan program
if (!$product) {
    die("Produk tidak ditemukan.");
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = (float) $_POST['price'];
    $stock = (int) $_POST['stock'];

    if (strlen($name) < 3) $errors[] = "Nama produk minimal 3 karakter.";
    if ($price <= 0) $errors[] = "Harga harus lebih dari 0.";
    if ($stock < 0) $errors[] = "Stok tidak boleh negatif.";

    // Cek nama unik, tapi abaikan jika namanya sama dengan milik produk yang sedang diedit ini
    $stmt = $pdo->prepare("SELECT id FROM products WHERE name = ? AND id != ?");
    $stmt->execute([$name, $id]);
    if ($stmt->fetch()) {
        $errors[] = "Nama produk sudah digunakan oleh produk lain.";
    }

    if (empty($errors)) {
        // Update data
        $stmt = $pdo->prepare("UPDATE products SET name = ?, category = ?, price = ?, stock = ? WHERE id = ?");
        $stmt->execute([$name, $category, $price, $stock, $id]);
        
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5" style="max-width: 600px;">
        <h2 class="mb-4">Edit Produk</h2>
        
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
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? $product['name'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Kategori</label>
                <input type="text" name="category" class="form-control" required value="<?= htmlspecialchars($_POST['category'] ?? $product['category'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Harga (Rp)</label>
                <input type="number" name="price" class="form-control" required value="<?= htmlspecialchars($_POST['price'] ?? $product['price'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Stok</label>
                <input type="number" name="stock" class="form-control" required value="<?= htmlspecialchars($_POST['stock'] ?? $product['stock'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div>
                <button type="submit" class="btn btn-success">Update Data</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</body>
</html>