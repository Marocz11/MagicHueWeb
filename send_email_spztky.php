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

    // Set font for Czech characters
    $pdf->SetFont('dejavusans', '', 12);

    // Supplier Info (Dodavatel)
    $html = <<<EOD
    <h2>Objednávka</h2>
    <p><strong>Dodavatel:</strong><br />
    Marek Halška<br />
    Heřmanická 1280<br />
    73532 Rychvald<br />
    IČ: 04372191<br />
    Nejsme plátci DPH</p>
    <p><strong>Kontaktní údaje:</strong><br />
    E-mail: info@printm.cz<br />
    Telefon: +420 737 359 994</p>
    EOD;

    // Customer Info (Odběratel) - from form
    $html .= <<<EOD
    <p><strong>Odběratel:</strong><br />
    {$name}<br />
    Telefon: {$phone}<br />
    E-mail: {$email}</p>
    EOD;

    // Order Items
    $html .= <<<EOD
    <table border="1" cellpadding="4">
    <tr>
        <th><strong>Položka</strong></th>
        <th><strong>Počet</strong></th>
        <th><strong>Cena za ks</strong></th>
        <th><strong>Celkem</strong></th>
    </tr>
    <tr>
        <td>SPZtky</td>
        <td>{$spzCount}</td>
        <td>{$pricePerSpz} Kč</td>
        <td>{$spzTotal} Kč</td>
    </tr>
    <tr>
        <td>Doprava</td>
        <td>-</td>
        <td>-</td>
        <td>{$shippingCost} Kč</td>
    </tr>
    <tr>
        <td colspan="3" align="right"><strong>Celkem k zaplacení:</strong></td>
        <td>{$totalPrice} Kč</td>
    </tr>
    </table>
    EOD;

    // Output HTML content to PDF
    $pdf->writeHTML($html, true, false, true, false, '');

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
