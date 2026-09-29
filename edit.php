<?php
// Tampilkan error agar halaman tidak putih kosong
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config.php';

// Ambil ID dari URL
$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

// Ambil data produk yang mau diedit dari database
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die("Produk tidak ditemukan.");
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = (float) $_POST['price'];
    $stock = (int) $_POST['stock'];

    if (empty($category)) $errors[] = "Kategori wajib dipilih.";
    if ($price <= 0) $errors[] = "Harga harus lebih dari 0.";
    if ($stock < 0) $errors[] = "Stok tidak boleh negatif.";

    // Jika tidak ada error, update ke database
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE products SET name = ?, category = ?, price = ?, stock = ? WHERE id = ?");
        $stmt->execute([$name, $category, $price, $stock, $id]);
        
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - POS App</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #F3F4F6;
            color: #111827;
        }
        .pos-header {
            background-color: #343058;
            padding: 25px 20px;
            color: white;
            text-align: center;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            margin-bottom: -40px; 
        }
        .form-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: none;
            position: relative;
            z-index: 10;
        }
        .form-label {
            font-weight: 600;
            color: #4B5563;
            font-size: 0.9rem;
        }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #E5E7EB;
            font-size: 0.95rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #343058;
            box-shadow: 0 0 0 3px rgba(52, 48, 88, 0.1);
        }
        .btn-success {
            background-color: #10B981; /* Hijau modern */
            border: none;
            border-radius: 10px;
            padding: 12px 20px;
            font-weight: 600;
        }
        .btn-success:hover { background-color: #059669; }
        
        .btn-secondary {
            background-color: #F3F4F6;
            color: #4B5563;
            border: none;
            border-radius: 10px;
            padding: 12px 20px;
            font-weight: 600;
        }
        .btn-secondary:hover { background-color: #E5E7EB; color: #111827; }
    </style>
</head>
<body>
    <div class="pos-header shadow-sm">
        <h2 class="m-0" style="font-weight: 600; font-size: 1.5rem;">Edit Produk</h2>
    </div>

    <div class="container mt-5 mb-5" style="max-width: 600px;">
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="border-radius: 12px;">
                <ul class="mb-0">
                    <?php foreach ($errors as $error) echo "<li>$error</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="form-card p-4 p-md-5">
            <div class="mb-3">
                <label class="form-label">Nama Produk</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? $product['name'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            
            <!-- Kategori Diubah Menjadi Dropdown Terkunci -->
            <div class="mb-3">
                <label class="form-label">Kategori</label>
                <?php $currentCategory = $_POST['category'] ?? $product['category']; ?>
                <select name="category" class="form-select" required>
                    <option value="" disabled>-- Pilih Kategori --</option>
                    <option value="Coffee" <?= ($currentCategory === 'Coffee') ? 'selected' : '' ?>>Coffee</option>
                    <option value="Non Coffee" <?= ($currentCategory === 'Non Coffee') ? 'selected' : '' ?>>Non Coffee</option>
                    <option value="Makanan" <?= ($currentCategory === 'Makanan') ? 'selected' : '' ?>>Makanan</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Harga (Rp)</label>
                <input type="number" name="price" class="form-control" required value="<?= htmlspecialchars($_POST['price'] ?? $product['price'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            
            <div class="mb-4">
                <label class="form-label">Stok</label>
                <input type="number" name="stock" class="form-control" required value="<?= htmlspecialchars($_POST['stock'] ?? $product['stock'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success flex-grow-1">Simpan Perubahan</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</body>
</html>