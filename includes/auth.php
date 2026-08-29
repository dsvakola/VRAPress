<?php
require_once __DIR__ . '/functions.php';

function require_admin(): void {
    if (!current_admin()) {
        redirect(admin_url('/login.php'));
    }
}
