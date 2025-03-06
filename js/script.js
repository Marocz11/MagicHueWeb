document.addEventListener("DOMContentLoaded", function () {
    updateCartCount();

    function updateCartCount() {
        let cart = JSON.parse(localStorage.getItem("cart")) || [];
        let totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);

        let cartCounter = document.getElementById("cart-count");
        if (cartCounter) {
            cartCounter.textContent = `(${totalItems})`;
        }
    }

    // Ensure cart count updates when items are added
    document.getElementById("add-all-to-cart")?.addEventListener("click", function () {
        let nameFields = document.querySelectorAll(".name-field");
        let cart = JSON.parse(localStorage.getItem("cart")) || [];

        nameFields.forEach(field => {
            let nameValue = field.value.trim();
            if (nameValue !== "") {
                let existingItem = cart.find(item => item.name === `Jmenovka 3D - ${nameValue}`);
                if (existingItem) {
                    existingItem.quantity += 1;
                } else {
                    cart.push({
                        name: `Jmenovka 3D - ${nameValue}`,
                        price: 15,
                        quantity: 1
                    });
                }
            }
        });

        localStorage.setItem("cart", JSON.stringify(cart));
        alert("Všechna jména byla přidána do košíku.");

        updateCartCount();
    });
});
