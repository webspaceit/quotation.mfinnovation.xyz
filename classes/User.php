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
        $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
        return $this->db->query(
            "INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())",
            [$data['name'], $data['email'], $hashed, $data['role']]
        );
    }
    
    public function getAll() {
        return $this->db->fetchAll("SELECT id, name, email, role, created_at FROM users ORDER BY id DESC");
    }
}
?>
