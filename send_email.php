<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.seznam.cz';
    $mail->SMTPAuth = true;
    $mail->Username = 'kamsvatba@seznam.cz';  // Your username
    $mail->Password = 'Marekkiki';  // Your password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';

    // Recipients
    $mail->setFrom('kamsvatba@seznam.cz', 'Kam svatba');
    $mail->addAddress('kamsvatba@seznam.cz', 'Příjemce');
    $mail->addAddress('marek.halska@gmail.com', 'Marek Halska');

    // Collect form data
    $name = $_POST['name'] ?? 'Není zadáno';
    $email = $_POST['email'] ?? 'Není zadáno';
    $phone = $_POST['phone'] ?? 'Není zadáno';
    $subject = $_POST['subject'] ?? 'Není zadáno';
    $request = $_POST['request'] ?? 'Není zadáno';
    $color = $_POST['color'] ?? 'Není zadáno';
    $use_location = $_POST['use_location'] ?? 'Není zadáno';
    $delivery = $_POST['delivery'] ?? 'Není zadáno';

    // Handle file uploads (limit to 25MB total)
    $totalFileSize = 0;
    $maxFileSize = 25 * 1024 * 1024; // 25MB in bytes

    if (!empty($_FILES['files']['name'][0])) {
        for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
            $file_name = $_FILES['files']['name'][$i];
            $file_size = $_FILES['files']['size'][$i];
            $file_tmp = $_FILES['files']['tmp_name'][$i];

            // Check file size
            if ($file_size > 0 && $totalFileSize + $file_size <= $maxFileSize) {
                $totalFileSize += $file_size;
                // Add file as an attachment
                $mail->addAttachment($file_tmp, $file_name);
            } else {
                throw new Exception('Celková velikost příloh překračuje 25MB.');
            }
        }
    }

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Nová zpráva z webu - ' . $subject;
    $mail->Body    = "<h1>Nová zpráva z webu</h1>
                      <p><strong>Jméno:</strong> {$name}</p>
                      <p><strong>Email:</strong> {$email}</p>
                      <p><strong>Telefon:</strong> {$phone}</p>
                      <p><strong>Předmět:</strong> {$subject}</p>
                      <p><strong>Požadavek:</strong> {$request}</p>
                      <p><strong>Barva:</strong> {$color}</p>
                      <p><strong>Kde bude výtisk používán:</strong> {$use_location}</p>
                      <p><strong>Doprava:</strong> {$delivery}</p>";
    $mail->AltBody = "Nová zpráva z webu\nJméno: {$name}\nEmail: {$email}\nTelefon: {$phone}\nPředmět: {$subject}\nPožadavek: {$request}\nBarva: {$color}\nKde bude výtisk používán: {$use_location}\nDoprava: {$delivery}";

    // Send the email
    $mail->send();
    header('Location: index.html?success=1'); // Redirect back to the form page
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$e->getMessage()}";
}
