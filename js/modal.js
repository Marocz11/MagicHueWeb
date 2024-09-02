document.addEventListener("DOMContentLoaded", function() {
    // Get the modal elements
    var modals = document.querySelectorAll('.modal');

    // Get the clickable images that should open a modal
    var modalImgs = document.querySelectorAll('.open-modal');

    modalImgs.forEach(function(modalImg, index) {
        modalImg.addEventListener('click', function(event) {
            event.preventDefault();
            
            // Get the associated modal
            var modal = document.querySelector(modalImg.getAttribute('href'));
            var modalImgElement = modal.querySelector('.modal-content');
            var captionTextElement = modal.querySelector('#caption-1');

            // Set the modal content
            modal.style.display = "block";
            modalImgElement.src = this.querySelector('img').src;

            // Set the caption text
            captionTextElement.innerHTML = this.nextElementSibling.querySelector('h4').innerText;
        });
    });

    // Close the modal when the close button is clicked
    var closeButtons = document.querySelectorAll('.close');
    closeButtons.forEach(function(closeBtn) {
        closeBtn.onclick = function() {
            var modal = this.parentElement;
            modal.style.display = "none";
        }
    });

    // Close the modal when clicking outside the modal content
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = "none";
        }
    };
});
