// Appliqué dans le <head>, avant l'affichage, pour éviter un flash de mauvais thème.
// Deux thèmes seulement : le choix mémorisé, sinon celui du système la première fois.
(function () {
    var theme = null;
    try {
        var saved = localStorage.getItem('theme');
        if (saved === 'light' || saved === 'dark') theme = saved;
    } catch (e) {}
    if (!theme) {
        theme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    document.documentElement.setAttribute('data-theme', theme);

    // Groupe Projets masqué : choix mémorisé, appliqué ici aussi pour qu'il ne flashe pas à l'affichage.
    try {
        if (localStorage.getItem('hideProjects') === '1') document.documentElement.setAttribute('data-hide-projects', '');
    } catch (e) {}
})();
