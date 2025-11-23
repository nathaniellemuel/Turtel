<?php
// Get current page filename
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
    .bottom-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        width: 100%;
        background-color: #4A2511;
        display: flex;
        justify-content: space-around;
        padding: 10px 0;
        box-shadow: 0 -2px 5px rgba(0,0,0,0.2);
        z-index: 1000;
    }
    .nav-btn {
        background: none;
        border: none;
        padding: 8px 12px;
        border-radius: 10px;
        transition: background-color 0.2s;
        cursor: pointer;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .nav-btn img {
        width: 30px;
        height: 30px;
    }
    .nav-btn.active {
        background-color: #F39C12;
    }
    .nav-btn:not(.active):hover {
        background-color: rgba(243, 156, 18, 0.3);
    }
</style>

<!-- Bottom Navigation -->
<div class="bottom-nav">
    <a href="<?= BASE_URL ?>/View/pages/Admin/admindashboard.php" class="nav-btn <?= $currentPage === 'admindashboard.php' ? 'active' : '' ?>">
        <img src="<?= BASE_URL ?>/View/Assets/icons/home-menu.png" alt="Home">
    </a>
    <a href="<?= BASE_URL ?>/View/pages/Admin/employee.php" class="nav-btn <?= $currentPage === 'employee.php' ? 'active' : '' ?>">
        <img src="<?= BASE_URL ?>/View/Assets/icons/employee-menu.png" alt="Employee">
    </a>
    <a href="<?= BASE_URL ?>/View/pages/Admin/barn.php" class="nav-btn <?= $currentPage === 'barn.php' ? 'active' : '' ?>">
        <img src="<?= BASE_URL ?>/View/Assets/icons/barn-menu.png" alt="Barn">
    </a>
    <a href="<?= BASE_URL ?>/View/pages/Admin/feed.php" class="nav-btn <?= $currentPage === 'feed.php' ? 'active' : '' ?>">
        <img src="<?= BASE_URL ?>/View/Assets/icons/feed-menu.png" alt="Feed">
    </a>
    <a href="<?= BASE_URL ?>/View/pages/Admin/profile.php" class="nav-btn <?= $currentPage === 'profile.php' ? 'active' : '' ?>">
        <img src="<?= BASE_URL ?>/View/Assets/icons/setting-menu.png" alt="Settings">
    </a>
</div>
