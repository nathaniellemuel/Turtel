<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../Connection/Connection.php';
require_once __DIR__ . '/../../../Controller/StokController.php';

session_start();

// Simple access control: only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL . '/View/pages/auth/index.php' : '/Turtel/View/pages/auth/index.php'));
    exit;
}

$stokCtrl = new StokController($conn);
$username = $_SESSION['username'] ?? 'Admin';

// Handle form submissions
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_stock') {
        $kategori = $_POST['kategori'] ?? '';
        $nama_stock = $_POST['nama_stock'] ?? '';
        $jumlah = (int)($_POST['jumlah'] ?? 0);
        
        $res = $stokCtrl->create($kategori, $nama_stock, $jumlah);
        $flash = $res['message'] ?? 'Stock added';
    } elseif ($action === 'edit_stock') {
        $id = (int)($_POST['id_stock'] ?? 0);
        $kategori = $_POST['kategori'] ?? '';
        $nama_stock = $_POST['nama_stock'] ?? '';
        $jumlah = (int)($_POST['jumlah'] ?? 0);
        
        $res = $stokCtrl->update($id, $nama_stock, $kategori, $jumlah);
        $flash = $res['message'] ?? 'Stock updated';
    } elseif ($action === 'refill_stock') {
        $id = (int)($_POST['id_stock'] ?? 0);
        $refill_amount = (int)($_POST['refill_amount'] ?? 0);
        
        // Get current stock
        $stokData = $stokCtrl->getById($id);
        if ($stokData['success']) {
            $currentJumlah = (int)$stokData['data']['jumlah'];
            $newJumlah = $currentJumlah + $refill_amount;
            
            $res = $stokCtrl->updateJumlah($id, $newJumlah);
            $flash = "Stock refilled successfully! Added {$refill_amount}kg";
        } else {
            $flash = 'Stock not found';
        }
    } elseif ($action === 'delete_stock') {
        $id = (int)($_POST['id_stock'] ?? 0);
        $res = $stokCtrl->delete($id);
        $flash = $res['message'] ?? 'Stock deleted';
    }
}

// Get all stocks with used amount from pakan
$stockQuery = "SELECT s.id_stock, s.kategori, s.nama_stock, s.jumlah,
               COALESCE(SUM(p.jumlah_digunakan), 0) as jumlah_digunakan
               FROM stok s
               LEFT JOIN pakan p ON s.id_stock = p.id_stock
               GROUP BY s.id_stock, s.kategori, s.nama_stock, s.jumlah
               ORDER BY s.id_stock DESC";
$stockResult = $conn->query($stockQuery);
$stocks = [];
if ($stockResult) {
    while ($r = $stockResult->fetch_assoc()) {
        $stocks[] = $r;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management</title>
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
        .stock-card {
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
        .stock-card:active {
            transform: scale(0.98);
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        @media (hover: hover) and (pointer: fine) {
            .stock-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 16px rgba(0,0,0,0.3);
            }
        }
        .stock-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            position: relative;
            padding-right: 40px;
        }
        .stock-icon {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .stock-info {
            flex-grow: 1;
            padding-top: 5px;
        }
        .stock-info h5 {
            margin: 0 0 8px 0;
            font-size: 1.4rem;
            font-weight: 700;
        }
        .stock-info p {
            margin: 0;
            font-size: 0.85rem;
            opacity: 0.9;
            line-height: 1.4;
        }
        .edit-icon {
            position: absolute;
            top: 0px;
            right: 0px;
            width: 30px;
            height: 30px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .edit-icon:active {
            transform: scale(0.85) rotate(15deg);
        }
        @media (hover: hover) and (pointer: fine) {
            .edit-icon:hover {
                transform: scale(1.2) rotate(15deg);
            }
        }
        .stock-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stock-date {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
        }
        .stock-date img {
            width: 20px;
            height: 20px;
            margin-right: 5px;
        }
        .stock-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }
        .availability-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.65rem;
            font-weight: 600;
            white-space: nowrap;
            display: inline-block;
            margin-bottom: 10px;
        }
        .availability-badge.available {
            background-color: #27AE60;
            color: white;
        }
        .availability-badge.unavailable {
            background-color: #E74C3C;
            color: white;
        }
        .refill-button {
            background-color: #F39C12;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9rem;
            white-space: nowrap;
        }
        .refill-button:hover {
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
        
        /* Modal Styles */
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
            border-radius: 20px;
            padding: 30px;
            width: 90%;
            max-width: 400px;
            color: white;
            animation: slideUp 0.3s ease-out;
        }
        @keyframes slideUp {
            from {
                transform: translateY(50px);
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
        .modal-buttons {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 30px;
        }
        .btn-cancel, .btn-save {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Montserrat', sans-serif;
            transition: all 0.2s ease;
        }
        .btn-cancel {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        .btn-cancel:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }
        .btn-save {
            background-color: #F39C12;
            color: white;
        }
        .btn-save:hover {
            background-color: #E67E22;
        }
        
        /* Confirmation Modal */
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
            width: 90%;
            max-width: 350px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .confirm-box h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.3rem;
        }
        .confirm-box p {
            color: #666;
            margin-bottom: 25px;
            font-size: 0.95rem;
        }
        .confirm-buttons {
            display: flex;
            gap: 15px;
        }
        .confirm-buttons button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Montserrat', sans-serif;
            transition: all 0.2s ease;
        }
        .btn-confirm-cancel {
            background-color: #95a5a6;
            color: white;
        }
        .btn-confirm-cancel:hover {
            background-color: #7f8c8d;
        }
        .btn-confirm-delete {
            background-color: #e74c3c;
            color: white;
        }
        .btn-confirm-delete:hover {
            background-color: #c0392b;
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
            <span>STOCK</span>
        </div>
        <div class="top-bar-right">
            <img src="<?= BASE_URL ?>/View/Assets/icons/stock.png" alt="Stock Icon">
        </div>
    </div>

    <div class="main-container">
        <?php if (!empty($flash)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert" style="position: relative; padding-right: 40px;">
                <?= htmlspecialchars($flash) ?>
                <button type="button" class="btn-close-custom" onclick="this.parentElement.style.display='none'" style="position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 18px; cursor: pointer; color: #333; font-weight: bold; line-height: 1; padding: 0; width: 20px; height: 20px; opacity: 0.5; transition: opacity 0.2s;">&times;</button>
            </div>
        <?php endif; ?>

        <?php foreach ($stocks as $s): 
            $isAvailable = (int)$s['jumlah'] > 0;
            $availabilityClass = $isAvailable ? 'available' : 'unavailable';
            $availabilityText = $isAvailable ? 'available' : 'unavailable';
            $usedAmount = (int)$s['jumlah_digunakan'];
        ?>
        <div class="stock-card">
            <div class="stock-header">
                <img src="<?= BASE_URL ?>/View/Assets/icons/stock.png" alt="Stock" class="stock-icon">
                <div class="stock-info">
                    <h5><?= htmlspecialchars($s['nama_stock']) ?></h5>
                    <p>Total : <?= htmlspecialchars($s['jumlah']) ?>kg | Used <?= $usedAmount ?>kg</p>
                </div>
                <img src="<?= BASE_URL ?>/View/Assets/icons/pencil.png" alt="Edit" class="edit-icon" onclick="openEditStockModal(<?= $s['id_stock'] ?>, '<?= htmlspecialchars($s['nama_stock'], ENT_QUOTES) ?>', '<?= htmlspecialchars($s['kategori'], ENT_QUOTES) ?>', <?= $s['jumlah'] ?>)">
            </div>
            
            <span class="availability-badge <?= $availabilityClass ?>"><?= $availabilityText ?></span>
            
            <div class="stock-footer">
                <div class="stock-date">
                    <img src="<?= BASE_URL ?>/View/Assets/icons/date.png" alt="Date">
                    <span><?= date('d/m/Y') ?></span>
                </div>
                <div class="stock-actions">
                    <button type="button" class="refill-button" onclick="openRefillModal(<?= $s['id_stock'] ?>, '<?= htmlspecialchars($s['nama_stock'], ENT_QUOTES) ?>')">Refill</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <button class="add-button" onclick="openAddStockModal()">+</button>

    <!-- Add Stock Modal -->
    <div class="modal-overlay" id="addStockModal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>ADD STOCK</h4>
            </div>
            
            <form method="POST" id="addStockForm">
                <input type="hidden" name="action" value="add_stock">
                
                <div class="form-group">
                    <label>Category</label>
                    <div class="custom-select-wrapper">
                        <input type="hidden" name="kategori" id="kategori" required>
                        <div class="custom-select-trigger" data-target="kategori">
                            <span class="selected-text">Select Category</span>
                            <div class="custom-select-arrow"></div>
                        </div>
                        <div class="custom-select-options">
                            <div class="custom-select-option" data-value="pakan">Pakan</div>
                            <div class="custom-select-option" data-value="obat">Obat</div>
                            <div class="custom-select-option" data-value="vitamin">Vitamin</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Stock Name</label>
                    <input type="text" name="nama_stock" placeholder="Enter stock name" required>
                </div>
                
                <div class="form-group">
                    <label>Total (kg)</label>
                    <input type="number" name="jumlah" placeholder="Enter total in kg" min="0" required>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeAddStockModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Stock Modal -->
    <div class="modal-overlay" id="editStockModal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>EDIT STOCK</h4>
            </div>
            
            <form method="POST" id="editStockForm">
                <input type="hidden" name="action" value="edit_stock">
                <input type="hidden" name="id_stock" id="edit_id_stock">
                
                <div class="form-group">
                    <label>Stock Name</label>
                    <input type="text" name="nama_stock" id="edit_nama_stock" placeholder="Enter stock name" required>
                </div>
                
                <div class="form-group">
                    <label>Category</label>
                    <div class="custom-select-wrapper">
                        <input type="hidden" name="kategori" id="edit_kategori" required>
                        <div class="custom-select-trigger" data-target="edit_kategori">
                            <span class="selected-text">Select Category</span>
                            <div class="custom-select-arrow"></div>
                        </div>
                        <div class="custom-select-options">
                            <div class="custom-select-option" data-value="pakan">Pakan</div>
                            <div class="custom-select-option" data-value="obat">Obat</div>
                            <div class="custom-select-option" data-value="vitamin">Vitamin</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Total (kg)</label>
                    <input type="number" name="jumlah" id="edit_jumlah" placeholder="Enter total in kg" min="0" required>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeEditStockModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Refill Stock Modal -->
    <div class="modal-overlay" id="refillStockModal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>REFILL STOCK</h4>
            </div>
            
            <form method="POST" id="refillStockForm">
                <input type="hidden" name="action" value="refill_stock">
                <input type="hidden" name="id_stock" id="refill_id_stock">
                
                <div class="form-group">
                    <label>Stock Name</label>
                    <input type="text" id="refill_nama_stock" disabled>
                </div>
                
                <div class="form-group">
                    <label>Refill Amount (kg)</label>
                    <input type="number" name="refill_amount" placeholder="Enter amount to add" min="1" required>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeRefillModal()">Cancel</button>
                    <button type="submit" class="btn-save">Refill</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="confirm-overlay" id="deleteConfirmModal">
        <div class="confirm-box">
            <h3>Confirm Delete</h3>
            <p>Are you sure you want to delete stock "<span id="deleteStockName"></span>"?</p>
            <div class="confirm-buttons">
                <button class="btn-confirm-cancel" onclick="closeDeleteConfirm()">Cancel</button>
                <button class="btn-confirm-delete" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>

    <!-- Hidden form for delete -->
    <form method="POST" id="deleteStockForm" style="display: none;">
        <input type="hidden" name="action" value="delete_stock">
        <input type="hidden" name="id_stock" id="delete_id_stock">
    </form>

    <?php include __DIR__ . '/../../Components/bottom-nav-admin.php'; ?>

    <script>
        // Add Stock Modal
        function openAddStockModal() {
            document.getElementById('addStockModal').classList.add('active');
        }
        
        function closeAddStockModal() {
            document.getElementById('addStockModal').classList.remove('active');
        }

        // Edit Stock Modal
        function openEditStockModal(id, nama, kategori, jumlah) {
            document.getElementById('edit_id_stock').value = id;
            document.getElementById('edit_nama_stock').value = nama;
            document.getElementById('edit_kategori').value = kategori;
            document.getElementById('edit_jumlah').value = jumlah;
            
            // Set custom dropdown selected value
            const editWrapper = document.querySelector('#editStockModal .custom-select-wrapper');
            const editTrigger = editWrapper.querySelector('.custom-select-trigger .selected-text');
            const kategoriText = kategori.charAt(0).toUpperCase() + kategori.slice(1);
            editTrigger.textContent = kategoriText;
            
            document.getElementById('editStockModal').classList.add('active');
        }
        
        function closeEditStockModal() {
            document.getElementById('editStockModal').classList.remove('active');
        }

        // Refill Stock Modal
        function openRefillModal(id, nama) {
            document.getElementById('refill_id_stock').value = id;
            document.getElementById('refill_nama_stock').value = nama;
            document.getElementById('refillStockModal').classList.add('active');
        }
        
        function closeRefillModal() {
            document.getElementById('refillStockModal').classList.remove('active');
        }

        // Delete Confirmation
        let deleteStockId = null;
        
        function confirmDeleteStock(id, nama) {
            deleteStockId = id;
            document.getElementById('deleteStockName').textContent = nama;
            document.getElementById('deleteConfirmModal').classList.add('active');
        }
        
        function closeDeleteConfirm() {
            document.getElementById('deleteConfirmModal').classList.remove('active');
            deleteStockId = null;
        }
        
        function confirmDelete() {
            if (deleteStockId) {
                document.getElementById('delete_id_stock').value = deleteStockId;
                document.getElementById('deleteStockForm').submit();
            }
        }

        // Close modals when clicking outside
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
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
    </script>
</body>
</html>
