<?php
session_start();
define('ADMIN_SECURE', true);

function getAuthConfig() {
    $configFile = __DIR__ . '/auth_config.php';
    if (!file_exists($configFile)) {
        return [
            'username' => 'admin',
            'password_hash' => password_hash('admin123', PASSWORD_BCRYPT),
            'site_title' => 'Fdrtech Admin CMS'
        ];
    }
    return require $configFile;
}

function saveAuthConfig($username, $newPasswordHash) {
    $configFile = __DIR__ . '/auth_config.php';
    $content = "<?php\n";
    $content .= "defined('ADMIN_SECURE') or die('Akses langsung ditolak.');\n\n";
    $content .= "return [\n";
    $content .= "    'username' => " . var_export($username, true) . ",\n";
    $content .= "    'password_hash' => " . var_export($newPasswordHash, true) . ",\n";
    $content .= "    'site_title' => 'Fdrtech Admin CMS'\n";
    $content .= "];\n";
    return file_put_contents($configFile, $content) !== false;
}

function isAdminLoggedIn() {
    return isset($_SESSION['fdrtech_admin_logged']) && $_SESSION['fdrtech_admin_logged'] === true;
}

function verifyAdminLogin($username, $password) {
    $config = getAuthConfig();
    if ($username === $config['username'] && password_verify($password, $config['password_hash'])) {
        $_SESSION['fdrtech_admin_logged'] = true;
        $_SESSION['fdrtech_admin_user'] = $username;
        return true;
    }
    return false;
}

function logoutAdmin() {
    $_SESSION['fdrtech_admin_logged'] = false;
    unset($_SESSION['fdrtech_admin_logged']);
    unset($_SESSION['fdrtech_admin_user']);
    session_destroy();
}

