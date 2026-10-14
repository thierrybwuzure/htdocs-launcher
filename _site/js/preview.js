// Page d'aperçu d'un fichier Markdown : rendu du texte, et bascule Rendu / Source.
(function () {
    var rendered = document.getElementById('md-rendered');
    var source = document.getElementById('md-source');
    var raw = document.getElementById('md-raw');
    var buttons = document.querySelectorAll('[data-view]');

    if (!rendered || !raw || !window.renderMarkdown) return;

    rendered.innerHTML = window.renderMarkdown(raw.value, rendered.dataset.base || '/');

    function show(view) {
        rendered.hidden = view !== 'rendered';
        source.hidden = view !== 'source';
        buttons.forEach(function (button) {
            button.setAttribute('aria-pressed', button.dataset.view === view ? 'true' : 'false');
        });
    }

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            show(button.dataset.view);
        });
    });
})();
