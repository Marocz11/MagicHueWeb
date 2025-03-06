document.addEventListener("DOMContentLoaded", function() {
    let cartItemsContainer = document.getElementById("cart-items");
    let cartTotal = document.getElementById("cart-total");
    let cartCount = document.getElementById("cart-count");
    let totalPrice = 0;
    let shippingCost = 79;

    window.updateCartUI = function() {
        let cart = JSON.parse(localStorage.getItem("cart")) || [];
        cartItemsContainer.innerHTML = "";
        totalPrice = 0;

        cart.forEach((item, index) => {
            let row = document.createElement("tr");
            row.innerHTML = `
                <td>${item.name}</td>
                <td>${item.price} Kč</td>
                <td><input type="number" min="1" value="${item.quantity}" class="form-control quantity-input" data-index="${index}"></td>
                <td><button class="btn btn-danger btn-sm remove-btn" data-index="${index}">Odstranit</button></td>
            `;
            cartItemsContainer.appendChild(row);
            totalPrice += item.price * item.quantity;
        });

        cartTotal.innerText = `${totalPrice.toFixed(2)} Kč`;
        cartCount.innerText = `(${cart.length})`;

        document.querySelectorAll(".remove-btn").forEach(button => {
            button.addEventListener("click", function() {
                let index = parseInt(this.getAttribute("data-index"), 10);
                removeFromCart(index);
            });
        });
    };

    window.removeFromCart = function(index) {
        let cart = JSON.parse(localStorage.getItem("cart")) || [];
        if (index >= 0 && index < cart.length) {
            cart.splice(index, 1);
            localStorage.setItem("cart", JSON.stringify(cart));
            updateCartUI();
        }
    };

    updateCartUI();
});
