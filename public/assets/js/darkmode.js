// Kada se stranica učita, proveravamo da li je postavljena tamna tema
document.addEventListener("DOMContentLoaded", function() {
    const body = document.body;
    const button = document.getElementById("toggleDarkMode");
    
    if(localStorage.getItem("theme") === "dark") {
        body.classList.add("dark-mode");
        button.textContent = "Svetli režim";
    } else {
        body.classList.remove("dark-mode");
        button.textContent = "Noćni režim";
    }
});

document.getElementById("toggleDarkMode").addEventListener("click", function() {
    const body = document.body;
    const button = document.getElementById("toggleDarkMode");
    
    if(body.classList.contains("dark-mode")) {
        body.classList.remove("dark-mode");
        button.textContent = "Noćni režim";
        localStorage.setItem("theme", "light");
    } else {
        body.classList.add("dark-mode");
        button.textContent = "Svetli režim";
        localStorage.setItem("theme", "dark");
    }
});
