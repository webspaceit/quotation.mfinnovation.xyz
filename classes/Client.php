<?php
class Client {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        return $this->db->query(
            "INSERT INTO clients (name, company, email, phone, address, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
            [$data['name'], $data['company'], $data['email'], $data['phone'], $data['address']]
        );
    }
    
    public function update($id, $data) {
        return $this->db->query(
            "UPDATE clients SET name=?, company=?, email=?, phone=?, address=?, updated_at=NOW() WHERE id=?",
            [$data['name'], $data['company'], $data['email'], $data['phone'], $data['address'], $id]
        );
    }
    
    public function delete($id) {
        return $this->db->query("DELETE FROM clients WHERE id=?", [$id]);
    }
    
    public function getById($id) {
        return $this->db->fetch("SELECT * FROM clients WHERE id=?", [$id]);
    }
    
    public function getAll() {
        return $this->db->fetchAll("SELECT * FROM clients ORDER BY id DESC");
    }
    
    public function search($term) {
        $term = "%$term%";
        return $this->db->fetchAll("SELECT * FROM clients WHERE name LIKE ? OR company LIKE ? OR email LIKE ? ORDER BY name LIMIT 20", [$term, $term, $term]);
    }
}
?>
