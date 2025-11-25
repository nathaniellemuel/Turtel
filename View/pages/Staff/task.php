<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../Connection/Connection.php';
require_once __DIR__ . '/../../../Controller/TugasController.php';
require_once __DIR__ . '/../../../Controller/PakanController.php';
require_once __DIR__ . '/../../../Controller/KandangController.php';

session_start();

// Access control: only allow staff
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL . '/View/pages/auth/index.php' : '/Turtel/View/pages/auth/index.php'));
    exit;
}

$tugasCtrl = new TugasController($conn);
$pakanCtrl = new PakanController($conn);
$kandangCtrl = new KandangController($conn);

$flash = '';
$userId = $_SESSION['user_id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'done_task') {
        $id_tugas = (int)($_POST['id_tugas'] ?? 0);
        $res = $tugasCtrl->setStatus($id_tugas, 'selesai');
        $flash = $res['message'] ?? 'Task completed!';
    } elseif ($action === 'cancel_task') {
        $id_tugas = (int)($_POST['id_tugas'] ?? 0);
        $res = $tugasCtrl->setStatus($id_tugas, 'proses');
        $flash = $res['message'] ?? 'Task status changed to in progress!';
    }
}

// Get tasks for current user
$userTasks = [];
if ($userId) {
    $allTasks = $tugasCtrl->getAll()['data'] ?? [];
    foreach ($allTasks as $task) {
        if ((int)$task['id_user'] === (int)$userId) {
            $userTasks[] = $task;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/View/Assets/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #FFFFFF;
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
        }
        .main-container {
            padding: 20px;
        }
        .task-card {
            background: linear-gradient(135deg, #6B2C2C 0%, #4A1F1F 100%);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 15px;
            color: white;
            position: relative;
            box-shadow: 0 4px 12px rgba(107, 44, 44, 0.3);
        }
        .task-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .barn-icon {
            width: 60px;
            height: 60px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .task-info h5 {
            margin: 0 0 8px 0;
            font-size: 1.4rem;
            font-weight: 700;
        }
        .task-info p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.95;
            display: flex;
            align-items: center;
        }
        .task-info img.feed-icon {
            width: 20px;
            height: 20px;
            margin-right: 8px;
        }
        .alert-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .task-note {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            padding: 12px;
            margin: 15px 0;
        }
        .task-note p {
            margin: 0 0 5px 0;
            font-size: 0.85rem;
            line-height: 1.5;
        }
        .task-note p:first-child {
            font-weight: 700;
            margin-bottom: 8px;
        }
        .task-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }
        .task-status {
            font-size: 0.9rem;
            font-weight: 600;
        }
        .status-pending {
            color: #FF6B6B;
        }
        .status-completed {
            color: #51CF66;
        }
        .status-proses {
            color: #FFD93D;
        }
        .btn-done {
            background: linear-gradient(135deg, #FF9F1C 0%, #FF8C00 100%);
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 25px;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.95rem;
            box-shadow: 0 4px 10px rgba(255, 140, 0, 0.3);
            transition: all 0.3s ease;
        }
        .btn-done:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 140, 0, 0.4);
        }
        .btn-cancel {
            background: linear-gradient(135deg, #FF9F1C 0%, #FF8C00 100%);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.9rem;
            box-shadow: 0 4px 10px rgba(255, 140, 0, 0.3);
            transition: all 0.3s ease;
        }
        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 140, 0, 0.4);
        }
        .btn-disabled {
            background: #999;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .btn-disabled:hover {
            transform: none;
            box-shadow: none;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        YOUR JOB FOR <?= date('d/m/y') ?>
    </div>

    <div class="main-container">
        <?php if (!empty($flash)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert" style="position: relative; padding-right: 40px;">
                <?= htmlspecialchars($flash) ?>
                <button type="button" class="btn-close-custom" onclick="this.parentElement.style.display='none'" style="position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 18px; cursor: pointer; color: #333; font-weight: bold; line-height: 1; padding: 0; width: 20px; height: 20px; opacity: 0.5; transition: opacity 0.2s;">&times;</button>
            </div>
        <?php endif; ?>

        <?php if (empty($userTasks)): ?>
            <div class="task-card">
                <p style="text-align: center; margin: 20px 0;">No tasks assigned yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($userTasks as $task): 
                $isPending = $task['status'] === 'pending';
                $isCompleted = $task['status'] === 'selesai';
                $isProses = $task['status'] === 'proses';
                $statusClass = $isPending ? 'status-pending' : ($isCompleted ? 'status-completed' : 'status-proses');
                $statusText = $isPending ? 'Pending' : ($isCompleted ? 'Completed' : 'In Progress');
            ?>
            <div class="task-card">
                <div class="task-header">
                    <img src="<?= BASE_URL ?>/View/Assets/icons/barn.png" alt="Barn" class="barn-icon">
                    <div class="task-info">
                        <h5><?= htmlspecialchars($task['nama_kandang'] ?? 'Unknown Barn') ?></h5>
                        <p>
                            <img src="<?= BASE_URL ?>/View/Assets/icons/chicken.png" alt="Feed" class="feed-icon">
                            <?= htmlspecialchars($task['jumlah_digunakan'] ?? 0) ?> KG <?= htmlspecialchars($task['nama_pakan'] ?? 'Unknown Feed') ?>
                        </p>
                    </div>
                </div>
                
                <?php if (!$isCompleted): ?>
                    <img src="<?= BASE_URL ?>/View/Assets/icons/tandaseru.png" alt="Alert" class="alert-icon">
                <?php endif; ?>
                
                <div class="task-note">
                    <p>NOTE :</p>
                    <p><?= htmlspecialchars($task['deskripsi_tugas'] ?? 'No description') ?></p>
                </div>
                
                <div class="task-footer">
                    <div class="task-status <?= $statusClass ?>">
                        Status : <?= $statusText ?>
                    </div>
                    
                    <?php if ($isCompleted): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="cancel_task">
                            <input type="hidden" name="id_tugas" value="<?= $task['id_tugas'] ?>">
                            <button type="submit" class="btn-cancel">CANCEL</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="done_task">
                            <input type="hidden" name="id_tugas" value="<?= $task['id_tugas'] ?>">
                            <button type="submit" class="btn-done">DONE</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../../Components/bottom-nav-staff.php'; ?>
</body>
</html>
