<?php
require_once 'config/database.php';
$db = Database::getInstance();

// Add sample client
$db->query("INSERT IGNORE INTO clients (name, company, email, phone, address) VALUES 
('Rahim Uddin', 'Tech Solutions Ltd', 'rahim@techsolutions.com', '+880 1712345678', 'House 45, Road 12, Dhanmondi, Dhaka')");

// Add sample quotation
$q = new Quotation();
$client = $db->fetch("SELECT id FROM clients WHERE email = 'rahim@techsolutions.com'");
$company = new Company();
$comp = $company->getSettings();

$qId = $q->create([
    'client_id' => $client['id'],
    'subject' => 'Quotation for Domain, Hosting & Membership/Document Management Website',
    'introduction' => "Dear Rahim Uddin,\n\nThank you for your inquiry. We are pleased to submit our quotation as follows:",
    'overview' => "• Domain Registration & Management\n• Web Hosting (Shared/VPS)\n• Website Design & Development\n• Membership/Document Management System\n• SSL Certificate\n• Email Services",
    'support_services' => "• 24/7 Technical Support\n• Free Maintenance for 3 Months\n• Regular Backups\n• Security Updates",
    'time_period' => "Project will be completed within 15-20 working days from advance payment.",
    'validity_text' => "This offer is valid for 30 days from the date of quotation.",
    'terms_conditions' => $comp['default_terms'],
    'prepared_by' => 'Administrator',
    'quotation_date' => date('Y-m-d'),
    'validity_days' => 30
]);

// Add sample items
$db->query("INSERT INTO quotation_items (quotation_id, sort_order, description, quantity, unit_price, amount) VALUES 
($qId, 0, 'Domain Registration (.com) - 1 Year', 1, 1200.00, 1200.00),
($qId, 1, 'Web Hosting (Linux Shared Hosting) - 1 Year\n- Unlimited Bandwidth\n- 10GB Disk Space\n- cPanel Access\n- Free SSL', 1, 3500.00, 3500.00),
($qId, 2, 'VPS Server Setup & Configuration\n- Intel Xeon Processor\n- 4GB RAM\n- 80GB SSD\n- 1TB Bandwidth\n- Root Access\n- CentOS/Ubuntu', 1, 15000.00, 15000.00),
($qId, 3, 'Website Design & Development with Membership System\n- Responsive Design\n- User Registration/Login\n- Document Management\n- Admin Panel\n- Payment Gateway Integration', 1, 25000.00, 25000.00),
($qId, 4, 'SSL Certificate (Comodo/Let''s Encrypt) - 1 Year', 1, 1500.00, 1500.00)");

$q->updateTotals($qId, 46200, 0, 0, 46200);
$q->updateStatus($qId, 'draft');

echo "Sample quotation created successfully! Quotation ID: $qId<br><a href='admin/quotations/view.php?id=$qId'>View Quotation</a>";
?>
