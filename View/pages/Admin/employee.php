<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../Connection/Connection.php';
require_once __DIR__ . '/../../../Controller/UserController.php';

session_start();

// Simple access control: only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL . '/View/pages/auth/index.php' : '/Turtel/View/pages/auth/index.php'));
    exit;
}

$userCtrl = new UserController($conn);
$username = $_SESSION['username'] ?? 'Admin';

// Handle form submissions
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_user') {
        $newUsername = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? 'default123'; // Default password
        $status = $_POST['status'] ?? 'aktif';
        $res = $userCtrl->register($newUsername, $email, $password, 'staff', $status);
        $flash = $res['message'] ?? 'Employee added successfully';
    } elseif ($action === 'edit_user') {
        $userId = $_POST['user_id'] ?? 0;
        $newName = $_POST['employee_name'] ?? '';
        $newStatus = $_POST['status'] ?? 'aktif';
        
        // Update user in database
        $stmt = $conn->prepare("UPDATE user SET username = ?, status = ? WHERE id_user = ?");
        $stmt->bind_param("ssi", $newName, $newStatus, $userId);
        if ($stmt->execute()) {
            $flash = 'Employee updated successfully';
        } else {
            $flash = 'Failed to update employee';
        }
        $stmt->close();
    }
}

// Get all users
$usersRes = $conn->query("SELECT id_user, username, email, role, status, created_at FROM user WHERE role = 'staff' ORDER BY id_user DESC");
$usersArr = [];
if ($usersRes) {
    while ($r = $usersRes->fetch_assoc()) {
        $usersArr[] = $r;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management</title>
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
        .employee-card {
            background: linear-gradient(135deg, #6B2C2C 0%, #4A1F1F 100%);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 15px;
            color: white;
            position: relative;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .employee-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .employee-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
        }
        .employee-info h5 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 700;
        }
        .employee-info p {
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
        }
        .employee-details {
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
        .tasks-button {
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
        .tasks-button:hover {
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
        }
        .add-button:hover {
            background-color: #8B3A3A;
        }
        
        /* Alert Close Button */
        .btn-close-custom:hover {
            opacity: 1 !important;
        }
        
        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
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
            position: relative;
        }
        .modal-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        .modal-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
        }
        .modal-header-info h5 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 700;
        }
        .modal-header-info p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            margin-bottom: 5px;
            opacity: 0.8;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: none !important;
            border-radius: 25px;
            background-color: rgba(255, 255, 255, 0.9);
            color: #333;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9rem;
            transition: all 0.3s ease !important;
            cursor: pointer;
        }
        .form-group select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }
        .form-group select:hover {
            background-color: rgba(255, 255, 255, 1) !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
            transform: scale(1.02);
        }
        .form-group select:focus {
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(243, 156, 18, 0.3) !important;
        }
        .form-group input:disabled {
            background-color: rgba(255, 255, 255, 0.6) !important;
            color: #666 !important;
            cursor: not-allowed;
        }
        .form-group input:not(:disabled):hover {
            background-color: rgba(255, 255, 255, 1) !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
            transform: scale(1.02);
        }
        .form-group input:not(:disabled):focus {
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(243, 156, 18, 0.3) !important;
        }
        
        /* Custom Dropdown Styles */
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
        }
        .custom-select-trigger:hover {
            background-color: rgba(255, 255, 255, 1) !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
            transform: scale(1.02);
        }
        .custom-select-trigger.active {
            box-shadow: 0 0 0 3px rgba(243, 156, 18, 0.3) !important;
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
            background: transparent !important;
            color: #F39C12 !important;
            border: none !important;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer !important;
            padding: 10px 20px !important;
            border-radius: 10px !important;
            transition: all 0.2s ease !important;
            -webkit-tap-highlight-color: transparent;
        }
        button.btn-cancel:hover,
        button.btn-cancel:active,
        button.btn-cancel:focus {
            background-color: rgba(243, 156, 18, 0.3) !important;
            color: #F39C12 !important;
        }
        @media (hover: none) {
            button.btn-cancel:active {
                background-color: rgba(243, 156, 18, 0.5) !important;
            }
        }
        .btn-save {
            background-color: white !important;
            color: #6B2C2C !important;
            border: none !important;
            border-radius: 8px;
            padding: 10px 40px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease !important;
        }
        .btn-save:hover {
            background-color: #763A12 !important;
            color: white !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
        }
    </style>
</head>
<body>

    <div class="top-bar">
        <img src="<?= BASE_URL ?>/View/Assets/icons/staff.png" alt="Employee Icon">
        <span>Employee</span>
    </div>

    <div class="main-container">
        <?php if (!empty($flash)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert" style="position: relative; padding-right: 40px;">
                <?= htmlspecialchars($flash) ?>
                <button type="button" class="btn-close-custom" onclick="this.parentElement.style.display='none'" style="position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 18px; cursor: pointer; color: #333; font-weight: bold; line-height: 1; padding: 0; width: 20px; height: 20px; opacity: 0.5; transition: opacity 0.2s;">&times;</button>
            </div>
        <?php endif; ?>

        <?php foreach ($usersArr as $u): 
            $createdDate = date('d/m/Y', strtotime($u['created_at']));
        ?>
        <div class="employee-card">
            <div class="employee-header">
                <img src="<?= BASE_URL ?>/View/Assets/icons/staff.png" alt="Staff" class="employee-avatar">
                <div class="employee-info">
                    <h5><?= htmlspecialchars($u['username']) ?></h5>
                    <p>Position : Staff</p>
                </div>
            </div>
            
            <img src="<?= BASE_URL ?>/View/Assets/icons/pencil.png" alt="Edit" class="edit-icon" 
                 onclick="openEditModal(<?= $u['id_user'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>', '<?= $createdDate ?>', '<?= $u['status'] ?>')">
            
            <div class="employee-details">
                <div class="detail-row">
                    <strong>STATUS : <?= strtoupper($u['status']) ?></strong>
                </div>
                <div class="detail-row">
                    <img src="<?= BASE_URL ?>/View/Assets/icons/date.png" alt="Date">
                    <span><?= $createdDate ?></span>
                </div>
            </div>
            
            <button class="tasks-button">TASKS</button>
        </div>
        <?php endforeach; ?>
    </div>

    <button class="add-button" onclick="openAddModal()">+</button>

    <!-- Add Employee Modal -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <img src="<?= BASE_URL ?>/View/Assets/icons/staff.png" alt="Staff" class="modal-avatar">
                <div class="modal-header-info">
                    <h5>Add employee</h5>
                    <p>Position : Peternak</p>
                </div>
            </div>
            
            <form method="POST" id="addForm">
                <input type="hidden" name="action" value="add_user">
                
                <div class="form-group">
                    <label>Name of employee</label>
                    <input type="text" name="username" id="addEmployeeName" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="addEmail" required>
                </div>
                
                <div class="form-group">
                    <label>Position</label>
                    <input type="text" value="Staff" disabled>
                </div>
                
                <div class="form-group">
                    <label>Employment start date</label>
                    <input type="text" id="addEmploymentDate" value="<?= date('d/m/Y') ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <input type="hidden" name="status" id="addStatus" value="aktif">
                    <div class="custom-select-wrapper">
                        <div class="custom-select-trigger" id="addStatusTrigger">
                            <span id="addStatusSelected">Aktif</span>
                            <div class="custom-select-arrow"></div>
                        </div>
                        <div class="custom-select-options" id="addStatusOptions">
                            <div class="custom-select-option selected" data-value="aktif">Aktif</div>
                            <div class="custom-select-option" data-value="nonaktif">Nonaktif</div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <img src="<?= BASE_URL ?>/View/Assets/icons/staff.png" alt="Staff" class="modal-avatar">
                <div class="modal-header-info">
                    <h5 id="modalEmployeeName">Employee Name</h5>
                    <p>Position : Peternak</p>
                </div>
            </div>
            
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="editUserId">
                
                <div class="form-group">
                    <label>Name of employee</label>
                    <input type="text" name="employee_name" id="editEmployeeName" required>
                </div>
                
                <div class="form-group">
                    <label>Position</label>
                    <input type="text" value="Staff" disabled>
                </div>
                
                <div class="form-group">
                    <label>Employment start date</label>
                    <input type="text" id="editEmploymentDate" disabled>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <input type="hidden" name="status" id="editStatus">
                    <div class="custom-select-wrapper">
                        <div class="custom-select-trigger" id="statusTrigger">
                            <span id="statusSelected">Aktif</span>
                            <div class="custom-select-arrow"></div>
                        </div>
                        <div class="custom-select-options" id="statusOptions">
                            <div class="custom-select-option selected" data-value="aktif">Aktif</div>
                            <div class="custom-select-option" data-value="nonaktif">Nonaktif</div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../../Components/bottom-nav-admin.php'; ?>

    <script src="<?= BASE_URL ?>/View/Assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function openEditModal(userId, username, employmentDate, status) {
            document.getElementById('editUserId').value = userId;
            document.getElementById('modalEmployeeName').textContent = username;
            document.getElementById('editEmployeeName').value = username;
            document.getElementById('editEmploymentDate').value = employmentDate;
            
            // Set status in custom dropdown
            document.getElementById('editStatus').value = status;
            const statusText = status === 'aktif' ? 'Aktif' : 'Nonaktif';
            document.getElementById('statusSelected').textContent = statusText;
            
            // Update selected state
            document.querySelectorAll('.custom-select-option').forEach(opt => {
                opt.classList.remove('selected');
                if (opt.dataset.value === status) {
                    opt.classList.add('selected');
                }
            });
            
            document.getElementById('editModal').classList.add('active');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
            document.getElementById('statusOptions').classList.remove('active');
            document.getElementById('statusTrigger').classList.remove('active');
        }
        
        // Custom dropdown functionality
        const statusTrigger = document.getElementById('statusTrigger');
        const statusOptions = document.getElementById('statusOptions');
        
        statusTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            statusOptions.classList.toggle('active');
            statusTrigger.classList.toggle('active');
        });
        
        document.querySelectorAll('.custom-select-option').forEach(option => {
            option.addEventListener('click', function() {
                const value = this.dataset.value;
                const text = this.textContent;
                
                document.getElementById('editStatus').value = value;
                document.getElementById('statusSelected').textContent = text;
                
                // Update selected state
                document.querySelectorAll('.custom-select-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                
                // Close dropdown
                statusOptions.classList.remove('active');
                statusTrigger.classList.remove('active');
            });
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.custom-select-wrapper')) {
                statusOptions.classList.remove('active');
                statusTrigger.classList.remove('active');
            }
        });
        
        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
        
        // Add Employee Modal Functions
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }
        
        function closeAddModal() {
            document.getElementById('addModal').classList.remove('active');
            document.getElementById('addStatusOptions').classList.remove('active');
            document.getElementById('addStatusTrigger').classList.remove('active');
            document.getElementById('addForm').reset();
            document.getElementById('addStatus').value = 'aktif';
            document.getElementById('addStatusSelected').textContent = 'Aktif';
        }
        
        // Add Status Dropdown functionality
        const addStatusTrigger = document.getElementById('addStatusTrigger');
        const addStatusOptions = document.getElementById('addStatusOptions');
        
        addStatusTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            addStatusOptions.classList.toggle('active');
            addStatusTrigger.classList.toggle('active');
        });
        
        document.querySelectorAll('#addStatusOptions .custom-select-option').forEach(option => {
            option.addEventListener('click', function() {
                const value = this.dataset.value;
                const text = this.textContent;
                
                document.getElementById('addStatus').value = value;
                document.getElementById('addStatusSelected').textContent = text;
                
                // Update selected state
                document.querySelectorAll('#addStatusOptions .custom-select-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                
                // Close dropdown
                addStatusOptions.classList.remove('active');
                addStatusTrigger.classList.remove('active');
            });
        });
        
        // Close add modal when clicking outside
        document.getElementById('addModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddModal();
            }
        });
        
        // Update click outside to close both dropdowns
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.custom-select-wrapper')) {
                // Close edit modal dropdown
                document.getElementById('statusOptions').classList.remove('active');
                document.getElementById('statusTrigger').classList.remove('active');
                
                // Close add modal dropdown
                if (document.getElementById('addStatusOptions')) {
                    addStatusOptions.classList.remove('active');
                    addStatusTrigger.classList.remove('active');
                }
            }
        });
    </script>
</body>
</html>
