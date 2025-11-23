<?php
// Temporary: show all PHP errors in browser to diagnose blank white screen
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../Connection/Connection.php';
require_once __DIR__ . '/../../../Controller/UserController.php';
require_once __DIR__ . '/../../../Controller/KandangController.php';
require_once __DIR__ . '/../../../Controller/StokController.php';

session_start();

// Simple access control: only allow admin to view admin dashboard
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
	header('Location: ' . (defined('BASE_URL') ? BASE_URL . '/View/pages/auth/index.php' : '/Turtel/View/pages/auth/index.php'));
	exit;
}

// Instantiate controllers
$userCtrl = new UserController($conn);
$kandangCtrl = new KandangController($conn);
$stokCtrl = new StokController($conn);

// Fetch data for dashboard cards
$kandangs = $kandangCtrl->getAll()['data'] ?? [];
$stoks = $stokCtrl->getAll()['data'] ?? [];

$totalKandang = count($kandangs);
$totalAyam = 0;
foreach ($kandangs as $k) {
    $totalAyam += (int)$k['jumlah_ayam'];
}

$kategoriStok = [];
foreach ($stoks as $s) {
    if (!in_array($s['kategori'], $kategoriStok)) {
        $kategoriStok[] = $s['kategori'];
    }
}
$totalKategoriStok = count($kategoriStok);

$username = $_SESSION['username'] ?? 'Admin';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/View/Assets/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Montserrat', sans-serif;
            padding-bottom: 90px; /* Space for bottom nav */
            margin: 0; /* Fix for white line on the right */
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
        .summary-card {
            background-color: #4A2511;
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .summary-card h5 {
            font-weight: bold;
            margin-bottom: 20px;
        }
        .chart-container {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 150px;
            border-bottom: 1px solid #888;
            padding-bottom: 10px;
        }
        .chart-bar {
            background-color: #D3D3D3;
            width: 15%;
            position: relative;
        }
        .chart-bar span {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.8rem;
        }
        .chart-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.7rem;
            color: #ccc;
            margin-top: 5px;
        }
        .chart-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }
        .chart-footer .date-pill {
            background-color: #F39C12;
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 0.8rem;
        }
        .info-card {
            background-color: #4A2511;
            color: white;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .info-card img {
            width: 40px;
            margin-right: 15px;
        }
        .info-card .text-content {
            flex-grow: 1;
        }
        .info-card .text-content h6 {
            margin: 0;
            font-size: 0.9rem;
            font-weight: normal;
        }
        .info-card .text-content p {
            margin: 0;
            font-size: 1.5rem;
            font-weight: bold;
        }

    </style>
</head>
<body>

    <div class="top-bar">
        Hi, <?= htmlspecialchars($username) ?>
    </div>

    <div class="main-container">
        <!-- Info Cards -->
        <div class="info-card">
            <img src="<?= BASE_URL ?>/View/Assets/icons/barn.png" alt="Barn">
            <div class="text-content">
                <h6>BARN / COOP</h6>
                <p><?= $totalKandang ?></p>
            </div>
        </div>

        <div class="info-card">
            <img src="<?= BASE_URL ?>/View/Assets/icons/chicken.png" alt="Chickens">
            <div class="text-content">
                <h6>CHICKENS</h6>
                <p><?= $totalAyam ?></p>
            </div>
        </div>

        <div class="info-card">
            <img src="<?= BASE_URL ?>/View/Assets/icons/stock.png" alt="Stock">
            <div class="text-content">
                <h6>STOCK CATEGORY</h6>
                <p><?= $totalKategoriStok ?></p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../Components/bottom-nav-admin.php'; ?>

    <script src="<?= BASE_URL ?>/View/Assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
