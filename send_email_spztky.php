<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);  // Enable error reporting

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'tcpdf/tcpdf.php';  // Include the TCPDF library

$mail = new PHPMailer(true);

try {
    // SMTP settings
    $mail->isSMTP();
    $mail->Host = 'smtp.forpsi.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'info@printm.cz';
    $mail->Password = 's_a9eMAc2W';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';

    // Enable debugging for SMTP
    $mail->SMTPDebug = 2;  // Set to 2 for full debug output

    // Collect form data
    $name = $_POST['name'] ?? 'Unknown';  // Add default value if missing
    $email = $_POST['email'] ?? 'Unknown';
    $phone = $_POST['phone'] ?? 'Unknown';
    $subject = $_POST['subject'] ?? 'Unknown';
    $request = $_POST['request'] ?? 'Unknown';
    $spzImages = json_decode($_POST['spz_images'], true); // Decode the base64 images
    $spzCount = count($spzImages);
    $pricePerSpz = 55; // Price per SPZ
    $spzTotal = $spzCount * $pricePerSpz;
    $shippingCost = $spzCount >= 12 ? 0 : 80; // Free shipping if 12 or more
    $totalPrice = $spzTotal + $shippingCost;

    // Check if form inputs are not missing
    if (!$name || !$email || !$subject || !$phone) {
        throw new Exception("Missing form inputs");
    }

    // Create PDF using TCPDF
    $pdf = new TCPDF();
    $pdf->AddPage();

    // Add the custom font (DejaVu)
    $pdf->SetFont('dejavusans', '', 12);

    // Company Information
    $pdf->Cell(0, 10, 'Jméno firmy: Marek Halška', 0, 1);
    $pdf->Cell(0, 10, 'Adresa: Heřmanická 1280, Rychvald, 73532', 0, 1);
    $pdf->Ln(20);

    // Customer Information
    $pdf->Cell(0, 10, "Zákazník: {$name}", 0, 1);
    $pdf->Cell(0, 10, "Telefon: {$phone}", 0, 1);
    $pdf->Cell(0, 10, "Email: {$email}", 0, 1);
    $pdf->Ln(20);

    // Order Items
    $pdf->Cell(0, 10, "Položky:", 0, 1);
    $pdf->Cell(0, 10, "1. SPZtky (počet: {$spzCount}), Cena: {$spzTotal} Kč", 0, 1);
    $pdf->Cell(0, 10, "2. Doprava, Cena: {$shippingCost} Kč", 0, 1);
    $pdf->Ln(20);
    
    $pdf->Cell(0, 10, "Celkem k zaplacení: {$totalPrice} Kč", 0, 1);

    // Define a local path to save the PDF
    $pdfFileName = __DIR__ . '/objednavka_' . time() . '.pdf';  // Use absolute or relative path

    // Save PDF to file
    $pdf->Output($pdfFileName, 'F');  // 'F' stands for "File"

    // Send email
    $mail->setFrom('info@printm.cz', 'Print M');
    $mail->addAddress($email); // Send email to the customer
    $mail->addAddress('info@printm.cz'); // Send a copy to your email

    // Attach the PDF
    $mail->addAttachment($pdfFileName);

    // Attach SPZ images as files
    foreach ($spzImages as $index => $spzImage) {
        $imageData = explode(',', $spzImage)[1]; // Remove the data URL header
        $imageContent = base64_decode($imageData); // Decode the base64 data
        $mail->addStringAttachment($imageContent, "spz_$index.png"); // Attach as a PNG file
    }

    // Email content
    $mail->isHTML(true);
    $mail->Subject = 'Objednávkový formulář - ' . $subject;
    $mail->Body = "<h1>Děkujeme za Vaši objednávku</h1>
                   <p>Přílohou naleznete objednávkový formulář ve formátu PDF.</p>
                   <p><strong>Jméno:</strong> {$name}</p>
                   <p><strong>Telefon:</strong> {$phone}</p>
                   <p><strong>Email:</strong> {$email}</p>";

    // Send the email
    $mail->send();
    echo "Zpráva byla odeslána.";
} catch (Exception $e) {
    echo "Chyba při odesílání zprávy: {$e->getMessage()}";
}
