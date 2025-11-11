<?php
// Redirect root project requests to the auth index (which will show the splash page first)
// Use a relative redirect so this works even if the project isn't mounted at '/Turtel'.
header('Location: View/pages/auth/index.php');
exit;
