<?php
// config/constants.php
// กำหนด BASE_URL อัตโนมัติ รองรับทั้ง localhost และ production

if (!defined('BASE_URL')) {
    $protocol   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host       = $_SERVER['HTTP_HOST'];
    $projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $docRoot    = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $subPath    = str_replace($docRoot, '', $projectRoot);
    define('BASE_URL', $protocol . '://' . $host . $subPath . '/');
}
?>
