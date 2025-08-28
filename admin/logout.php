<?php
session_start();

// Destroy the session and redirect to login
session_destroy();
header('Location: login.php?logout=1');
exit();
?> 