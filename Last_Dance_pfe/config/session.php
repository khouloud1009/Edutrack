<?php
/**
 * Session Management Configuration
 * EduTrack Management System
 */

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    // Configure session security
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
    ini_set('session.cookie_samesite', 'Strict');
    
    // Set session timeout (2 hours)
    ini_set('session.gc_maxlifetime', 7200);
    
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_type'] === 'admin';
}

/**
 * Check if user is student
 */
function isStudent() {
    return isLoggedIn() && $_SESSION['user_type'] === 'student';
}

/**
 * Require admin access
 */
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ../index.php');
        exit();
    }
}

/**
 * Require student access
 */
function requireStudent() {
    if (!isStudent()) {
        header('Location: ../index.php');
        exit();
    }
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user name
 */
function getCurrentUserName() {
    return $_SESSION['user_name'] ?? null;
}

/**
 * Get current user type
 */
function getCurrentUserType() {
    return $_SESSION['user_type'] ?? null;
}

/**
 * Login user
 */
function loginUser($userId, $userName, $userType) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $userName;
    $_SESSION['user_type'] = $userType;
    $_SESSION['login_time'] = time();
}

/**
 * Logout user
 */
function logoutUser() {
    // Clear all session variables
    $_SESSION = array();
    
    // Delete session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Check session timeout
 */
function checkSessionTimeout() {
    $timeout = 7200; // 2 hours
    
    if (isset($_SESSION['login_time']) && 
        (time() - $_SESSION['login_time'] > $timeout)) {
        logoutUser();
        return true;
    }
    
    return false;
}

/**
 * Update session activity
 */
function updateSessionActivity() {
    $_SESSION['last_activity'] = time();
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get session status information
 */
function getSessionInfo() {
    return [
        'user_id' => getCurrentUserId(),
        'user_name' => getCurrentUserName(),
        'user_type' => getCurrentUserType(),
        'login_time' => $_SESSION['login_time'] ?? null,
        'last_activity' => $_SESSION['last_activity'] ?? null,
        'is_logged_in' => isLoggedIn()
    ];
}

// Auto-update session activity
if (isLoggedIn()) {
    updateSessionActivity();
    
    // Check for timeout
    if (checkSessionTimeout()) {
        header('Location: ../index.php?timeout=1');
        exit();
    }
}
?>
