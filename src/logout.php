<?php
// Simple logout: destroy session and redirect to homepage
session_start();
// Unset all session variables
$_SESSION = array();
// If there's a session cookie, delete it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
// Destroy the session
session_destroy();


// Xóa localStorage giỏ hàng phía client bằng JS
echo '<script>';
echo 'Object.keys(localStorage).forEach(function(k){ if(k.startsWith("myshop_cart_")) localStorage.removeItem(k); });';
echo 'window.location.href = "trangchu.php";';
echo '</script>';
exit;

?>
