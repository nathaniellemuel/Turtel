<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../Connection/Connection.php';
require_once __DIR__ . '/../../../Controller/KandangController.php';
require_once __DIR__ . '/../../../Controller/TelurController.php';

session_start();

// Simple access control: only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL . '/View/pages/auth/index.php' : '/Turtel/View/pages/auth/index.php'));
    exit;
}

$kandangCtrl = new KandangController($conn);
$username = $_SESSION['username'] ?? 'Admin';

// Handle form submissions
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_kandang') {
        $nama = $_POST['nama_kandang'] ?? '';
        $jenis = $_POST['jenis_ayam'] ?? '';
        $jumlah = $_POST['jumlah_ayam'] ?? 0;
        $created = !empty($_POST['created_at']) ? $_POST['created_at'] : date('Y-m-d H:i:s');
        $res = $kandangCtrl->create($nama, $jenis, (int)$jumlah, null, $created);
        $flash = $res['message'] ?? 'Done';
    } elseif ($action === 'delete_kandang') {
        $id = $_POST['id_kandang'] ?? 0;
        $res = $kandangCtrl->delete((int)$id);
        $flash = $res['message'] ?? 'Deleted';
    }
}

// Get all kandang
$kandangs = $kandangCtrl->getAll()['data'] ?? [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barn Management</title>
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
            <h4 class="mb-3">Tambah Kandang</h4>
            <form method="post" class="row g-2">
                <input type="hidden" name="action" value="add_kandang">
                <div class="col-md-4"><input name="nama_kandang" class="form-control" placeholder="Nama Kandang" required></div>
                <div class="col-md-3">
                    <select name="jenis_ayam" class="form-select" required>
                        <option value="negeri">Negeri</option>
                        <option value="kampung">Kampung</option>
                    </select>
                </div>
                <div class="col-md-3"><input name="jumlah_ayam" type="number" class="form-control" placeholder="Jumlah Ayam" required></div>
                <div class="col-md-2"><button class="btn btn-primary w-100">Tambah</button></div>
            </form>
        </div>

        <div class="card-custom">
            <h4 class="mb-3">Daftar Kandang</h4>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nama Kandang</th>
                            <th>Jenis Ayam</th>
                            <th>Jumlah Ayam</th>
                            <th>Laporan Telur</th>
                            <th>Created</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kandangs as $k): ?>
                            <tr>
                                <td><?= $k['id_kandang'] ?></td>
                                <td><?= htmlspecialchars($k['nama_kandang']) ?></td>
                                <td><?= htmlspecialchars($k['jenis_ayam']) ?></td>
                                <td><?= htmlspecialchars($k['jumlah_ayam']) ?></td>
                                <td>
                                    <?php if (!empty($k['jumlah_telur'])): ?>
                                        <small>
                                            <strong><?= $k['jumlah_telur'] ?> butir</strong> 
                                            (<?= $k['berat'] ?> kg)<br>
                                            <em><?= date('d/m/Y H:i', strtotime($k['layed_at'])) ?></em>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($k['created_at']) ?></td>
                                <td>
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="action" value="delete_kandang">
                                        <input type="hidden" name="id_kandang" value="<?= $k['id_kandang'] ?>">
                                        <button class="btn btn-sm btn-danger" onclick="return confirm('Hapus kandang?')">Hapus</button>
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
