<?php
// Accueil : liste les projets (dossiers) et les fichiers de la racine de htdocs.
// Données dans _site/lib, affichage dans _site/views, styles et scripts dans _site/css et _site/js.
require __DIR__ . '/_site/lib/fs.php';
require __DIR__ . '/_site/lib/view.php';
require __DIR__ . '/_site/lib/preview.php';
require __DIR__ . '/_site/lib/vhosts.php';

$entries  = list_entries(__DIR__, ['index.php']);
$projects = sort_by_date_desc($entries['dirs']);
$files    = sort_by_date_desc($entries['files']);

render('home', [
    'title'        => 'localhost',
    'heading'      => 'localhost',
    'crumbs'       => [],
    'subtitle'     => count_label(count($projects), count($files)),
    'default_sort' => 'date',
    'hide_toggle'  => true,
    'projects'     => $projects,
    'files'        => $files,
    'base'         => '',
]);
