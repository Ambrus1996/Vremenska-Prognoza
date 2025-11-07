// File: public/assets/js/modal.js

// We wait for the entire HTML document to be loaded and parsed
document.addEventListener("DOMContentLoaded", function() {

    // Get the modal element
    var modal = document.getElementById("weatherDescriptionModal");

    // Get the button that opens the modal
    var btn = document.getElementById("weatherDescriptionBtn");

    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close")[0];

    // Safety check: if these elements don't exist on the page (e.g., error page),
    // stop the script right here to prevent errors.
    if (!modal || !btn || !span) {
        return;
    }

    // When the user clicks the button, open the modal 
    btn.onclick = function() {
        modal.style.display = "block";
    }

    // When the user clicks on <span> (x), close the modal
    span.onclick = function() {
        modal.style.display = "none";
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // [FIX] Removed the duplicated, buggy .onclick function that referenced
    // the non-existent 'weatherDetailsText' element.
});