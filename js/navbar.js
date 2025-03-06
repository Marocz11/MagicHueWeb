// Aktualizace počtu položek v košíku ve všech stránkách
document.addEventListener("DOMContentLoaded", function() {
    let cart = JSON.parse(localStorage.getItem("cart")) || [];
    let cartCountEl = document.getElementById("cart-count");
  
    if (cartCountEl) {
      let totalCount = cart.reduce((sum, item) => sum + item.quantity, 0);
      cartCountEl.textContent = totalCount;
    }
  });
  