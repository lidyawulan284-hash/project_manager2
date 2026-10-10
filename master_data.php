<?php
// Mencegah error blank putih
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

// Cek keamanan: Hanya Admin yang bisa akses Master Data
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("<h2 style='text-align:center; margin-top:50px; font-family:sans-serif;'>Akses Ditolak! Hanya Admin yang boleh mengakses halaman ini.</h2>");
}

// --- SCRIPT OTOMATIS: MEMBUAT TABEL SUPPLIER JIKA BELUM ADA ---
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        contact VARCHAR(50),
        address TEXT
    )");
} catch (PDOException $e) {
    // Abaikan jika tabel sudah ada
}
// --------------------------------------------------------------

$error = '';
$success = '';

// ================= PROSES KATEGORI =================
if (isset($_POST['add_category'])) {
    $cat_name = trim($_POST['cat_name']);
    if (!empty($cat_name)) {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$cat_name]);
        $success = "Kategori '$cat_name' berhasil ditambahkan.";
    }
}
if (isset($_POST['delete_category'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$_POST['cat_id']]);
        $success = "Kategori berhasil dihapus.";
    } catch (PDOException $e) {
        $error = "Kategori gagal dihapus karena masih digunakan oleh produk di menu!";
    }
}

// ================= PROSES SUPPLIER =================
if (isset($_POST['add_supplier'])) {
    $sup_name = trim($_POST['sup_name']);
    $sup_contact = trim($_POST['sup_contact']);
    $sup_address = trim($_POST['sup_address']);
    
    if (!empty($sup_name)) {
        $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact, address) VALUES (?, ?, ?)");
        $stmt->execute([$sup_name, $sup_contact, $sup_address]);
        $success = "Supplier '$sup_name' berhasil ditambahkan.";
    }
}
if (isset($_POST['delete_supplier'])) {
    $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
    $stmt->execute([$_POST['sup_id']]);
    $success = "Supplier berhasil dihapus.";
}

// Ambil Data Terkini
$categories = $pdo->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Data - Ruang Rindu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-body: #FAF9F6;       
            --bg-card: #FFFFFF;       
            --pastel-pink: #FADADD;   
            --pastel-mint: #B5EAD7;   
            --pastel-peach: #FFDAC1;  
            --pastel-blue: #C7CEEA;
            --text-dark: #4A4A4A;     
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-body); color: var(--text-dark); padding-bottom: 50px; }
        .pos-header { background-color: var(--pastel-pink); padding: 25px 20px 35px 20px; color: var(--text-dark); border-bottom-left-radius: 25px; border-bottom-right-radius: 25px; box-shadow: 0 4px 15px rgba(250, 218, 221, 0.5); margin-bottom: 40px; }
        .cafe-brand { font-size: 1.6rem; font-weight: 800; color: var(--text-dark); letter-spacing: 1px; margin: 0; }
        .cafe-brand span { color: #D98A8A; }
        
        .pos-tabs { display: flex; gap: 25px; font-size: 1.05rem; font-weight: 600; margin-top: 20px; flex-wrap: wrap; }
        .pos-tab-link { color: #A88B8E; text-decoration: none; padding-bottom: 5px; position: relative; transition: 0.2s; }
        .pos-tab-link.active { color: var(--text-dark); }
        .pos-tab-link.active::after { content: ''; position: absolute; bottom: -4px; left: 0; width: 100%; height: 3px; background-color: var(--text-dark); border-radius: 3px; }
        
        .card-master { background-color: var(--bg-card); border-radius: 24px; padding: 25px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02); border: 1px solid #FFF0F2; height: 100%; }
        .form-control { border-radius: 12px; border: 2px solid #FFF0F2; padding: 10px 15px; }
        .form-control:focus { border-color: var(--pastel-pink); box-shadow: none; }
        .btn-custom { border-radius: 12px; font-weight: 600; padding: 10px 20px; border: none; transition: 0.2s; }
        .btn-add { background-color: var(--pastel-mint); color: var(--text-dark); }
        .btn-add:hover { background-color: #A0DBC6; transform: scale(1.02); }
        
        .btn-delete { background-color: #FDE4E4; color: #D98A8A; padding: 5px 12px; border-radius: 8px; font-size: 0.85rem; border: none; font-weight: 600; transition: 0.2s;}
        .btn-delete:hover { background-color: #D98A8A; color: white; transform: scale(1.05);}
        
        .table thead th { background-color: #FAF9F6 !important; border-bottom: none; color: #9CA3AF; font-size: 0.85rem; text-transform: uppercase; }
        .table tbody td { vertical-align: middle; border-bottom: 1px solid #F3F4F6; }
        .table tbody tr:last-child td { border-bottom: none; }

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
            <a href="dashboard.php" class="btn btn-sm" style="background:#FFFFFF; color:#4A4A4A; border-radius:10px; font-weight:600; padding: 6px 14px; text-decoration: none;">Dashboard</a>
        </div>

        <div class="pos-tabs">
            <a href="index.php" class="pos-tab-link">Kasir</a>
            <a href="history.php" class="pos-tab-link">Order</a>
            <a href="manage.php" class="pos-tab-link">Kelola</a>
            <a href="master_data.php" class="pos-tab-link active">Master Data</a>
            <a href="stock_in.php" class="pos-tab-link">Barang Masuk</a>
        </div>
    </div>

    <div class="container-fluid px-4">
        
        <?php if($success): ?>
            <div class="alert alert-success" style="border-radius: 16px; background-color: #E6F4EA; color: #1E4620; border: none;">
                ✅ <?= $success ?>
            </div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger" style="border-radius: 16px; background-color: #FCE8E8; color: #A52A2A; border: none;">
                ⚠️ <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            
            <!-- KOLOM KATEGORI -->
            <div class="col-md-5">
                <div class="card-master">
                    <h4 class="fw-bold mb-4" style="color: var(--text-dark);">🏷️ Kategori Menu</h4>
                    
                    <form method="POST" class="mb-4 d-flex gap-2">
                        <input type="text" name="cat_name" class="form-control" placeholder="Nama Kategori Baru..." required>
                        <button type="submit" name="add_category" class="btn-custom btn-add">Tambah</button>
                    </form>

                    <div style="max-height: 400px; overflow-y: auto;">
                        <table class="table">
                            <thead><tr><th>Nama Kategori</th><th class="text-end">Aksi</th></tr></thead>
                            <tbody>
                                <?php foreach($categories as $cat): ?>
                                    <tr>
                                        <td class="fw-semibold text-capitalize"><?= htmlspecialchars($cat['name']) ?></td>
                                        <td class="text-end">
                                            <!-- Tombol pemicu Modal Kategori -->
                                            <button type="button" class="btn-delete" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteCatModal" 
                                                data-id="<?= $cat['id'] ?>" 
                                                data-name="<?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- KOLOM SUPPLIER -->
            <div class="col-md-7">
                <div class="card-master">
                    <h4 class="fw-bold mb-4" style="color: var(--text-dark);">🚚 Data Supplier</h4>
                    
                    <form method="POST" class="mb-4 row g-2">
                        <div class="col-md-4">
                            <input type="text" name="sup_name" class="form-control" placeholder="Nama Supplier" required>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="sup_contact" class="form-control" placeholder="No. Telp">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="sup_address" class="form-control" placeholder="Kota/Alamat">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" name="add_supplier" class="btn-custom btn-add w-100">Simpan</button>
                        </div>
                    </form>

                    <div style="max-height: 400px; overflow-y: auto;">
                        <table class="table">
                            <thead><tr><th>Nama</th><th>Kontak</th><th>Alamat</th><th class="text-end">Aksi</th></tr></thead>
                            <tbody>
                                <?php foreach($suppliers as $sup): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= htmlspecialchars($sup['name']) ?></td>
                                        <td><?= htmlspecialchars($sup['contact']) ?></td>
                                        <td><small><?= htmlspecialchars($sup['address']) ?></small></td>
                                        <td class="text-end">
                                            <!-- Tombol pemicu Modal Supplier -->
                                            <button type="button" class="btn-delete" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteSupModal" 
                                                data-id="<?= $sup['id'] ?>" 
                                                data-name="<?= htmlspecialchars($sup['name'], ENT_QUOTES, 'UTF-8') ?>">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL POP-UP KONFIRMASI HAPUS KATEGORI -->
    <div class="modal fade" id="deleteCatModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <div class="mb-3" style="font-size: 3rem;">🏷️</div>
                    <h4 class="fw-bold mb-3" style="color: #4A4A4A;">Hapus Kategori</h4>
                    <p class="mb-4 text-muted" style="font-size: 0.95rem;">
                        Apakah Anda yakin ingin menghapus kategori <br>
                        <strong id="modalCatName" style="color: #D98A8A; font-size: 1.1rem;"></strong>?
                    </p>
                    <form method="POST" action="">
                        <input type="hidden" name="cat_id" id="deleteCatId">
                        <div class="d-flex justify-content-center gap-3">
                            <button type="button" class="btn btn-modal-cancel" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="delete_category" class="btn btn-modal-delete">Ya, Hapus</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL POP-UP KONFIRMASI HAPUS SUPPLIER -->
    <div class="modal fade" id="deleteSupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <div class="mb-3" style="font-size: 3rem;">🚚</div>
                    <h4 class="fw-bold mb-3" style="color: #4A4A4A;">Hapus Supplier</h4>
                    <p class="mb-4 text-muted" style="font-size: 0.95rem;">
                        Apakah Anda yakin ingin menghapus data supplier <br>
                        <strong id="modalSupName" style="color: #D98A8A; font-size: 1.1rem;"></strong>?
                    </p>
                    <form method="POST" action="">
                        <input type="hidden" name="sup_id" id="deleteSupId">
                        <div class="d-flex justify-content-center gap-3">
                            <button type="button" class="btn btn-modal-cancel" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="delete_supplier" class="btn btn-modal-delete">Ya, Hapus</button>
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
            // Script Modal Kategori
            var deleteCatModal = document.getElementById('deleteCatModal');
            if(deleteCatModal) {
                deleteCatModal.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget;
                    var id = button.getAttribute('data-id');
                    var name = button.getAttribute('data-name');
                    
                    deleteCatModal.querySelector('#modalCatName').textContent = name;
                    deleteCatModal.querySelector('#deleteCatId').value = id;
                });
            }

            // Script Modal Supplier
            var deleteSupModal = document.getElementById('deleteSupModal');
            if(deleteSupModal) {
                deleteSupModal.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget;
                    var id = button.getAttribute('data-id');
                    var name = button.getAttribute('data-name');
                    
                    deleteSupModal.querySelector('#modalSupName').textContent = name;
                    deleteSupModal.querySelector('#deleteSupId').value = id;
                });
            }
        });
    </script>
</body>
</html>