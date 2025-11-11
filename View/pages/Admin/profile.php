<?php
    session_start();
    require_once __DIR__ . '/../../../Connection/Connection.php';
    require_once __DIR__ . '/../../../Controller/UserController.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $userController = new UserController($conn);
        $userId = $_SESSION['user_id'];
        $newUsername = $_POST['username'] ?? '';
        $oldPassword = $_POST['old_password'] ?? null;
        $newPassword = $_POST['new_password'] ?? null;

        $result = $userController->updateProfile($userId, $newUsername, $oldPassword, $newPassword);
        $message = $result['message'];
        $success = $result['success'];

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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="container">
    <?php
        $success = $success ?? false;
        $message = $message ?? '';
        if ($success) {
            echo '<script>
                Swal.fire({
                    icon: "success",
                    title: "Success",
                    text: "' . htmlspecialchars($message, ENT_QUOTES) . '",
                    showConfirmButton: true,
                    timer: 2000
                }).then(() => {
                    window.location.href = "../homepage/profile.php";
                });
            </script>';
            $_SESSION['username'] = $newUsername;
        } elseif ($message) {
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
    <div class="row mt-3">
        <img src="<?= BASE_URL ?>/View/Assets/icons/profile.png" alt="Profile Picture" style="width: 150px;" class="d-block mx-auto mb-3">
    </div>
    <form class="row mt-3" method="POST">
        <div class="col-md-6">
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-person-fill" style="color: #2B3788;"></i>
                    </span>
                    <input type="text" class="form-control border-start-0" placeholder="Username" name="username" value="<?= htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES) ?>" required />
                </div>
            </div>

            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-envelope-fill" style="color: #868080;"></i>
                    </span>
                    <input type="email" class="form-control border-start-0" name="email" value="<?= htmlspecialchars($_SESSION['email'] ?? '', ENT_QUOTES) ?>" disabled />
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-lock-fill" style="color: #2B3788;"></i>
                    </span>
                    <input type="password" class="form-control border-start-0" placeholder="Old Password" name="old_password"  />
                </div>
            </div>

            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-lock-fill" style="color: #2B3788;"></i>
                    </span>
                    <input type="password" class="form-control border-start-0" placeholder="New Password" name="new_password"  />
                </div>
            </div>
        </div>
        <div class="col-md-12 mt-3">
            <button class="btn text-light" style="background-color:#2B3788; width: 100%;" type="submit">Save Changes</button>
        </div>
    </form>
    <div class="row mt-5">
        <div class="col-md-12 text-center">
            <form method="POST" action="<?= BASE_URL ?>/Controller/Logout.php">
                <button class="btn d-block ms-auto" style="background-color: #2B3788; color: white;" type="submit">Logout</button>
            </form>
        </div>
    </div>


    <!-- Include the bottom navigation bar -->
    <?php require_once __DIR__ . '/../../../View/Components/bottom-nav.php'; ?>

    <script src="<?= BASE_URL ?>/View/Assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>