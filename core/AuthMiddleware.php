<?php
// ==========================================
// Core: Authentication & Role Middleware
// Path: core/AuthMiddleware.php
// ==========================================

// เรียกใช้งานการตั้งค่าระบบ (เพื่อเช็ค Session และ BASE_URL)
require_once __DIR__ . '/../config/app.php';

class Auth {
    
    //    /**
     * ตรวจสอบว่าผู้ใช้ Login เข้าระบบแล้วหรือยัง
     * @return bool true ถ้า Login แล้ว, false ถ้ายัง
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    //    /**
     * บังคับว่าต้อง Login เท่านั้น (ใช้ดักหน้าเว็บ)
     * หากยังไม่ Login จะถูกเด้งกลับไปหน้าแรก (Login Page)
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header("Location: " . BASE_URL . "index.php");
            exit();
        }
    }

    //    /**
     * ตรวจสอบว่าผู้ใช้ปัจจุบันมีสิทธิ์เป็น Admin หรือไม่
     * @return bool true ถ้าเป็น Admin, false ถ้าไม่ใช่
     */
    public static function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    //    /**
     * บังคับว่าต้องเป็น Admin เท่านั้น (ใช้ดักหน้าตั้งค่า/Dashboard)
     * หากเป็นแค่ User ธรรมดา จะถูกเด้งไปหน้า Phone Directory ทันที
     */
    public static function requireAdmin() {
        // เช็คก่อนว่า Login หรือยัง
        self::requireLogin();
        
        // ถ้า Login แล้ว แต่ไม่ใช่ Admin ให้เด้งไปหน้า User
        if (!self::isAdmin()) {
            header("Location: " . BASE_URL . "views/user/directory.php");
            exit();
        }
    }

    //    /**
     * ดึงข้อมูลพื้นฐานของคนที่กำลัง Login อยู่
     * @return array ข้อมูล User ปัจจุบัน
     */
    public static function getCurrentUser() {
        if (self::isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'full_name' => $_SESSION['full_name'],
                'role' => $_SESSION['role']
            ];
        }
        return null;
    }
}
?>