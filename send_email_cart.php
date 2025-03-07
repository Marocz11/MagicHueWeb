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
    // SMTP nastavení
    $mail->isSMTP();
    $mail->Host = 'smtp.forpsi.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'info@printm.cz';
    $mail->Password = 's_a9eMAc2W';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';

    // Načtení dat z formuláře
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $pickupLocation = $_POST['pickupLocation'];
    $messageText = isset($_POST['message']) ? $_POST['message'] : '';
    $cartItemsJson = $_POST['cart_items'];
    $cartItems = json_decode($cartItemsJson, true);

    // Vygenerovat číslo objednávky (variabilní symbol)
    $orderNumber = date('mdHis');

    // Výpočet celkové ceny z položek
    $totalItemsPrice = 0;
    if(is_array($cartItems)){
        foreach($cartItems as $item){
            $totalItemsPrice += $item['price'] * $item['quantity'];
        }
    }
    $shippingCost = 80;
    $totalPrice = $totalItemsPrice + $shippingCost;

    // QR kód – vytvoření dat pro platbu
    $iban = 'CZ7106000000000262192938';
    $amount = number_format($totalPrice, 2, '.', '');
    $msg = "Objednávka $orderNumber";
    $vs = $orderNumber;
    $qrData = "SPD*1.0*ACC:$iban*AM:$amount*CC:CZK*MSG:$msg*X-VS:$vs";
    
    // Vygenerování QR kódu do souboru
    $qrFile = __DIR__ . '/qrcode_' . time() . '.png';
    QRcode::png($qrData, $qrFile, QR_ECLEVEL_L, 5);
    // Převedení souboru na Base64
    $qrCodeBase64 = base64_encode(file_get_contents($qrFile));
    $qrCodeDataUri = 'data:image/png;base64,' . $qrCodeBase64;

    // --- Vytvoření PDF objednávky ---
    $pdf = new TCPDF();
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();
    $pdf->setCellHeightRatio(1.0);
    $pdf->SetFont('dejavusans', '', 12);

    // Hlavička objednávky
    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->Cell(0, 15, 'Objednávka: ' . $orderNumber, 0, 1, 'C');
    $pdf->Ln(5);

    // Dodavatel a Odběratel – dva sloupce
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(95, 6, 'Dodavatel:', 0, 0, 'L');
    $pdf->Cell(95, 6, 'Odběratel:', 0, 1, 'L');
    $pdf->SetFont('dejavusans', '', 10);
    
    // Dodavatelské údaje (fixní)
    $supplierName = 'Print M mam';
    $supplierAddress = 'Heřmanická 1280, Rychvald, 73532';
    $supplierInfo = 'IČ: 04372191, Nejsme plátci DPH';
    // Odběratelské údaje (z formuláře)
    $buyerName = $name;
    $buyerContact = "Telefon: {$phone}\nEmail: {$email}";

    // První řádek – jména
    $pdf->MultiCell(95, 6, $supplierName, 0, 'L', false, 0, '', '', true);
    $pdf->MultiCell(95, 6, $buyerName, 0, 'L', false, 1, '', '', true);
    // Druhý řádek – adresy a kontakty
    $pdf->MultiCell(95, 6, $supplierAddress, 0, 'L', false, 0, '', '', true);
    $pdf->MultiCell(95, 6, $buyerContact, 0, 'L', false, 1, '', '', true);
    // Třetí řádek – dodatečné info
    $pdf->MultiCell(95, 6, $supplierInfo, 0, 'L', false, 0, '', '', true);
    // Pravý sloupec ponecháme prázdný
    $pdf->MultiCell(95, 6, "", 0, 'L', false, 1, '', '', true);
    $pdf->Ln(5);

    // Výdejní místo
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(0, 6, 'Výdejní místo: ' . $pickupLocation, 0, 1, 'L');
    $pdf->Ln(5);

    // Bankovní údaje a variabilní symbol
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(0, 6, 'Bankovní účet: 262192938/0600', 0, 1, 'L');
    $pdf->Cell(0, 6, 'Variabilní symbol: ' . $orderNumber, 0, 1, 'L');
    $pdf->Ln(5);

    // Tabulka objednávky – hlavička
    $pdf->SetFillColor(200,200,200);
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(80, 10, 'Označení', 1, 0, 'C', 1);
    $pdf->Cell(30, 10, 'Počet', 1, 0, 'C', 1);
    $pdf->Cell(40, 10, 'Cena za ks', 1, 0, 'C', 1);
    $pdf->Cell(40, 10, 'Celkem', 1, 1, 'C', 1);
    $pdf->SetFont('dejavusans', '', 10);

    // Iterace přes položky košíku
    if(is_array($cartItems)){
        foreach($cartItems as $item){
            $itemName = $item['name'];
            $quantity = intval($item['quantity']);
            $price = floatval($item['price']);
            $total = $price * $quantity;
            // Nastavíme šířku první buňky (označení dodávky)
            $cellWidth = 80;
            // Zjistíme počet řádků, které se název vejde – TCPDF metoda getNumLines
            $numLines = $pdf->getNumLines($itemName, $cellWidth);
            $rowHeight = $numLines * 6; // předpokládaná výška řádku 6 mm

            // Vypíšeme první buňku pomocí MultiCell (s okrajem)
            $pdf->MultiCell($cellWidth, $rowHeight, $itemName, 1, 'L', 0, 0, '', '', true);
            // Druhá buňka – počet
            $pdf->Cell(30, $rowHeight, $quantity, 1, 0, 'C');
            // Třetí buňka – cena za ks
            $pdf->Cell(40, $rowHeight, $price . " Kč", 1, 0, 'C');
            // Čtvrtá buňka – celkem
            $pdf->Cell(40, $rowHeight, $total . " Kč", 1, 1, 'C');
        }
    }

    // Přidáme řádek pro dopravu
    $pdf->Cell(80, 10, 'Doprava', 1);
    $pdf->Cell(30, 10, '1', 1, 0, 'C');
    $pdf->Cell(40, 10, $shippingCost . " Kč", 1, 0, 'C');
    $pdf->Cell(40, 10, $shippingCost . " Kč", 1, 1, 'C');

    // Cena celkem
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(150, 10, 'Cena celkem:', 1, 0, 'R');
    $pdf->Cell(40, 10, $totalPrice . " Kč", 1, 1, 'C');
    $pdf->Ln(5);

    // Přidání QR kódu do PDF
    $pdf->Image($qrFile, 80, $pdf->GetY(), 50, 50);

    // Uložení PDF do souboru
    $pdfFileName = __DIR__ . '/objednavka_' . time() . '.pdf';
    $pdf->Output($pdfFileName, 'F');

    // --- Odeslání emailu zákazníkovi ---
    $mail->setFrom('info@printm.cz', 'Print M');
    $mail->addAddress($email);
    $mail->addAttachment($pdfFileName);
    $mail->isHTML(true);
    $mail->Subject = 'Objednávka ' . $orderNumber;
    $mail->Body = "<h2>Děkujeme za Vaši objednávku</h2>
                   <p>Vaše objednávka byla úspěšně přijata. V příloze najdete PDF s detaily objednávky.</p>
                   <p>Pro zaplacení prosím použijte níže uvedený QR kód.</p>";
    $mail->send();

    // --- Odeslání emailu firmě ---
    $mail->clearAddresses();
    $mail->clearAttachments();
    $mail->addAddress('info@printm.cz');
    $mail->addAttachment($pdfFileName);
    $mail->send();

    // Vrátíme JSON s Base64 daty QR kódu pro zobrazení v popupu
    echo json_encode([
        'success' => true,
        'qrCodeData' => $qrCodeDataUri
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => "Chyba při odesílání zprávy: {$e->getMessage()}"
    ]);
}
?>
