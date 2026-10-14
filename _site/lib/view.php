<?php
/**
 * Rendu des pages : échappement, adresse des ressources, et assemblage vue + gabarit.
 */

function h(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/** Adresse d'une ressource de _site/, avec la date du fichier pour renouveler le cache du navigateur. */
function asset(string $path): string
{
    $file = dirname(__DIR__) . '/' . $path;
    return '/_site/' . $path . '?v=' . (@filemtime($file) ?: 0);
}

/**
 * Affiche une vue dans le gabarit commun (views/layout.php).
 *
 * Obligatoires : title, heading, crumbs (liste de [libellé, adresse], vide à l'accueil ; le dernier élément est la page courante), subtitle.
 * Facultatifs  : show_tools (filtre et tri, vrai par défaut), hide_toggle (bouton « Masquer les projets », accueil seulement), default_sort ('date' ou 'name'),
 *                tool_links (liste de [libellé, adresse], par défaut phpMyAdmin sur toutes les pages ; dans la zone de droite de la ligne des outils, ou dans l'en-tête s'il n'y a pas de barre d'outils), extra_css et extra_js (chemins dans _site/).
 */
function render(string $view, array $vars): void
{
    $vars += [
        'show_tools'   => true,
        'hide_toggle'  => false,
        'default_sort' => 'name',
        'tool_links'   => [['phpMyAdmin', '/phpmyadmin/']],   // sur toutes les pages

        'extra_css'    => [],
        'extra_js'     => [],
    ];
    extract($vars, EXTR_SKIP);

    ob_start();
    require dirname(__DIR__) . "/views/$view.php";
    $content = ob_get_clean();

    require dirname(__DIR__) . '/views/layout.php';
}

/** Lien qui ouvre un dossier dans une nouvelle fenêtre VS Code (schéma vscode://, géré par l'application ; le navigateur demande confirmation). */
function vscode_url(string $path): string
{
    $real = realpath($path) ?: $path;
    return 'vscode://file' . implode('/', array_map('rawurlencode', explode('/', $real))) . '?windowId=_blank';
}
