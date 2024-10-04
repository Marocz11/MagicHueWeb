<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'tcpdf/tcpdf.php'; 
require 'phpqrcode/qrlib.php';  

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
    $pickupLocation = $_POST['pickupLocation'];
    $subject = $_POST['subject'];
    $request = $_POST['request'];
    $spzImages = json_decode($_POST['spz_images'], true);
    
    $totalSpzCount = intval($_POST['total_spz_count']);
    $freeSpzCount = intval($_POST['free_spz_count']);
    $shippingCost = intval($_POST['shipping_cost']);
    $totalPrice = floatval($_POST['total_price']);
    
    // Generate order number
    $orderNumber = date('mdHis');
    
    // Create IBAN and QR code
    $iban = 'CZ7106000000000262192938';
    $amount = number_format($totalPrice, 2, '.', '');
    $message = "Objednávka $orderNumber";
    $vs = $orderNumber;
    $qrPlatbaData = "SPD*1.0*ACC:$iban*AM:$amount*CC:CZK*MSG:$message*X-VS:$vs";
    
    $qrCodeFileName = __DIR__ . '/qrcode_' . time() . '.png';
    QRcode::png($qrPlatbaData, $qrCodeFileName, QR_ECLEVEL_L, 5);
    
    // --- Create PDF ---
    $pdf = new TCPDF();
    $pdf->SetMargins(15, 15, 15);  
    $pdf->AddPage();
    $pdf->setCellHeightRatio(0.8);
    $pdf->SetFont('dejavusans', '', 12);
    
    // --- Header ---
    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->Cell(0, 15, 'Objednávka: ' . $orderNumber, 0, 1, 'C');
    
    // --- Supplier & Buyer ---
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
    $pdf->Ln(10);
    
    // --- Bank Details ---
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(40, 10, 'Bankovní účet:', 0, 0, 'L');
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->Cell(0, 10, '262192938/0600', 0, 1, 'L');
    
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(40, 10, 'Variabilní symbol:', 0, 0, 'L');
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->Cell(0, 10, $orderNumber, 0, 1, 'L');
    
    $pdf->Ln(5);
    
    // --- Pickup Location ---
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(40, 10, 'Výdejní místo:', 0, 0, 'L');
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->Cell(0, 10, $pickupLocation, 0, 1, 'L');
    
    // --- Table Header ---
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->SetFillColor(200, 200, 200); 
    $pdf->Cell(80, 10, 'Označení dodávky', 1, 0, 'C', 1);
    $pdf->Cell(30, 10, 'Počet', 1, 0, 'C', 1);
    $pdf->Cell(40, 10, 'Cena za ks', 1, 0, 'C', 1);
    $pdf->Cell(40, 10, 'Celkem', 1, 1, 'C', 1);
    
    // --- Table Items ---
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->Cell(80, 10, 'SPZtky', 1);
    $pdf->Cell(30, 10, $totalSpzCount, 1, 0, 'C');
    $pdf->Cell(40, 10, '55 Kč', 1, 0, 'C');
    $pdf->Cell(40, 10, ($totalSpzCount * 55) . ' Kč', 1, 1, 'C');
    
    // Add free SPZ row if applicable
    if ($freeSpzCount > 0) {
        $pdf->Cell(80, 10, 'SPZtky SLEVA', 1);
        $pdf->Cell(30, 10, $freeSpzCount, 1, 0, 'C');
        $pdf->Cell(40, 10, '- 55 Kč', 1, 0, 'C');
        $pdf->Cell(40, 10, '-' . ($freeSpzCount * 55) . ' Kč', 1, 1, 'C');
    }

    // Add shipping cost row
    $pdf->Cell(80, 10, 'Doprava', 1);
    $pdf->Cell(30, 10, 1, 1, 0, 'C');
    $pdf->Cell(40, 10, $shippingCost . ' Kč', 1, 0, 'C');
    $pdf->Cell(40, 10, $shippingCost . ' Kč', 1, 1, 'C');

    // --- Total Price ---
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(150, 10, 'Cena celkem:', 1, 0, 'R');
    $pdf->Cell(40, 10, "{$totalPrice} Kč", 1, 1, 'C');
    
    // Add QR code
    $pdf->Ln(10);
    $pdf->Image($qrCodeFileName, 80, $pdf->GetY(), 50, 50);
    
    $pdfFileName = __DIR__ . '/objednavka_' . time() . '.pdf';
    $pdf->Output($pdfFileName, 'F');
    
    // --- Send email to Customer ---
    $mail->setFrom('info@printm.cz', 'Print M');
    $mail->addAddress($email);  // Customer's email
    
    // Attach PDF to customer email
    $mail->addAttachment($pdfFileName);
    $mail->isHTML(true);
    $mail->Subject = 'Objednávkový formulář - ' . $subject;
    $mail->Body = "<h1>Děkujeme za Vaši objednávku</h1><p>Přílohou naleznete objednávkový formulář ve formátu PDF.</p>";
    $mail->send();  // Send to customer
    
    // --- Clear Recipients and Attachments ---
    $mail->clearAddresses();
    $mail->clearAttachments();
    
    // --- Send email to Company ---
    $mail->addAddress('info@printm.cz');  // Company email
    $mail->addAttachment($pdfFileName);   // Attach the same PDF for the company
    $mail->send();  // Send to company

    // Return success and the QR code URL
    echo json_encode([
        'success' => true,
        'qrCodeUrl' => basename($qrCodeFileName)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => "Chyba při odesílání zprávy: {$e->getMessage()}"
    ]);
}

