<?php

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
    .navbar-bottom .nav-link {
        transition: all 0.3s ease;
        border-radius: 50%;
        padding: 12px;
        position: relative;
        overflow: hidden;
    }
    .navbar-bottom .nav-link:hover {
        background: rgba(255,255,255,0.1);
        transform: scale(1.1);
    }
    .navbar-bottom .nav-link.active {
        background: #F39C12;
        box-shadow: 0 4px 12px rgba(243, 156, 18, 0.4);
    }
    .navbar-bottom .nav-link img {
        filter: brightness(0) invert(1);
    }
    
    /* Hide bottom nav on desktop */
    @media (min-width: 768px) {
        .navbar-bottom {
            display: none !important;
        }
    }
</style>

<!-- Bottom Navigation Bar -->
<nav class="navbar navbar-expand navbar-light fixed-bottom navbar-bottom shadow" style="background-color: #4A2511;">
    <div class="container justify-content-around">
        <a class="nav-link <?= $currentPage === 'staffdashboard.php' ? 'active' : '' ?> d-flex flex-column align-items-center" href="<?= BASE_URL ?>/View/pages/Staff/staffdashboard.php">
            <img src="<?= BASE_URL ?>/View/Assets/icons/home-menu.png" alt="Home" style="width: 30px; height: 30px;">
        </a>
        <a class="nav-link <?= $currentPage === 'task.php' ? 'active' : '' ?> d-flex flex-column align-items-center" href="<?= BASE_URL ?>/View/pages/Staff/task.php">
            <img src="<?= BASE_URL ?>/View/Assets/icons/task.png" alt="Task" style="width: 30px; height: 30px;">
        </a>
        <a class="nav-link <?= $currentPage === 'egg.php' ? 'active' : '' ?> d-flex flex-column align-items-center" href="<?= BASE_URL ?>/View/pages/Staff/egg.php">
            <img src="<?= BASE_URL ?>/View/Assets/icons/egg.png" alt="Egg" style="width: 30px; height: 30px;">
        </a>
        <a class="nav-link <?= $currentPage === 'profile.php' ? 'active' : '' ?> d-flex flex-column align-items-center" href="<?= BASE_URL ?>/View/pages/Staff/profile.php">
            <img src="<?= BASE_URL ?>/View/Assets/icons/setting-menu.png" alt="Settings" style="width: 30px; height: 30px;">
        </a>
    </div>
</nav>
