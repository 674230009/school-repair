<?php
// index.php — redirect ผู้ใช้ตาม role ไปยัง dashboard ของตัวเอง
require_once 'config/database.php';
require_once 'config/auth.php';

checkLogin();
$user = currentUser();

switch ($user['role']) {
    case 'admin':
        header('Location: pages/admin/dashboard.php');
        break;
    case 'technician':
        header('Location: pages/technician/dashboard.php');
        break;
    case 'student':
    default:
        header('Location: pages/student/dashboard.php');
        break;
}
exit;
?>