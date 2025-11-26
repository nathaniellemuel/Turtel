<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../Connection/Connection.php';
require_once __DIR__ . '/../../../Controller/UserController.php';
require_once __DIR__ . '/../../../Controller/PakanController.php';
require_once __DIR__ . '/../../../Controller/KandangController.php';
require_once __DIR__ . '/../../../Controller/TugasController.php';

session_start();

// Simple access control: only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL . '/View/pages/auth/index.php' : '/Turtel/View/pages/auth/index.php'));
    exit;
}

$userCtrl = new UserController($conn);
$pakanCtrl = new PakanController($conn);
$kandangCtrl = new KandangController($conn);
$tugasCtrl = new TugasController($conn);
$username = $_SESSION['username'] ?? 'Admin';

// Get pakan and kandang data for dropdowns
$pakanList = $pakanCtrl->getAll()['data'] ?? [];
$kandangList = $kandangCtrl->getAll()['data'] ?? [];

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
    } elseif ($action === 'delete_user') {
        $userId = $_POST['user_id'] ?? 0;
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // First, delete all tasks associated with this user
            $stmt1 = $conn->prepare("DELETE FROM tugas WHERE id_user = ?");
            $stmt1->bind_param("i", $userId);
            $stmt1->execute();
            $stmt1->close();
            
            // Then delete all laporan associated with this user
            $stmt2 = $conn->prepare("DELETE FROM laporan WHERE id_user = ?");
            $stmt2->bind_param("i", $userId);
            $stmt2->execute();
            $stmt2->close();
            
            // Finally, delete the user
            $stmt3 = $conn->prepare("DELETE FROM user WHERE id_user = ? AND role = 'staff'");
            $stmt3->bind_param("i", $userId);
            $stmt3->execute();
            $stmt3->close();
            
            // Commit transaction
            $conn->commit();
            $flash = 'Employee deleted successfully';
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $flash = 'Failed to delete employee: ' . $e->getMessage();
        }
    } elseif ($action === 'add_task') {
        $userId = $_POST['user_id'] ?? 0;
        $idPakan = $_POST['id_pakan'] ?? 0;
        $idKandang = $_POST['id_kandang'] ?? 0;
        $status = $_POST['task_status'] ?? 'proses';
        $deskripsi = $_POST['deskripsi_tugas'] ?? 'Pemberian pakan';
        $createdAt = date('Y-m-d H:i:s');
        
        // Insert task
        $stmt = $conn->prepare("INSERT INTO tugas (created_at, deskripsi_tugas, status, id_user, id_pakan, id_kandang) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiii", $createdAt, $deskripsi, $status, $userId, $idPakan, $idKandang);
        if ($stmt->execute()) {
            $flash = 'Task assigned successfully';
        } else {
            $flash = 'Failed to assign task';
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
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .employee-card:active {
            transform: scale(0.98);
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        @media (hover: hover) and (pointer: fine) {
            .employee-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 16px rgba(0,0,0,0.3);
            }
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
        
        /* Delete Button in Modal */
        .btn-delete-employee {
            position: absolute;
            top: 15px;
            right: 15px;
            background: transparent;
            border: none;
            width: 30px;
            height: 30px;
            cursor: pointer;
            transition: all 0.2s ease;
            padding: 0;
            z-index: 10;
        }
        .btn-delete-employee img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .btn-delete-employee:hover {
            transform: scale(1.15);
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
            
            <button class="tasks-button" onclick="openTaskModal(<?= $u['id_user'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')">TASKS</button>
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
            <button type="button" class="btn-delete-employee" onclick="deleteEmployee()">
                <img src="<?= BASE_URL ?>/View/Assets/icons/x.png" alt="Delete">
            </button>
            
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

    <!-- Task Modal -->
    <div class="modal-overlay" id="taskModal">
        <div class="modal-content">
            <div class="modal-header">
                <img src="<?= BASE_URL ?>/View/Assets/icons/staff.png" alt="Staff" class="modal-avatar">
                <div class="modal-header-info">
                    <h5 id="taskEmployeeName">Employee Name</h5>
                    <p>Assign Task</p>
                </div>
            </div>
            
            <form method="POST" id="taskForm">
                <input type="hidden" name="action" value="add_task">
                <input type="hidden" name="user_id" id="taskUserId">
                
                <div class="form-group">
                    <label>Pilih Pakan</label>
                    <input type="hidden" name="id_pakan" id="taskPakan">
                    <div class="custom-select-wrapper">
                        <div class="custom-select-trigger" id="pakanTrigger">
                            <span id="pakanSelected">Pilih pakan...</span>
                            <div class="custom-select-arrow"></div>
                        </div>
                        <div class="custom-select-options" id="pakanOptions">
                            <?php foreach ($pakanList as $pakan): ?>
                                <div class="custom-select-option" data-value="<?= $pakan['id_pakan'] ?>">
                                    <?= htmlspecialchars($pakan['nama_stock']) ?> - <?= $pakan['jumlah_digunakan'] ?> kg
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Pilih Kandang</label>
                    <input type="hidden" name="id_kandang" id="taskKandang">
                    <div class="custom-select-wrapper">
                        <div class="custom-select-trigger" id="kandangTrigger">
                            <span id="kandangSelected">Pilih kandang...</span>
                            <div class="custom-select-arrow"></div>
                        </div>
                        <div class="custom-select-options" id="kandangOptions">
                            <?php foreach ($kandangList as $kandang): ?>
                                <div class="custom-select-option" data-value="<?= $kandang['id_kandang'] ?>">
                                    <?= htmlspecialchars($kandang['nama_kandang']) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Date Pemberian Tugas</label>
                    <input type="text" value="<?= date('d/m/Y H:i') ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <input type="hidden" name="task_status" id="taskStatus" value="proses">
                    <div class="custom-select-wrapper">
                        <div class="custom-select-trigger" id="taskStatusTrigger">
                            <span id="taskStatusSelected">Proses</span>
                            <div class="custom-select-arrow"></div>
                        </div>
                        <div class="custom-select-options" id="taskStatusOptions">
                            <div class="custom-select-option selected" data-value="proses">Proses</div>
                            <div class="custom-select-option" data-value="selesai">Selesai</div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeTaskModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Custom Confirmation Modal -->
    <div class="confirm-overlay" id="confirmDeleteModal">
        <div class="confirm-box">
            <h4>Delete Employee?</h4>
            <p id="confirmDeleteMessage">Are you sure you want to delete this employee?</p>
            <div class="confirm-buttons">
                <button class="confirm-btn confirm-btn-no" onclick="closeConfirmDelete()">No</button>
                <button class="confirm-btn confirm-btn-yes" onclick="confirmDeleteAction()">Yes</button>
            </div>
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
        
        // Delete Employee Function
        let pendingDeleteUserId = null;
        
        function deleteEmployee() {
            const userId = document.getElementById('editUserId').value;
            const userName = document.getElementById('modalEmployeeName').textContent;
            
            pendingDeleteUserId = userId;
            document.getElementById('confirmDeleteMessage').textContent = 
                'Are you sure you want to delete employee "' + userName + '"?';
            document.getElementById('confirmDeleteModal').classList.add('active');
        }
        
        function closeConfirmDelete() {
            document.getElementById('confirmDeleteModal').classList.remove('active');
            pendingDeleteUserId = null;
        }
        
        function confirmDeleteAction() {
            if (pendingDeleteUserId) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete_user">' +
                                '<input type="hidden" name="user_id" value="' + pendingDeleteUserId + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
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
        
        // Task Modal Functions
        function openTaskModal(userId, username) {
            document.getElementById('taskUserId').value = userId;
            document.getElementById('taskEmployeeName').textContent = username;
            document.getElementById('taskModal').classList.add('active');
        }
        
        function closeTaskModal() {
            document.getElementById('taskModal').classList.remove('active');
            document.getElementById('pakanOptions').classList.remove('active');
            document.getElementById('pakanTrigger').classList.remove('active');
            document.getElementById('kandangOptions').classList.remove('active');
            document.getElementById('kandangTrigger').classList.remove('active');
            document.getElementById('taskStatusOptions').classList.remove('active');
            document.getElementById('taskStatusTrigger').classList.remove('active');
            document.getElementById('taskForm').reset();
        }
        
        // Pakan Dropdown
        const pakanTrigger = document.getElementById('pakanTrigger');
        const pakanOptions = document.getElementById('pakanOptions');
        
        pakanTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            pakanOptions.classList.toggle('active');
            pakanTrigger.classList.toggle('active');
        });
        
        document.querySelectorAll('#pakanOptions .custom-select-option').forEach(option => {
            option.addEventListener('click', function() {
                const value = this.dataset.value;
                const text = this.textContent;
                
                document.getElementById('taskPakan').value = value;
                document.getElementById('pakanSelected').textContent = text;
                
                document.querySelectorAll('#pakanOptions .custom-select-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                
                pakanOptions.classList.remove('active');
                pakanTrigger.classList.remove('active');
            });
        });
        
        // Kandang Dropdown
        const kandangTrigger = document.getElementById('kandangTrigger');
        const kandangOptions = document.getElementById('kandangOptions');
        
        kandangTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            kandangOptions.classList.toggle('active');
            kandangTrigger.classList.toggle('active');
        });
        
        document.querySelectorAll('#kandangOptions .custom-select-option').forEach(option => {
            option.addEventListener('click', function() {
                const value = this.dataset.value;
                const text = this.textContent;
                
                document.getElementById('taskKandang').value = value;
                document.getElementById('kandangSelected').textContent = text;
                
                document.querySelectorAll('#kandangOptions .custom-select-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                
                kandangOptions.classList.remove('active');
                kandangTrigger.classList.remove('active');
            });
        });
        
        // Task Status Dropdown
        const taskStatusTrigger = document.getElementById('taskStatusTrigger');
        const taskStatusOptions = document.getElementById('taskStatusOptions');
        
        taskStatusTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            taskStatusOptions.classList.toggle('active');
            taskStatusTrigger.classList.toggle('active');
        });
        
        document.querySelectorAll('#taskStatusOptions .custom-select-option').forEach(option => {
            option.addEventListener('click', function() {
                const value = this.dataset.value;
                const text = this.textContent;
                
                document.getElementById('taskStatus').value = value;
                document.getElementById('taskStatusSelected').textContent = text;
                
                document.querySelectorAll('#taskStatusOptions .custom-select-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                
                taskStatusOptions.classList.remove('active');
                taskStatusTrigger.classList.remove('active');
            });
        });
        
        // Close task modal when clicking outside
        document.getElementById('taskModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeTaskModal();
            }
        });
    </script>
</body>
</html>
