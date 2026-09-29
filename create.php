<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = (float) $_POST['price'];
    $stock = (int) $_POST['stock'];
    
    // Tetapan nama gambar
    $imageName = null;

    if (strlen($name) < 3) $errors[] = "Nama produk minimum 3 aksara.";
    if ($price <= 0) $errors[] = "Harga mesti lebih daripada 0.";
    if ($stock < 0) $errors[] = "Stok tidak boleh negatif.";
    if (empty($category)) $errors[] = "Kategori wajib dipilih.";

    $stmt = $pdo->prepare("SELECT id FROM products WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        $errors[] = "Nama produk sudah didaftarkan.";
    }

    // PROSES MUAT NAIK GAMBAR
    if (empty($errors) && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['image']['tmp_name'];
        $fileName = basename($_FILES['image']['name']);
        
        $imageName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $fileName); 
        $uploadDir = 'uploads/';
        
        if (!move_uploaded_file($tmpName, $uploadDir . $imageName)) {
            $errors[] = "Gagal memuat naik gambar. Pastikan folder 'uploads' wujud.";
        }
    }

    // SIMPAN KE DATABASE
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO products (name, category, price, stock, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $category, $price, $stock, $imageName]);
        
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
    <title>Tambah Menu - Ruang Rindu</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #FAF9F6; color: #4A4A4A; }
        .pos-header { background-color: #FADADD; padding: 25px 20px; color: #4A4A4A; text-align: center; border-bottom-left-radius: 25px; border-bottom-right-radius: 25px; margin-bottom: -40px; box-shadow: 0 4px 15px rgba(250, 218, 221, 0.5); }
        .form-card { background: white; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: none; position: relative; z-index: 10; }
        .form-label { font-weight: 600; color: #4A4A4A; font-size: 0.9rem; }
        .form-control, .form-select { border-radius: 12px; padding: 12px 15px; border: 2px solid #FFF0F2; font-size: 0.95rem; }
        .form-control:focus, .form-select:focus { border-color: #FADADD; box-shadow: 0 0 0 3px rgba(250, 218, 221, 0.3); }
        .btn-primary { background-color: #C7CEEA; color: #4A4A4A; border: none; border-radius: 12px; padding: 12px 20px; font-weight: 600; }
        .btn-primary:hover { filter: brightness(0.95); color: #4A4A4A; }
        .btn-secondary { background-color: transparent; color: #D98A8A; border: 2px solid #FDE4E4; border-radius: 12px; padding: 12px 20px; font-weight: 600; }
        .btn-secondary:hover { background-color: #FFDAC1; border-color: #FFDAC1; color: #4A4A4A; }
    </style>
</head>
<body>
    <div class="pos-header">
        <h2 class="m-0" style="font-weight: 700; font-size: 1.5rem;">Tambah Menu Baru</h2>
    </div>

    <div class="container mt-5 mb-5" style="max-width: 600px;">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="border-radius: 12px;">
                <ul class="mb-0">
                    <?php foreach ($errors as $error) echo "<li>$error</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="form-card p-4 p-md-5">
            <div class="mb-3">
                <label class="form-label">Nama Menu</label>
                <input type="text" name="name" class="form-control" required placeholder="Contoh: Iced Americano" value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Kategori</label>
                <select name="category" class="form-select" required>
                    <option value="" disabled <?= empty($_POST['category']) ? 'selected' : '' ?>>-- Pilih Kategori --</option>
                    <option value="Coffee" <?= (isset($_POST['category']) && $_POST['category'] === 'Coffee') ? 'selected' : '' ?>>Coffee</option>
                    <option value="Non Coffee" <?= (isset($_POST['category']) && $_POST['category'] === 'Non Coffee') ? 'selected' : '' ?>>Non Coffee</option>
                    <option value="Makanan" <?= (isset($_POST['category']) && $_POST['category'] === 'Makanan') ? 'selected' : '' ?>>Makanan</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Gambar Menu (Opsional)</label>
                <input type="file" name="image" class="form-control" accept="image/png, image/jpeg, image/jpg">
                <small class="text-muted" style="font-size: 0.8rem;">Format: JPG atau PNG.</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Harga (Rp)</label>
                <input type="number" name="price" class="form-control" required placeholder="Contoh: 25000" value="<?= htmlspecialchars($_POST['price'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            
            <div class="mb-4">
                <label class="form-label">Stok Awal</label>
                <input type="number" name="stock" class="form-control" required placeholder="Contoh: 50" value="<?= htmlspecialchars($_POST['stock'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Simpan Menu</button>
                <a href="index.php" class="btn btn-secondary text-decoration-none text-center">Batal</a>
            </div>
        </form>
    </div>
</body>
</html>