<?php
// Temporary: show all PHP errors in browser to diagnose blank white screen
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Follow the same include approach as your `index.php`: require the controller which
// in turn requires Connection.php and defines $conn and BASE_URL.
require_once __DIR__ . '/../../../Controller/UserController.php';
session_start();

// Simple access control: only allow admin to view admin dashboard
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
	// Prefer using BASE_URL when defined; otherwise fall back to building an absolute URL
	if (defined('BASE_URL')) {
		$loginUrl = BASE_URL . '/View/pages/auth/index.php';
	} else {
		$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
		$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
		$loginUrl = $scheme . '://' . $host . '/Turtel/View/pages/auth/index.php';
	}
	header('Location: ' . $loginUrl);
	exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Admin Dashboard</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/View/Assets/css/bootstrap.min.css">
</head>
<body>
	<div class="container mt-5">
		<h1>Admin Dashboard</h1>
		<p>Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>. This is the admin dashboard.</p>
	</div>
</body>
</html>
