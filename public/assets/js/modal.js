document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('weatherDescriptionModal');
    var btn = document.getElementById('weatherDescriptionBtn');
    var span = document.getElementsByClassName('close')[0];

    if (!modal || !btn || !span) {
        console.error("Neki od elemenata nisu pronađeni: modal, btn, ili span.");
        return;
    }

    btn.onclick = function() {
        modal.style.display = 'block';
    }

    span.onclick = function() {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    var body = document.body;

btn.onclick = function() {
    modal.style.display = 'block';
    modal.classList.add('show');
    body.classList.add('modal-open'); // Ovo dodaje klasu na <body> kada je modal otvoren
    document.getElementById('weatherDetailsText').textContent = "Ovde će ići specifičan opis vremena za taj dan.";
}

span.onclick = function() {
    modal.classList.remove('show');
    body.classList.remove('modal-open'); // Ovo uklanja klasu sa <body> kada se modal zatvori
    setTimeout(() => {
        modal.style.display = 'none'; // Ovo sklanja modal nakon što animacija završi
    }, 400);
}

window.onclick = function(event) {
    if (event.target == modal) {
        modal.classList.remove('show');
        body.classList.remove('modal-open');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 400);
    }
}


});
