<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login page if not logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: ../login.php');
    exit;
}

// Simple role-based access control (you can expand this)
function checkPermission($requiredRole) {
    // In a real system, you would check against user roles from database
    // This is a simplified version for demonstration
    
    // Admin has access to everything
    if ($_SESSION['username'] === 'admin') {
        return true;
    }
    
    // Example for other roles (you can add more as needed)
    switch($requiredRole) {
        case 'sales':
            return $_SESSION['role'] === 'sales' || $_SESSION['role'] === 'manager';
        case 'inventory':
            return $_SESSION['role'] === 'inventory' || $_SESSION['role'] === 'manager';
        case 'reports':
            return $_SESSION['role'] === 'manager';
        default:
            return false;
    }
}

// Check if user has permission to access the current page
$currentPage = basename($_SERVER['PHP_SELF']);
$allowedPages = [
    'admin' => ['index.php', 'sales/*', 'inventory/*', 'products/*', 'customers/*', 'reports/*'],
    'manager' => ['index.php', 'sales/*', 'inventory/*', 'reports/*'],
    'sales' => ['index.php', 'sales/*'],
    'inventory' => ['index.php', 'inventory/*']
];

// Simple permission check (you can enhance this further)
if ($_SESSION['username'] !== 'admin') {
    $allowed = false;
    foreach ($allowedPages[$_SESSION['role'] ?? ''] as $pattern) {
        if (fnmatch($pattern, $currentPage)) {
            $allowed = true;
            break;
        }
    }
    
    if (!$allowed) {
        header('Location: ../index.php');
        exit;
    }
}

// Logout after inactivity (30 minutes)
$inactive = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive)) {
    session_unset();
    session_destroy();
    header('Location: ../login.php');
    exit;
}
$_SESSION['last_activity'] = time();

// CSRF protection (basic implementation)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_token() {
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>