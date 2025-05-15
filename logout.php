<?php
// logout.php - Fungsi logout
require_once 'includes/config.php';

// Hapus semua data session
session_unset();
session_destroy();

// Redirect ke halaman login
header("Location: login.php");
exit;
