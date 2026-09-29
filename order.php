<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'config.php';

// Ambil ID produk dari URL
$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die("Produk tidak ditemukan.");
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qty = (int) $_POST['quantity'];
    
    if ($qty <= 0) {
        $errors[] = "Jumlah pesanan harus lebih dari 0.";
    } elseif ($qty > $product['stock']) {
        $errors[] = "Stok tidak mencukupi! Sisa stok hanya " . $product['stock'] . " pcs.";
    } else {
        $total_price = $qty * $product['price'];
        
        // Simpan pesanan ke tabel orders (agar muncul di history.php)
        $stmt = $pdo->prepare("INSERT INTO orders (product_name, quantity, total_price) VALUES (?, ?, ?)");
        $stmt->execute([$product['name'], $qty, $total_price]);
        
        // Kurangi stok di tabel products
        $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt->execute([$qty, $id]);
        
        // Arahkan ke halaman riwayat pesanan setelah sukses
        header("Location: history.php"); 
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Menu - Ruang Rindu</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #FAF9F6; color: #4A4A4A; }
        .pos-header { background-color: #FADADD; padding: 25px 20px; color: #4A4A4A; text-align: center; border-bottom-left-radius: 25px; border-bottom-right-radius: 25px; margin-bottom: -40px; box-shadow: 0 4px 15px rgba(250, 218, 221, 0.5); }
        .form-card { background: white; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: none; position: relative; z-index: 10; padding: 30px; }
        .product-title { font-weight: 700; font-size: 1.4rem; color: #4A4A4A; margin-bottom: 5px; }
        .product-info { color: #9CA3AF; font-size: 0.95rem; margin-bottom: 25px; }
        .form-label { font-weight: 600; color: #4A4A4A; font-size: 0.95rem; }
        .form-control { border-radius: 12px; padding: 12px 15px; border: 2px solid #FFF0F2; font-size: 1rem; text-align: center; font-weight: 600; }
        .form-control:focus { border-color: #FADADD; box-shadow: 0 0 0 3px rgba(250, 218, 221, 0.3); }
        
        .btn-primary { background-color: #C7CEEA; color: #4A4A4A; border: none; border-radius: 12px; padding: 12px 20px; font-weight: 600; }
        .btn-primary:hover { filter: brightness(0.95); color: #4A4A4A; }
        .btn-secondary { background-color: transparent; color: #D98A8A; border: 2px solid #FDE4E4; border-radius: 12px; padding: 12px 20px; font-weight: 600; }
        .btn-secondary:hover { background-color: #FFDAC1; border-color: #FFDAC1; color: #4A4A4A; }
    </style>
</head>
<body>
    <div class="pos-header">
        <h2 class="m-0" style="font-weight: 700; font-size: 1.5rem;">Pesan Produk</h2>
    </div>

    <div class="container mt-5 mb-5" style="max-width: 500px;">
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="border-radius: 12px;">
                <ul class="mb-0">
                    <?php foreach ($errors as $error) echo "<li>$error</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="form-card">
            
            <div class="text-center">
                <h3 class="product-title text-capitalize"><?= htmlspecialchars($product["name"], ENT_QUOTES, "UTF-8") ?></h3>
                <div class="product-info">
                    Harga: <strong>Rp <?= number_format($product["price"], 0, ',', '.') ?></strong> <br>
                    Sisa Stok: <strong style="<?= $product['stock'] <= 5 ? 'color: #D98A8A;' : 'color: #4A4A4A;' ?>"><?= htmlspecialchars($product["stock"], ENT_QUOTES, "UTF-8") ?> pcs</strong>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label d-block text-center">Berapa banyak yang ingin dipesan?</label>
                <input type="number" name="quantity" class="form-control" required min="1" max="<?= $product['stock'] ?>" value="1">
            </div>
            
            <div class="d-flex flex-column gap-2">
                <button type="submit" class="btn btn-primary w-100">Konfirmasi Pesanan</button>
                <a href="index.php" class="btn btn-secondary text-center text-decoration-none w-100">Batal</a>
            </div>
        </form>
    </div>
</body>
</html>