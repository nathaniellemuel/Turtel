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
require_once __DIR__ . '/../../../Controller/TelurController.php';

session_start();

// Simple access control: only allow admin to view admin dashboard
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
	header('Location: ' . (defined('BASE_URL') ? BASE_URL . '/View/pages/auth/index.php' : '/Turtel/View/pages/auth/index.php'));
	exit;
}

// Handle task deletion
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'delete_task') {
        $taskId = $_POST['task_id'] ?? 0;
        $stmt = $conn->prepare("DELETE FROM tugas WHERE id_tugas = ?");
        $stmt->bind_param("i", $taskId);
        if ($stmt->execute()) {
            $_SESSION['flash'] = 'Task deleted successfully';
        } else {
            $_SESSION['flash'] = 'Failed to delete task';
        }
        $stmt->close();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } elseif ($action === 'sell_egg') {
        $id_telur = (int)($_POST['id_telur'] ?? 0);
        $jumlah_terjual = (int)($_POST['jumlah_terjual'] ?? 0);
        $harga_jual = (float)($_POST['harga_jual'] ?? 0);
        $tanggal_jual = date('Y-m-d H:i:s');
        
        // Get current data
        $checkStmt = $conn->prepare("SELECT jumlah_telur, jumlah_terjual FROM telur WHERE id_telur = ?");
        $checkStmt->bind_param('i', $id_telur);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $total_eggs = $row['jumlah_telur'];
            $already_sold = $row['jumlah_terjual'] ?? 0;
            $available = $total_eggs - $already_sold;
            
            if ($jumlah_terjual > $available) {
                $_SESSION['flash'] = 'Cannot sell more eggs than available! Available: ' . $available . ' eggs';
            } else {
                $new_jumlah_terjual = $already_sold + $jumlah_terjual;
                
                $stmt = $conn->prepare("UPDATE telur SET jumlah_terjual = ?, harga_jual = ?, tanggal_jual = ? WHERE id_telur = ?");
                $stmt->bind_param('idsi', $new_jumlah_terjual, $harga_jual, $tanggal_jual, $id_telur);
                
                if ($stmt->execute()) {
                    $_SESSION['flash'] = 'Eggs sold successfully!';
                } else {
                    $_SESSION['flash'] = 'Failed to sell eggs: ' . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $_SESSION['flash'] = 'Egg data not found!';
        }
        $checkStmt->close();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get flash message from session
$flash = $_SESSION['flash'] ?? '';
if (!empty($flash)) {
    unset($_SESSION['flash']);
}

// Instantiate controllers
$userCtrl = new UserController($conn);
$kandangCtrl = new KandangController($conn);
$stokCtrl = new StokController($conn);
$telurCtrl = new TelurController($conn);

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

// Fetch kandang with egg data for selling
$kandangWithEggsQuery = "SELECT k.nama_kandang, t.id_telur, t.jumlah_telur, t.jumlah_terjual, t.jumlah_buruk 
    FROM kandang k 
    LEFT JOIN telur t ON k.id_kandang = t.id_kandang 
    WHERE t.id_telur IS NOT NULL 
    ORDER BY k.nama_kandang ASC";
$kandangWithEggsResult = $conn->query($kandangWithEggsQuery);
$kandangWithEggs = [];
if ($kandangWithEggsResult) {
    while ($r = $kandangWithEggsResult->fetch_assoc()) {
        $kandangWithEggs[] = $r;
    }
}

// Fetch sales history
$salesHistoryQuery = "SELECT k.nama_kandang, t.jumlah_terjual, t.harga_jual, t.tanggal_jual,
    (t.jumlah_terjual * t.harga_jual) as total_sales
    FROM telur t
    JOIN kandang k ON t.id_kandang = k.id_kandang
    WHERE t.tanggal_jual IS NOT NULL 
    AND t.harga_jual IS NOT NULL
    ORDER BY t.tanggal_jual DESC
    LIMIT 10";
$salesHistoryResult = $conn->query($salesHistoryQuery);
$salesHistory = [];
if ($salesHistoryResult) {
    while ($r = $salesHistoryResult->fetch_assoc()) {
        $salesHistory[] = $r;
    }
}

// Fetch financial data from sold eggs
$financialQuery = "SELECT 
    DATE(tanggal_jual) as tanggal,
    SUM(jumlah_terjual * harga_jual) as total_income,
    SUM(jumlah_terjual) as total_quantity
    FROM telur 
    WHERE tanggal_jual IS NOT NULL 
    AND harga_jual IS NOT NULL
    AND tanggal_jual >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(tanggal_jual)
    ORDER BY tanggal ASC";
$financialRes = $conn->query($financialQuery);
$financialData = [];
if ($financialRes) {
    while ($r = $financialRes->fetch_assoc()) {
        $financialData[] = $r;
    }
}

// Calculate total income and max for chart scaling
$totalIncome = 0;
$maxIncome = 0;
$startDate = '';
$endDate = '';
if (!empty($financialData)) {
    $startDate = date('d M', strtotime($financialData[0]['tanggal']));
    $endDate = date('d M Y', strtotime($financialData[count($financialData) - 1]['tanggal']));
    foreach ($financialData as $data) {
        $totalIncome += $data['total_income'];
        if ($data['total_income'] > $maxIncome) {
            $maxIncome = $data['total_income'];
        }
    }
}

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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: #f0f2f5;
            font-family: 'Montserrat', sans-serif;
            padding-bottom: 90px;
            margin: 0;
        }
        
        /* Desktop Sidebar */
        .desktop-sidebar {
            display: none;
            position: fixed;
            left: 0;
            top: 0;
            width: 200px;
            height: 100vh;
            background: linear-gradient(180deg, #6B2C2C 0%, #4A1F1F 100%);
            padding: 20px 0;
            z-index: 1000;
        }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            padding: 0 20px;
            margin-bottom: 40px;
        }
        
        .sidebar-logo img {
            width: 40px;
            height: 40px;
            margin-right: 10px;
        }
        
        .sidebar-logo span {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: rgba(243, 156, 18, 0.1);
            color: white;
            border-left-color: #F39C12;
        }
        
        .sidebar-menu a img {
            width: 24px;
            height: 24px;
            margin-right: 15px;
            filter: brightness(0) invert(1);
            opacity: 0.7;
        }
        
        .sidebar-menu a:hover img,
        .sidebar-menu a.active img {
            opacity: 1;
        }
        
        .sidebar-user {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            padding: 15px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-user-info {
            display: flex;
            align-items: center;
        }
        
        .sidebar-user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .sidebar-user-info .user-details {
            color: white;
        }
        
        .sidebar-user-info .user-name {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .sidebar-user-info .user-role {
            font-size: 0.75rem;
            opacity: 0.7;
        }
        
        .top-bar {
            background-color: #F39C12;
            color: white;
            padding: 15px 20px;
            font-size: 1.2rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .top-bar-left {
            display: flex;
            align-items: center;
        }
        
        .top-bar img {
            width: 40px;
            height: 40px;
            margin-right: 15px;
        }
        
        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .time-badge {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .main-container {
            padding: 20px;
        }
        
        /* Desktop Layout */
        @media (min-width: 768px) {
            .desktop-sidebar {
                display: block;
            }
            
            body {
                padding-bottom: 0;
            }
            
            .top-bar {
                margin-left: 200px;
            }
            
            .main-container {
                margin-left: 200px;
                padding: 30px;
            }
            
            .desktop-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
                margin-bottom: 30px;
            }
            
            .desktop-content {
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 30px;
            }
            
            .bottom-nav-admin {
                display: none !important;
            }
        }
        
        /* Mobile only - show bottom nav */
        @media (max-width: 767px) {
            .bottom-nav-admin {
                display: flex !important;
            }
        }
        
        .info-card {
            background: linear-gradient(135deg, #6B2C2C 0%, #4A1F1F 100%);
            color: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 160px;
            position: relative;
            overflow: hidden;
        }
        
        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(243, 156, 18, 0.1) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .info-card:hover::before {
            opacity: 1;
        }
        
        .info-card:active {
            transform: scale(0.95);
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .info-card img {
            width: 60px;
            height: 60px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }
        
        .info-card:active img {
            transform: scale(1.2) rotate(10deg);
        }
        
        .info-card .text-content {
            position: relative;
            z-index: 1;
        }
        
        .info-card .text-content h6 {
            margin: 0 0 8px 0;
            font-size: 0.85rem;
            font-weight: 600;
            opacity: 0.9;
            letter-spacing: 0.5px;
        }
        
        .info-card .text-content p {
            margin: 0;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .info-card .badge-status {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: #27AE60;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .info-card.needs-restock .badge-status {
            background-color: #E74C3C;
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
        }
        
        /* Financial Summary Card */
        .financial-summary {
            background: linear-gradient(135deg, #6B2C2C 0%, #4A1F1F 100%);
            border-radius: 20px;
            padding: 25px;
            color: white;
            margin-bottom: 30px;
        }
        
        .financial-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .financial-header h5 {
            font-weight: bold;
            font-size: 1.3rem;
            margin: 0;
        }
        
        .date-range {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.75rem;
        }
        
        .total-income {
            text-align: right;
            margin-bottom: 25px;
        }
        
        .total-income h6 {
            font-size: 0.85rem;
            opacity: 0.8;
            margin-bottom: 5px;
        }
        
        .total-income p {
            font-size: 1.8rem;
            font-weight: bold;
            color: #F39C12;
            margin: 0;
        }
        
        .chart-container {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 180px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 15px;
            margin-bottom: 10px;
            gap: 5px;
        }
        
        .chart-bar {
            flex: 1;
            max-width: 50px;
            position: relative;
            background: linear-gradient(to top, #F39C12, #FDB45C);
            border-radius: 8px 8px 0 0;
            min-height: 20px;
        }
        
        .chart-labels {
            display: flex;
            justify-content: space-around;
            font-size: 0.7rem;
            color: white;
            opacity: 0.8;
            margin-top: 10px;
            gap: 5px;
        }
        
        .chart-labels span {
            flex: 1;
            text-align: center;
        }
        
        /* Recent Activity */
        .recent-activity {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .activity-header {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f2f5;
        }
        
        .activity-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f2f5;
        }
        
        .activity-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .activity-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #6B2C2C 0%, #4A1F1F 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .activity-icon img {
            width: 24px;
            height: 24px;
            filter: brightness(0) invert(1);
        }
        
        .activity-content {
            flex-grow: 1;
        }
        
        .activity-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
            font-size: 0.95rem;
        }
        
        .activity-desc {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 3px;
        }
        
        .activity-time {
            font-size: 0.75rem;
            color: #999;
        }
        
        /* Sell Eggs Section */
        .sell-eggs-section {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .sell-eggs-header {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f2f5;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sell-eggs-header img {
            width: 28px;
            height: 28px;
        }
        
        .egg-sell-card {
            background: linear-gradient(135deg, #F39C12 0%, #D68910 100%);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 12px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(243, 156, 18, 0.3);
        }
        
        .egg-sell-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(243, 156, 18, 0.4);
        }
        
        .egg-sell-card:active {
            transform: scale(0.98);
        }
        
        .egg-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .egg-kandang-name {
            font-size: 1rem;
            font-weight: 600;
        }
        
        .egg-stock-info {
            display: flex;
            gap: 15px;
            font-size: 0.85rem;
        }
        
        .egg-stock-item {
            display: flex;
            flex-direction: column;
        }
        
        .egg-stock-label {
            opacity: 0.9;
            font-size: 0.75rem;
        }
        
        .egg-stock-value {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        /* Sales History */
        .sales-history {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #f0f2f5;
        }
        
        .sales-history-header {
            font-size: 1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        
        .sales-history-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sales-history-info {
            flex: 1;
        }
        
        .sales-history-kandang {
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }
        
        .sales-history-detail {
            font-size: 0.75rem;
            color: #666;
            margin-top: 3px;
        }
        
        .sales-history-amount {
            text-align: right;
        }
        
        .sales-history-price {
            font-weight: 700;
            color: #F39C12;
            font-size: 0.95rem;
        }
        
        .sales-history-date {
            font-size: 0.7rem;
            color: #999;
            margin-top: 2px;
        }
        
        /* Task Cards */
        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
            margin-top: 10px;
        }
        
        .task-card {
            background: linear-gradient(135deg, #6B2C2C 0%, #4A1F1F 100%);
            color: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 15px;
            position: relative;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .task-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.3);
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
    <?php include __DIR__ . '/../../Components/sidebar-admin.php'; ?>

    <div class="top-bar">
        <div class="d-flex align-items-center gap-2">
            <img src="<?= BASE_URL ?>/View/Assets/icons/staff.png" alt="Admin Icon" style="width: 24px; height: 24px;" onerror="this.src='<?= BASE_URL ?>/View/Assets/icons/logo-background.png'">
            <span class="fw-semibold">Hi, <?= htmlspecialchars($username) ?> 👋</span>
        </div>
        <span class="time-badge" id="currentTime"></span>
    </div>

    <div class="main-container">
        <?php if (!empty($flash)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert" style="position: relative; padding-right: 40px;">
                <?= htmlspecialchars($flash) ?>
                <button type="button" class="btn-close-custom" onclick="this.parentElement.style.display='none'" style="position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 18px; cursor: pointer; color: #333; font-weight: bold; line-height: 1; padding: 0; width: 20px; height: 20px; opacity: 0.5; transition: opacity 0.2s;">&times;</button>
            </div>
        <?php endif; ?>
        
        <!-- Desktop Grid Layout -->
        <div class="desktop-grid">
            <div class="info-card">
                <span class="badge-status">Good Condition</span>
                <img src="<?= BASE_URL ?>/View/Assets/icons/barn.png" alt="Barn">
                <div class="text-content">
                    <h6>BARN / COOP</h6>
                    <p><?= $totalKandang ?></p>
                </div>
            </div>

            <div class="info-card">
                <span class="badge-status">Good Condition</span>
                <img src="<?= BASE_URL ?>/View/Assets/icons/chicken.png" alt="Chickens">
                <div class="text-content">
                    <h6>TOTAL CHICKENS</h6>
                    <p><?= $totalAyam ?></p>
                </div>
            </div>

            <div class="info-card <?= $totalKategoriStok === 0 ? 'needs-restock' : '' ?>">
                <span class="badge-status"><?= $totalKategoriStok === 0 ? 'Needs Restock' : 'Available' ?></span>
                <img src="<?= BASE_URL ?>/View/Assets/icons/stock.png" alt="Stock">
                <div class="text-content">
                    <h6>STOCK CATEGORY</h6>
                    <p><?= $totalKategoriStok ?></p>
                </div>
            </div>
        </div>
        
        <!-- Desktop Content Grid -->
        <div class="desktop-content">
            <!-- Left Column: Financial Summary & Tasks -->
            <div>
                <!-- Financial Summary -->
                <div class="financial-summary">
                    <div class="financial-header">
                        <h5>Financial Summary</h5>
                        <div class="date-range">📅 <?= !empty($startDate) ? $startDate . ' - ' . $endDate : 'No Data' ?></div>
                    </div>
                    
                    <div class="total-income">
                        <h6>TOTAL INCOME</h6>
                        <p>Rp. <?= number_format($totalIncome, 0, ',', '.') ?></p>
                    </div>
                    
                    <div class="chart-container">
                        <?php if (empty($financialData)): ?>
                            <div style="text-align: center; opacity: 0.5; width: 100%; padding: 20px;">
                                No sales data available
                            </div>
                        <?php else: ?>
                            <?php foreach ($financialData as $fd): 
                                $heightPercent = $maxIncome > 0 ? ($fd['total_income'] / $maxIncome * 70) + 20 : 25;
                                $amount = number_format($fd['total_income'], 0, ',', '.');
                            ?>
                                <div class="chart-bar" 
                                     style="height: <?= $heightPercent ?>%;">
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="chart-labels">
                        <?php if (!empty($financialData)): ?>
                            <?php foreach ($financialData as $fd): ?>
                                <span><?= date('d/m', strtotime($fd['tanggal'])) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Tasks -->
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
            
            <!-- Right Column: Recent Activity -->
            <div>
                <div class="recent-activity">
                    <h5 class="activity-header">Recent Activity</h5>
                    
                    <div class="activity-item">
                        <div class="activity-icon">
                            <img src="<?= BASE_URL ?>/View/Assets/icons/egg.png" alt="Egg">
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">Egg...</div>
                            <div class="activity-desc">Daily Harvest from Kandang 1</div>
                            <div class="activity-time">2 hours ago</div>
                        </div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-icon">
                            <img src="<?= BASE_URL ?>/View/Assets/icons/feed.png" alt="Feed">
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">Fee...</div>
                            <div class="activity-desc">Daily Corn purchased has...</div>
                            <div class="activity-time">4 hours ago</div>
                        </div>
                    </div>
                </div>
                
                <!-- Sell Eggs Section -->
                <div class="sell-eggs-section">
                    <h5 class="sell-eggs-header">
                        <img src="<?= BASE_URL ?>/View/Assets/icons/egg.png" alt="Sell Eggs" onerror="this.src='<?= BASE_URL ?>/View/Assets/icons/logo-background.png'">
                        Sell Eggs
                    </h5>
                    
                    <?php if (empty($kandangWithEggs)): ?>
                        <p style="text-align: center; color: #999; padding: 20px;">
                            No eggs available to sell
                        </p>
                    <?php else: ?>
                        <?php foreach ($kandangWithEggs as $egg): 
                            $available = ($egg['jumlah_telur'] ?? 0) - ($egg['jumlah_terjual'] ?? 0);
                            if ($available <= 0) continue;
                        ?>
                            <div class="egg-sell-card" onclick="openSellModal(<?= $egg['id_telur'] ?>, '<?= htmlspecialchars($egg['nama_kandang']) ?>', <?= $available ?>)">
                                <div class="egg-card-header">
                                    <div class="egg-kandang-name"><?= htmlspecialchars($egg['nama_kandang']) ?></div>
                                </div>
                                <div class="egg-stock-info">
                                    <div class="egg-stock-item">
                                        <span class="egg-stock-label">Total</span>
                                        <span class="egg-stock-value"><?= $egg['jumlah_telur'] ?? 0 ?></span>
                                    </div>
                                    <div class="egg-stock-item">
                                        <span class="egg-stock-label">Available</span>
                                        <span class="egg-stock-value"><?= $available ?></span>
                                    </div>
                                    <div class="egg-stock-item">
                                        <span class="egg-stock-label">Sold</span>
                                        <span class="egg-stock-value"><?= $egg['jumlah_terjual'] ?? 0 ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <!-- Sales History -->
                    <?php if (!empty($salesHistory)): ?>
                        <div class="sales-history">
                            <div class="sales-history-header">📊 Recent Sales History</div>
                            <?php foreach ($salesHistory as $sale): ?>
                                <div class="sales-history-item">
                                    <div class="sales-history-info">
                                        <div class="sales-history-kandang"><?= htmlspecialchars($sale['nama_kandang']) ?></div>
                                        <div class="sales-history-detail">
                                            <?= $sale['jumlah_terjual'] ?> eggs × Rp <?= number_format($sale['harga_jual'], 0, ',', '.') ?>
                                        </div>
                                    </div>
                                    <div class="sales-history-amount">
                                        <div class="sales-history-price">Rp <?= number_format($sale['total_sales'], 0, ',', '.') ?></div>
                                        <div class="sales-history-date"><?= date('d M Y, H:i', strtotime($sale['tanggal_jual'])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Sell Egg Modal -->
    <div id="sellEggModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 20px; padding: 25px; width: 90%; max-width: 400px; max-height: 90vh; overflow-y: auto;">
            <h5 style="margin-bottom: 20px; font-weight: bold; color: #333;">Sell Eggs</h5>
            <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
                <input type="hidden" name="action" value="sell_egg">
                <input type="hidden" name="id_telur" id="sell_id_telur">
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">Kandang</label>
                    <input type="text" id="sell_kandang_name" readonly style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 10px; background: #f5f5f5;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">Available Eggs</label>
                    <input type="text" id="sell_available" readonly style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 10px; background: #f5f5f5;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">Quantity to Sell *</label>
                    <input type="number" name="jumlah_terjual" id="sell_quantity" min="1" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 10px;">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">Price per Egg (Rp) *</label>
                    <input type="number" name="harga_jual" step="0.01" min="0" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 10px;">
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="button" onclick="closeSellModal()" style="flex: 1; padding: 12px; background: #ddd; color: #333; border: none; border-radius: 10px; font-weight: 600; cursor: pointer;">Cancel</button>
                    <button type="submit" style="flex: 1; padding: 12px; background: linear-gradient(135deg, #F39C12 0%, #D68910 100%); color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer;">Sell</button>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../../Components/bottom-nav-admin.php'; ?>

    <script src="<?= BASE_URL ?>/View/Assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sell Egg Modal Functions
        function openSellModal(idTelur, kandangName, available) {
            document.getElementById('sell_id_telur').value = idTelur;
            document.getElementById('sell_kandang_name').value = kandangName;
            document.getElementById('sell_available').value = available + ' eggs';
            document.getElementById('sell_quantity').max = available;
            document.getElementById('sellEggModal').style.display = 'flex';
        }
        
        function closeSellModal() {
            document.getElementById('sellEggModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('sellEggModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSellModal();
            }
        });
        
        // Update time every minute
        function updateTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('currentTime').textContent = hours + ':' + minutes + ' WIB';
        }
        
        // Update time immediately and then every minute
        updateTime();
        setInterval(updateTime, 60000);
    </script>
</body>
</html>
