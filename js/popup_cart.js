// Skript pro odeslání objednávky z košíku a zobrazení QR kódu

document.getElementById('show-qr-btn').addEventListener('click', function () {
    const searchEl = document.getElementById("search");
    if (!searchEl.value) {
        alert("Vyberte výdejní místo (Zásilkovna)!");
        searchEl.focus();
        return;
    }
    
    const orderForm = document.getElementById("order-form");
    if (!orderForm.checkValidity()){
        orderForm.reportValidity();
        return;
    }
    
    // Vložíme do skrytého pole data košíku
    document.getElementById('cart_items').value = JSON.stringify(cart);
    
    // Vytvoříme FormData a odešleme AJAXem
    let formData = new FormData(orderForm);
    const btn = document.getElementById('show-qr-btn');
    btn.disabled = true;
    
    fetch(orderForm.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        if (data.success) {
            // Nastavíme Base64 data QR kódu a zobrazíme popup
            document.getElementById('qrCodeImage').src = data.qrCodeData;
            document.getElementById('qrModal').style.display = 'flex';
        } else {
            alert('Chyba při odesílání objednávky: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        alert('Nastala chyba při odesílání objednávky.');
    });
});

document.getElementById('closeModalBtn').addEventListener('click', function () {
    document.getElementById('qrModal').style.display = 'none';
});
window.addEventListener('click', function(e){
    const modal = document.getElementById('qrModal');
    if (e.target === modal){
        modal.style.display = 'none';
    }
});
