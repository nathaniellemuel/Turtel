<?php

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
    .navbar-bottom .nav-link {
        transition: 
            background 0.2s,
            color 0.2s,
            box-shadow 0.2s,
            transform 0.18s;
        border-radius: 16px;
        padding: 6px 10px;
        position: relative;
        overflow: hidden;
    }
    .navbar-bottom .nav-link:hover {
        background: rgba(255,255,255,0.22);
        color: #fff !important;
        box-shadow: 0 4px 18px rgba(43,55,136,0.18);
        transform: scale(1.08);
        backdrop-filter: blur(2px);
    }
    .navbar-bottom .nav-link.active {
        background: #1e2766;
        color: #fff !important;
        box-shadow: 0 2px 8px rgba(43,55,136,0.18);
    }
</style>

<!-- Bottom Navigation Bar -->
<nav class="navbar navbar-expand navbar-light fixed-bottom navbar-bottom shadow" style="background-color: #2B3788;">
    <div class="container justify-content-around">
        <a class="nav-link <?= $currentPage === 'explore.php' ? 'active' : '' ?> d-flex flex-column align-items-center" href="<?= BASE_URL ?>/View/pages/homepage/explore.php">
            <img src="<?= BASE_URL ?>/View/Assets/icons/explore-menu.png" alt="Explore" style="width: 37px; height: 37px;">
            <span style="font-size: 0.85rem;" class="text-light">Explore</span>
        </a>
        <a class="nav-link <?= $currentPage === 'analyze.php' ? 'active' : '' ?> d-flex flex-column align-items-center" href="<?= BASE_URL ?>/View/pages/homepage/analyze.php">
            <img src="<?= BASE_URL ?>/View/Assets/icons/analyze-menu.png" alt="Analyze" style="width: 37px; height: 37px;">
            <span style="font-size: 0.85rem;" class="text-light">Analyze</span>
        </a>
        <a class="nav-link <?= $currentPage === 'fishpedia.php' ? 'active' : '' ?> d-flex flex-column align-items-center" href="<?= BASE_URL ?>/View/pages/homepage/fishpedia.php">
            <img src="<?= BASE_URL ?>/View/Assets/icons/fishpedia-menu.png" alt="Fishpedia" style="width: 37px; height: 37px;">
            <span style="font-size: 0.85rem;" class="text-light">Fishpedia</span>
        </a>
        <a class="nav-link <?= $currentPage === 'profile.php' ? 'active' : '' ?> d-flex flex-column align-items-center" href="<?= BASE_URL ?>/View/pages/homepage/profile.php">
            <img src="<?= BASE_URL ?>/View/Assets/icons/setting-menu.png" alt="Settings" style="width: 37px; height: 37px;">
            <span style="font-size: 0.85rem;" class="text-light">Settings</span>
        </a>
    </div>
</nav>
