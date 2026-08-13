<?php
// ==========================================
// Core: Database Wrapper Class (PDO Helper)
// Path: core/Database.php
// ==========================================

// เรียกใช้ไฟล์ config เพื่อเอาตัวแปร $pdo ที่เชื่อมต่อแล้วมาใช้งาน
require_once __DIR__ . '/../config/database.php';

class Database {

    /**
     * ดึง Object PDO หลักมาใช้งาน (กรณีต้องการทำ Transaction หรือ Query ซับซ้อน)
     */
    public static function getConnection() {
        global $pdo;
        return $pdo;
    }

    /**
     * ฟังก์ชันหลักสำหรับรันคำสั่ง SQL
     * @param string $sql คำสั่ง SQL (เช่น SELECT * FROM users WHERE id = ?)
     * @param array $params ตัวแปรที่จะนำมา Bind (เช่น [1])
     * @return PDOStatement
     */
    public static function query($sql, $params = []) {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * ดึงข้อมูลออกมาทั้งหมด (ผลลัพธ์เป็น Array หลายมิติ)
     * เหมาะสำหรับคำสั่ง SELECT ที่มีหลายบรรทัด
     */
    public static function getAll($sql, $params = []) {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * ดึงข้อมูลออกมาแค่แถวเดียว (ผลลัพธ์เป็น Array 1 มิติ)
     * เหมาะสำหรับคำสั่ง SELECT ที่เจาะจง ID
     */
    public static function getRow($sql, $params = []) {
        return self::query($sql, $params)->fetch();
    }

    /**
     * ฟังก์ชันสำเร็จรูปสำหรับ Insert ข้อมูล
     * @param string $table ชื่อตาราง
     * @param array $data ข้อมูลรูปแบบ ['column' => 'value']
     * @return bool|string คืนค่าเป็น ID ล่าสุดถ้าสำเร็จ หรือ false ถ้าล้มเหลว
     */
    public static function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = self::getConnection()->prepare($sql);
        
        // ผูกค่า (Bind) ตามคีย์ที่ส่งมา
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        if ($stmt->execute()) {
            return self::getConnection()->lastInsertId();
        }
        return false;
    }

    /**
     * ฟังก์ชันสำเร็จรูปสำหรับ Update ข้อมูล
     * @param string $table ชื่อตาราง
     * @param array $data ข้อมูลที่ต้องการแก้ ['column' => 'new_value']
     * @param string $where เงื่อนไขเช่น "id = :id"
     * @param array $whereParams ค่าตัวแปรเงื่อนไข ['id' => 1]
     */
    public static function update($table, $data, $where, $whereParams = []) {
        $setQuery = "";
        $params = [];
        
        foreach ($data as $key => $value) {
            $setQuery .= "{$key} = :set_{$key}, ";
            $params[":set_{$key}"] = $value;
        }
        $setQuery = rtrim($setQuery, ", "); // ตัดลูกน้ำตัวสุดท้ายออก
        
        $sql = "UPDATE {$table} SET {$setQuery} WHERE {$where}";
        $stmt = self::getConnection()->prepare($sql);
        
        // ผูกค่า Set
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        // ผูกค่า Where
        foreach ($whereParams as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        return $stmt->execute();
    }

    /**
     * ดึงจำนวนแถว (COUNT)
     */
    public static function count($sql, $params = []) {
        return (int) self::query($sql, $params)->fetchColumn();
    }
}
?>