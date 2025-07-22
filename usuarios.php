<?php
require_once 'pwclass.php';

class UserList extends pwclass {
    public function getUsuarios() {
        try {
            $query = "SELECT id, username, email FROM usuarios";
            $stmt = $this->db->query($query);
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode($usuarios);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}

$usuarios = new UserList();
$usuarios->getUsuarios();
?>
