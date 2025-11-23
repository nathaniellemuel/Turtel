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
        $created = $_POST['created_at'] ?? date('Y-m-d H:i:s');
        $res = $kandangCtrl->create($nama, $jenis, (int)$jumlah, null, $created);
        $flash = $res['message'] ?? 'Done';
    } elseif ($action === 'edit_kandang') {
        $id = $_POST['id_kandang'] ?? 0;
        $nama = $_POST['nama_kandang'] ?? '';
        $jenis = $_POST['jenis_ayam'] ?? '';
        $jumlah = $_POST['jumlah_ayam'] ?? 0;
        $created = $_POST['created_at'] ?? date('Y-m-d H:i:s');
        $res = $kandangCtrl->update((int)$id, $nama, $jenis, (int)$jumlah, $created);
        $flash = $res['message'] ?? 'Updated';
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
        .barn-card {
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
        .barn-card:active {
            transform: scale(0.98);
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        @media (hover: hover) and (pointer: fine) {
            .barn-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 16px rgba(0,0,0,0.3);
            }
        }
        .barn-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .barn-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            margin-right: 15px;
        }
        .barn-info h5 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 700;
        }
        .barn-info p {
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
        .barn-details {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }
        .detail-row {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        .detail-row img {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }
        .remove-button {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background-color: #F39C12;
            color: white;
            border: none;
            border-radius: 25px;
            padding: 10px 30px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
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
        
        /* Add Barn Modal */
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
            cursor: not-allowed;
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
            transform: scale(1.02);
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
    </style>
</head>
<body>

    <div class="top-bar">
        <img src="<?= BASE_URL ?>/View/Assets/icons/barn.png" alt="Barn Icon">
        <span>BARN / COOP</span>
    </div>

    <div class="main-container">
        <?php if (!empty($flash)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert" style="position: relative; padding-right: 40px;">
                <?= htmlspecialchars($flash) ?>
                <button type="button" class="btn-close-custom" onclick="this.parentElement.style.display='none'" style="position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 18px; cursor: pointer; color: #333; font-weight: bold; line-height: 1; padding: 0; width: 20px; height: 20px; opacity: 0.5; transition: opacity 0.2s;">&times;</button>
            </div>
        <?php endif; ?>

        <?php foreach ($kandangs as $k): 
            $createdDate = date('d/m/Y', strtotime($k['created_at']));
            $species = ucfirst($k['jenis_ayam']);
        ?>
        <div class="barn-card">
            <div class="barn-header">
                <img src="<?= BASE_URL ?>/View/Assets/icons/barn.png" alt="Barn" class="barn-icon">
                <div class="barn-info">
                    <h5><?= htmlspecialchars($k['nama_kandang']) ?></h5>
                    <p>Species : <?= htmlspecialchars($species) ?></p>
                </div>
            </div>
            
            <img src="<?= BASE_URL ?>/View/Assets/icons/pencil.png" alt="Edit" class="edit-icon" onclick="openEditBarnModal(<?= $k['id_kandang'] ?>, '<?= htmlspecialchars($k['nama_kandang'], ENT_QUOTES) ?>', '<?= $k['jenis_ayam'] ?>', <?= $k['jumlah_ayam'] ?>, '<?= $createdDate ?>')">
            
            <div class="barn-details">
                <div class="detail-row">
                    <img src="<?= BASE_URL ?>/View/Assets/icons/chicken.png" alt="Chicken">
                    <span><?= htmlspecialchars($k['jumlah_ayam']) ?> Ekor</span>
                </div>
                <div class="detail-row">
                    <img src="<?= BASE_URL ?>/View/Assets/icons/date.png" alt="Date">
                    <span><?= $createdDate ?></span>
                </div>
            </div>
            
            <button type="button" class="remove-button" onclick="confirmDeleteBarn(<?= $k['id_kandang'] ?>, '<?= htmlspecialchars($k['nama_kandang'], ENT_QUOTES) ?>')">Remove</button>
        </div>
        <?php endforeach; ?>
    </div>

    <button class="add-button" onclick="openAddBarnModal()">+</button>

    <!-- Add Barn Modal -->
    <div class="modal-overlay" id="addBarnModal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>ADD BARN</h4>
            </div>
            
            <form method="POST" id="addBarnForm">
                <input type="hidden" name="action" value="add_kandang">
                
                <div class="form-group">
                    <label>Barn Name</label>
                    <input type="text" name="nama_kandang" id="barnName" placeholder="Enter barn name" required>
                </div>
                
                <div class="form-group">
                    <label>Species</label>
                    <input type="hidden" name="jenis_ayam" id="barnSpecies" value="negeri">
                    <div class="custom-select-wrapper">
                        <div class="custom-select-trigger" id="speciesTrigger">
                            <span id="speciesSelected">Negeri</span>
                            <div class="custom-select-arrow"></div>
                        </div>
                        <div class="custom-select-options" id="speciesOptions">
                            <div class="custom-select-option selected" data-value="negeri">Negeri</div>
                            <div class="custom-select-option" data-value="kampung">Kampung</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Total Chicken</label>
                    <input type="number" name="jumlah_ayam" id="totalChicken" placeholder="Enter total chicken" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Date Received</label>
                    <input type="text" value="<?= date('d/m/Y H:i') ?>" disabled>
                    <input type="hidden" name="created_at" value="<?= date('Y-m-d H:i:s') ?>">
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeAddBarnModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Barn Modal -->
    <div class="modal-overlay" id="editBarnModal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>EDIT BARN</h4>
            </div>
            
            <form method="POST" id="editBarnForm">
                <input type="hidden" name="action" value="edit_kandang">
                <input type="hidden" name="id_kandang" id="editBarnId">
                
                <div class="form-group">
                    <label>Barn Name</label>
                    <input type="text" name="nama_kandang" id="editBarnName" required>
                </div>
                
                <div class="form-group">
                    <label>Species</label>
                    <input type="hidden" name="jenis_ayam" id="editBarnSpecies">
                    <div class="custom-select-wrapper">
                        <div class="custom-select-trigger" id="editSpeciesTrigger">
                            <span id="editSpeciesSelected">Negeri</span>
                            <div class="custom-select-arrow"></div>
                        </div>
                        <div class="custom-select-options" id="editSpeciesOptions">
                            <div class="custom-select-option" data-value="negeri">Negeri</div>
                            <div class="custom-select-option" data-value="kampung">Kampung</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Total Chicken</label>
                    <input type="number" name="jumlah_ayam" id="editTotalChicken" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Date Received</label>
                    <input type="text" id="editDateReceived" disabled>
                    <input type="hidden" name="created_at" id="editCreatedAt">
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeEditBarnModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Custom Confirmation Modal -->
    <div class="confirm-overlay" id="confirmDeleteModal">
        <div class="confirm-box">
            <h4>Remove Barn?</h4>
            <p id="confirmDeleteMessage">Are you sure you want to remove this barn?</p>
            <div class="confirm-buttons">
                <button class="confirm-btn confirm-btn-no" onclick="closeConfirmDelete()">No</button>
                <button class="confirm-btn confirm-btn-yes" onclick="confirmDeleteAction()">Yes</button>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../Components/bottom-nav-admin.php'; ?>

    <script src="<?= BASE_URL ?>/View/Assets/js/bootstrap.bundle.min.js"></script>
    <script>
        let pendingDeleteBarnId = null;
        
        function confirmDeleteBarn(barnId, barnName) {
            pendingDeleteBarnId = barnId;
            document.getElementById('confirmDeleteMessage').textContent = 
                'Are you sure you want to remove barn "' + barnName + '"?';
            document.getElementById('confirmDeleteModal').classList.add('active');
        }
        
        function closeConfirmDelete() {
            document.getElementById('confirmDeleteModal').classList.remove('active');
            pendingDeleteBarnId = null;
        }
        
        function confirmDeleteAction() {
            if (pendingDeleteBarnId) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete_kandang">' +
                                '<input type="hidden" name="id_kandang" value="' + pendingDeleteBarnId + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Add Barn Modal Functions
        function openAddBarnModal() {
            document.getElementById('addBarnModal').classList.add('active');
        }
        
        function closeAddBarnModal() {
            document.getElementById('addBarnModal').classList.remove('active');
            document.getElementById('addBarnForm').reset();
            document.getElementById('speciesOptions').classList.remove('active');
            document.getElementById('speciesTrigger').classList.remove('active');
            // Reset species to default
            document.getElementById('barnSpecies').value = 'negeri';
            document.getElementById('speciesSelected').textContent = 'Negeri';
        }
        
        // Custom Dropdown for Species
        const speciesTrigger = document.getElementById('speciesTrigger');
        const speciesOptions = document.getElementById('speciesOptions');
        const speciesInput = document.getElementById('barnSpecies');
        const speciesSelected = document.getElementById('speciesSelected');
        
        speciesTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            speciesTrigger.classList.toggle('active');
            speciesOptions.classList.toggle('active');
        });
        
        document.querySelectorAll('#speciesOptions .custom-select-option').forEach(option => {
            option.addEventListener('click', function(e) {
                e.stopPropagation();
                const value = this.dataset.value;
                const text = this.textContent;
                
                speciesInput.value = value;
                speciesSelected.textContent = text;
                
                document.querySelectorAll('#speciesOptions .custom-select-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                
                speciesTrigger.classList.remove('active');
                speciesOptions.classList.remove('active');
            });
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.custom-select-wrapper')) {
                document.querySelectorAll('.custom-select-trigger').forEach(trigger => {
                    trigger.classList.remove('active');
                });
                document.querySelectorAll('.custom-select-options').forEach(options => {
                    options.classList.remove('active');
                });
            }
        });
        
        // Close modal when clicking outside
        document.getElementById('addBarnModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddBarnModal();
            }
        });
        
        // Edit Barn Modal Functions
        function openEditBarnModal(barnId, barnName, species, totalChicken, dateReceived) {
            document.getElementById('editBarnId').value = barnId;
            document.getElementById('editBarnName').value = barnName;
            document.getElementById('editBarnSpecies').value = species;
            document.getElementById('editTotalChicken').value = totalChicken;
            document.getElementById('editDateReceived').value = dateReceived;
            
            // Convert date from dd/mm/yyyy to Y-m-d H:i:s format for hidden input
            const dateParts = dateReceived.split('/');
            if (dateParts.length === 3) {
                const formattedDate = dateParts[2] + '-' + dateParts[1] + '-' + dateParts[0] + ' 00:00:00';
                document.getElementById('editCreatedAt').value = formattedDate;
            }
            
            // Set species dropdown
            const speciesText = species.charAt(0).toUpperCase() + species.slice(1);
            document.getElementById('editSpeciesSelected').textContent = speciesText;
            
            // Update selected state
            document.querySelectorAll('#editSpeciesOptions .custom-select-option').forEach(opt => {
                opt.classList.remove('selected');
                if (opt.dataset.value === species) {
                    opt.classList.add('selected');
                }
            });
            
            document.getElementById('editBarnModal').classList.add('active');
        }
        
        function closeEditBarnModal() {
            document.getElementById('editBarnModal').classList.remove('active');
            document.getElementById('editBarnForm').reset();
            document.getElementById('editSpeciesOptions').classList.remove('active');
            document.getElementById('editSpeciesTrigger').classList.remove('active');
        }
        
        // Custom Dropdown for Edit Species
        const editSpeciesTrigger = document.getElementById('editSpeciesTrigger');
        const editSpeciesOptions = document.getElementById('editSpeciesOptions');
        const editSpeciesInput = document.getElementById('editBarnSpecies');
        const editSpeciesSelected = document.getElementById('editSpeciesSelected');
        
        editSpeciesTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            editSpeciesTrigger.classList.toggle('active');
            editSpeciesOptions.classList.toggle('active');
        });
        
        document.querySelectorAll('#editSpeciesOptions .custom-select-option').forEach(option => {
            option.addEventListener('click', function(e) {
                e.stopPropagation();
                const value = this.dataset.value;
                const text = this.textContent;
                
                editSpeciesInput.value = value;
                editSpeciesSelected.textContent = text;
                
                document.querySelectorAll('#editSpeciesOptions .custom-select-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                
                editSpeciesTrigger.classList.remove('active');
                editSpeciesOptions.classList.remove('active');
            });
        });
        
        // Close edit modal when clicking outside
        document.getElementById('editBarnModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditBarnModal();
            }
        });
    </script>
</body>
</html>
