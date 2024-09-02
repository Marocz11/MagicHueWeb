document.addEventListener("DOMContentLoaded", function() {
    var modals = document.querySelectorAll('.modal');
    var modalImgs = document.querySelectorAll('.open-modal');

    modalImgs.forEach(function(modalImg) {
        modalImg.addEventListener('click', function(event) {
            event.preventDefault();
            var modal = document.querySelector(modalImg.getAttribute('href'));
            var modalContent = modal.querySelector('.modal-content');
            
            // Zobrazit modalní okno
            modal.style.display = "block";

            // Automatické přizpůsobení velikosti modalu obrázku a textu
            var img = modalContent.querySelector('img');
            img.onload = function() {
                modalContent.style.width = img.width + 40 + "px";  // Přidáno 40px kvůli paddingu (20px na každé straně)
                modalContent.style.height = img.height + modalContent.querySelector('.caption').clientHeight + 40 + "px";
            };

            // Vypnout posouvání stránky při otevření modalu
            document.body.style.overflow = 'hidden';

            // Přidání overlay
            const overlay = document.createElement('div');
            overlay.id = 'modal-overlay';
            overlay.style.position = 'fixed';
            overlay.style.top = 0;
            overlay.style.left = 0;
            overlay.style.width = '100%';
            overlay.style.height = '100%';
            overlay.style.zIndex = '10';
            document.body.appendChild(overlay);

            // Zavření modalu při kliknutí na overlay
            overlay.addEventListener('click', function(event) {
                if (!modalContent.contains(event.target)) {
                    closeModal(modal);
                }
            });
        });
    });

    function closeModal(modal) {
        modal.style.display = "none";
        document.body.style.overflow = '';
        const overlay = document.getElementById('modal-overlay');
        if (overlay) {
            overlay.remove();
        }
    }

    var closeButtons = document.querySelectorAll('.close');
    closeButtons.forEach(function(closeBtn) {
        closeBtn.onclick = function() {
            var modal = this.closest('.modal');
            closeModal(modal);
        }
    });
});
