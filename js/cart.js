// Initialize cart array from localStorage, or start with an empty array
let cart = JSON.parse(localStorage.getItem('spices_cart')) || [];

// Execute functions on DOM load to sync badge count and render cart page if present
document.addEventListener('DOMContentLoaded', () => {
    updateCartBadge();
    if (document.getElementById('cart-table-body')) {
        renderCartPage();
    }
});

// Save current cart state to localStorage and refresh the header badge count
function saveCart() {
    localStorage.setItem('spices_cart', JSON.stringify(cart));
    updateCartBadge();
}

// Step 1: Handle Add to Cart event logic
function addToCart(id, name, price, image) {
    // Read selected quantity from input or default to 1
    const qtyInput = document.getElementById('quantity');
    const quantity = qtyInput ? parseInt(qtyInput.value) : 1;
    
    // Check if item already exists in cart array
    const existingItem = cart.find(item => item.id === id);

    if (existingItem) {
        // Increment quantity if item is already present
        existingItem.quantity += quantity;
    } else {
        // Append new item object to cart array
        cart.push({
            id: id,
            name: name,
            price: parseFloat(price),
            image: image,
            quantity: quantity
        });
    }

    // Persist updated array and alert user
    saveCart();
    alert(`${name} added to cart!`);
}

// Step 2: Dynamically calculate and update badge counter in header
function updateCartBadge() {
    const badge = document.getElementById('cart-badge');
    if (badge) {
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        badge.innerText = totalItems;
    }
}

// Step 3: Render item rows and calculate subtotals on cart.php
function renderCartPage() {
    const tableBody = document.getElementById('cart-table-body');
    const subtotalEl = document.getElementById('cart-subtotal');
    const grandTotalEl = document.getElementById('cart-total');

    if (!tableBody) return;

    // Display empty cart notification if array is empty
    if (cart.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:20px;">Your cart is empty. <a href="shop.php">Continue Shopping</a></td></tr>`;
        if (subtotalEl) subtotalEl.innerText = "LKR 0.00";
        if (grandTotalEl) grandTotalEl.innerText = "LKR 0.00";
        return;
    }

    let subtotal = 0;
    tableBody.innerHTML = '';

    // Loop through stored cart items and generate table rows
    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        subtotal += itemTotal;

        const row = document.createElement('tr');
        row.innerHTML = `
            <td><img src="assets/${item.image}" alt="${item.name}" width="50" onerror="this.src='assets/chili.jpg';"></td>
            <td>${item.name}</td>
            <td>LKR ${item.price.toFixed(2)}</td>
            <td>
                <button class="qty-btn" onclick="updateQuantity(${item.id}, -1)">-</button>
                <span class="qty-val">${item.quantity}</span>
                <button class="qty-btn" onclick="updateQuantity(${item.id}, 1)">+</button>
            </td>
            <td>LKR ${itemTotal.toFixed(2)}</td>
            <td><button class="btn-remove" onclick="removeFromCart(${item.id})">Remove</button></td>
        `;
        tableBody.appendChild(row);
    });

    // Display formatted subtotal and grand total
    if (subtotalEl) subtotalEl.innerText = `LKR ${subtotal.toFixed(2)}`;
    if (grandTotalEl) grandTotalEl.innerText = `LKR ${subtotal.toFixed(2)}`;
}

// Step 4: Adjust item quantities (+ / -) and handle edge cases
function updateQuantity(id, change) {
    const item = cart.find(item => item.id === id);
    if (item) {
        item.quantity += change;
        
        // Edge Case: Automatically remove item if quantity drops below 1
        if (item.quantity <= 0) {
            removeFromCart(id);
            return;
        }
        
        saveCart();
        renderCartPage();
    }
}

// Step 5: Remove item completely using array filter method
function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    saveCart();
    renderCartPage();
}