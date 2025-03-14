document.addEventListener("DOMContentLoaded", function() {
    fetch("navbar.html?v=1")
      .then(response => {
        if (!response.ok) {
          throw new Error("Chyba při načítání navbar.html");
        }
        return response.text();
      })
      .then(data => {
        document.getElementById("navbar-placeholder").innerHTML = data;
        
        // Aktualizace počtu položek v košíku
        let cart = JSON.parse(localStorage.getItem("cart")) || [];
        let cartCountEl = document.getElementById("cart-count");
        if (cartCountEl) {
          let totalCount = cart.reduce((sum, item) => sum + item.quantity, 0);
          cartCountEl.textContent = totalCount;
        }
        
        // Nastavení aktivní položky na základě aktuální URL
        let currentPage = window.location.pathname.split("/").pop();
        if (currentPage === "") currentPage = "index.html"; // výchozí stránka
        let navLinks = document.querySelectorAll(".navbar-nav .nav-link");
        navLinks.forEach(link => {
          // Odstraníme případnou aktivní třídu ze všech odkazů
          link.classList.remove("active");
          // Pokud se href shoduje s aktuální stránkou, přidáme třídu active
          if (link.getAttribute("href") === currentPage) {
            link.classList.add("active");
          }
        });
      })
      .catch(error => console.error("Chyba:", error));
  });
  