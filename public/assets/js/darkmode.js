// File: public/assets/js/darkmode.js

// We wait for the entire HTML document to be loaded and parsed
document.addEventListener("DOMContentLoaded", function() {
    
    // Now it's safe to find our elements
    const body = document.body;
    const button = document.getElementById("toggleDarkMode");

    // Safety check: if the button doesn't exist on the page, stop right here.
    if (!button) {
        return;
    }

    // --- Part 1: Set the initial state on page load ---
    if(localStorage.getItem("theme") === "dark") {
        body.classList.add("dark-mode");
        button.textContent = "Svetli režim"; // Light mode
    } else {
        body.classList.remove("dark-mode");
        button.textContent = "Noćni režim"; // Night mode
    }

    // --- Part 2: Add the click listener *inside* DOMContentLoaded ---
    // This code now runs ONLY after the button is guaranteed to exist.
    button.addEventListener("click", function() {
        if(body.classList.contains("dark-mode")) {
            // Switch to Light Mode
            body.classList.remove("dark-mode");
            button.textContent = "Noćni režim";
            localStorage.setItem("theme", "light");
        } else {
            // Switch to Dark Mode
            body.classList.add("dark-mode");
            button.textContent = "Svetli režim";
            localStorage.setItem("theme", "dark");
        }
    });
});