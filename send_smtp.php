<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';

/**
 * ΡΥΘΜΙΣΕΙΣ
 * - Από ποιο Gmail θα στέλνει ο server (ίδιο με αυτό που θα λαμβάνει για απλότητα)
 * - App Password αυτού του Gmail
 */
$gmailUser = "nomosarmenakas@gmail.com";            // αυτό θα είναι το "From"
$gmailAppPassword = "eappxinxioepcwev";     // App Password του nomosarmenakas@gmail.com
$toEmail = "nomosarmenakas@gmail.com";              // παραλήπτης

// Honeypot anti-spam
if (!empty($_POST['website'] ?? '')) {
  http_response_code(400);
  exit("Bad request.");
}

function clean($v) {
  $v = trim($v ?? "");
  $v = str_replace(["\r", "\n"], " ", $v);
  return $v;
}

$name  = clean($_POST['name'] ?? "");
$phone = clean($_POST['phone'] ?? "");
$email = trim($_POST['email'] ?? "");
$msg   = trim($_POST['message'] ?? ""); // προαιρετικό

// Υποχρεωτικά: name, phone, email
if ($name === "" || $phone === "" || $email === "") {
  http_response_code(400);
  exit("Συμπλήρωσε ονοματεπώνυμο, τηλέφωνο και email.");
}

// Έλεγχος ότι το email είναι σωστό format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  exit("Το email δεν είναι έγκυρο.");
}

// Αν δεν γράψει μήνυμα, βάζουμε κάτι αξιοπρεπές
if ($msg === "") {
  $msg = "(Δεν καταχωρήθηκε μήνυμα.)";
}

$body =
"Νέο αίτημα από την ιστοσελίδα:\n\n" .
"Ονοματεπώνυμο: $name\n" .
"Τηλέφωνο: $phone\n" .
"Email: $email\n\n" .
"Μήνυμα:\n$msg\n\n" .
"IP: " . ($_SERVER['REMOTE_ADDR'] ?? "unknown") . "\n";

$mail = new PHPMailer(true);

try {
  $mail->CharSet = 'UTF-8';
  $mail->isSMTP();
  $mail->Host = 'smtp.gmail.com';
  $mail->SMTPAuth = true;
  $mail->Username = $gmailUser;
  $mail->Password = $gmailAppPassword; // App Password
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port = 587;

  // Το From ΠΡΕΠΕΙ να είναι το δικό σου Gmail (όχι του πελάτη)
  $mail->setFrom($gmailUser, 'Φόρμα Ιστοσελίδας');

  // Αυτό είναι το κλειδί: όταν πατήσεις Reply, θα πάει στον πελάτη
  $mail->addReplyTo($email, $name);

  // Παραλήπτης: ο δικηγόρος
  $mail->addAddress($toEmail);

  $mail->Subject = 'Νέο μήνυμα από την ιστοσελίδα';
  $mail->Body = $body;

  $mail->send();
  header("Location: success.html");
  exit;

} catch (Exception $e) {
  http_response_code(500);
  echo "Αποτυχία αποστολής: " . htmlspecialchars($mail->ErrorInfo);
}

