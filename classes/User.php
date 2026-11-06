<?php
class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function login($email, $password) {
        $user = $this->db->fetch("SELECT * FROM users WHERE email = ?", [$email]);
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            session_regenerate_id(true);
            return true;
        }
        return false;
    }
    
    public function logout() {
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    public function getCurrentUser() {
        if(!$this->isLoggedIn()) return null;
        return $this->db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    }
    
    public function create($data) {
        $role = in_array($data['role'] ?? '', ['admin', 'staff'], true) ? $data['role'] : 'staff';
        $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
        return $this->db->query(
            "INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())",
            [trim($data['name']), trim($data['email']), $hashed, $role]
        );
    }

    public function getById($id) {
        return $this->db->fetch(
            "SELECT id, name, email, role, created_at FROM users WHERE id = ?",
            [intval($id)]
        );
    }

    public function emailExists($email, $excludeId = 0) {
        $row = $this->db->fetch(
            "SELECT id FROM users WHERE email = ? AND id <> ?",
            [trim($email), intval($excludeId)]
        );
        return (bool)$row;
    }

    public function update($id, $data) {
        $id = intval($id);
        $role = in_array($data['role'] ?? '', ['admin', 'staff'], true) ? $data['role'] : 'staff';

        $sql = "UPDATE users SET name = ?, email = ?, role = ?, updated_at = NOW()";
        $params = [trim($data['name']), trim($data['email']), $role];

        // Password is only touched when a new one is supplied.
        if(!empty($data['password'])) {
            $sql .= ", password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        return $this->db->query($sql, $params);
    }

    public function delete($id) {
        return $this->db->query("DELETE FROM users WHERE id = ?", [intval($id)]);
    }

    /** Number of admins left if $excludeId were demoted or deleted. */
    public function countAdmins($excludeId = 0) {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS c FROM users WHERE role = 'admin' AND id <> ?",
            [intval($excludeId)]
        );
        return (int)$row['c'];
    }

    public function getAll() {
        return $this->db->fetchAll("SELECT id, name, email, role, created_at FROM users ORDER BY id DESC");
    }
}
?>
