// Bouton de thème : bascule entre clair et sombre, et mémorise le choix.
(function () {
    var button = document.getElementById('theme');
    var root = document.documentElement;

    function current() {
        return root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    }

    function describe() {
        var text = current() === 'dark' ? 'Passer au thème clair' : 'Passer au thème sombre';
        button.setAttribute('aria-label', text);
        button.title = text;
    }

    button.addEventListener('click', function () {
        var next = current() === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        try {
            localStorage.setItem('theme', next);
        } catch (e) {}
        describe();
    });

    describe();
})();
