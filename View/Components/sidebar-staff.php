<?php
// Get current page filename
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
    /* Desktop Sidebar */
    .desktop-sidebar {
        display: none;
        position: fixed;
        left: 0;
        top: 0;
        width: 200px;
        height: 100vh;
        background: linear-gradient(180deg, #6B3410 0%, #4A2511 100%);
        padding: 20px 0;
        z-index: 1000;
        overflow-y: auto;
    }
    
    .sidebar-logo {
        display: flex;
        align-items: center;
        padding: 0 20px;
        margin-bottom: 40px;
    }
    
    .sidebar-logo img {
        width: 40px;
        height: 40px;
        margin-right: 10px;
    }
    
    .sidebar-logo span {
        color: white;
        font-size: 1.5rem;
        font-weight: bold;
    }
    
    .sidebar-menu {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    
    .sidebar-menu li {
        margin-bottom: 5px;
    }
    
    .sidebar-menu a {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .sidebar-menu a:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        padding-left: 25px;
    }
    
    .sidebar-menu a.active {
        background: #F39C12;
        color: white;
        border-left: 4px solid white;
    }
    
    .sidebar-menu a img {
        width: 24px;
        height: 24px;
        margin-right: 12px;
        filter: brightness(0) invert(1);
    }
    
    .sidebar-user {
        position: absolute;
        bottom: 20px;
        left: 0;
        right: 0;
        padding: 15px 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
    }
    
    .sidebar-user-name {
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 3px;
    }
    
    .sidebar-user-role {
        font-size: 0.75rem;
        opacity: 0.7;
    }
    
    /* Show sidebar on desktop */
    @media (min-width: 768px) {
        .desktop-sidebar {
            display: block;
        }
        
        /* Adjust main content to not overlap with sidebar */
        body {
            margin-left: 200px;
        }
    }
</style>

<!-- Desktop Sidebar -->
<div class="desktop-sidebar">
    <div class="sidebar-logo">
        <img src="<?= BASE_URL ?>/View/Assets/icons/logo-primary.png" alt="Turtel Logo">
        <span>Turtel</span>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="<?= BASE_URL ?>/View/pages/Staff/staffdashboard.php" class="<?= $currentPage === 'staffdashboard.php' ? 'active' : '' ?>">
                <img src="<?= BASE_URL ?>/View/Assets/icons/home-menu.png" alt="Dashboard">
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>/View/pages/Staff/task.php" class="<?= $currentPage === 'task.php' ? 'active' : '' ?>">
                <img src="<?= BASE_URL ?>/View/Assets/icons/task.png" alt="Task">
                <span>Task</span>
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>/View/pages/Staff/egg.php" class="<?= $currentPage === 'egg.php' ? 'active' : '' ?>">
                <img src="<?= BASE_URL ?>/View/Assets/icons/egg.png" alt="Egg">
                <span>Egg Production</span>
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>/View/pages/Staff/profile.php" class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>">
                <img src="<?= BASE_URL ?>/View/Assets/icons/setting-menu.png" alt="Profile">
                <span>Profile</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-user">
        <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Staff') ?></div>
        <div class="sidebar-user-role">Staff Member</div>
    </div>
</div>
