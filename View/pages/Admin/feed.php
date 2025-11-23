<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../Connection/Connection.php';
require_once __DIR__ . '/../../../Controller/StokController.php';
require_once __DIR__ . '/../../../Controller/PakanController.php';

session_start();

// Simple access control: only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL . '/View/pages/auth/index.php' : '/Turtel/View/pages/auth/index.php'));
    exit;
}

$stokCtrl = new StokController($conn);
$pakanCtrl = new PakanController($conn);
$username = $_SESSION['username'] ?? 'Admin';

// Handle form submissions
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_stok') {
        $nama = $_POST['nama_stock'] ?? '';
        $kategori = $_POST['kategori'] ?? 'pakan';
        $jumlah = $_POST['jumlah'] ?? 0;
        $res = $stokCtrl->create($nama, $kategori, (int)$jumlah);
        $flash = $res['message'] ?? 'Done';
    } elseif ($action === 'delete_stok') {
        $id = $_POST['id_stock'] ?? 0;
        $res = $stokCtrl->delete((int)$id);
        $flash = $res['message'] ?? 'Deleted';
    } elseif ($action === 'add_pakan') {
        $jumlah = (int)($_POST['jumlah_digunakan'] ?? 0);
        $id_stock = (int)($_POST['id_stock'] ?? 0);
        $created = !empty($_POST['created_at']) ? $_POST['created_at'] : date('Y-m-d H:i:s');
        
        // Get current stock
        $stokData = $stokCtrl->getById($id_stock);
        if ($stokData['success']) {
            $currentJumlah = (int)$stokData['data']['jumlah'];
            
            // Validation
            if ($currentJumlah <= 0) {
                $flash = "Stok habis (0). Tidak bisa menambah pakan.";
            } elseif ($jumlah > $currentJumlah) {
                $flash = "Jumlah yang digunakan ($jumlah) melebihi stok tersedia ($currentJumlah)";
            } else {
                // Create pakan
                $res = $pakanCtrl->create($jumlah, $created, $id_stock);
                if ($res['success']) {
                    // Reduce stock
                    $newJumlah = $currentJumlah - $jumlah;
                    $stokCtrl->update($id_stock, $stokData['data']['nama_stock'], $stokData['data']['kategori'], $newJumlah);
                    $flash = "Pakan berhasil ditambahkan. Stok dikurangi otomatis.";
                } else {
                    $flash = $res['message'];
                }
            }
        }
    } elseif ($action === 'delete_pakan') {
        $id = $_POST['id_pakan'] ?? 0;
        $res = $pakanCtrl->delete((int)$id);
        $flash = $res['message'] ?? 'Deleted';
    }
}

// Get all data
$stoks = $stokCtrl->getAll()['data'] ?? [];
$pakans = $pakanCtrl->getAll()['data'] ?? [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed & Stock Management</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/View/Assets/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Montserrat', sans-serif;
            padding-bottom: 90px;
            margin: 0;
        }
        .top-bar {
            background-color: #F39C12;
            color: white;
            padding: 15px 20px;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .main-container {
            padding: 20px;
        }
        .card-custom {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

    <div class="top-bar">
        Hi, <?= htmlspecialchars($username) ?>
    </div>

    <div class="main-container">
        <?php if (!empty($flash)): ?>
            <div class="alert alert-info"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <div class="card-custom">
            <h4 class="mb-3">Tambah Stok</h4>
            <form method="post" class="row g-2">
                <input type="hidden" name="action" value="add_stok">
                <div class="col-md-4"><input name="nama_stock" class="form-control" placeholder="Nama Stock" required></div>
                <div class="col-md-3">
                    <select name="kategori" class="form-select">
                        <option value="pakan">Pakan</option>
                        <option value="vitamin">Vitamin</option>
                        <option value="obat">Obat</option>
                    </select>
                </div>
                <div class="col-md-3"><input name="jumlah" type="number" class="form-control" placeholder="Jumlah" required></div>
                <div class="col-md-2"><button class="btn btn-primary w-100">Tambah</button></div>
            </form>
        </div>

        <div class="card-custom">
            <h4 class="mb-3">Daftar Stok</h4>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Jumlah</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stoks as $s): ?>
                            <tr>
                                <td><?= $s['id_stock'] ?></td>
                                <td><?= htmlspecialchars($s['nama_stock']) ?></td>
                                <td><span class="badge bg-info"><?= htmlspecialchars($s['kategori']) ?></span></td>
                                <td><strong><?= htmlspecialchars($s['jumlah']) ?></strong></td>
                                <td>
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="action" value="delete_stok">
                                        <input type="hidden" name="id_stock" value="<?= $s['id_stock'] ?>">
                                        <button class="btn btn-sm btn-danger" onclick="return confirm('Hapus stok?')">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-custom">
            <h4 class="mb-3">Tambah Pakan (Penggunaan Stok)</h4>
            <form method="post" class="row g-2">
                <input type="hidden" name="action" value="add_pakan">
                <div class="col-md-5">
                    <select name="id_stock" class="form-select" required>
                        <option value="">Pilih Stok</option>
                        <?php foreach ($stoks as $s): ?>
                            <option value="<?= $s['id_stock'] ?>"><?= htmlspecialchars($s['nama_stock']) ?> (Stok: <?= $s['jumlah'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5"><input name="jumlah_digunakan" type="number" class="form-control" placeholder="Jumlah Digunakan" required></div>
                <div class="col-md-2"><button class="btn btn-success w-100">Gunakan</button></div>
            </form>
        </div>

        <div class="card-custom">
            <h4 class="mb-3">Riwayat Penggunaan Pakan</h4>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Jumlah</th>
                            <th>Tanggal</th>
                            <th>ID Stok</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pakans as $p): ?>
                            <tr>
                                <td><?= $p['id_pakan'] ?></td>
                                <td><strong><?= htmlspecialchars($p['jumlah_digunakan']) ?></strong></td>
                                <td><?= htmlspecialchars($p['created_at']) ?></td>
                                <td><?= htmlspecialchars($p['id_stock']) ?></td>
                                <td>
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="action" value="delete_pakan">
                                        <input type="hidden" name="id_pakan" value="<?= $p['id_pakan'] ?>">
                                        <button class="btn btn-sm btn-danger" onclick="return confirm('Hapus pakan?')">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../Components/bottom-nav-admin.php'; ?>

    <script src="<?= BASE_URL ?>/View/Assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
