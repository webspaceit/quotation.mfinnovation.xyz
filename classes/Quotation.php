<?php
class Quotation {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function generateNumber() {
        $year = date('Y');
        $prefix = "QT-$year-";
        $last = $this->db->fetch("SELECT quotation_number FROM quotations WHERE quotation_number LIKE ? ORDER BY id DESC LIMIT 1", [$prefix.'%']);
        if($last) {
            $num = intval(substr($last['quotation_number'], -4)) + 1;
        } else {
            $num = 1;
        }
        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
    
    public function create($data) {
        $number = $this->generateNumber();
        $validUntil = date('Y-m-d', strtotime('+' . ($data['validity_days'] ?? 30) . ' days'));
        
        $this->db->query(
            "INSERT INTO quotations (quotation_number, client_id, subject, introduction, overview, support_services, time_period, validity_text, terms_conditions, status, prepared_by, signature_image, authorized_signature, quotation_date, valid_until, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?, ?, ?, ?, NOW())",
            [
                $number, $data['client_id'], $data['subject'], $data['introduction'],
                $data['overview'], $data['support_services'], $data['time_period'],
                $data['validity_text'], $data['terms_conditions'], $data['prepared_by'],
                $data['signature_image'] ?? null,
                $data['authorized_signature'] ?? null,
                $data['quotation_date'], $validUntil
            ]
        );
        return $this->db->lastInsertId();
    }
    
    public function update($id, $data) {
        $validUntil = date('Y-m-d', strtotime('+' . ($data['validity_days'] ?? 30) . ' days'));
        return $this->db->query(
            "UPDATE quotations SET client_id=?, subject=?, introduction=?, overview=?, support_services=?, time_period=?, validity_text=?, terms_conditions=?, prepared_by=?, signature_image=?, authorized_signature=?, quotation_date=?, valid_until=?, updated_at=NOW() WHERE id=?",
            [
                $data['client_id'], $data['subject'], $data['introduction'],
                $data['overview'], $data['support_services'], $data['time_period'],
                $data['validity_text'], $data['terms_conditions'], $data['prepared_by'],
                $data['signature_image'] ?? null,
                $data['authorized_signature'] ?? null,
                $data['quotation_date'], $validUntil, $id
            ]
        );
    }
    
    public function updateTotals($id, $subtotal, $taxPercent, $taxAmount, $grandTotal) {
        return $this->db->query(
            "UPDATE quotations SET subtotal=?, tax_percent=?, tax_amount=?, grand_total=?, updated_at=NOW() WHERE id=?",
            [$subtotal, $taxPercent, $taxAmount, $grandTotal, $id]
        );
    }
    
    public function getById($id) {
        $q = $this->db->fetch("SELECT q.*, c.name as client_name, c.company, c.email as client_email, c.phone as client_phone, c.address as client_address FROM quotations q LEFT JOIN clients c ON c.id = q.client_id WHERE q.id=?", [$id]);
        if($q) {
            $q['items'] = $this->db->fetchAll("SELECT * FROM quotation_items WHERE quotation_id=? ORDER BY sort_order ASC", [$id]);
        }
        return $q;
    }
    
    public function getAll($status = null) {
        $sql = "SELECT q.*, c.name as client_name, c.company FROM quotations q LEFT JOIN clients c ON c.id = q.client_id";
        $params = [];
        if($status) {
            $sql .= " WHERE q.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY q.id DESC";
        return $this->db->fetchAll($sql, $params);
    }
    
    public function updateStatus($id, $status) {
        return $this->db->query("UPDATE quotations SET status=?, updated_at=NOW() WHERE id=?", [$status, $id]);
    }
    
    public function duplicate($id) {
        $orig = $this->getById($id);
        if(!$orig) return false;
        $newId = $this->create([
            'client_id' => $orig['client_id'],
            'subject' => $orig['subject'] . ' (Copy)',
            'introduction' => $orig['introduction'],
            'overview' => $orig['overview'],
            'support_services' => $orig['support_services'],
            'time_period' => $orig['time_period'],
            'validity_text' => $orig['validity_text'],
            'terms_conditions' => $orig['terms_conditions'],
            'prepared_by' => $orig['prepared_by'],
            'signature_image' => $orig['signature_image'],
            'authorized_signature' => $orig['authorized_signature'],
            'quotation_date' => date('Y-m-d'),
            'validity_days' => 30
        ]);
        foreach($orig['items'] as $item) {
            $this->db->query(
                "INSERT INTO quotation_items (quotation_id, sort_order, description, quantity, unit_price, amount, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$newId, $item['sort_order'], $item['description'], $item['quantity'], $item['unit_price'], $item['amount'], $item['remarks']]
            );
        }
        $this->updateTotals($newId, $orig['subtotal'], $orig['tax_percent'], $orig['tax_amount'], $orig['grand_total']);
        return $newId;
    }
    
    public function delete($id) {
        $this->db->query("DELETE FROM quotation_items WHERE quotation_id=?", [$id]);
        return $this->db->query("DELETE FROM quotations WHERE id=?", [$id]);
    }
    
    public function getStats() {
        $stats = [];
        $stats['total'] = $this->db->fetch("SELECT COUNT(*) as c FROM quotations")['c'];
        $stats['draft'] = $this->db->fetch("SELECT COUNT(*) as c FROM quotations WHERE status='draft'")['c'];
        $stats['sent'] = $this->db->fetch("SELECT COUNT(*) as c FROM quotations WHERE status='sent'")['c'];
        $stats['accepted'] = $this->db->fetch("SELECT COUNT(*) as c FROM quotations WHERE status='accepted'")['c'];
        $stats['rejected'] = $this->db->fetch("SELECT COUNT(*) as c FROM quotations WHERE status='rejected'")['c'];
        return $stats;
    }
}
?>
