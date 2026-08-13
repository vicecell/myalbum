<?php
require_once __DIR__ . '/../app/bootstrap.php';

logout_admin();
header('Location: /login.php');
exit;
