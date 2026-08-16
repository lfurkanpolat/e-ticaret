<?php
// =====================================================
// ÇIKIŞ YAP - public/cikis.php
// =====================================================

// Config dosyasını dahil et
require_once '../includes/config.php';

// =====================================================
// OTURUMU TEMİZLE
// =====================================================

// Tüm session değişkenlerini temizle
$_SESSION = array();

// Session cookie'sini sil
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Session'ı yok et
session_destroy();

// =====================================================
// YÖNLENDİRME
// =====================================================

// Kullanıcıyı ana sayfaya yönlendir
header("Location: " . SITE_URL . "public/index.php");
exit();
?>