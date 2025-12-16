<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../Connection/Connection.php';
require_once __DIR__ . '/../../../Config/Language.php';
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
    <title><?= t('feed_stock') ?></title>
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
            display: flex;
            align-items: center;
        }
        .top-bar img {
            width: 40px;
            height: 40px;
            margin-right: 15px;
        }
        .main-container {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .selection-card {
            background: linear-gradient(135deg, #6B2C2C 0%, #4A1F1F 100%);
            border-radius: 20px;
            padding: 25px;
            color: white;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            text-decoration: none;
            display: flex;
            flex-direction: column;
            position: relative;
            height: 200px;
            overflow: hidden;
        }
        .selection-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s ease;
        }
        .selection-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 12px 24px rgba(0,0,0,0.4);
        }
        .selection-card:hover::before {
            left: 100%;
        }
        .selection-card:active {
            transform: translateY(-4px) scale(1.01);
            box-shadow: 0 8px 16px rgba(0,0,0,0.35);
            transition: all 0.1s ease;
        }
        .selection-card h3 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: white;
            transition: all 0.3s ease;
        }
        .selection-card:hover h3,
        .selection-card:active h3 {
            transform: translateX(5px);
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .selection-card p {
            font-size: 0.8rem;
            opacity: 0.95;
            margin-bottom: 0;
            line-height: 1.4;
            max-width: 60%;
            transition: all 0.3s ease;
        }
        .selection-card:hover p,
        .selection-card:active p {
            opacity: 1;
            transform: translateX(5px);
        }
        .selection-card img {
            position: absolute;
            bottom: 15px;
            right: 15px;
            width: 85px;
            height: 85px;
            object-fit: contain;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .selection-card:hover img,
        .selection-card:active img {
            transform: scale(1.15) rotate(5deg);
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.4));
        }
    </style>
</head>
<body>

    <div class="top-bar">
        <img src="<?= BASE_URL ?>/View/Assets/icons/feed-stock.png" alt="Feed & Stock">
        <span><?= strtoupper(t('feed_stock')) ?></span>
    </div>

    <div class="main-container">
        <a href="<?= BASE_URL ?>/View/pages/Admin/feed.php" class="selection-card">
            <h3><?= strtoupper(t('cat_feed')) ?></h3>
            <p><?= t('note_feed_stock') ?></p>
            <img src="<?= BASE_URL ?>/View/Assets/icons/feed.png" alt="Feed">
        </a>

        <a href="<?= BASE_URL ?>/View/pages/Admin/stock.php" class="selection-card">
            <h3><?= strtoupper(t('stock')) ?></h3>
            <p><?= t('note_stock_entry') ?></p>
            <img src="<?= BASE_URL ?>/View/Assets/icons/stock.png" alt="Stock">
        </a>
    </div>

    <?php include __DIR__ . '/../../Components/bottom-nav-admin.php'; ?>

    <script src="<?= BASE_URL ?>/View/Assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
