<?php
// Splash page: show logo briefly, then go to login
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Follow the same include approach as your `index.php`: require the controller which
// in turn requires Connection.php and defines $conn and BASE_URL.
require_once __DIR__ . '/../../../Controller/UserController.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Turtel — Splash</title>
    <style>
        html,body{height:100%;margin:0}
        .splash{height:100%;display:flex;align-items:center;justify-content:center;background:#fff}
        .splash img{max-width:60%;height:auto}
        @media(min-width:768px){.splash img{max-width:320px}}
    </style>
    <script>
        // Redirect after 2 seconds to the login page, but only after the page has loaded
        function goToLogin(){
            var login = "<?= (defined('BASE_URL') ? BASE_URL : '') ?>/View/pages/auth/index.php?from_splash=1";
            // Use replace so back button doesn't return to splash
            window.location.replace(login);
        }

        // Start timer after the window load event so the logo has a chance to render
        window.addEventListener('load', function () {
            // safety: if load already happened, this still runs
            setTimeout(goToLogin, 2000);
        });
    </script>
    <!-- Non-JS fallback: meta refresh after 2 seconds -->
    <noscript>
        <meta http-equiv="refresh" content="2;url=<?= (defined('BASE_URL') ? BASE_URL : '') ?>/View/pages/auth/index.php?from_splash=1">
    </noscript>
</head>
<body>
    <div class="splash">
        <img src="<?= (defined('BASE_URL') ? BASE_URL : '') ?>/View/Assets/icons/logo-background.png" alt="Turtel Logo">
    </div>
</body>
</html>
