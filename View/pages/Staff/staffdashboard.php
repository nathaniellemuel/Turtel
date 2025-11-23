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
	<title>Staff Dashboard</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/View/Assets/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
	<div class="d-flex justify-content-between align-items-center">
		<h3>Staff Dashboard</h3>
		<div>
			<span class="me-3">Logged as: <?= htmlspecialchars($_SESSION['username'] ?? 'Staff') ?></span>
			<a href="<?= BASE_URL ?>/Controller/Logout.php" class="btn btn-sm btn-outline-secondary">Logout</a>
		</div>
	</div>

	<?php if (!empty($flash)): ?>
		<div class="alert alert-info mt-3"><?= htmlspecialchars($flash) ?></div>
	<?php endif; ?>

	<div class="row mt-3">
		<div class="col-md-6">
			<div class="card">
				<div class="card-body">
					<h5>Tugas Anda (To-do)</h5>
					<?php if (empty($tugass)): ?>
						<p>Tidak ada tugas untuk Anda.</p>
					<?php else: ?>
						<ul class="list-group">
							<?php foreach ($tugass as $t): ?>
								<li class="list-group-item d-flex justify-content-between align-items-center">
									<div>
										<strong><?= htmlspecialchars($t['deskripsi_tugas']) ?></strong><br>
										<small>Status: <?= htmlspecialchars($t['status']) ?></small>
									</div>
									<div>
										<?php if ($t['status'] !== 'selesai'): ?>
											<form method="post" style="display:inline">
												<input type="hidden" name="action" value="mark_selesai">
												<input type="hidden" name="id_tugas" value="<?= $t['id_tugas'] ?>">
												<button class="btn btn-sm btn-success">Mark Selesai</button>
											</form>
										<?php else: ?>
											<span class="badge bg-success">Selesai</span>
										<?php endif; ?>
									</div>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="col-md-6">
			<div class="card">
				<div class="card-body">
					<h5>Input Jumlah Telur Hari Ini</h5>
					<form method="post" class="row g-2">
						<input type="hidden" name="action" value="input_telur">
						<div class="col-md-6">
							<select name="id_kandang" class="form-select" required>
								<option value="">Pilih Kandang</option>
								<?php foreach ($kandangs as $k): ?>
									<option value="<?= $k['id_kandang'] ?>"><?= htmlspecialchars($k['nama_kandang']) ?> (<?= htmlspecialchars($k['jenis_ayam']) ?>)</option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="col-md-3"><input name="jumlah_telur" type="number" class="form-control" placeholder="Jumlah" required></div>
						<div class="col-md-3"><input name="berat" type="number" step="0.01" class="form-control" placeholder="Berat total (kg)" required></div>
						<div class="col-md-12 mt-2"><button class="btn btn-primary">Simpan Telur</button></div>
					</form>
				</div>
			</div>
		</div>
	</div>

</div>
</body>
</html>
