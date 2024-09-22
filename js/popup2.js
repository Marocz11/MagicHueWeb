// Function to show the popup
function showConfirmationPopup() {
    var popup = document.getElementById("confirmationPopup");
    popup.style.display = "block";
}

// Function to close the popup and reload the page
document.querySelector(".close-btn").addEventListener("click", function () {
    var popup = document.getElementById("confirmationPopup");
    popup.style.display = "none";
    location.reload(); // Reload the page after closing the popup
});

// Submit handler to send form and show popup after email is sent
document.getElementById('quoteForm').addEventListener('submit', function (e) {
    e.preventDefault(); // Prevent the default form submission

    var formData = new FormData(this);

    // Send form data via AJAX
    fetch('send_email.php', {
        method: 'POST',
        body: formData
    }).then(function (response) {
        if (response.ok) {
            showConfirmationPopup(); // Show the popup after email is sent
        } else {
            alert('Došlo k chybě při odesílání formuláře. Zkuste to prosím znovu.');
        }
    }).catch(function (error) {
        console.error('Error:', error);
    });
});        