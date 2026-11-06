<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../classes/Quotation.php';
require_once '../classes/Company.php';

header('Content-Type: application/json');

function json_fail($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function valid_email_list($value) {
    if(trim($value) === '') return true;
    foreach(explode(',', $value) as $addr) {
        if(!filter_var(trim($addr), FILTER_VALIDATE_EMAIL)) return false;
    }
    return true;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_fail('Invalid request', 405);
}

// Authentication. This endpoint previously had none, which made it an open
// relay through the company SMTP account for any anonymous visitor.
$authUser = new User();
if(!$authUser->isLoggedIn()) {
    json_fail('You are not signed in. Please sign in and try again.', 401);
}

// CSRF failure must return JSON, otherwise the client cannot tell the
// difference between an expired session and a broken mail server.
if(!csrf_valid()) {
    // 403, not 419: Apache rewrites non-standard status codes to 500.
    json_fail('Your session expired. Reload the page and try again.', 403);
}

$quotationId = intval($_POST['quotation_id'] ?? 0);
if($quotationId <= 0) {
    json_fail('Invalid quotation reference.');
}

$to      = trim($_POST['to_email'] ?? '');
$cc      = trim($_POST['cc'] ?? '');
$bcc     = trim($_POST['bcc'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = (string)($_POST['message'] ?? '');
$attachPdf = isset($_POST['attach_pdf']);

if($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    json_fail('A valid recipient email address is required.');
}
if(!valid_email_list($cc)) {
    json_fail('One of the CC addresses is not a valid email address.');
}
if(!valid_email_list($bcc)) {
    json_fail('One of the BCC addresses is not a valid email address.');
}

// Strip CR/LF so nothing can be injected into mail headers.
$subject = trim(preg_replace('/[\r\n]+/', ' ', $subject));
$message = str_replace(["\r\n", "\r"], "\n", $message);

$quotation = new Quotation();
$company = new Company();
$comp = $company->getSettings();
$q = $quotation->getById($quotationId);

if(!$q) {
    json_fail('Quotation not found.', 404);
}

if($subject === '') {
    $subject = 'Quotation ' . $q['quotation_number'] . ' - ' . $comp['company_name'];
}

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
    $fromName = preg_replace('/[\r\n]+/', ' ', $fromName);
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
