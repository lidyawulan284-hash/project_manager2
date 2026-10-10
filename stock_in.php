<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

// Hanya Admin yang boleh akses Barang Masuk
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// --- SCRIPT OTOMATIS: MEMBUAT TABEL & CONTOH DATA ---
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        contact VARCHAR(50),
        address TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS stock_in (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        supplier_id INT NULL,
        qty INT NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $cekSup = $pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
    if ($cekSup == 0) {
        $pdo->exec("INSERT INTO suppliers (name, contact, address) VALUES ('Supplier Contoh (PT Segar Mentari)', '-', '-')");
    }
} catch (PDOException $e) {
}
// --------------------------------------------------------------

$success = '';
$error = '';

// ========================================================
// 1. PROSES TAMBAH BARANG MASUK & TAMBAH STOK FISIK
// ========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stock'])) {
    $product_id = (int)$_POST['product_id'];
    $supplier_id = empty($_POST['supplier_id']) ? NULL : (int)$_POST['supplier_id'];
    $qty = (int)$_POST['qty'];
    $notes = trim($_POST['notes']);

    if ($qty > 0) {
        try {
            $pdo->beginTransaction();

            $stmtIn = $pdo->prepare("INSERT INTO stock_in (product_id, supplier_id, qty, notes) VALUES (?, ?, ?, ?)");
            $stmtIn->execute([$product_id, $supplier_id, $qty, $notes]);

            $stmtUpdate = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmtUpdate->execute([$qty, $product_id]);

            $pdo->commit();
            $success = "Stok berhasil ditambahkan dan dicatat di histori!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Terjadi kesalahan saat memproses data.";
        }
    } else {
        $error = "Jumlah stok masuk harus lebih dari 0.";
    }
}

// ========================================================
// 2. PROSES HAPUS HISTORI BARANG MASUK & KURANGI STOK 
// ========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_history_id'])) {
    $history_id = (int)$_POST['delete_history_id'];

    try {
        $pdo->beginTransaction();

        $stmtGet = $pdo->prepare("SELECT product_id, qty FROM stock_in WHERE id = ?");
        $stmtGet->execute([$history_id]);
        $historyData = $stmtGet->fetch();

        if ($historyData) {
            // Kurangi stok di tabel products
            $stmtUpdate = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmtUpdate->execute([$historyData['qty'], $historyData['product_id']]);

            // Hapus histori
            $stmtDel = $pdo->prepare("DELETE FROM stock_in WHERE id = ?");
            $stmtDel->execute([$history_id]);

            $pdo->commit();
            $success = "Histori berhasil dihapus! Stok produk telah dikurangi kembali secara otomatis.";
        } else {
            $pdo->rollBack();
            $error = "Data histori tidak ditemukan.";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Terjadi kesalahan saat menghapus histori.";
    }
}

// Ambil Data untuk Form Dropdown
$products = $pdo->query("SELECT id, name, stock FROM products ORDER BY name ASC")->fetchAll();
$suppliers = $pdo->query("SELECT id, name FROM suppliers ORDER BY name ASC")->fetchAll();

// Ambil Histori Barang Masuk
$histories = $pdo->query("
    SELECT s.*, p.name AS product_name, sup.name AS supplier_name 
    FROM stock_in s 
    JOIN products p ON s.product_id = p.id 
    LEFT JOIN suppliers sup ON s.supplier_id = sup.id 
    ORDER BY s.created_at DESC LIMIT 50
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang Masuk - Ruang Rindu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-body: #FAF9F6;       
            --bg-card: #FFFFFF;       
            --pastel-pink: #FADADD;   
            --pastel-mint: #B5EAD7;   
            --pastel-peach: #FFDAC1;  
            --text-dark: #4A4A4A;     
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-body); color: var(--text-dark); padding-bottom: 50px; }
        .pos-header { background-color: var(--pastel-pink); padding: 25px 20px 35px 20px; border-bottom-left-radius: 25px; border-bottom-right-radius: 25px; box-shadow: 0 4px 15px rgba(250, 218, 221, 0.5); margin-bottom: 40px; }
        .cafe-brand { font-size: 1.6rem; font-weight: 800; color: var(--text-dark); margin: 0; }
        .cafe-brand span { color: #D98A8A; }
        
        .pos-tabs { display: flex; gap: 20px; font-size: 1.05rem; font-weight: 600; margin-top: 20px; flex-wrap: wrap; }
        .pos-tab-link { color: #A88B8E; text-decoration: none; padding-bottom: 5px; position: relative; transition: 0.2s; }
        .pos-tab-link.active { color: var(--text-dark); }
        .pos-tab-link.active::after { content: ''; position: absolute; bottom: -4px; left: 0; width: 100%; height: 3px; background-color: var(--text-dark); border-radius: 3px; }
        
        .card-custom { background-color: var(--bg-card); border-radius: 24px; padding: 25px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02); border: 1px solid #FFF0F2; height: 100%; }
        .form-control, .form-select { border-radius: 12px; border: 2px solid #FFF0F2; padding: 10px 15px; }
        .form-control:focus, .form-select:focus { border-color: var(--pastel-mint); box-shadow: none; }
        .btn-custom { border-radius: 12px; font-weight: 600; padding: 12px 20px; border: none; transition: 0.2s; width: 100%; }
        .btn-add { background-color: var(--pastel-mint); color: var(--text-dark); }
        .btn-add:hover { background-color: #A0DBC6; transform: scale(1.02); }
        
        .btn-delete-history { background-color: #FDE4E4; color: #D98A8A; padding: 6px 14px; border-radius: 10px; font-size: 0.85rem; border: none; font-weight: 600; transition: 0.2s; }
        .btn-delete-history:hover { background-color: #D98A8A; color: white; transform: scale(1.05); }

        .table thead th { background-color: #FAF9F6 !important; border-bottom: none; color: #9CA3AF; font-size: 0.85rem; text-transform: uppercase; }
        .table tbody td { vertical-align: middle; border-bottom: 1px solid #F3F4F6; font-size: 0.95rem; }

        /* Style untuk Modal Pop-up */
        .modal-content { border-radius: 24px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .btn-modal-cancel { background-color: #F3F4F6; color: #4A4A4A; border-radius: 12px; font-weight: 600; border: none; padding: 10px 24px; transition: 0.2s; }
        .btn-modal-cancel:hover { background-color: #E5E7EB; }
        .btn-modal-delete { background-color: #D98A8A; color: white; border-radius: 12px; font-weight: 600; border: none; padding: 10px 24px; transition: 0.2s; }
        .btn-modal-delete:hover { background-color: #C07979; color: white; }
    </style>
</head>
<body>

    <div class="pos-header">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h1 class="cafe-brand">☕ RUANG <span>RINDU</span></h1>
            <div class="d-flex gap-2">
                <a href="dashboard.php" class="btn btn-sm" style="background:#FFFFFF; color:#4A4A4A; border-radius:10px; font-weight:600; padding: 6px 14px; text-decoration: none;">Dashboard</a>
            </div>
        </div>

        <div class="pos-tabs">
            <a href="index.php" class="pos-tab-link">Kasir</a>
            <a href="history.php" class="pos-tab-link">Order</a>
            <a href="manage.php" class="pos-tab-link">Kelola</a>
            <a href="master_data.php" class="pos-tab-link">Master Data</a>
            <a href="stock_in.php" class="pos-tab-link active">Barang Masuk</a>
        </div>
    </div>

    <div class="container-fluid px-4">
        <?php if($success): ?>
            <div class="alert alert-success" style="border-radius: 16px; background-color: #E6F4EA; color: #1E4620; border: none;">✅ <?= $success ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger" style="border-radius: 16px; background-color: #FCE8E8; color: #A52A2A; border: none;">⚠️ <?= $error ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- FORM BARANG MASUK -->
            <div class="col-md-4">
                <div class="card-custom">
                    <h4 class="fw-bold mb-4">📥 Input Barang Masuk</h4>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pilih Produk</label>
                            <select name="product_id" class="form-select" required>
                                <option value="" disabled selected>-- Pilih Produk --</option>
                                <?php foreach($products as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (Stok saat ini: <?= $p['stock'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Supplier (Opsional)</label>
                            <select name="supplier_id" class="form-select">
                                <option value="">-- Tanpa Supplier --</option>
                                <?php foreach($suppliers as $sup): ?>
                                    <option value="<?= $sup['id'] ?>"><?= htmlspecialchars($sup['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jumlah Masuk (Qty)</label>
                            <input type="number" name="qty" class="form-control" min="1" required placeholder="Contoh: 50">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Cth: Restock mingguan..."></textarea>
                        </div>
                        <button type="submit" name="add_stock" class="btn-custom btn-add">Simpan Barang Masuk</button>
                    </form>
                </div>
            </div>

            <!-- TABEL HISTORI BARANG MASUK -->
            <div class="col-md-8">
                <div class="card-custom">
                    <h4 class="fw-bold mb-4">📜 Histori Barang Masuk</h4>
                    <div style="max-height: 500px; overflow-y: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Produk</th>
                                    <th>Supplier</th>
                                    <th>Qty Masuk</th>
                                    <th>Catatan</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($histories)): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada riwayat barang masuk.</td></tr>
                                <?php else: ?>
                                    <?php foreach($histories as $h): ?>
                                        <tr>
                                            <td style="white-space: nowrap;"><?= date('d M Y, H:i', strtotime($h['created_at'])) ?></td>
                                            <td class="fw-bold"><?= htmlspecialchars($h['product_name']) ?></td>
                                            <td><span class="badge" style="background-color: #F3F4F6; color: #4A4A4A;"><?= htmlspecialchars($h['supplier_name'] ?? '-') ?></span></td>
                                            <td class="fw-bold text-success">+<?= $h['qty'] ?></td>
                                            <td class="text-muted"><small><?= htmlspecialchars($h['notes']) ?></small></td>
                                            <td class="text-end">
                                                <!-- TOMBOL HAPUS BARU (MEMICU MODAL POP-UP) -->
                                                <button type="button" class="btn-delete-history" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal" 
                                                    data-id="<?= $h['id'] ?>" 
                                                    data-name="<?= htmlspecialchars($h['product_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-qty="<?= $h['qty'] ?>">
                                                    Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL POP-UP KONFIRMASI HAPUS HISTORI -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <div class="mb-3" style="font-size: 3rem;">🗑️</div>
                    <h4 class="fw-bold mb-3" style="color: #4A4A4A;">Konfirmasi Hapus Riwayat</h4>
                    <p class="mb-4 text-muted" style="font-size: 0.95rem;">
                        Apakah Anda yakin ingin menghapus riwayat masuk untuk <br>
                        <strong id="modalProductName" style="color: #D98A8A; font-size: 1.1rem;"></strong>? <br><br>
                        <span style="font-size: 0.85rem; color: #A52A2A; background: #FCE8E8; padding: 5px 10px; border-radius: 8px;">
                            PERHATIAN: Stok fisik akan DIBATALKAN/DIKURANGI sebanyak <strong id="modalQty"></strong> pcs.
                        </span>
                    </p>
                    <form method="POST" action="">
                        <input type="hidden" name="delete_history_id" id="deleteHistoryId">
                        <div class="d-flex justify-content-center gap-3">
                            <button type="button" class="btn btn-modal-cancel" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-modal-delete">Ya, Hapus & Kurangi Stok</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Script JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Logika untuk mengisi data ke dalam Modal Pop-up
            var deleteModal = document.getElementById('deleteModal');
            if(deleteModal) {
                deleteModal.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget;
                    var historyId = button.getAttribute('data-id');
                    var productName = button.getAttribute('data-name');
                    var qty = button.getAttribute('data-qty');
                    
                    var modalProductName = deleteModal.querySelector('#modalProductName');
                    var modalQty = deleteModal.querySelector('#modalQty');
                    var modalInputId = deleteModal.querySelector('#deleteHistoryId');
                    
                    if(modalProductName) modalProductName.textContent = productName;
                    if(modalQty) modalQty.textContent = qty;
                    if(modalInputId) modalInputId.value = historyId;
                });
            }
        });
    </script>
</body>
</html>