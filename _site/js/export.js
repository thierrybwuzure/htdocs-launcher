// Document Office : le bouton « Exporter en PDF » affiche la commande à lancer dans le Terminal,
// avec un bouton pour la copier.
(function () {
    var toggle = document.querySelector('[data-export-toggle]');
    var help = document.getElementById('export-help');
    var copy = document.querySelector('[data-export-copy]');
    var command = document.getElementById('export-command');
    var copied = document.getElementById('export-copied');
    if (!toggle || !help || !copy || !command) return;

    toggle.addEventListener('click', function () {
        var open = help.hidden;
        help.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    function select() {
        var range = document.createRange();
        range.selectNodeContents(command);
        var selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
    }

    function confirm(message) {
        copied.textContent = message;
        copied.hidden = false;
        setTimeout(function () { copied.hidden = true; }, 2500);
    }

    copy.addEventListener('click', function () {
        var text = command.textContent;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                confirm('Commande copiée');
            }, function () {
                select();
                confirm('Commande sélectionnée : copie-la avec Cmd+C');
            });
        } else {
            select();
            confirm('Commande sélectionnée : copie-la avec Cmd+C');
        }
    });
})();
