<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?></title>
    <?php /* Icône : SVG qui suit le thème du navigateur, PNG pour les navigateurs qui ne lisent pas le SVG et pour l'écran d'accueil. */ ?>
    <link rel="icon" type="image/svg+xml" href="<?= asset('img/favicon.svg') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('img/favicon-32.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset('img/apple-touch-icon.png') ?>">
    <script src="<?= asset('js/theme-init.js') ?>"></script>
    <link rel="stylesheet" href="<?= asset('css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/site.css') ?>">
    <?php foreach ($extra_css as $css): ?>
        <link rel="stylesheet" href="<?= asset($css) ?>">
    <?php endforeach; ?>
</head>

<body>
    <?php /* Le bouton de thème est dans la barre d'outils quand il y en a une ; sinon (page d'aperçu), il reste dans l'en-tête, en haut à droite. */ ?>
    <?php if (!$show_tools): ?>
    <header class="top">
        <div class="top-tools">
            <?php foreach ($tool_links as [$label, $href]): ?>
                <a class="tool" href="<?= h($href) ?>"><span class="dot" aria-hidden="true"></span><?= h($label) ?></a>
            <?php endforeach; ?>
            <?php require __DIR__ . '/partials/theme_button.php'; ?>
        </div>
    </header>
    <?php endif; ?>

    <?php /* Titre, puis le fil d'Ariane des sous-pages juste à côté, sur la même ligne. */ ?>
    <div class="title-row">
        <h1><?= h($heading) ?></h1>
        <?php if ($crumbs): ?>
            <?php /* Fil d'Ariane : à côté du titre, à la même place que le bouton phpMyAdmin de l'accueil, pour que les pages ne « sautent » pas. */ ?>
            <?php $trail = array_merge([['localhost', '/']], $crumbs); $last = count($trail) - 1; ?>
            <nav class="crumbs" aria-label="Fil d'Ariane">
                <?php foreach ($trail as $i => [$label, $href]): ?>
                    <?php if ($i > 0): ?><span class="sep">/</span><?php endif; ?>
                    <?php if ($i === $last): ?>
                        <span aria-current="page"><?= h($label) ?></span>
                    <?php else: ?>
                        <a href="<?= h($href) ?>"><?= h($label) ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </div>
    <p class="count"><?= h($subtitle) ?></p>

    <?php if ($show_tools): ?>
        <?php /* Barre d'outils en deux zones dans le rapport d'or : 61,8 % pour les contrôles (le filtre s'étire), 38,2 % pour les liens d'outils, alignés à droite. */ ?>
        <div class="tools">
            <div class="tools-main">
            <input id="q" type="search" placeholder="Filtrer par nom" aria-label="Filtrer par nom" autocomplete="off">
            <div class="sort" role="group" aria-label="Trier">
                <button type="button" data-sort="date" aria-pressed="<?= $default_sort === 'date' ? 'true' : 'false' ?>">Récent</button>
                <button type="button" data-sort="name" aria-pressed="<?= $default_sort === 'name' ? 'true' : 'false' ?>">Nom</button>
            </div>
            <?php require __DIR__ . '/partials/theme_button.php'; ?>
            <?php if ($hide_toggle): ?>
                <?php /* Masque le groupe Projets (discrétion en cours ou en partage d'écran) ; le choix est mémorisé. Icône volontairement neutre : anneau = groupe affiché, disque plein = groupe masqué (choisi par le CSS). */ ?>
                <div class="sort sort-single">
                    <button type="button" id="hide-projects" class="icon-btn" aria-pressed="false" aria-label="Afficher ou masquer le groupe" title="Afficher ou masquer le groupe">
                        <svg class="dot dot-open" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <circle cx="12" cy="12" r="6.5" fill="none" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <svg class="dot dot-off" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <circle cx="12" cy="12" r="6.5" fill="currentColor" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </button>
                </div>
            <?php endif; ?>
            </div>
            <div class="tools-side">
            <?php foreach ($tool_links as [$label, $href]): ?>
                <?php /* Liens d'outils (phpMyAdmin) : sur la ligne des outils, tout à droite. */ ?>
                <a class="tool" href="<?= h($href) ?>"><span class="dot" aria-hidden="true"></span><?= h($label) ?></a>
            <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?= $content ?>

    <?php if ($show_tools): ?>
        <p class="empty" id="empty" hidden>Rien ne correspond à ce filtre.</p>
    <?php endif; ?>

    <script src="<?= asset('js/theme.js') ?>"></script>
    <?php if ($show_tools): ?>
        <script src="<?= asset('js/filter-sort.js') ?>"></script>
    <?php endif; ?>
    <?php foreach ($extra_js as $js): ?>
        <script src="<?= asset($js) ?>"></script>
    <?php endforeach; ?>
</body>

</html>
