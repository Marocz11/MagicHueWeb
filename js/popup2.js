// Show the confirmation popup with the QR code
function showConfirmationPopup(qrCodeUrl) {
    var popup = document.getElementById("confirmationPopup");
    var qrCodeImage = document.getElementById("qrCodeImage");
    qrCodeImage.src = qrCodeUrl; // Set the QR code image URL
    popup.style.display = "flex"; // Display the popup
}

// Function to close the popup and reload the page
document.querySelector(".close-btn").addEventListener("click", function () {
    var popup = document.getElementById("confirmationPopup");
    popup.style.display = "none"; // Hide the popup
    location.reload(); // Reload the page after closing the popup
});

// Submit handler to send form and show popup after email is sent
document.getElementById('quoteForm').addEventListener('submit', function (e) {
    e.preventDefault(); // Prevent the default form submission

    var formData = new FormData(this);

    // Send form data via AJAX
    fetch('send_email_spztky.php', {
        method: 'POST',
        body: formData
    }).then(function (response) {
        return response.json(); // Assume the server returns a JSON with the QR code URL
    }).then(function (data) {
        if (data.success) {
            showConfirmationPopup(data.qrCodeUrl); // Show the popup with the QR code
        } else {
            alert('Došlo k chybě při odesílání formuláře. Zkuste to prosím znovu.');
        }
    }).catch(function (error) {
        console.error('Error:', error);
    });
});        