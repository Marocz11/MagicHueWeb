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
    $name           = $_POST['name'];
    $email          = $_POST['email'];
    $phone          = $_POST['phone'];
    $pickupLocation = $_POST['pickupLocation'];
    $messageText    = isset($_POST['message']) ? $_POST['message'] : '';
    $cartItemsJson  = $_POST['cart_items'];
    $cartItems      = json_decode($cartItemsJson, true);

    // Vygenerovat číslo objednávky (variabilní symbol)
    $orderNumber = date('mdHis');

    // Výpočet celkové ceny z položek
    $totalItemsPrice = 0;
    if (is_array($cartItems)) {
        foreach ($cartItems as $item) {
            $totalItemsPrice += $item['price'] * $item['quantity'];
        }
    }
    $shippingCost = 80;
    $totalPrice   = $totalItemsPrice + $shippingCost;

    // QR kód – vytvoření dat pro platbu
    $iban   = 'CZ7106000000000262192938'; // IBAN
    $amount = number_format($totalPrice, 2, '.', '');
    $msg    = "Objednávka $orderNumber";
    $vs     = $orderNumber;
    $qrData = "SPD*1.0*ACC:$iban*AM:$amount*CC:CZK*MSG:$msg*X-VS:$vs";

    // Vygenerování QR kódu do souboru
    $qrFile = __DIR__ . '/qrcode_' . time() . '.png';
    QRcode::png($qrData, $qrFile, QR_ECLEVEL_L, 5);

    // Převedení souboru na Base64 pro případné použití
    $qrCodeBase64  = base64_encode(file_get_contents($qrFile));
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

    // Dva sloupce: Dodavatel a Odběratel
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(95, 6, 'Dodavatel:', 0, 0, 'L');
    $pdf->Cell(95, 6, 'Odběratel:', 0, 1, 'L');
    $pdf->SetFont('dejavusans', '', 10);

    // Dodavatelské údaje (fixní)
    $supplierName    = 'Marek Halška';
    $supplierAddress = 'Heřmanická 1280, Rychvald, 73532';
    $supplierInfo    = 'IČ: 04372191, Nejsme plátci DPH';

    // Odběratelské údaje: každý údaj zvlášť (aby měly stejné mezery)
    $pdf->MultiCell(95, 6, $supplierName, 0, 'L', false, 0, '', '', true);
    $pdf->MultiCell(95, 6, $name,         0, 'L', false, 1, '', '', true);

    $pdf->MultiCell(95, 6, $supplierAddress, 0, 'L', false, 0, '', '', true);
    $pdf->MultiCell(95, 6, "Telefon: $phone",  0, 'L', false, 1, '', '', true);

    $pdf->MultiCell(95, 6, $supplierInfo, 0, 'L', false, 0, '', '', true);
    $pdf->MultiCell(95, 6, "Email: $email", 0, 'L', false, 1, '', '', true);

    $pdf->Ln(5);

    // Výdejní místo
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(0, 6, 'Výdejní místo: ' . $pickupLocation, 0, 1, 'L');
    $pdf->Ln(5);

    // Bankovní údaje vlevo, QR kód vpravo
    $pdf->SetFont('dejavusans', 'B', 10);
    $startY = $pdf->GetY();
    $bankText = "Bankovní účet: 262192938/0600\nVariabilní symbol: $orderNumber";
    $pdf->MultiCell(100, 6, $bankText, 0, 'L', false, 0);
    // Vložíme QR kód (posun X = 120)
    $pdf->Image($qrFile, 120, $startY, 35, 35);
    // Posuneme kurzor za QR kód
    $pdf->Ln(40);

    // Tabulka objednávky – hlavička
    $pdf->SetFillColor(200, 200, 200);
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(80, 10, 'Označení',   1, 0, 'C', 1);
    $pdf->Cell(30, 10, 'Počet',      1, 0, 'C', 1);
    $pdf->Cell(40, 10, 'Cena za ks', 1, 0, 'C', 1);
    $pdf->Cell(40, 10, 'Celkem',     1, 1, 'C', 1);
    $pdf->SetFont('dejavusans', '', 10);

    // Iterace přes položky v košíku
    if (is_array($cartItems)) {
        foreach ($cartItems as $item) {
            $itemName = $item['name'];
            $quantity = intval($item['quantity']);
            $price    = floatval($item['price']);
            $rowTotal = $price * $quantity;

            // Stanovení výšky řádku (kvůli delším názvům)
            $cellWidth = 80;
            $numLines  = $pdf->getNumLines($itemName, $cellWidth);
            $rowHeight = $numLines * 6;

            // 1. buňka: název (MultiCell)
            $pdf->MultiCell($cellWidth, $rowHeight, $itemName, 1, 'L', 0, 0, '', '', true);
            // 2. buňka: počet
            $pdf->Cell(30, $rowHeight, $quantity, 1, 0, 'C');
            // 3. buňka: cena za ks
            $pdf->Cell(40, $rowHeight, $price . " Kč", 1, 0, 'C');
            // 4. buňka: celkem
            $pdf->Cell(40, $rowHeight, $rowTotal . " Kč", 1, 1, 'C');
        }
    }

    // Přidat řádek pro dopravu
    $pdf->Cell(80, 10, 'Doprava', 1);
    $pdf->Cell(30, 10, '1',      1, 0, 'C');
    $pdf->Cell(40, 10, $shippingCost . " Kč", 1, 0, 'C');
    $pdf->Cell(40, 10, $shippingCost . " Kč", 1, 1, 'C');

    // Cena celkem
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(150, 10, 'Cena celkem:', 1, 0, 'R');
    $pdf->Cell(40, 10, $totalPrice . " Kč", 1, 1, 'C');
    $pdf->Ln(5);

    // Uložení PDF do souboru
    $pdfFileName = __DIR__ . '/objednavka_' . time() . '.pdf';
    $pdf->Output($pdfFileName, 'F');

    // --- Odeslání emailu zákazníkovi ---
    $mail->setFrom('info@printm.cz', 'Print M');
    $mail->addAddress($email);
    $mail->addAttachment($pdfFileName);
    $mail->isHTML(true);
    $mail->Subject = 'Objednávka ' . $orderNumber;

    // Veselejší znění e‑mailu s emoji a mírně upraveným formátováním:
    $mail->Body = "
      <div style='font-family: Arial, sans-serif; line-height: 1.5; color: #333;'>
        <h2 style='color: #2c3e50; font-weight: bold; margin-bottom: 0.5em;'>
          Děkujeme za Vaši objednávku! 🎉
        </h2>
        <p style='margin-top: 0.2em;'>
          Vážený zákazníku,<br>
          s potěšením vám oznamujeme, že jsme obdrželi Vaši objednávku a zahájili její zpracování. 
          <strong>V příloze naleznete PDF s kompletními podrobnostmi</strong> o objednávce. 
        </p>

        <p style='margin-bottom: 1em;'>
          <span style='font-size: 1.1em;'>💳 <strong>Platební údaje</strong></span><br>
          Platba probíhá vždy převodem na náš účet. Všechny potřebné informace 
          najdete v přiloženém PDF. 
          K úhradě můžete také využít <strong>QR kód</strong> ve své bankovní aplikaci.<br>
          <em style='color: #c0392b;'>Pokud jste už objednávku zaplatili pomocí QR kódu na webu, 
          prosíme, abyste platbu neprováděli dvakrát.</em>
        </p>

        <p style='margin-bottom: 1em;'>
          <span style='font-size: 1.1em;'>🚚 <strong>Doručení</strong></span><br>
          Zboží posíláme výhradně přes Zásilkovnu. 
          Jakmile vaši zásilku odešleme, obdržíte e‑mail s bližšími informacemi 
          a možností sledování balíčku.
        </p>

        <p style='margin-bottom: 1em;'>
          <span style='font-size: 1.1em;'>🤝 <strong>Kontakt</strong></span><br>
          Máte‑li jakékoli otázky nebo potřebujete změnit detaily objednávky, 
          neváhejte nás kontaktovat na 
          <a href='mailto:info@printm.cz' style='color: #2980b9;'>info@printm.cz</a>
        </p>

        <p style='margin-bottom: 0; font-weight: bold;'>
          Děkujeme za Váš nákup a přejeme hezký den! ✨
        </p>
      </div>
    ";

    $mail->send();

    // --- Odeslání emailu firmě (kopie) ---
    $mail->clearAddresses();
    $mail->clearAttachments();
    $mail->addAddress('info@printm.cz');
    $mail->addAttachment($pdfFileName);
    $mail->send();

    // Vrátíme JSON s Base64 daty pro zobrazení (popup) ve frontendu
    echo json_encode([
        'success'    => true,
        'qrCodeData' => $qrCodeDataUri
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => "Chyba při odesílání zprávy: {$e->getMessage()}"
    ]);
}
?>
