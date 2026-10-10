<?php
// Koneksi database dengan PDO
$host = '127.0.0.1';
$db   = 'ruang_rindu_db';
$user = 'root';
$pass = '';
$pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Data dari form kasir (anggap saja dikirim via POST)
$product_id = $_POST['product_id']; 
$qty_diminta = $_POST['qty']; // Jumlah yang dibeli

try {
    // ==========================================
    // MULAI TRANSAKSI DATABASE (Syarat PPT 4)
    // ==========================================
    $pdo->beginTransaction(); 

    // 1. Ambil sisa stok saat ini dan kunci barisnya (FOR UPDATE)
    $stmtCek = $pdo->prepare("SELECT stock FROM products WHERE id = :id FOR UPDATE");
    $stmtCek->execute(['id' => $product_id]);
    $stok_sekarang = $stmtCek->fetchColumn();

    // ==========================================
    // VALIDASI STOK TIDAK NEGATIF (Syarat PPT 4)
    // ==========================================
    if ($qty_diminta > $stok_sekarang) {
        // Jika stok kurang, lemparkan error untuk ditangkap di blok catch
        throw new Exception("Transaksi Gagal: Stok tidak mencukupi! Sisa stok hanya $stok_sekarang.");
    }

    // 2. Kurangi stok di tabel products
    $stmtUpdate = $pdo->prepare("UPDATE products SET stock = stock - :qty WHERE id = :id");
    $stmtUpdate->execute([
        'qty' => $qty_diminta,
        'id' => $product_id
    ]);

    // 3. Catat ke tabel histori / pemesanan
    $stmtHistori = $pdo->prepare("INSERT INTO stock_movements (product_id, qty, type) VALUES (:id, :qty, 'OUT')");
    $stmtHistori->execute([
        'id' => $product_id,
        'qty' => $qty_diminta
    ]);

    // ==========================================
    // SIMPAN PERMANEN JIKA SEMUA BERHASIL
    // ==========================================
    $pdo->commit();
    echo "<script>alert('Pesanan berhasil dibuat!'); window.location='index.php';</script>";

} catch (Exception $e) {
    // ==========================================
    // BATALKAN SEMUA JIKA ADA YANG ERROR
    // ==========================================
    $pdo->rollBack(); 
    
    // Tampilkan pesan error (entah karena stok kurang atau database error)
    echo "<script>alert('" . $e->getMessage() . "'); window.history.back();</script>";
}
?>