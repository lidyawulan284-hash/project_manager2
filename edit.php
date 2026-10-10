<?php
session_start();
// Mematikan tampilan error default yang merusak desain
error_reporting(0);
ini_set('display_errors', 0);

require 'config.php';

// HANYA ADMIN YANG BOLEH MENGEDIT PRODUK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("<h2 style='text-align:center; margin-top:50px; font-family:sans-serif;'>Akses Ditolak! Hanya Admin yang boleh mengakses halaman ini.</h2>");
}

// Ambil ID produk dari URL
$id = $_GET['id'] ?? 0;

// Ambil data produk yang mau diedit
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die("<h2 style='text-align:center; margin-top:50px;'>Produk tidak ditemukan!</h2>");
}

// Ambil data kategori untuk pilihan form
$stmtCat = $pdo->query("SELECT * FROM categories");
$categories = $stmtCat->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category_id = (int) $_POST['category_id'];
    $price = (float) $_POST['price'];
    $stock = (int) $_POST['stock'];

    if (strlen($name) < 3) $errors[] = "Nama produk minimum 3 aksara.";
    if ($price <= 0) $errors[] = "Harga mesti lebih daripada 0.";
    if ($stock < 0) $errors[] = "Stok tidak boleh negatif.";
    if (empty($category_id)) $errors[] = "Kategori wajib dipilih.";

    if (empty($errors)) {
        // Update data ke database
        $update = $pdo->prepare("UPDATE products SET name = ?, category_id = ?, price = ?, stock = ? WHERE id = ?");
        $update->execute([$name, $category_id, $price, $stock, $id]);
        
        // Setelah sukses, kembalikan ke halaman Kelola
        header("Location: manage.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Menu - Ruang Rindu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #FAF9F6; color: #4A4A4A; }
        .pos-header { background-color: #FADADD; padding: 25px 20px; color: #4A4A4A; text-align: center; border-bottom-left-radius: 25px; border-bottom-right-radius: 25px; margin-bottom: -40px; box-shadow: 0 4px 15px rgba(250, 218, 221, 0.5); }
        .form-card { background: white; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: none; position: relative; z-index: 10; }
        .form-label { font-weight: 600; color: #4A4A4A; font-size: 0.9rem; }
        .form-control, .form-select { border-radius: 12px; padding: 12px 15px; border: 2px solid #FFF0F2; font-size: 0.95rem; }
        .form-control:focus, .form-select:focus { border-color: #FADADD; box-shadow: 0 0 0 3px rgba(250, 218, 221, 0.3); }
        .btn-primary { background-color: #C7CEEA; color: #4A4A4A; border: none; border-radius: 12px; padding: 12px 20px; font-weight: 600; transition: 0.2s; }
        .btn-primary:hover { filter: brightness(0.95); color: #4A4A4A; transform: scale(1.02); }
        .btn-secondary { background-color: transparent; color: #D98A8A; border: 2px solid #FDE4E4; border-radius: 12px; padding: 12px 20px; font-weight: 600; transition: 0.2s; }
        .btn-secondary:hover { background-color: #FFDAC1; border-color: #FFDAC1; color: #4A4A4A; transform: scale(1.02); }
    </style>
</head>
<body>
    <div class="pos-header">
        <h2 class="m-0" style="font-weight: 700; font-size: 1.5rem;">Edit Menu</h2>
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
                <label class="form-label">Nama Menu</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Kategori</label>
                <!-- DATA KATEGORI DIAMBIL DARI DATABASE DAN DIPILIH OTOMATIS -->
                <select name="category_id" class="form-select" required>
                    <option value="" disabled>-- Pilih Kategori --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Harga (Rp)</label>
                <!-- Dikonversi ke angka bulat agar mudah di-edit -->
                <input type="number" name="price" class="form-control" required value="<?= round($product['price']) ?>">
            </div>
            
            <div class="mb-4">
                <label class="form-label">Sisa Stok Saat Ini</label>
                <input type="number" name="stock" class="form-control" required value="<?= htmlspecialchars($product['stock'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Simpan Perubahan</button>
                <a href="manage.php" class="btn btn-secondary text-decoration-none text-center">Batal</a>
            </div>
        </form>
    </div>
</body>
</html>