<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../classes/Quotation.php';
require_once '../classes/Company.php';

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

verify_csrf();

$quotation = new Quotation();
$company = new Company();
$comp = $company->getSettings();
$q = $quotation->getById(intval($_POST['quotation_id']));

if(!$q) {
    echo json_encode(['success' => false, 'message' => 'Quotation not found']);
    exit;
}

$to = $_POST['to_email'];
$cc = $_POST['cc'] ?? '';
$bcc = $_POST['bcc'] ?? '';
$subject = $_POST['subject'];
$message = $_POST['message'];
$attachPdf = isset($_POST['attach_pdf']);

// Try to use PHPMailer if available
$sent = false;
$errorMsg = '';

if(file_exists('../vendor/PHPMailer/src/PHPMailer.php')) {
    require_once '../vendor/PHPMailer/src/Exception.php';
    require_once '../vendor/PHPMailer/src/PHPMailer.php';
    require_once '../vendor/PHPMailer/src/SMTP.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        if(!empty($comp['smtp_host'])) {
            $mail->isSMTP();
            $mail->Host = $comp['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $comp['smtp_username'];
            $mail->Password = $comp['smtp_password'];
            if($comp['smtp_encryption'] === 'tls') $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            elseif($comp['smtp_encryption'] === 'ssl') $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $comp['smtp_port'] ?: 587;
        }
        
        $fromEmail = $comp['from_email'] ?: $comp['email'];
        $fromName = $comp['from_name'] ?: $comp['company_name'];
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);
        if($cc) $mail->addCC($cc);
        if($bcc) $mail->addBCC($bcc);
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = nl2br($message);
        
        if($attachPdf) {
            // Generate simple HTML to PDF using browser approach - save as PDF alternative
            // For pure PHP without Composer, we'll create a temp HTML file
            $pdfContent = generatePdfHtml($q, $comp);
            $mail->addStringAttachment($pdfContent, 'Quotation_' . $q['quotation_number'] . '.html', 'base64', 'text/html');
        }
        
        $mail->send();
        $sent = true;
    } catch(Exception $e) {
        $errorMsg = $e->getMessage();
    }
} else {
    // Fallback to mail()
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $fromEmail = $comp['from_email'] ?: $comp['email'];
    $fromName = $comp['from_name'] ?: $comp['company_name'];
    $headers .= "From: $fromName <$fromEmail>\r\n";
    if($cc) $headers .= "Cc: $cc\r\n";
    
    $htmlMessage = nl2br($message);
    $sent = @mail($to, $subject, $htmlMessage, $headers);
    if(!$sent) $errorMsg = 'Mail function failed';
}

if($sent) {
    $db = Database::getInstance();
    $db->query(
        "INSERT INTO email_logs (quotation_id, sent_to, cc, bcc, subject, message, sent_by, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'success', NOW())",
        [$q['id'], $to, $cc, $bcc, $subject, $message, $_SESSION['user_id']]
    );
    $quotation->updateStatus($q['id'], 'sent');
    echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
} else {
    $db = Database::getInstance();
    $db->query(
        "INSERT INTO email_logs (quotation_id, sent_to, cc, bcc, subject, message, sent_by, status, error_message, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'failed', ?, NOW())",
        [$q['id'], $to, $cc, $bcc, $subject, $message, $_SESSION['user_id'], $errorMsg]
    );
    echo json_encode(['success' => false, 'message' => $errorMsg ?: 'Failed to send email']);
}

function generatePdfHtml($q, $comp) {
    ob_start();
    include '../admin/quotations/preview.php';
    return ob_get_clean();
}
?>
