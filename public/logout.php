<?php
require_once __DIR__ . '/../src/middleware/authentication.php';
logoutUser();
redirectTo('login');
