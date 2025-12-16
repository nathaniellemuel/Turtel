<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../Connection/Connection.php';
require_once __DIR__ . '/../../../Config/Language.php';
require_once __DIR__ . '/../../../Controller/PakanController.php';
require_once __DIR__ . '/../../../Controller/StokController.php';
require_once __DIR__ . '/../../../Controller/KandangController.php';

session_start();

// Simple access control: only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL . '/View/pages/auth/index.php' : '/Turtel/View/pages/auth/index.php'));
    exit;
}

$pakanCtrl = new PakanController($conn);
$stokCtrl = new StokController($conn);
$kandangCtrl = new KandangController($conn);
$username = $_SESSION['username'] ?? 'Admin';

// Handle form submissions
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_pakan') {
        $jumlah = (int)($_POST['jumlah_digunakan'] ?? 0);
        $id_stock = (int)($_POST['id_stock'] ?? 0);
        $id_kandang = $_POST['id_kandang'] ?? '';
        $created = date('Y-m-d H:i:s');
        
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
    } elseif ($action === 'edit_pakan') {
        $id = $_POST['id_pakan'] ?? 0;
        $jumlah = (int)($_POST['jumlah_digunakan'] ?? 0);
        $id_kandang = (int)($_POST['id_kandang'] ?? 0);
        $created = $_POST['created_at'] ?? date('Y-m-d H:i:s');
        
        // Check if pakan is already assigned to a task
        $checkTask = $conn->prepare("SELECT id_tugas FROM tugas WHERE id_pakan = ?");
        $checkTask->bind_param("i", $id);
        $checkTask->execute();
        $checkTask->store_result();
        
        if ($checkTask->num_rows > 0) {
            $checkTask->close();
            $flash = 'Pakan sudah ditugaskan ke pegawai dan tidak bisa diedit';
        } else {
            $checkTask->close();
            
            // Get current pakan data to know the stock
            $currentPakan = $pakanCtrl->getById((int)$id);
            if ($currentPakan['success']) {
                $id_stock = $currentPakan['data']['id_stock'];
                $res = $pakanCtrl->update((int)$id, $jumlah, $created, $id_stock);
                $flash = $res['message'] ?? 'Updated';
            } else {
                $flash = 'Pakan tidak ditemukan';
            }
        }
    } elseif ($action === 'delete_pakan') {
        $id = $_POST['id_pakan'] ?? 0;
        
        // Check if pakan is already assigned to a task
        $checkTask = $conn->prepare("SELECT id_tugas FROM tugas WHERE id_pakan = ?");
        $checkTask->bind_param("i", $id);
        $checkTask->execute();
        $checkTask->store_result();
        
        if ($checkTask->num_rows > 0) {
            $checkTask->close();
            $flash = 'Pakan sudah ditugaskan ke pegawai dan tidak bisa dihapus';
        } else {
            $checkTask->close();
            
            // Get pakan data to return stock
            $pakanData = $pakanCtrl->getById((int)$id);
            if ($pakanData['success']) {
                $jumlahDigunakan = (int)$pakanData['data']['jumlah_digunakan'];
                $id_stock = (int)$pakanData['data']['id_stock'];
                
                // Get current stock
                $stokData = $stokCtrl->getById($id_stock);
                if ($stokData['success']) {
                    // Return stock - hanya update jumlah, tidak mengubah nama atau kategori
                    $currentJumlah = (int)$stokData['data']['jumlah'];
                    $newJumlah = $currentJumlah + $jumlahDigunakan;
                    $stokCtrl->updateJumlah($id_stock, $newJumlah);
                    
                    // Delete pakan
                    $res = $pakanCtrl->delete((int)$id);
                    $flash = 'Pakan dihapus dan stok dikembalikan';
                } else {
                    $flash = 'Gagal mengupdate stok';
                }
            } else {
                $flash = 'Pakan tidak ditemukan';
            }
        }
    }
}

// Get all pakan with task status check
$pakanQuery = "SELECT p.id_pakan, p.jumlah_digunakan, p.created_at, p.id_stock, s.nama_stock, s.kategori,
               (SELECT COUNT(*) FROM tugas WHERE id_pakan = p.id_pakan) as is_assigned
               FROM pakan p
               LEFT JOIN stok s ON p.id_stock = s.id_stock
               ORDER BY p.id_pakan DESC";
$pakanResult = $conn->query($pakanQuery);
$pakans = [];
if ($pakanResult) {
    while ($r = $pakanResult->fetch_assoc()) {
        $pakans[] = $r;
    }
}

$stoks = $stokCtrl->getAll()['data'] ?? [];
$kandangs = $kandangCtrl->getAll()['data'] ?? [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('cat_feed') ?></title>
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
            display: grid;
            grid-template-columns: 40px 1fr 50px;
            align-items: center;
            gap: 10px;
        }
        .top-bar-left {
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }
        .top-bar-center {
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .back-button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .back-button img {
            width: 25px;
            height: 25px;
        }
        .top-bar-right {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }
        .top-bar-right img {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }
        .main-container {
            padding: 20px;
        }
        .feed-card {
            background: linear-gradient(135deg, #6B2C2C 0%, #4A1F1F 100%);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 15px;
            color: white;
            position: relative;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .feed-card:active {
            transform: scale(0.98);
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        @media (hover: hover) and (pointer: fine) {
            .feed-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 16px rgba(0,0,0,0.3);
            }
        }
        .feed-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .feed-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .feed-info {
            flex-grow: 1;
        }
        .feed-info h5 {
            margin: 0 0 5px 0;
            font-size: 1.3rem;
            font-weight: 700;
        }
        .feed-info p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .edit-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 30px;
            height: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .edit-icon:active {
            transform: scale(0.9) rotate(-10deg);
        }
        @media (hover: hover) and (pointer: fine) {
            .edit-icon:hover {
                transform: scale(1.2) rotate(10deg);
                filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
            }
        }
        .feed-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }
        .feed-date {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
        }
        .feed-date img {
            width: 20px;
            height: 20px;
            margin-right: 8px;
        }
        .remove-button {
            background-color: #F39C12;
            color: white;
            border: none;
            border-radius: 25px;
            padding: 10px 30px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            transition: all 0.2s ease;
        }
        .remove-button:hover {
            background-color: #E67E22;
        }
        .add-button {
            position: fixed;
            bottom: 100px;
            right: 20px;
            width: 60px;
            height: 60px;
            background-color: #6B2C2C;
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 2rem;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            z-index: 100;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .add-button:active {
            transform: scale(0.9) rotate(90deg);
            background-color: #8B3A3A;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        @media (hover: hover) and (pointer: fine) {
            .add-button:hover {
                background-color: #8B3A3A;
                transform: scale(1.1) rotate(90deg);
                box-shadow: 0 6px 16px rgba(0,0,0,0.4);
            }
        }
        
        /* Custom Confirmation Modal */
        .confirm-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        .confirm-overlay.active {
            display: flex;
        }
        .confirm-box {
            background: white;
            border-radius: 20px;
            padding: 30px;
            width: 85%;
            max-width: 350px;
            text-align: center;
            animation: confirmSlideIn 0.3s ease;
        }
        @keyframes confirmSlideIn {
            from {
                transform: scale(0.8);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        .confirm-box h4 {
            color: #333;
            margin: 0 0 15px 0;
            font-size: 1.3rem;
            font-weight: 700;
        }
        .confirm-box p {
            color: #666;
            margin: 0 0 25px 0;
            font-size: 0.95rem;
        }
        .confirm-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .confirm-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: 'Montserrat', sans-serif;
        }
        .confirm-btn-no {
            background-color: #E0E0E0;
            color: #333;
        }
        .confirm-btn-no:hover {
            background-color: #BDBDBD;
        }
        .confirm-btn-yes {
            background-color: #E74C3C;
            color: white;
        }
        .confirm-btn-yes:hover {
            background-color: #C0392B;
        }
        
        /* Add Feed Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-content {
            background: linear-gradient(135deg, #6B2C2C 0%, #4A1F1F 100%);
            border-radius: 30px;
            padding: 30px;
            width: 90%;
            max-width: 400px;
            color: white;
            animation: modalSlideIn 0.3s ease;
            position: relative;
        }
        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        .modal-header {
            text-align: center;
            margin-bottom: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .modal-header h4 {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 0.9rem;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 25px;
            background-color: rgba(255, 255, 255, 0.9);
            color: #333;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9rem;
            box-sizing: border-box;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(243, 156, 18, 0.3);
        }
        .form-group input:disabled {
            background-color: rgba(200, 200, 200, 0.5);
            color: #666;
            cursor: not-allowed;
        }
        
        /* Custom Date Input Styling */
        .form-group input[type="date"] {
            cursor: pointer;
            position: relative;
            font-weight: 600;
        }
        .form-group input[type="date"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            filter: invert(48%) sepia(79%) saturate(2476%) hue-rotate(360deg) brightness(95%) contrast(97%);
            transition: all 0.3s ease;
        }
        .form-group input[type="date"]:hover::-webkit-calendar-picker-indicator {
            transform: scale(1.1);
            filter: invert(48%) sepia(79%) saturate(2476%) hue-rotate(360deg) brightness(105%) contrast(97%) drop-shadow(0 2px 4px rgba(243, 156, 18, 0.6));
        }
        .form-group input[type="date"]::-webkit-datetime-edit-fields-wrapper {
            padding: 0;
        }
        .form-group input[type="date"]::-webkit-datetime-edit-text {
            color: #666;
            padding: 0 0.3em;
        }
        .form-group input[type="date"]::-webkit-datetime-edit-month-field,
        .form-group input[type="date"]::-webkit-datetime-edit-day-field,
        .form-group input[type="date"]::-webkit-datetime-edit-year-field {
            color: #333;
            font-weight: 600;
            padding: 4px 6px;
            margin: 0 2px;
            border-radius: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .form-group input[type="date"]::-webkit-datetime-edit-month-field:hover,
        .form-group input[type="date"]::-webkit-datetime-edit-day-field:hover,
        .form-group input[type="date"]::-webkit-datetime-edit-year-field:hover {
            background-color: rgba(243, 156, 18, 0.2);
            transform: scale(1.05);
        }
        .form-group input[type="date"]::-webkit-datetime-edit-month-field:focus,
        .form-group input[type="date"]::-webkit-datetime-edit-day-field:focus,
        .form-group input[type="date"]::-webkit-datetime-edit-year-field:focus {
            background-color: #F39C12;
            color: white;
            outline: none;
            border-radius: 8px;
            transform: scale(1.08);
            box-shadow: 0 2px 8px rgba(243, 156, 18, 0.4);
        }
        
        .modal-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
        }
        .btn-cancel {
            background: transparent;
            color: #F39C12;
            border: none;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            padding: 10px 20px;
            border-radius: 10px;
            transition: all 0.2s ease;
            font-family: 'Montserrat', sans-serif;
        }
        .btn-cancel:hover {
            background-color: rgba(243, 156, 18, 0.3);
        }
        .btn-save {
            background-color: white;
            color: #6B2C2C;
            border: none;
            border-radius: 8px;
            padding: 10px 40px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Montserrat', sans-serif;
        }
        .btn-save:hover {
            background-color: #763A12;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        /* Custom Select Dropdown */
        .custom-select-wrapper {
            position: relative;
            width: 100%;
        }
        .custom-select-trigger {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 25px;
            background-color: rgba(255, 255, 255, 0.9);
            color: #333;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        .custom-select-trigger:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .custom-select-arrow {
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 6px solid #666;
            transition: transform 0.3s ease;
        }
        .custom-select-trigger.active .custom-select-arrow {
            transform: rotate(180deg);
        }
        .custom-select-options {
            position: absolute;
            top: calc(100% + 5px);
            left: 0;
            width: 100%;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 100;
            overflow: hidden;
            max-height: 250px;
            overflow-y: auto;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }
        .custom-select-options.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .custom-select-option {
            padding: 12px 15px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            color: #333;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9rem;
        }
        .custom-select-option:hover {
            background-color: #F39C12;
            color: white;
        }
    </style>
</head>
<body>

    <div class="top-bar">
        <div class="top-bar-left">
            <button class="back-button" onclick="window.history.back()">
                <img src="<?= BASE_URL ?>/View/Assets/icons/back.png" alt="Back">
            </button>
        </div>
        <div class="top-bar-center">
            <span><?= strtoupper(t('cat_feed')) ?></span>
        </div>
        <div class="top-bar-right">
            <img src="<?= BASE_URL ?>/View/Assets/icons/feed.png" alt="Feed Icon">
        </div>
    </div>

    <div class="main-container">
        <?php if (!empty($flash)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert" style="position: relative; padding-right: 40px;">
                <?= htmlspecialchars($flash) ?>
                <button type="button" class="btn-close-custom" onclick="this.parentElement.style.display='none'" style="position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 18px; cursor: pointer; color: #333; font-weight: bold; line-height: 1; padding: 0; width: 20px; height: 20px; opacity: 0.5; transition: opacity 0.2s;">&times;</button>
            </div>
        <?php endif; ?>

        <?php foreach ($pakans as $p): 
            $feedDate = date('d/m/Y', strtotime($p['created_at']));
            $stockName = $p['nama_stock'] ?? 'Unknown';
        ?>
        <div class="feed-card">
            <div class="feed-header">
                <img src="<?= BASE_URL ?>/View/Assets/icons/barn.png" alt="Feed" class="feed-icon">
                <div class="feed-info">
                    <h5><?= htmlspecialchars($stockName) ?></h5>
                    <p>Total : <?= htmlspecialchars($p['jumlah_digunakan']) ?>kg | For A.100.1</p>
                </div>
            </div>
            
            <?php if ($p['is_assigned'] > 0): ?>
                <img src="<?= BASE_URL ?>/View/Assets/icons/pencil.png" alt="Edit" class="edit-icon" style="cursor: not-allowed; opacity: 0.4;" title="Tidak bisa diedit karena sudah ditugaskan">
            <?php else: ?>
                <img src="<?= BASE_URL ?>/View/Assets/icons/pencil.png" alt="Edit" class="edit-icon" onclick="openEditFeedModal(<?= $p['id_pakan'] ?>, '<?= htmlspecialchars($stockName, ENT_QUOTES) ?>', <?= $p['jumlah_digunakan'] ?>, 'A.100.1', '<?= $feedDate ?>', '<?= $p['created_at'] ?>')">
            <?php endif; ?>
            
            <div class="feed-footer">
                <div class="feed-date">
                    <img src="<?= BASE_URL ?>/View/Assets/icons/date.png" alt="Date">
                    <span><?= $feedDate ?></span>
                </div>
                <?php if ($p['is_assigned'] > 0): ?>
                    <button type="button" class="remove-button" style="cursor: not-allowed; opacity: 0.4; background-color: #999;" title="Tidak bisa dihapus karena sudah ditugaskan" disabled>Remove</button>
                <?php else: ?>
                    <button type="button" class="remove-button" onclick="confirmDeleteFeed(<?= $p['id_pakan'] ?>, '<?= htmlspecialchars($stockName, ENT_QUOTES) ?>')">Remove</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <button class="add-button" onclick="openAddFeedModal()">+</button>

    <!-- Add Feed Modal -->
    <div class="modal-overlay" id="addFeedModal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>ADD FEED</h4>
            </div>
            
            <form method="POST" id="addFeedForm">
                <input type="hidden" name="action" value="add_pakan">
                
                <div class="form-group">
                    <label>Stock Name</label>
                    <div class="custom-select-wrapper">
                        <input type="hidden" name="id_stock" id="stockSelect" required>
                        <div class="custom-select-trigger" data-target="stockSelect">
                            <span class="selected-text">Select Stock</span>
                            <div class="custom-select-arrow"></div>
                        </div>
                        <div class="custom-select-options">
                            <?php foreach ($stoks as $s): ?>
                                <div class="custom-select-option" data-value="<?= $s['id_stock'] ?>"><?= htmlspecialchars($s['nama_stock']) ?> (<?= $s['jumlah'] ?> available)</div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Total (kg)</label>
                    <input type="number" name="jumlah_digunakan" id="totalKg" placeholder="Enter total in kg" min="1" required>
                </div>
                
                <div class="form-group">
                    <label>For Barn</label>
                    <div class="custom-select-wrapper">
                        <input type="hidden" name="id_kandang" id="forBarn" required>
                        <div class="custom-select-trigger" data-target="forBarn">
                            <span class="selected-text">Select Barn</span>
                            <div class="custom-select-arrow"></div>
                        </div>
                        <div class="custom-select-options">
                            <?php foreach ($kandangs as $k): ?>
                                <div class="custom-select-option" data-value="<?= $k['id_kandang'] ?>"><?= htmlspecialchars($k['nama_kandang']) ?> (<?= $k['jenis_ayam'] ?> - <?= $k['jumlah_ayam'] ?> chickens)</div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="created_at" id="addFeedDate" value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeAddFeedModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Feed Modal -->
    <div class="modal-overlay" id="editFeedModal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>EDIT FEED</h4>
            </div>
            
            <form method="POST" id="editFeedForm">
                <input type="hidden" name="action" value="edit_pakan">
                <input type="hidden" name="id_pakan" id="editFeedId">
                
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" id="editFeedName" disabled>
                </div>
                
                <div class="form-group">
                    <label>Total</label>
                    <input type="number" name="jumlah_digunakan" id="editTotalKg" placeholder="Enter total in kg" min="1" required>
                </div>
                
                <div class="form-group">
                    <label>Barn</label>
                    <input type="hidden" name="id_kandang" id="editBarnId">
                    <div class="custom-select-wrapper">
                        <input type="hidden" id="editBarnSelect" required>
                        <div class="custom-select-trigger" data-target="editBarnSelect">
                            <span class="selected-text">Select Barn</span>
                            <div class="custom-select-arrow"></div>
                        </div>
                        <div class="custom-select-options">
                            <?php foreach ($kandangs as $k): ?>
                                <div class="custom-select-option" data-value="<?= $k['id_kandang'] ?>|<?= htmlspecialchars($k['nama_kandang'], ENT_QUOTES) ?>"><?= htmlspecialchars($k['nama_kandang']) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Date Received</label>
                    <input type="date" name="created_at" id="editCreatedAt" required>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeEditFeedModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Custom Confirmation Modal -->
    <div class="confirm-overlay" id="confirmDeleteModal">
        <div class="confirm-box">
            <h4>Remove Feed?</h4>
            <p id="confirmDeleteMessage">Are you sure you want to remove this feed?</p>
            <div class="confirm-buttons">
                <button class="confirm-btn confirm-btn-no" onclick="closeConfirmDelete()">No</button>
                <button class="confirm-btn confirm-btn-yes" onclick="confirmDeleteAction()">Yes</button>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../Components/bottom-nav-admin.php'; ?>

    <script src="<?= BASE_URL ?>/View/Assets/js/bootstrap.bundle.min.js"></script>
    <script>
        let pendingDeleteFeedId = null;
        
        function confirmDeleteFeed(feedId, feedName) {
            pendingDeleteFeedId = feedId;
            document.getElementById('confirmDeleteMessage').textContent = 
                'Are you sure you want to remove feed "' + feedName + '"?';
            document.getElementById('confirmDeleteModal').classList.add('active');
        }
        
        function closeConfirmDelete() {
            document.getElementById('confirmDeleteModal').classList.remove('active');
            pendingDeleteFeedId = null;
        }
        
        function confirmDeleteAction() {
            if (pendingDeleteFeedId) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete_pakan">' +
                                '<input type="hidden" name="id_pakan" value="' + pendingDeleteFeedId + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Add Feed Modal Functions
        function openAddFeedModal() {
            document.getElementById('addFeedModal').classList.add('active');
        }
        
        function closeAddFeedModal() {
            document.getElementById('addFeedModal').classList.remove('active');
            document.getElementById('addFeedForm').reset();
        }
        
        // Close modal when clicking outside
        document.getElementById('addFeedModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddFeedModal();
            }
        });
        
        // Convert date to MySQL format before submit (Add Feed)
        document.getElementById('addFeedForm').addEventListener('submit', function(e) {
            const dateInput = document.getElementById('addFeedDate');
            if (dateInput.value) {
                const dateValue = dateInput.value + ' 00:00:00';
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'created_at';
                hiddenInput.value = dateValue;
                this.appendChild(hiddenInput);
                dateInput.removeAttribute('name');
            }
        });
        
        // Edit Feed Modal Functions
        function openEditFeedModal(feedId, feedName, totalKg, barn, dateReceived, createdAt) {
            document.getElementById('editFeedId').value = feedId;
            document.getElementById('editFeedName').value = feedName;
            document.getElementById('editTotalKg').value = totalKg;
            
            // Convert from dd/mm/yyyy to YYYY-MM-DD for date input
            const dateParts = dateReceived.split('/');
            if (dateParts.length === 3) {
                const formattedDate = dateParts[2] + '-' + dateParts[1] + '-' + dateParts[0];
                document.getElementById('editCreatedAt').value = formattedDate;
            }
            
            // Set custom dropdown barn
            const editWrapper = document.querySelector('#editFeedModal .custom-select-wrapper');
            const editTrigger = editWrapper.querySelector('.custom-select-trigger .selected-text');
            const options = editWrapper.querySelectorAll('.custom-select-option');
            
            // Find and set the matching barn option
            options.forEach(opt => {
                const optText = opt.textContent.trim();
                if (optText === barn) {
                    const [barnId, barnName] = opt.dataset.value.split('|');
                    document.getElementById('editBarnId').value = barnId;
                    document.getElementById('editBarnSelect').value = opt.dataset.value;
                    editTrigger.textContent = optText;
                }
            });
            
            document.getElementById('editFeedModal').classList.add('active');
        }
        
        function closeEditFeedModal() {
            document.getElementById('editFeedModal').classList.remove('active');
            document.getElementById('editFeedForm').reset();
        }
        
        // Close edit modal when clicking outside
        document.getElementById('editFeedModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditFeedModal();
            }
        });
        
        // Convert date to MySQL format before submit (Edit Feed)
        document.getElementById('editFeedForm').addEventListener('submit', function(e) {
            const dateInput = document.getElementById('editCreatedAt');
            if (dateInput.value) {
                const dateValue = dateInput.value + ' 00:00:00';
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'created_at';
                hiddenInput.value = dateValue;
                this.appendChild(hiddenInput);
                dateInput.removeAttribute('name');
            }
        });

        // Custom Select Dropdown
        document.querySelectorAll('.custom-select-trigger').forEach(trigger => {
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                
                // Close other dropdowns
                document.querySelectorAll('.custom-select-trigger').forEach(t => {
                    if (t !== this) {
                        t.classList.remove('active');
                        t.nextElementSibling.classList.remove('active');
                    }
                });
                
                // Toggle this dropdown
                this.classList.toggle('active');
                this.nextElementSibling.classList.toggle('active');
            });
        });

        document.querySelectorAll('.custom-select-option').forEach(option => {
            option.addEventListener('click', function() {
                const wrapper = this.closest('.custom-select-wrapper');
                const trigger = wrapper.querySelector('.custom-select-trigger');
                const selectedText = trigger.querySelector('.selected-text');
                const hiddenInput = wrapper.querySelector('input[type="hidden"]');
                const optionsContainer = wrapper.querySelector('.custom-select-options');
                
                // Update selected value
                hiddenInput.value = this.dataset.value;
                selectedText.textContent = this.textContent;
                
                // For edit barn dropdown, also update editBarnId
                if (hiddenInput.id === 'editBarnSelect') {
                    const [barnId, barnName] = this.dataset.value.split('|');
                    document.getElementById('editBarnId').value = barnId;
                }
                
                // Close dropdown
                trigger.classList.remove('active');
                optionsContainer.classList.remove('active');
            });
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            document.querySelectorAll('.custom-select-trigger').forEach(trigger => {
                trigger.classList.remove('active');
                trigger.nextElementSibling.classList.remove('active');
            });
        });
    </script>
</body>
</html>
