<?php

class pwclass {
    private $db;
    private $dbPath;
    
    public function __construct($dbPath = 'database/nikenza.db') {
        $this->dbPath = $dbPath;
        $this->connect();
        $this->createTables();
        $this->createDefaultUsers();
    }
    
    /**
     * Establece conexión con la base de datos SQLite
     */
    private function connect() {
        try {
            // Crear directorio si no existe
            $dir = dirname($this->dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            $this->db = new PDO('sqlite:' . $this->dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die('Error de conexión: ' . $e->getMessage());
        }
    }
    
    /**
     * Crea las tablas necesarias si no existen
     */
    private function createTables() {
        $sql = "
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(80) UNIQUE NOT NULL,
                email VARCHAR(120) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(20) NOT NULL DEFAULT 'cliente',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                is_active BOOLEAN DEFAULT 1
            )
        ";
        
        try {
            $this->db->exec($sql);
        } catch (PDOException $e) {
            die('Error creando tablas: ' . $e->getMessage());
        }
    }
    
    /**
     * Crea usuarios por defecto si no existen
     */
    private function createDefaultUsers() {
        // Verificar si ya existen usuarios
        $count = $this->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
        
        if ($count == 0) {
            // Crear usuario administrador
            $this->insert('users', [
                'username' => 'admin',
                'email' => 'admin@nikenza.com',
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'role' => 'admin'
            ]);
            
            // Crear usuario cliente
            $this->insert('users', [
                'username' => 'cliente',
                'email' => 'cliente@example.com',
                'password_hash' => password_hash('cliente123', PASSWORD_DEFAULT),
                'role' => 'cliente'
            ]);
        }
    }
    
    /**
     * Ejecuta una consulta SQL
     * @param string $sql
     * @param array $params
     * @return PDOStatement
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception('Error en consulta: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtiene todos los registros de una tabla
     * @param string $table
     * @param string $conditions
     * @param array $params
     * @return array
     */
    public function getAll($table, $conditions = '', $params = []) {
        $sql = "SELECT * FROM $table";
        if ($conditions) {
            $sql .= " WHERE $conditions";
        }
        return $this->query($sql, $params)->fetchAll();
    }
    
    /**
     * Obtiene un registro por ID
     * @param string $table
     * @param int $id
     * @return array|false
     */
    public function getById($table, $id) {
        $sql = "SELECT * FROM $table WHERE id = ?";
        return $this->query($sql, [$id])->fetch();
    }
    
    /**
     * Obtiene un registro por condiciones específicas
     * @param string $table
     * @param string $conditions
     * @param array $params
     * @return array|false
     */
    public function getOne($table, $conditions, $params = []) {
        $sql = "SELECT * FROM $table WHERE $conditions LIMIT 1";
        return $this->query($sql, $params)->fetch();
    }
    
    /**
     * Inserta un nuevo registro
     * @param string $table
     * @param array $data
     * @return int ID del registro insertado
     */
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $this->query($sql, $data);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Actualiza un registro
     * @param string $table
     * @param array $data
     * @param string $conditions
     * @param array $params
     * @return int Número de filas afectadas
     */
    public function update($table, $data, $conditions, $params = []) {
        $setClause = [];
        foreach ($data as $key => $value) {
            $setClause[] = "$key = :$key";
        }
        $setClause = implode(', ', $setClause);
        
        $sql = "UPDATE $table SET $setClause WHERE $conditions";
        $allParams = array_merge($data, $params);
        
        return $this->query($sql, $allParams)->rowCount();
    }
    
    /**
     * Elimina registros
     * @param string $table
     * @param string $conditions
     * @param array $params
     * @return int Número de filas afectadas
     */
    public function delete($table, $conditions, $params = []) {
        $sql = "DELETE FROM $table WHERE $conditions";
        return $this->query($sql, $params)->rowCount();
    }
    
    /**
     * Cuenta registros
     * @param string $table
     * @param string $conditions
     * @param array $params
     * @return int
     */
    public function count($table, $conditions = '', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM $table";
        if ($conditions) {
            $sql .= " WHERE $conditions";
        }
        return $this->query($sql, $params)->fetch()['count'];
    }
    
    /**
     * Verifica si existe un registro
     * @param string $table
     * @param string $conditions
     * @param array $params
     * @return bool
     */
    public function exists($table, $conditions, $params = []) {
        return $this->count($table, $conditions, $params) > 0;
    }
    
    /**
     * Obtiene una lista de registros con paginación
     * @param string $table
     * @param int $page
     * @param int $limit
     * @param string $conditions
     * @param array $params
     * @param string $orderBy
     * @return array
     */
    public function getList($table, $page = 1, $limit = 10, $conditions = '', $params = [], $orderBy = 'id DESC') {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT * FROM $table";
        if ($conditions) {
            $sql .= " WHERE $conditions";
        }
        $sql .= " ORDER BY $orderBy LIMIT $limit OFFSET $offset";
        
        return $this->query($sql, $params)->fetchAll();
    }
    
    /**
     * Inicia una transacción
     */
    public function beginTransaction() {
        $this->db->beginTransaction();
    }
    
    /**
     * Confirma una transacción
     */
    public function commit() {
        $this->db->commit();
    }
    
    /**
     * Revierte una transacción
     */
    public function rollback() {
        $this->db->rollback();
    }
    
    /**
     * Cierra la conexión
     */
    public function close() {
        $this->db = null;
    }
    
    /**
     * Obtiene la instancia de PDO (para consultas complejas)
     * @return PDO
     */
    public function getPDO() {
        return $this->db;
    }
}

?>

