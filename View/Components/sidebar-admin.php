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
        background: linear-gradient(180deg, #6B2C2C 0%, #4A1F1F 100%);
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
        padding: 15px 20px;
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
    }
    
    .sidebar-menu a:hover,
    .sidebar-menu a.active {
        background-color: rgba(243, 156, 18, 0.1);
        color: white;
        border-left-color: #F39C12;
    }
    
    .sidebar-menu a img {
        width: 24px;
        height: 24px;
        margin-right: 15px;
        filter: brightness(0) invert(1);
        opacity: 0.7;
    }
    
    .sidebar-menu a:hover img,
    .sidebar-menu a.active img {
        opacity: 1;
    }
    
    .sidebar-user {
        position: absolute;
        bottom: 20px;
        left: 0;
        right: 0;
        padding: 15px 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .sidebar-user-info {
        display: flex;
        align-items: center;
    }
    
    .sidebar-user-info img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        margin-right: 10px;
    }
    
    .sidebar-user-info .user-details {
        color: white;
    }
    
    .sidebar-user-info .user-name {
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .sidebar-user-info .user-role {
        font-size: 0.75rem;
        opacity: 0.7;
    }
    
    /* Show sidebar on desktop */
    @media (min-width: 768px) {
        .desktop-sidebar {
            display: block;
        }
    }
</style>

<!-- Desktop Sidebar -->
<div class="desktop-sidebar">
    <div class="sidebar-logo">
        <img src="<?= BASE_URL ?>/View/Assets/icons/logo-primary.png" alt="Logo" onerror="this.style.display='none'">
        <span>turtel</span>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="<?= BASE_URL ?>/View/pages/Admin/admindashboard.php" class="<?= $currentPage === 'admindashboard.php' ? 'active' : '' ?>">
                <img src="<?= BASE_URL ?>/View/Assets/icons/home-menu.png" alt="Dashboard" onerror="this.src='<?= BASE_URL ?>/View/Assets/icons/barn.png'">
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>/View/pages/Admin/employee.php" class="<?= $currentPage === 'employee.php' ? 'active' : '' ?>">
                <img src="<?= BASE_URL ?>/View/Assets/icons/employee-menu.png" alt="Employee" onerror="this.src='<?= BASE_URL ?>/View/Assets/icons/staff.png'">
                <span>Employee</span>
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>/View/pages/Admin/barn.php" class="<?= $currentPage === 'barn.php' ? 'active' : '' ?>">
                <img src="<?= BASE_URL ?>/View/Assets/icons/barn-menu.png" alt="Barn" onerror="this.src='<?= BASE_URL ?>/View/Assets/icons/barn.png'">
                <span>Barn / Coop</span>
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>/View/pages/Admin/feed.php" class="<?= $currentPage === 'feed.php' ? 'active' : '' ?>">
                <img src="<?= BASE_URL ?>/View/Assets/icons/feed-menu.png" alt="Feed" onerror="this.src='<?= BASE_URL ?>/View/Assets/icons/feed.png'">
                <span>Feed</span>
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>/View/pages/Admin/stock.php" class="<?= $currentPage === 'stock.php' ? 'active' : '' ?>">
                <img src="<?= BASE_URL ?>/View/Assets/icons/stock.png" alt="Stock" onerror="this.src='<?= BASE_URL ?>/View/Assets/icons/feed-stock.png'">
                <span>Stock</span>
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>/View/pages/Admin/profile.php" class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>">
                <img src="<?= BASE_URL ?>/View/Assets/icons/setting-menu.png" alt="Setting" onerror="this.src='<?= BASE_URL ?>/View/Assets/icons/user.png'">
                <span>Setting</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-user">
        <div class="sidebar-user-info">
            <img src="<?= BASE_URL ?>/View/Assets/icons/user.png" alt="User">
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></div>
                <div class="user-role">Admin</div>
            </div>
        </div>
    </div>
</div>
