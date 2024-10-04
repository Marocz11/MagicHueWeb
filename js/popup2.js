// Funkce pro zobrazení popupu s QR kódem
function showConfirmationPopup(qrCodeUrl) {
    var popup = document.getElementById("confirmationPopup");
    var qrCodeImage = document.getElementById("qrCodeImage");

    console.log("Displaying popup with QR code");

    qrCodeImage.src = qrCodeUrl; // Nastavení URL obrázku QR kódu
    popup.style.display = 'block'; // Zobrazit popup nastavením stylu display na 'block'
    popup.classList.add("show"); // Přidáme třídu 'show' pro zobrazení popupu
}

// Funkce pro zavření popupu a resetování formuláře
document.querySelector(".close-btn").addEventListener("click", function () {
    var popup = document.getElementById("confirmationPopup");
    popup.classList.remove("show"); // Odebereme třídu 'show' pro skrytí popupu
    popup.style.display = 'none'; // Skrytí popupu nastavením stylu display na 'none'
    
    document.getElementById("quoteForm").reset(); // Resetujeme formulář místo obnovy stránky
});

