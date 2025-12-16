<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../Connection/Connection.php';
require_once __DIR__ . '/../../../Controller/TelurController.php';
require_once __DIR__ . '/../../../Controller/KandangController.php';
require_once __DIR__ . '/../../../Config/Language.php';

session_start();

// Access control: only allow staff
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL . '/View/pages/auth/index.php' : '/Turtel/View/pages/auth/index.php'));
    exit;
}

$telurCtrl = new TelurController($conn);
$kandangCtrl = new KandangController($conn);

// Handle flash message from session
$flash = $_SESSION['flash'] ?? '';
if (!empty($flash)) {
    unset($_SESSION['flash']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_egg') {
        $id_kandang = (int)($_POST['id_kandang'] ?? 0);
        $jumlah_telur = (int)($_POST['jumlah_telur'] ?? 0);
        $jumlah_buruk = (int)($_POST['jumlah_buruk'] ?? 0);
        $berat = (float)($_POST['berat'] ?? 0);
        $layed_at = $_POST['layed_at'] ?? date('Y-m-d H:i:s');
        
        // Check if telur already exists for this kandang
        $checkStmt = $conn->prepare("SELECT id_telur, jumlah_telur, jumlah_buruk, berat FROM telur WHERE id_kandang = ?");
        $checkStmt->bind_param('i', $id_kandang);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            // Add to existing telur (increment)
            $row = $checkResult->fetch_assoc();
            $id_telur = $row['id_telur'];
            $new_jumlah_telur = $row['jumlah_telur'] + $jumlah_telur;
            $new_jumlah_buruk = $row['jumlah_buruk'] + $jumlah_buruk;
            $new_berat = $row['berat'] + $berat;
            
            $stmt = $conn->prepare("UPDATE telur SET jumlah_telur = ?, jumlah_buruk = ?, berat = ?, layed_at = ? WHERE id_telur = ?");
            $stmt->bind_param('iidsi', $new_jumlah_telur, $new_jumlah_buruk, $new_berat, $layed_at, $id_telur);
            
            if ($stmt->execute()) {
                $_SESSION['flash'] = 'Egg production added successfully!';
            } else {
                $_SESSION['flash'] = 'Failed to add egg data: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            // Insert new telur record
            $stmt = $conn->prepare("INSERT INTO telur (jumlah_telur, jumlah_buruk, berat, layed_at, id_kandang) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('iidsi', $jumlah_telur, $jumlah_buruk, $berat, $layed_at, $id_kandang);
            
            if ($stmt->execute()) {
                $_SESSION['flash'] = 'Egg production added successfully!';
            } else {
                $_SESSION['flash'] = 'Failed to add egg data: ' . $stmt->error;
            }
            $stmt->close();
        }
        $checkStmt->close();
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } elseif ($action === 'edit_egg') {
        $id_telur = (int)($_POST['id_telur'] ?? 0);
        $jumlah_telur = (int)($_POST['jumlah_telur'] ?? 0);
        $jumlah_buruk = (int)($_POST['jumlah_buruk'] ?? 0);
        $berat = (float)($_POST['berat'] ?? 0);
        $layed_at = $_POST['layed_at'] ?? date('Y-m-d H:i:s');
        
        $stmt = $conn->prepare("UPDATE telur SET jumlah_telur = ?, jumlah_buruk = ?, berat = ?, layed_at = ? WHERE id_telur = ?");
        $stmt->bind_param('iidsi', $jumlah_telur, $jumlah_buruk, $berat, $layed_at, $id_telur);
        
        if ($stmt->execute()) {
            $_SESSION['flash'] = 'Egg production data updated successfully!';
        } else {
            $_SESSION['flash'] = 'Failed to update egg data: ' . $stmt->error;
        }
        $stmt->close();
        
        // Redirect to prevent form resubmission
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
            $available = $total_eggs - $already_sold; // Calculate remaining eggs
            
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
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get filter parameter
$showHistory = isset($_GET['history']) && $_GET['history'] === 'true';

// Get all kandang with egg data
if ($showHistory) {
    // Show all egg data
    $query = "SELECT k.*, t.id_telur, t.jumlah_telur, t.jumlah_buruk, t.jumlah_terjual, t.berat, t.layed_at 
              FROM kandang k 
              LEFT JOIN telur t ON k.id_kandang = t.id_kandang 
              ORDER BY t.layed_at DESC, k.nama_kandang";
} else {
    // Show only today's egg data
    $today = date('Y-m-d');
    $query = "SELECT k.*, t.id_telur, t.jumlah_telur, t.jumlah_buruk, t.jumlah_terjual, t.berat, t.layed_at 
              FROM kandang k 
              LEFT JOIN telur t ON k.id_kandang = t.id_kandang AND DATE(t.layed_at) = '$today'
              ORDER BY k.nama_kandang";
}

$result = $conn->query($query);
$kandangs = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $kandangs[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Egg Production</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/View/Assets/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Montserrat', sans-serif;
            padding-bottom: 90px;
            margin: 0;
            min-height: 100vh;
        }
        .top-bar {
            background: linear-gradient(135deg, #FF9F1C 0%, #FF8C00 100%);
            color: white;
            padding: 15px 20px;
            font-size: 1.2rem;
            font-weight: 700;
            text-align: center;
            box-shadow: 0 4px 10px rgba(255, 140, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .date-badge {
            background: #8B4513;
            border-radius: 25px;
            padding: 8px 20px;
            margin-right: 15px;
            font-size: 1rem;
        }
        .main-container {
            padding: 20px;
        }
        .egg-card {
            background: linear-gradient(135deg, #6B2C2C 0%, #4A1F1F 100%);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 15px;
            color: white;
            position: relative;
            box-shadow: 0 4px 12px rgba(107, 44, 44, 0.3);
            min-height: 150px;
        }
        .egg-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .barn-icon {
            width: 55px;
            height: 55px;
            margin-right: 12px;
        }
        .barn-name {
            font-size: 1.3rem;
            font-weight: 700;
            margin: 0;
            margin-bottom: 2px;
        }
        .barn-weight {
            font-size: 0.75rem;
            font-weight: 500;
            margin: 0;
            color: rgba(255, 255, 255, 0.7);
            letter-spacing: 0.3px;
        }
        .card-date {
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.4);
            color: white;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.65rem;
            font-weight: 600;
            backdrop-filter: blur(8px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            z-index: 2;
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
        .edit-icon:hover {
            transform: scale(1.2) rotate(10deg);
        }
        .egg-stats {
            margin: 15px 0;
            padding-left: 5px;
        }
        .egg-row {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
        }
        .egg-row img {
            width: 28px;
            height: 28px;
            margin-right: 10px;
        }
        .egg-row .count {
            font-size: 1.2rem;
            font-weight: 700;
            margin-right: 8px;
            min-width: 35px;
        }
        .egg-row .label {
            font-size: 1rem;
            font-weight: 600;
        }
        .egg-row .label.good {
            color: #4CAF50;
        }
        .egg-row .label.bad {
            color: #E74C3C;
        }
        .btn-sell {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, #FF9F1C 0%, #FF8C00 100%);
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 25px;
            font-weight: 700;
            cursor: pointer;
            font-size: 1rem;
            box-shadow: 0 4px 10px rgba(255, 140, 0, 0.3);
            transition: all 0.3s ease;
        }
        .btn-sell:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 140, 0, 0.4);
        }
        .fab-btn {
            position: fixed;
            bottom: 110px;
            right: 20px;
            width: 60px;
            height: 60px;
            background: #6B2C2C;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        .fab-btn:hover {
            transform: scale(1.1) rotate(90deg);
            background: #8B3A3A;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
        }
        .history-btn {
            position: fixed;
            bottom: 110px;
            left: 20px;
            background: #FF9F1C;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(255, 159, 28, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
            font-family: 'Montserrat', sans-serif;
        }
        .history-btn:hover {
            background: #FF8C00;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 159, 28, 0.4);
        }
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
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
            position: relative;
            animation: modalSlideIn 0.3s ease;
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
        }
        .modal-header h4 {
            margin: 0;
            font-weight: 700;
            color: white;
            font-size: 1.5rem;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .form-group input {
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
        .form-group input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(243, 156, 18, 0.3);
        }
        .form-group input:disabled {
            background-color: rgba(200, 200, 200, 0.5);
            color: #666;
        }
        .custom-select-wrapper {
            position: relative;
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
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        .custom-select-trigger:hover {
            background-color: rgba(255, 255, 255, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .custom-select-trigger.active {
            box-shadow: 0 0 0 3px rgba(243, 156, 18, 0.3);
        }
        .custom-select-arrow {
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 6px solid #333;
            transition: transform 0.3s ease;
        }
        .custom-select-trigger.active .custom-select-arrow {
            transform: rotate(180deg);
        }
        .custom-select-options {
            position: absolute;
            top: calc(100% + 5px);
            left: 0;
            right: 0;
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 10;
            max-height: 200px;
            overflow-y: auto;
        }
        .custom-select-options.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .custom-select-option {
            padding: 12px 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #333;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9rem;
        }
        .custom-select-option:hover {
            background-color: #F39C12;
            color: white;
        }
        .custom-select-option.selected {
            background-color: rgba(243, 156, 18, 0.2);
            font-weight: 600;
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
        .no-eggs {
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            font-style: italic;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <?php if (!$showHistory): ?>
        <div class="date-badge"><?= date('d/m/y') ?></div>
        <?php endif; ?>
        <span><?= $showHistory ? t('history') : t('egg_production') ?></span>
    </div>

    <div class="main-container">
        <?php if (!empty($flash)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert" style="position: relative; padding-right: 40px;">
                <?= htmlspecialchars($flash) ?>
                <button type="button" class="btn-close-custom" onclick="this.parentElement.style.display='none'" style="position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 18px; cursor: pointer; color: #333; font-weight: bold; line-height: 1; padding: 0; width: 20px; height: 20px; opacity: 0.5; transition: opacity 0.2s;">&times;</button>
            </div>
        <?php endif; ?>

        <?php foreach ($kandangs as $kandang): 
            $hasEgg = !empty($kandang['id_telur']);
            $totalGoodEggs = $hasEgg ? (int)$kandang['jumlah_telur'] : 0;
            $soldEggs = $hasEgg ? (int)($kandang['jumlah_terjual'] ?? 0) : 0;
            $goodEggs = $totalGoodEggs - $soldEggs; // Available eggs = total - sold
            $badEggs = $hasEgg ? (int)$kandang['jumlah_buruk'] : 0;
        ?>
        <div class="egg-card">
            <?php if ($showHistory && $hasEgg): ?>
            <div class="card-date"><?= date('d M Y', strtotime($kandang['layed_at'])) ?></div>
            <?php endif; ?>
            
            <div class="egg-header">
                <img src="<?= BASE_URL ?>/View/Assets/icons/barn.png" alt="Barn" class="barn-icon">
                <div>
                    <h5 class="barn-name"><?= htmlspecialchars($kandang['nama_kandang']) ?></h5>
                    <?php if ($hasEgg): ?>
                    <p class="barn-weight">Total: <?= number_format($kandang['berat'], 1) ?>kg</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <img src="<?= BASE_URL ?>/View/Assets/icons/pencil.png" alt="Edit" class="edit-icon" onclick="openEditModal(<?= $kandang['id_kandang'] ?>, <?= $kandang['id_telur'] ?? 0 ?>, <?= $goodEggs ?>, <?= $badEggs ?>, '<?= htmlspecialchars($kandang['layed_at'] ?? date('Y-m-d H:i:s')) ?>', <?= htmlspecialchars($kandang['berat'] ?? 0) ?>, '<?= htmlspecialchars($kandang['nama_kandang']) ?>')">
            
            <div class="egg-stats">
                <div class="egg-row">
                    <img src="<?= BASE_URL ?>/View/Assets/icons/eggs.png" alt="Good Eggs">
                    <span class="count"><?= $goodEggs ?></span>
                    <span class="label good"><?= t('good') ?></span>
                </div>
                <div class="egg-row">
                    <img src="<?= BASE_URL ?>/View/Assets/icons/eggs_failed.png" alt="Bad Eggs">
                    <span class="count"><?= $badEggs ?></span>
                    <span class="label bad"><?= t('bad') ?></span>
                </div>
            </div>
            
            <button type="button" class="btn-sell" onclick="openSellModal(<?= $kandang['id_telur'] ?? 0 ?>, '<?= htmlspecialchars($kandang['nama_kandang']) ?>', <?= $goodEggs ?>)" <?= !$hasEgg || $goodEggs <= 0 ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : '' ?>><?= t('sell') ?></button>
        </div>
        <?php endforeach; ?>
    </div>

    <button class="fab-btn" onclick="openAddModal()">+</button>
    
    <?php if ($showHistory): ?>
    <a href="egg.php" class="history-btn">← <?= t('today') ?></a>
    <?php else: ?>
    <a href="egg.php?history=true" class="history-btn">📋 <?= t('history') ?></a>
    <?php endif; ?>

    <!-- Add Modal -->
    <div id="addModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h4><?= strtoupper(t('add_production')) ?></h4>
            </div>
            <form method="POST" id="addEggForm">
                <input type="hidden" name="action" value="add_egg">
                <input type="hidden" name="layed_at" value="<?= date('Y-m-d H:i:s') ?>">
                
                <div class="form-group">
                    <label><?= t('barn') ?></label>
                    <div class="custom-select-wrapper">
                        <input type="hidden" name="id_kandang" id="add_id_kandang" required>
                        <div class="custom-select-trigger" data-target="add_id_kandang">
                            <span class="selected-text"><?= t('barn') ?></span>
                            <div class="custom-select-arrow"></div>
                        </div>
                        <div class="custom-select-options">
                            <?php foreach ($kandangs as $k): ?>
                                <div class="custom-select-option" data-value="<?= $k['id_kandang'] ?>"><?= htmlspecialchars($k['nama_kandang']) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><?= t('good') ?></label>
                    <input type="number" name="jumlah_telur" id="add_jumlah_telur" placeholder="<?= t('enter_quantity') ?>" min="0" required>
                </div>
                
                <div class="form-group">
                    <label><?= t('bad') ?></label>
                    <input type="number" name="jumlah_buruk" id="add_jumlah_buruk" placeholder="<?= t('enter_quantity') ?>" min="0" value="0" required>
                </div>
                
                <div class="form-group">
                    <label><?= t('weight') ?> (<?= t('kg') ?>)</label>
                    <input type="number" name="berat" id="add_berat" placeholder="0.0" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label><?= t('date') ?></label>
                    <input type="text" value="<?= date('d/m/Y') ?>" disabled>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeAddModal()"><?= t('cancel') ?></button>
                    <button type="submit" class="btn-save"><?= t('save') ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="modalTitle"><?= strtoupper(t('edit_production')) ?></h4>
            </div>
            <form method="POST" id="editEggForm">
                <input type="hidden" name="action" id="formAction" value="add_egg">
                <input type="hidden" name="id_telur" id="edit_id_telur">
                <input type="hidden" name="id_kandang" id="edit_id_kandang">
                <input type="hidden" name="layed_at" value="<?= date('Y-m-d H:i:s') ?>">
                
                <div class="form-group">
                    <label><?= t('barn') ?></label>
                    <input type="text" id="edit_barn_name" disabled>
                </div>
                
                <div class="form-group">
                    <label><?= t('good') ?></label>
                    <input type="number" name="jumlah_telur" id="edit_jumlah_telur" placeholder="<?= t('enter_quantity') ?>" min="0" required>
                </div>
                
                <div class="form-group">
                    <label><?= t('bad') ?></label>
                    <input type="number" name="jumlah_buruk" id="edit_jumlah_buruk" placeholder="<?= t('enter_quantity') ?>" min="0" value="0" required>
                </div>
                
                <div class="form-group">
                    <label><?= t('weight') ?> (<?= t('kg') ?>)</label>
                    <input type="number" name="berat" id="edit_berat" placeholder="0.0" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label><?= t('date') ?></label>
                    <input type="text" value="<?= date('d/m/Y') ?>" disabled>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()"><?= t('cancel') ?></button>
                    <button type="submit" class="btn-save"><?= t('save') ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sell Modal -->
    <div id="sellModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h4><?= strtoupper(t('sell_eggs')) ?></h4>
            </div>
            <form method="POST" id="sellEggForm">
                <input type="hidden" name="action" value="sell_egg">
                <input type="hidden" name="id_telur" id="sell_id_telur">
                
                <div class="form-group">
                    <label><?= t('barn') ?></label>
                    <input type="text" id="sell_barn_name" disabled>
                </div>
                
                <div class="form-group">
                    <label><?= t('available') ?></label>
                    <input type="number" id="sell_available" disabled>
                </div>
                
                <div class="form-group">
                    <label><?= t('quantity_sold') ?></label>
                    <input type="number" name="jumlah_terjual" id="sell_jumlah" placeholder="<?= t('enter_quantity') ?>" min="1" required>
                </div>
                
                <div class="form-group">
                    <label><?= t('sale_price') ?> (Rp)</label>
                    <input type="number" name="harga_jual" id="sell_harga" placeholder="<?= t('enter_price') ?>" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label><?= t('date') ?></label>
                    <input type="text" value="<?= date('d/m/Y') ?>" disabled>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeSellModal()"><?= t('cancel') ?></button>
                    <button type="submit" class="btn-save"><?= t('sell') ?></button>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../../Components/bottom-nav-staff.php'; ?>

    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }
        
        function closeAddModal() {
            document.getElementById('addModal').classList.remove('active');
            document.getElementById('addEggForm').reset();
            // Reset dropdown
            const trigger = document.querySelector('#addModal .custom-select-trigger .selected-text');
            if (trigger) trigger.textContent = 'Select Barn';
        }
        
        function openEditModal(idKandang, idTelur, jumlahTelur, jumlahBuruk, layedAt, berat, barnName) {
            const isEdit = idTelur > 0;
            
            // Set modal title
            document.getElementById('modalTitle').textContent = isEdit ? 'EDIT EGG PRODUCTION' : 'ADD EGG PRODUCTION';
            
            // Set form action
            document.getElementById('formAction').value = isEdit ? 'edit_egg' : 'add_egg';
            
            // Set form values
            document.getElementById('edit_id_kandang').value = idKandang;
            document.getElementById('edit_barn_name').value = barnName;
            
            if (isEdit) {
                document.getElementById('edit_id_telur').value = idTelur;
                document.getElementById('edit_jumlah_telur').value = jumlahTelur;
                document.getElementById('edit_jumlah_buruk').value = jumlahBuruk;
                document.getElementById('edit_berat').value = berat;
            } else {
                document.getElementById('edit_id_telur').value = '';
                document.getElementById('edit_jumlah_telur').value = '';
                document.getElementById('edit_jumlah_buruk').value = '0';
                document.getElementById('edit_berat').value = '';
            }
            
            document.getElementById('editModal').classList.add('active');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
            document.getElementById('editEggForm').reset();
        }
        
        // Close modals when clicking outside
        document.getElementById('addModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddModal();
            }
        });
        
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
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
        
        // Sell Modal Functions
        function openSellModal(idTelur, barnName, availableEggs) {
            if (idTelur <= 0 || availableEggs <= 0) {
                alert('No eggs available to sell!');
                return;
            }
            
            document.getElementById('sell_id_telur').value = idTelur;
            document.getElementById('sell_barn_name').value = barnName;
            document.getElementById('sell_available').value = availableEggs;
            document.getElementById('sell_jumlah').max = availableEggs;
            document.getElementById('sellModal').classList.add('active');
        }
        
        function closeSellModal() {
            document.getElementById('sellModal').classList.remove('active');
            document.getElementById('sellEggForm').reset();
        }
        
        // Close sell modal when clicking outside
        document.getElementById('sellModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSellModal();
            }
        });
        
        // Validate sell quantity
        document.getElementById('sellEggForm').addEventListener('submit', function(e) {
            const available = parseInt(document.getElementById('sell_available').value);
            const quantity = parseInt(document.getElementById('sell_jumlah').value);
            
            if (quantity > available) {
                e.preventDefault();
                alert('Cannot sell more than available eggs!');
            }
        });
    </script>
</body>
</html>
