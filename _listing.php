<?php
// Listing d'un dossier sans page d'accueil (réglé dans htdocs/.htaccess, pour localhost seulement).
// Données dans _site/lib, affichage dans _site/views, styles et scripts dans _site/css et _site/js.
require __DIR__ . '/_site/lib/fs.php';
require __DIR__ . '/_site/lib/view.php';
require __DIR__ . '/_site/lib/preview.php';
require __DIR__ . '/_site/lib/vhosts.php';

$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (str_contains($uri, '..') || str_contains($uri, "\0")) {
    http_response_code(400);
    exit;
}

// Jamais de listing pour un dossier caché (.git, .claude...) ni pour _site : Apache les bloque déjà, nginx (Herd) non.
foreach (explode('/', $uri) as $segment) {
    if ($segment !== '' && ($segment[0] === '.' || $segment === '_site')) {
        http_response_code(404);
        exit('Dossier introuvable');
    }
}

$path = rtrim(__DIR__ . $uri, '/');
if (!is_dir($path)) {
    http_response_code(404);
    exit('Dossier introuvable');
}

$segments = array_values(array_filter(explode('/', $uri), 'strlen'));
$base     = rtrim('/' . implode('/', array_map('rawurlencode', $segments)), '/');

// Fil d'Ariane : chaque dossier traversé, avec son adresse.
$crumbs = [];
$href   = '';
foreach ($segments as $segment) {
    $href    .= '/' . rawurlencode($segment);
    $crumbs[] = [$segment, "$href/"];
}

$entries = list_entries($path);

render('listing', [
    'title'        => $segments ? end($segments) : 'localhost',
    'heading'      => $segments ? end($segments) : 'localhost',
    'crumbs'       => $crumbs,
    'subtitle'     => count_label(count($entries['dirs']), count($entries['files'])),
    'default_sort' => 'name',
    'dirs'         => $entries['dirs'],
    'files'        => $entries['files'],
    'base'         => $base,
]);
