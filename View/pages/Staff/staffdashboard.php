<?php
// Temporary: show all PHP errors in browser to diagnose blank white screen
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Follow the same include approach as your `index.php`: require the controller which
// in turn requires Connection.php and defines $conn and BASE_URL.
require_once __DIR__ . '/../../../Connection/Connection.php';
require_once __DIR__ . '/../../../Controller/TugasController.php';
require_once __DIR__ . '/../../../Controller/TelurController.php';
require_once __DIR__ . '/../../../Controller/KandangController.php';
require_once __DIR__ . '/../../../Config/Language.php';

session_start();

// access control: staff or admin allowed
if (!isset($_SESSION['role'])) {
	header('Location: ' . (defined('BASE_URL') ? BASE_URL . '/View/pages/auth/index.php' : '/Turtel/View/pages/auth/index.php'));
	exit;
}
if ($_SESSION['role'] !== 'staff' && $_SESSION['role'] !== 'admin') {
	header('Location: ' . (defined('BASE_URL') ? BASE_URL . '/View/pages/auth/index.php' : '/Turtel/View/pages/auth/index.php'));
	exit;
}

$tugasCtrl = new TugasController($conn);
$telurCtrl = new TelurController($conn);
$kandangCtrl = new KandangController($conn);

$flash = '';
$userId = $_SESSION['user_id'] ?? null;

// Handle actions: mark selesai, input telur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';

	if ($action === 'mark_selesai') {
		$id = intval($_POST['id_tugas'] ?? 0);
		$res = $tugasCtrl->setStatus($id, 'selesai');
		$flash = $res['message'] ?? '';
	}

	if ($action === 'input_telur') {
		// insert into telur then link to kandang via kandang.setTelur
		$id_kandang = intval($_POST['id_kandang'] ?? 0);
		$jumlah = intval($_POST['jumlah_telur'] ?? 0);
		$berat = floatval($_POST['berat'] ?? 0.0);
		$layed_at = date('Y-m-d H:i:s');

		$res = $telurCtrl->create($jumlah, $berat, $layed_at);
		if ($res['success']) {
			$id_telur = $res['id_telur'] ?? null;
			if ($id_telur) {
				$res2 = $kandangCtrl->setTelur($id_kandang, $id_telur);
				$flash = ($res2['message'] ?? '') . ' | Telur ID: ' . $id_telur;
			}
		} else {
			$flash = $res['message'] ?? 'Gagal input telur';
		}
	}
}

// Fetch tugas for this user only
$tugass = [];
if ($userId) {
	$all = $tugasCtrl->getAll()['data'] ?? [];
	foreach ($all as $t) {
		if (intval($t['id_user']) === intval($userId)) {
			$tugass[] = $t;
		}
	}
}

// Fetch kandangs for input form
$kandangs = $kandangCtrl->getAll()['data'] ?? [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= t('staff_dashboard') ?></title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/View/Assets/css/bootstrap.min.css">
	<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
	<style>
		body {
			background: #FFFFFF;
			font-family: 'Montserrat', sans-serif;
			padding-bottom: 90px;
			margin: 0;
			min-height: 100vh;
			position: relative;
		}
		body::before {
			content: '';
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background-image: 
				linear-gradient(0deg, transparent 24%, rgba(200, 200, 200, 0.15) 25%, rgba(200, 200, 200, 0.15) 26%, transparent 27%, transparent 74%, rgba(200, 200, 200, 0.15) 75%, rgba(200, 200, 200, 0.15) 76%, transparent 77%, transparent),
				linear-gradient(90deg, transparent 24%, rgba(200, 200, 200, 0.15) 25%, rgba(200, 200, 200, 0.15) 26%, transparent 27%, transparent 74%, rgba(200, 200, 200, 0.15) 75%, rgba(200, 200, 200, 0.15) 76%, transparent 77%, transparent);
			background-size: 100px 100px;
			opacity: 0.5;
			z-index: 0;
			pointer-events: none;
		}
		.main-container {
			padding: 30px 20px;
			position: relative;
			z-index: 1;
		}
		.welcome-section {
			text-align: center;
			margin-bottom: 30px;
		}
		.avatar-circle {
			width: 160px;
			height: 160px;
			background: linear-gradient(135deg, #FF9F1C 0%, #FF8C00 100%);
			border-radius: 50%;
			margin: 0 auto 20px;
			display: flex;
			align-items: center;
			justify-content: center;
			box-shadow: 0 10px 25px rgba(255, 140, 0, 0.3);
			position: relative;
		}
		.avatar-circle::before {
			content: '';
			position: absolute;
			width: 70%;
			height: 70%;
			background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
			border-radius: 50%;
			top: 10%;
			left: 15%;
		}
		.avatar-circle img {
			width: 90px;
			height: 90px;
			filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
		}
		.welcome-text {
			font-size: 1.5rem;
			font-weight: 700;
			color: #2D2D2D;
			margin-bottom: 20px;
			text-shadow: 0 2px 4px rgba(0,0,0,0.05);
		}
		.contribution-badge {
			background: linear-gradient(135deg, #6B3410 0%, #4A2511 100%);
			color: white;
			padding: 14px 35px;
			border-radius: 30px;
			font-size: 1rem;
			font-weight: 600;
			display: inline-block;
			box-shadow: 0 6px 15px rgba(107, 52, 16, 0.3);
		}
		.stats-section {
			background: #FAFAFA;
			border-radius: 25px;
			padding: 25px;
			margin-top: 35px;
			box-shadow: 0 4px 15px rgba(107, 52, 16, 0.15);
			border: 2px solid #6B3410;
		}
		.stats-title {
			display: flex;
			align-items: center;
			font-size: 1.05rem;
			font-weight: 700;
			color: #4A2511;
			margin-bottom: 8px;
		}
		.stats-title svg {
			width: 26px;
			height: 26px;
			margin-right: 10px;
			fill: #6B3410;
		}
		.stats-subtitle {
			font-size: 0.8rem;
			color: #888;
			margin-bottom: 25px;
			margin-left: 36px;
		}
		.chart-wrapper {
			display: flex;
			align-items: flex-end;
		}
		.y-axis {
			display: flex;
			flex-direction: column;
			justify-content: space-between;
			height: 220px;
			margin-right: 15px;
			font-size: 0.75rem;
			color: #999;
			padding-bottom: 25px;
		}
		.chart-container {
			display: flex;
			align-items: flex-end;
			justify-content: space-around;
			height: 220px;
			flex: 1;
			gap: 15px;
			padding-bottom: 5px;
		}
		.chart-bar {
			display: flex;
			flex-direction: column;
			align-items: center;
			flex: 1;
		}
		.bar {
			width: 100%;
			background: linear-gradient(180deg, #6B3410 0%, #4A2511 100%);
			border-radius: 10px 10px 0 0;
			transition: all 0.3s ease;
			box-shadow: 0 -2px 8px rgba(107, 52, 16, 0.3);
		}
		.bar:hover {
			transform: translateY(-5px);
			box-shadow: 0 -4px 12px rgba(107, 52, 16, 0.4);
		}
		.bar-label {
			margin-top: 12px;
			font-size: 0.85rem;
			color: #555;
			font-weight: 600;
		}
	</style>
</head>
<body>
	<div class="main-container">
		<!-- Welcome Section -->
		<div class="welcome-section">
			<div class="avatar-circle">
				<img src="<?= BASE_URL ?>/View/Assets/icons/staff.png" alt="Staff Avatar">
			</div>
			<div class="welcome-text"><?= t('welcome') ?>, <?= htmlspecialchars($_SESSION['username'] ?? 'Staff') ?>!</div>
			<div class="contribution-badge">
				<?= t('your_contribution_on') ?> <?= date('d/m/y') ?>
			</div>
		</div>

		<!-- Stats Section -->
		<div class="stats-section">
			<div class="stats-title">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
					<path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
				</svg>
				<?= t('total_contribution') ?>
			</div>
			<div class="stats-subtitle"><?= t('data_last_7_days') ?></div>
			
			<!-- Chart -->
			<div class="chart-wrapper">
				<div class="y-axis">
					<div>400</div>
					<div>300</div>
					<div>200</div>
					<div>100</div>
					<div>50</div>
				</div>
				<div class="chart-container">
					<div class="chart-bar">
						<div class="bar" style="height: 25%;"></div>
						<div class="bar-label"><?= t('monday') ?></div>
					</div>
					<div class="chart-bar">
						<div class="bar" style="height: 45%;"></div>
						<div class="bar-label"><?= t('tuesday') ?></div>
					</div>
					<div class="chart-bar">
						<div class="bar" style="height: 80%;"></div>
						<div class="bar-label"><?= t('wednesday') ?></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php include __DIR__ . '/../../Components/bottom-nav-staff.php'; ?>
</body>
</html>
