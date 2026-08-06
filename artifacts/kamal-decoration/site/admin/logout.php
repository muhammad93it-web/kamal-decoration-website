<?php
require __DIR__ . '/includes/admin-bootstrap.php';

log_activity('logout', 'auth');
logout_user();
redirect(admin_url('login.php'));
