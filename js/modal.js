document.addEventListener("DOMContentLoaded", function() {
    // Get the modal elements
    var modals = document.querySelectorAll('.modal');

    // Get the clickable images that should open a modal
    var modalImgs = document.querySelectorAll('.open-modal');

    modalImgs.forEach(function(modalImg) {
        modalImg.addEventListener('click', function(event) {
            event.preventDefault();
            
            // Get the associated modal
            var modal = document.querySelector(modalImg.getAttribute('href'));
            modal.style.display = "block";

            // Disable scrolling while the modal is open
            document.body.style.overflow = 'hidden';
        });
    });

    // Function to close the modal
    function closeModal(modal) {
        modal.style.display = "none";
        // Re-enable scrolling when the modal is closed
        document.body.style.overflow = '';
    }

    // Close the modal when the close button is clicked
    var closeButtons = document.querySelectorAll('.close');
    closeButtons.forEach(function(closeBtn) {
        closeBtn.onclick = function() {
            var modal = this.closest('.modal');
            closeModal(modal);
        }
    });

    // Close the modal when clicking outside the modal content
    modals.forEach(function(modal) {
        modal.addEventListener('click', function(event) {
            var modalContent = modal.querySelector('.modal-content');
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });
});
