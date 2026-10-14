// Filtre par nom et tri (Récent / Nom).
// Chaque <section data-group> contient des éléments [data-name][data-date] ; une section sans résultat se cache.
(function () {
    var input = document.getElementById('q');
    var empty = document.getElementById('empty');
    var buttons = document.querySelectorAll('[data-sort]');
    var groups = document.querySelectorAll('[data-group]');
    var hideButton = document.getElementById('hide-projects');
    var root = document.documentElement;

    function items(group) {
        return Array.prototype.slice.call(group.querySelectorAll('[data-name]'));
    }

    function filter() {
        var query = input.value.trim().toLowerCase();
        var total = 0;

        groups.forEach(function (group) {
            var shown = 0;
            items(group).forEach(function (item) {
                var match = item.dataset.name.indexOf(query) !== -1;
                item.hidden = !match;
                if (match) shown++;
            });
            group.hidden = shown === 0;
            // Un groupe masqué par le bouton « Masquer les projets » ne compte pas parmi les résultats visibles.
            if (!(group.dataset.kind === 'projets' && root.hasAttribute('data-hide-projects'))) total += shown;
        });

        empty.hidden = total !== 0;
    }

    function sort(mode) {
        buttons.forEach(function (button) {
            button.setAttribute('aria-pressed', button.dataset.sort === mode ? 'true' : 'false');
        });

        groups.forEach(function (group) {
            items(group)
                .sort(function (a, b) {
                    return mode === 'date'
                        ? b.dataset.date - a.dataset.date
                        : a.dataset.name.localeCompare(b.dataset.name, 'fr', { numeric: true });
                })
                .forEach(function (item) {
                    item.parentElement.appendChild(item);
                });
        });
    }

    // Bouton œil (icône seule) : la règle CSS cache le groupe et choisit l'icône ; le choix est gardé dans localStorage.
    function showHideState() {
        hideButton.setAttribute('aria-pressed', root.hasAttribute('data-hide-projects') ? 'true' : 'false');
    }

    if (hideButton) {
        showHideState();
        hideButton.addEventListener('click', function () {
            var hide = !root.hasAttribute('data-hide-projects');
            if (hide) root.setAttribute('data-hide-projects', ''); else root.removeAttribute('data-hide-projects');
            try { localStorage.setItem('hideProjects', hide ? '1' : '0'); } catch (e) {}
            showHideState();
            filter();
        });
    }

    input.addEventListener('input', filter);
    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            sort(button.dataset.sort);
        });
    });
})();
