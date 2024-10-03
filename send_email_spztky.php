<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);  // Enable error reporting

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'tcpdf/tcpdf.php';  // Include the TCPDF library
require 'phpqrcode/qrlib.php';  // Include the PHP QR Code library

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

    // Collect form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $subject = $_POST['subject'];
    $request = $_POST['request'];
    $spzImages = json_decode($_POST['spz_images'], true); // SPZ images from form (base64)
    $spzCount = count($spzImages);  // Total number of SPZ entered
    $pricePerSpz = 55; // Price per SPZ
    $freeSpz = floor($spzCount / 5); // Calculate free SPZ based on every 5th being free
    $spzTotal = ($spzCount - $freeSpz) * $pricePerSpz; // Total price considering free SPZ
    $shippingCost = $spzCount >= 12 ? 0 : 80; // Free shipping if 12 or more SPZ
    $totalPrice = $spzTotal + $shippingCost;
    

    // Generate order number and variable symbol in YYMMDDHHMMSS format
    $orderNumber = date('mdHis');

    // --- Vytvoření správného IBAN a QR kódu ---
    $iban = 'CZ7106000000000262192938'; // Správný IBAN pro 262192938/0600
    $amount = number_format($totalPrice, 2, '.', ''); // Částka k zaplacení ve správném formátu
    $message = "Objednávka $orderNumber"; // Zpráva pro příjemce
    $vs = $orderNumber; // Variabilní symbol je číslo objednávky

    // Formát QR platby podle standardu
    $qrPlatbaData = "SPD*1.0*ACC:$iban*AM:$amount*CC:CZK*MSG:$message*X-VS:$vs";

    // Vytvoření QR kódu a uložení do souboru
    $qrCodeFileName = __DIR__ . '/qrcode_' . time() . '.png';
    QRcode::png($qrPlatbaData, $qrCodeFileName, QR_ECLEVEL_L, 5);  // Generate the QR code

    // --- Create PDF using TCPDF ---
    $pdf = new TCPDF();
    $pdf->SetMargins(15, 15, 15);  // Set margins
    $pdf->AddPage();

    // Set smaller line height ratio for reduced line spacing
    $pdf->setCellHeightRatio(0.8); // Default is 1.0, reducing it to 0.8

    // Add the custom font (DejaVu)
    $pdf->SetFont('dejavusans', '', 12);

    // --- Header ---
    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->Cell(0, 15, 'Objednávka: ' . $orderNumber, 0, 1, 'C'); // Kombinace textu "Objednávka" a čísla objednávky

    // --- Dodavatel & Odběratel ---
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(95, 10, 'Dodavatel:', 0, 0, 'L');
    $pdf->Cell(95, 10, 'Odběratel:', 0, 1, 'L');

    $pdf->SetFont('dejavusans', '', 10);
    $pdf->Cell(95, 10, 'Marek Halška', 0, 0, 'L');
    $pdf->Cell(95, 10, "{$name}", 0, 1, 'L');
    $pdf->Cell(95, 10, 'Heřmanická 1280, Rychvald, 73532', 0, 0, 'L');
    $pdf->Cell(95, 10, "Telefon: {$phone}", 0, 1, 'L');
    $pdf->Cell(95, 10, 'IČ: 04372191, Nejsme plátci DPH', 0, 0, 'L');
    $pdf->Cell(95, 10, "Email: {$email}", 0, 1, 'L');
    $pdf->Ln(10);  // Add spacing

    // --- Bankovní Údaje ---
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(40, 10, 'Bankovní účet:', 0, 0, 'L');
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->Cell(0, 10, '262192938/0600', 0, 1, 'L');

    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(40, 10, 'Variabilní symbol:', 0, 0, 'L');
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->Cell(0, 10, $orderNumber, 0, 1, 'L'); // Use same number for variabilní symbol

    $pdf->Ln(5);  // Add spacing

    // --- Date Info ---
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->Cell(0, 10, 'Datum vystavení: ' . date('d.m.Y'), 0, 1, 'L');
    $pdf->Cell(0, 10, 'Platnost do: ' . date('d.m.Y', strtotime('+14 days')), 0, 1, 'L');
    $pdf->Ln(5);  // Add spacing

    // --- Table Header ---
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->SetFillColor(200, 200, 200);  // Gray background
    $pdf->Cell(80, 10, 'Označení dodávky', 1, 0, 'C', 1);
    $pdf->Cell(30, 10, 'Počet', 1, 0, 'C', 1);
    $pdf->Cell(40, 10, 'Cena za ks', 1, 0, 'C', 1);
    $pdf->Cell(40, 10, 'Celkem', 1, 1, 'C', 1);

    // --- Table Items ---
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->Cell(80, 10, 'SPZtky', 1);
    $pdf->Cell(30, 10, $spzCount, 1, 0, 'C');
    $pdf->Cell(40, 10, '55 Kč', 1, 0, 'C');
    $pdf->Cell(40, 10, ($spzCount * (55)) . ' Kč', 1, 1, 'C');

    // Add free SPZ row if applicable
    if ($freeSpz > 0) {
        $pdf->Cell(80, 10, 'SPZtky SLEVA', 1);
        $pdf->Cell(30, 10, $freeSpz, 1, 0, 'C');
        $pdf->Cell(40, 10, '- 55 Kč', 1, 0, 'C');
        $pdf->Cell(40, 10, '-' . ($freeSpz * (55)) . ' Kč', 1, 1, 'C');
    }

    // --- Total Price ---
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(150, 10, 'Cena celkem:', 1, 0, 'R');
    $pdf->Cell(40, 10, "{$totalPrice} Kč", 1, 1, 'C');

    // Přidání QR kódu do PDF
    $pdf->Ln(10); // Přidat prostor před QR kódem
    $pdf->Image($qrCodeFileName, 80, $pdf->GetY(), 50, 50); // Přidání QR kódu (nastavit pozici a velikost)

    // Save PDF to file
    $pdfFileName = __DIR__ . '/objednavka_' . time() . '.pdf';  // Use absolute or relative path
    $pdf->Output($pdfFileName, 'F');  // Save the file

    // --- Send email ---
    $mail->setFrom('info@printm.cz', 'Print M');
    $mail->addAddress($email); // Send email to the customer
    $mail->addAddress('info@printm.cz'); // Send a copy to your email

    // Attach the PDF
    $mail->addAttachment($pdfFileName);

    // Send the email
    $mail->isHTML(true);
    $mail->Subject = 'Objednávkový formulář - ' . $subject;
    $mail->Body = "<h1>Děkujeme za Vaši objednávku</h1>
                   <p>Přílohou naleznete objednávkový formulář ve formátu PDF.</p>
                   <p><strong>Jméno:</strong> {$name}</p>
                   <p><strong>Telefon:</strong> {$phone}</p>
                   <p><strong>Email:</strong> {$email}</p>";

    // Send the email
    $mail->send();

    // Return success and the QR code URL to the frontend
    echo json_encode([
        'success' => true,
        'qrCodeUrl' => basename($qrCodeFileName) // Send back the QR code filename
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => "Chyba při odesílání zprávy: {$e->getMessage()}"
    ]);
}
