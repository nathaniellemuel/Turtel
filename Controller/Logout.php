<?php
session_start();
session_unset();
session_destroy();
header("Location: ../View/pages/auth/index.php");
exit;
?>