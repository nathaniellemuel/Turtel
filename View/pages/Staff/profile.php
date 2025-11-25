<?php
    session_start();
    require_once __DIR__ . '/../../../Connection/Connection.php';
    require_once __DIR__ . '/../../../Controller/UserController.php';

    // Simple access control: only allow staff
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
        header('Location: ' . (defined('BASE_URL') ? BASE_URL . '/View/pages/auth/index.php' : '/Turtel/View/pages/auth/index.php'));
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $userController = new UserController($conn);
        $userId = $_SESSION['user_id'];
        $newUsername = $_POST['username'] ?? '';
        $oldPassword = $_POST['old_password'] ?? null;
        $newPassword = $_POST['new_password'] ?? null;

        $result = $userController->updateProfile($userId, $newUsername, $oldPassword, $newPassword);
        $message = $result['message'];
        $success = $result['success'];

        // If successful, logout automatically
        if ($success) {
            $_SESSION['username'] = $newUsername;
            session_unset();
            session_destroy();
            header('Location: ' . BASE_URL . '/View/pages/auth/index.php');
            exit;
        }
    }


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/View/Assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/View/Assets/css/style.css">
    <link rel="shortcut icon" href="<?= BASE_URL ?>/View/Assets/icons/logo-background.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #f0f2f5;
            margin: 0;
            padding-bottom: 90px;
            font-family: 'Montserrat', sans-serif;
        }
        .profile-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 70px 20px 40px;
        }
        .profile-avatar {
            text-align: center;
            margin-bottom: 35px;
        }
        .profile-avatar img {
            width: 100px;
            height: 100px;
        }
        .input-wrapper-custom {
            position: relative;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }
        .input-wrapper-custom:active {
            transform: scale(0.98);
        }
        @media (hover: hover) and (pointer: fine) {
            .input-wrapper-custom:hover {
                transform: translateY(-2px);
                filter: brightness(1.05);
            }
        }
        .input-wrapper-custom img.input-bg {
            width: 100%;
            display: block;
        }
        .input-wrapper-custom .input-content {
            position: absolute;
            top: 40%;
            left: 0;
            right: 0;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            padding: 0 14px;
        }
        .input-wrapper-custom svg {
            width: 18px;
            height: 18px;
            fill: #6B4423;
            margin-right: 8px;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }
        .input-wrapper-custom:active svg {
            transform: scale(1.1);
        }
        @media (hover: hover) and (pointer: fine) {
            .input-wrapper-custom:hover svg {
                transform: scale(1.1);
            }
        }
        .input-wrapper-custom input {
            border: none;
            outline: none;
            background: transparent;
            color: #333;
            font-size: 0.85rem;
            width: 100%;
            padding: 0;
        }
        .input-wrapper-custom input:disabled {
            color: #999;
        }
        .input-wrapper-custom input::placeholder {
            color: #999;
        }
        .btn-save {
            background-color: #763A12;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px;
            width: 100%;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-save:hover {
            background-color: #7d5229;
        }
        .logout-container {
            position: fixed;
            bottom: 100px;
            right: 20px;
        }
        .btn-logout {
            background-color: #AA4C0A;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 30px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-logout:hover {
            background-color: #8B4513;
        }
    </style>
</head>
<body>
<div class="profile-container">
    <?php
        $success = $success ?? false;
        $message = $message ?? '';
        if ($message && !$success) {
            echo '<script>
                Swal.fire({
                    icon: "error",
                    title: "Failed",
                    text: "' . htmlspecialchars($message, ENT_QUOTES) . '",
                    showConfirmButton: true,
                    timer: 3000
                });
            </script>';
        }
        if (!isset($_SESSION['user_id'])) {
            echo '<script>
                Swal.fire({
                    icon: "warning",
                    title: "Not Logged In",
                    text: "Silakan login untuk mengakses fitur ini.",
                    showConfirmButton: true
                }).then(() => {
                    window.location.href = "../auth/index.php";
                });
            </script>';
            exit;
        }
    ?>
    <!-- Profile Avatar -->
    <div class="profile-avatar">
        <img src="<?= BASE_URL ?>/View/Assets/icons/profile1.png" alt="Profile">
    </div>

    <!-- Profile Form -->
    <form method="POST">
        <div class="row g-2">
            <div class="col-6">
                <div class="input-wrapper-custom">
                    <img src="<?= BASE_URL ?>/View/Assets/icons/input-profile.png" alt="" class="input-bg">
                    <div class="input-content">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                        <input type="text" name="username" placeholder="Jhoe" value="<?= htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES) ?>" required />
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="input-wrapper-custom">
                    <img src="<?= BASE_URL ?>/View/Assets/icons/input-profile.png" alt="" class="input-bg">
                    <div class="input-content">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                        </svg>
                        <input type="password" name="old_password" placeholder="Oldest password" />
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="input-wrapper-custom">
                    <img src="<?= BASE_URL ?>/View/Assets/icons/input-profile.png" alt="" class="input-bg">
                    <div class="input-content">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                        </svg>
                        <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($_SESSION['email'] ?? '', ENT_QUOTES) ?>" disabled />
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="input-wrapper-custom">
                    <img src="<?= BASE_URL ?>/View/Assets/icons/input-profile.png" alt="" class="input-bg">
                    <div class="input-content">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                        </svg>
                        <input type="password" name="new_password" placeholder="News password" />
                    </div>
                </div>
            </div>

            <div class="col-12 mt-2">
                <button type="submit" class="btn-save">Save Changes</button>
            </div>
        </div>
    </form>

</div>

<!-- Logout Button (Fixed Position) -->
<div class="logout-container">
    <form method="POST" action="<?= BASE_URL ?>/Controller/Logout.php">
        <button type="submit" class="btn-logout">Logout</button>
    </form>


<?php include __DIR__ . '/../../Components/bottom-nav-staff.php'; ?>

<script src="<?= BASE_URL ?>/View/Assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>