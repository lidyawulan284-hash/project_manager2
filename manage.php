<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

// Cek keamanan
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// PROSES MENGHAPUS PRODUK
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
    }
    header("Location: manage.php"); 
    exit;
}

// Ambil data produk
$stmt = $pdo->query("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id DESC
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Stok & Edit - Ruang Rindu</title>
    <!-- Tambahan Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-body: #FAF9F6;       
            --bg-card: #FFFFFF;       
            --pastel-pink: #FADADD;   
            --pastel-mint: #B5EAD7;   
            --pastel-peach: #FFDAC1;  
            --text-dark: #4A4A4A;     
            --text-muted: #9CA3AF;
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-body); color: var(--text-dark); padding-bottom: 50px; }
        
        /* HEADER NAVIGASI BARU (Konsisten dengan index.php) */
        .pos-header { background-color: var(--pastel-pink); padding: 25px 20px 35px 20px; color: var(--text-dark); border-bottom-left-radius: 25px; border-bottom-right-radius: 25px; box-shadow: 0 4px 15px rgba(250, 218, 221, 0.5); margin-bottom: 40px;}
        .cafe-brand { font-size: 1.6rem; font-weight: 800; color: var(--text-dark); letter-spacing: 1px; margin: 0; }
        .cafe-brand span { color: #D98A8A; }
        .pos-tabs { display: flex; gap: 25px; font-size: 1.05rem; font-weight: 600; flex-wrap: wrap; margin-top: 20px;}
        .pos-tab-link { color: #A88B8E; text-decoration: none; padding-bottom: 5px; position: relative; transition: 0.2s; }
        .pos-tab-link.active { color: var(--text-dark); }
        .pos-tab-link.active::after { content: ''; position: absolute; bottom: -4px; left: 0; width: 100%; height: 3px; background-color: var(--text-dark); border-radius: 3px; }

        /* Tabel */
        .table-wrapper { background-color: var(--bg-card); border-radius: 24px; padding: 25px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02); border: 1px solid #FFF0F2; overflow-x: auto; }
        .table { margin-bottom: 0; color: var(--text-dark); }
        .table thead th { background-color: var(--pastel-pink) !important; color: var(--text-dark) !important; font-weight: 600; border-bottom: none; padding: 15px; font-size: 0.95rem; text-align: center; }
        .table thead tr th:first-child { border-top-left-radius: 16px; border-bottom-left-radius: 16px; text-align: left; }
        .table thead tr th:last-child { border-top-right-radius: 16px; border-bottom-right-radius: 16px; }
        .table tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #F3F4F6; font-size: 0.95rem; font-weight: 500; text-align: center; }
        .table tbody tr td:first-child { text-align: left; }
        .table tbody tr:last-child td { border-bottom: none; }
        
        /* Tombol Aksi */
        .btn-action { padding: 8px 16px; border-radius: 12px; font-weight: 600; font-size: 0.85rem; text-decoration: none; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; border: none; height: 100%; cursor: pointer; }
        .btn-edit { background-color: var(--pastel-peach); color: var(--text-dark); border: 2px solid var(--pastel-peach); }
        .btn-edit:hover { background-color: #F5CBAE; border-color: #F5CBAE; color: var(--text-dark); transform: scale(1.03); }
        
        .btn-hapus-manage { background-color: transparent; color: #D98A8A; border: 2px solid #FDE4E4; }
        .btn-hapus-manage:hover { background-color: #FDE4E4; color: var(--text-dark); transform: scale(1.03); }
        
        .cat-badge { background-color: #F3F4F6; padding: 6px 14px; border-radius: 20px; font-size: 0.9rem; color: var(--text-dark); font-weight: 600; }

        /* Modal Pop-up */
        .modal-content { border-radius: 24px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .btn-modal-cancel { background-color: #F3F4F6; color: #4A4A4A; border-radius: 12px; font-weight: 600; border: none; padding: 10px 24px; }
        .btn-modal-cancel:hover { background-color: #E5E7EB; }
        .btn-modal-delete { background-color: #D98A8A; color: white; border-radius: 12px; font-weight: 600; border: none; padding: 10px 24px; }
        .btn-modal-delete:hover { background-color: #C07979; color: white; }
    </style>
</head>
<body>

    <!-- BAGIAN HEADER NAVIGASI BARU -->
    <div class="pos-header">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h1 class="cafe-brand">☕ RUANG <span>RINDU</span></h1>
            <div class="d-flex gap-2 align-items-center">
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="create.php" class="btn btn-sm" style="background:#B5EAD7; color:#4A4A4A; border-radius:10px; font-weight:600; padding: 6px 14px; text-decoration: none;">+ Tambah Menu</a>
                <?php endif; ?>
                <a href="dashboard.php" class="btn btn-sm" style="background:#FFFFFF; color:#4A4A4A; border-radius:10px; font-weight:600; padding: 6px 14px;">Dashboard</a>
            </div>
        </div>

        <div class="pos-tabs">
            <a href="index.php" class="pos-tab-link">Kasir</a>
            <a href="history.php" class="pos-tab-link">Order</a>
            <a href="manage.php" class="pos-tab-link active">Kelola</a>
            <a href="master_data.php" class="pos-tab-link">Master Data</a>
            <a href="stock_in.php" class="pos-tab-link">Barang Masuk</a>
        </div>
    </div>

    <div class="container">
        <h2 class="mb-4 fw-bold" style="color: var(--text-dark);">Kelola Stok & Edit Produk</h2>
        <div class="table-wrapper">
            <?php if(empty($products)): ?>
                <div class="alert text-center m-0" style="background-color: #FAF9F6; color: #9CA3AF; border-radius: 16px;">
                    Belum ada data produk.
                </div>
            <?php else: ?>
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Sisa Stok</th>
                            <th>Aksi (Edit / Hapus)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="fw-bold text-capitalize">
                                    <?= htmlspecialchars($product["name"] ?? '', ENT_QUOTES, "UTF-8") ?>
                                </td>
                                <td>
                                    <span class="cat-badge text-capitalize">
                                        <?= htmlspecialchars($product["category_name"] ?? 'Tanpa Kategori', ENT_QUOTES, "UTF-8") ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="<?= ($product["stock"] ?? 0) <= 20 ? 'color: #D98A8A; font-weight: 700;' : 'font-weight: 700;' ?>">
                                        <?= htmlspecialchars($product["stock"] ?? 0, ENT_QUOTES, "UTF-8") ?> pcs
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                            <a href="edit.php?id=<?= $product['id'] ?>" class="btn-action btn-edit">Edit Data</a>
                                            <button type="button" class="btn-action btn-hapus-manage" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteModal" 
                                                data-id="<?= $product['id'] ?>" 
                                                data-name="<?= htmlspecialchars($product['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                                Hapus
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL POP-UP KONFIRMASI HAPUS -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <div class="mb-3" style="font-size: 3rem;">🗑️</div>
                    <h4 class="fw-bold mb-3" style="color: #4A4A4A;">Konfirmasi Hapus</h4>
                    <p class="mb-4 text-muted" style="font-size: 0.95rem;">
                        Apakah Anda yakin ingin menghapus menu <br>
                        <strong id="productNameToDelete" style="color: #D98A8A; font-size: 1.1rem;"></strong>? <br>
                        <span style="font-size: 0.85rem;">Tindakan ini tidak dapat dibatalkan.</span>
                    </p>
                    <form method="POST" action="">
                        <input type="hidden" name="delete_id" id="deleteProductId">
                        <div class="d-flex justify-content-center gap-3">
                            <button type="button" class="btn btn-modal-cancel" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-modal-delete">Ya, Hapus</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Script Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var deleteModal = document.getElementById('deleteModal');
            if(deleteModal) {
                deleteModal.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget;
                    var productId = button.getAttribute('data-id');
                    var productName = button.getAttribute('data-name');
                    
                    var modalProductName = deleteModal.querySelector('#productNameToDelete');
                    var modalInputId = deleteModal.querySelector('#deleteProductId');
                    
                    if(modalProductName) modalProductName.textContent = productName;
                    if(modalInputId) modalInputId.value = productId;
                });
            }
        });
    </script>
</body>
</html>