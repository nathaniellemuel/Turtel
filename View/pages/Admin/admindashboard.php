<?php
// Temporary: show all PHP errors in browser to diagnose blank white screen
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../Connection/Connection.php';
require_once __DIR__ . '/../../../Config/Language.php';
require_once __DIR__ . '/../../../Controller/UserController.php';
require_once __DIR__ . '/../../../Controller/KandangController.php';
require_once __DIR__ . '/../../../Controller/StokController.php';

session_start();

// Simple access control: only allow admin to view admin dashboard
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
	header('Location: ' . (defined('BASE_URL') ? BASE_URL . '/View/pages/auth/index.php' : '/Turtel/View/pages/auth/index.php'));
	exit;
}

// Handle task deletion
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_task') {
    $taskId = $_POST['task_id'] ?? 0;
    $stmt = $conn->prepare("DELETE FROM tugas WHERE id_tugas = ?");
    $stmt->bind_param("i", $taskId);
    if ($stmt->execute()) {
        $flash = 'Task deleted successfully';
    } else {
        $flash = 'Failed to delete task';
    }
    $stmt->close();
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

// Fetch all tasks with details
$tugasQuery = "SELECT t.id_tugas, t.created_at, t.status, t.deskripsi_tugas,
               u.username, k.nama_kandang, p.jumlah_digunakan, s.nama_stock AS nama_pakan
               FROM tugas t
               LEFT JOIN user u ON t.id_user = u.id_user
               LEFT JOIN kandang k ON t.id_kandang = k.id_kandang
               LEFT JOIN pakan p ON t.id_pakan = p.id_pakan
               LEFT JOIN stok s ON p.id_stock = s.id_stock
               ORDER BY t.created_at DESC";
$tugasRes = $conn->query($tugasQuery);
$tugasList = [];
if ($tugasRes) {
    while ($r = $tugasRes->fetch_assoc()) {
        $tugasList[] = $r;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('admin_dashboard') ?></title>
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
            background: linear-gradient(135deg, #6B2C2C 0%, #4A1F1F 100%);
            color: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .info-card:active {
            transform: scale(0.95);
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .info-card img {
            width: 50px;
            height: 50px;
            margin-right: 15px;
            transition: all 0.3s ease;
        }
        .info-card:active img {
            transform: scale(1.2) rotate(10deg);
        }
        .info-card .text-content {
            flex-grow: 1;
            transition: all 0.3s ease;
        }
        .info-card:active .text-content {
            transform: translateX(5px);
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
        
        /* Desktop hover effects for info cards */
        @media (hover: hover) and (pointer: fine) {
            .info-card:hover {
                transform: translateY(-5px) scale(1.02);
                box-shadow: 0 8px 16px rgba(0,0,0,0.3);
            }
            .info-card:hover img {
                transform: scale(1.2) rotate(10deg);
            }
            .info-card:hover .text-content {
                transform: translateX(5px);
            }
        }
        
        /* Task Cards */
        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            margin-top: 20px;
        }
        .task-card {
            background: linear-gradient(135deg, #6B2C2C 0%, #4A1F1F 100%);
            color: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 15px;
            position: relative;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .task-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .task-employee {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-right: 15px;
        }
        .task-employee img {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            margin-bottom: 5px;
        }
        .task-info {
            flex-grow: 1;
        }
        .task-info h6 {
            margin: 0 0 8px 0;
            font-size: 1.3rem;
            font-weight: 700;
        }
        .task-info p {
            margin: 0 0 3px 0;
            font-size: 0.85rem;
            opacity: 0.9;
        }
        .task-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.2);
            padding-top: 15px;
        }
        .task-date {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
        }
        .task-date img {
            width: 20px;
            height: 20px;
            margin-right: 8px;
        }
        .task-status {
            padding: 8px 25px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
        }
        .task-status.proses {
            background-color: #F39C12;
        }
        .task-status.selesai {
            background-color: #27AE60;
        }
        .task-status.pending {
            background-color: #E74C3C;
        }
        .btn-delete-task {
            position: absolute;
            top: 15px;
            right: 15px;
            background: transparent;
            border: none;
            width: 35px;
            height: 35px;
            cursor: pointer;
            transition: all 0.2s ease;
            padding: 0;
        }
        .btn-delete-task img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .btn-delete-task:hover {
            transform: scale(1.15);
        }

    </style>
</head>
<body>

    <div class="top-bar">
        <img src="<?= BASE_URL ?>/View/Assets/icons/staff.png" alt="Admin Icon">
        <span>Hi, <?= htmlspecialchars($username) ?></span>
    </div>

    <div class="main-container">
        <?php if (!empty($flash)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert" style="position: relative; padding-right: 40px;">
                <?= htmlspecialchars($flash) ?>
                <button type="button" class="btn-close-custom" onclick="this.parentElement.style.display='none'" style="position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 18px; cursor: pointer; color: #333; font-weight: bold; line-height: 1; padding: 0; width: 20px; height: 20px; opacity: 0.5; transition: opacity 0.2s;">&times;</button>
            </div>
        <?php endif; ?>
        
        <!-- Info Cards -->
        <div class="info-card">
            <img src="<?= BASE_URL ?>/View/Assets/icons/barn.png" alt="Barn">
            <div class="text-content">
                <h6><?= strtoupper(t('total_barns')) ?></h6>
                <p><?= $totalKandang ?></p>
            </div>
        </div>

        <div class="info-card">
            <img src="<?= BASE_URL ?>/View/Assets/icons/chicken.png" alt="Chickens">
            <div class="text-content">
                <h6><?= strtoupper(t('total_chickens')) ?></h6>
                <p><?= $totalAyam ?></p>
            </div>
        </div>

        <div class="info-card">
            <img src="<?= BASE_URL ?>/View/Assets/icons/stock.png" alt="Stock">
            <div class="text-content">
                <h6><?= strtoupper(t('stock_categories')) ?></h6>
                <p><?= $totalKategoriStok ?></p>
            </div>
        </div>
        
        <!-- Task Progress Section -->
        <h5 class="section-title"><?= t('recent_tasks') ?></h5>
        <?php if (empty($tugasList)): ?>
            <p style="color: #999; text-align: center; padding: 20px;"><?= t('no_tasks') ?></p>
        <?php else: ?>
            <?php foreach ($tugasList as $task): 
                $taskDate = date('d/m/Y', strtotime($task['created_at']));
            ?>
            <div class="task-card">
                <form method="POST" style="display: inline;" onsubmit="return confirm('<?= t('are_you_sure') ?>');">
                    <input type="hidden" name="action" value="delete_task">
                    <input type="hidden" name="task_id" value="<?= $task['id_tugas'] ?>">
                    <button type="submit" class="btn-delete-task">
                        <img src="<?= BASE_URL ?>/View/Assets/icons/x.png" alt="Delete">
                    </button>
                </form>
                
                <div class="task-header">
                    <div class="task-employee">
                        <img src="<?= BASE_URL ?>/View/Assets/icons/staff.png" alt="Employee">
                    </div>
                    <div class="task-info">
                        <h6><?= htmlspecialchars($task['username'] ?? 'Unknown') ?></h6>
                        <p><?= t('total') ?>: <?= htmlspecialchars($task['nama_pakan'] ?? 'Unknown Feed') ?> <?= htmlspecialchars($task['jumlah_digunakan'] ?? '0') ?><?= t('kg') ?> | <?= t('for_barn') ?>: <?= htmlspecialchars($task['nama_kandang'] ?? 'A') ?></p>
                    </div>
                </div>
                
                <div class="task-footer">
                    <div class="task-date">
                        <img src="<?= BASE_URL ?>/View/Assets/icons/date.png" alt="Date">
                        <span><?= $taskDate ?></span>
                    </div>
                    <span class="task-status <?= strtolower($task['status']) ?>">
                        <?php 
                        if ($task['status'] === 'selesai') echo t('completed');
                        elseif ($task['status'] === 'proses') echo t('in_progress');
                        else echo t('pending');
                        ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../../Components/bottom-nav-admin.php'; ?>

    <script src="<?= BASE_URL ?>/View/Assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
