<?php
    require_once __DIR__ . '/../../../Controller/UserController.php';

    $message = '';
    $success = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
    // Jangan kirim createdAt atau role dari form — biarkan controller menetapkan role default 'staff'
    $userController = new UserController($conn);
    $result = $userController->register($username, $email, $password);
        $message = $result['message'];
        $success = $result['success'];
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/View/Assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/View/Assets/css/style.css">
    <link rel="shortcut icon" href="<?= BASE_URL ?>/View/Assets/icons/logo-background.png" type="image/x-icon">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body class="d-flex align-items-center justify-content-center vh-100 bg-white">
    <?php if ($message): ?>
        <script>
            Swal.fire({
                icon: '<?= $success ? "success" : "error" ?>',
                title: '<?= $success ? "Success" : "Failed" ?>',
                text: '<?= htmlspecialchars($message, ENT_QUOTES) ?>',
                showConfirmButton: true,
                timer: <?= $success ? "2000" : "3000" ?>
            }).then(() => {
                <?php if ($success): ?>
                    window.location.href = "index.php?skip_splash=1";
                <?php endif; ?>
            });
        </script>
    <?php endif; ?>
    <div class="container" style="max-width: 400px;">
        <div class="text-center mb-4">
            <img src="<?= BASE_URL ?>/View/Assets/icons/logo-primary.png" alt="Logo" class="img-fluid"
                style="width: 150px;" />
        </div>

        <h4 class="text-center fw-bold">Welcome!</h4>
        <p class="text-center text-muted mb-4">Create Your Account</p>

        <form method="POST">
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-person-fill" style="color: #F8941E;"></i>
                    </span>
                    <input type="text" class="form-control border-start-0" placeholder="Username" name="username" required />
                </div>
            </div>

            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-envelope-fill" style="color: #F8941E;"></i>
                    </span>
                    <input type="email" class="form-control border-start-0" placeholder="Email" name="email" required />
                </div>
            </div>

            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-lock-fill" style="color: #F8941E;"></i>
                    </span>
                    <input type="password" class="form-control border-start-0" placeholder="Password" name="password" required />
                </div>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn text-white" style="background-color: #F8941E;">Sign Up</button>
            </div>
        </form>

        <div class="d-flex align-items-center my-3">
            <hr class="flex-grow-1" />
            <span class="mx-2 text-muted">Or Sign Up With</span>
            <hr class="flex-grow-1" />
        </div>

        <div class="text-center mb-4">
            <a href="#" class="d-inline-block">
                <img src="<?= BASE_URL ?>/View/Assets/icons/google.png" width="36" alt="Google Sign In" />
            </a>
        </div>

        <p class="text-center text-muted">
            Have an account?
            <a href="<?= BASE_URL ?>/View/pages/auth/index.php?skip_splash=1" class="fw-semibold text-decoration-none"
                style="color: #F8941E;">Sign In Here</a>
        </p>
    </div>



    <script src="<?= BASE_URL ?>/View/Assets/js/bootstrap.bundle.min.js"></script>
</body>

</html>
