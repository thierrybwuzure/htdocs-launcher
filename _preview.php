<?php
// Aperçu d'un fichier de htdocs : ?f=/dossier/fichier.ext (chemin relatif à htdocs).
// Données dans _site/lib, affichage dans _site/views/preview.php, styles et scripts dans _site/css et _site/js.
require __DIR__ . '/_site/lib/fs.php';
require __DIR__ . '/_site/lib/view.php';
require __DIR__ . '/_site/lib/preview.php';

header('X-Content-Type-Options: nosniff');

$param = $_GET['f'] ?? '';   // un tableau (?f[]=x) donnerait un avertissement PHP qui révèle le chemin du serveur
$rel   = '/' . ltrim(is_string($param) ? $param : '', '/');
if (str_contains($rel, '..') || str_contains($rel, "\0") || str_contains($rel, '\\')) {
    http_response_code(400);
    exit;
}

$path = __DIR__ . $rel;

// Aperçu d'un dossier : sa nature, sa description et son contenu.
if (is_dir($path)) {
    if (preview_blocked($rel) || folder_preview_blocked(basename($rel))) {
        http_response_code(403);
        exit('Aperçu non autorisé pour ce dossier');
    }
    $folderName = basename($rel);
    $crumbs = [];
    $href   = '';
    foreach (array_filter(explode('/', dirname($rel)), 'strlen') as $segment) {
        $href    .= '/' . rawurlencode($segment);
        $crumbs[] = [$segment, "$href/"];
    }
    $crumbs[] = [$folderName, ''];
    $kind    = folder_kind($path);
    $entries = list_entries($path);
    $all     = array_merge($entries['dirs'], $entries['files']);
    $mtime   = last_change($path);
    $summary = folder_readme_line($path);   // vide s'il n'y a pas de README : la nature et le contenu sont déjà affichés

    render('folder_preview', [
        'title'      => $folderName,
        'heading'    => $folderName,
        'crumbs'     => $crumbs,
        'subtitle'   => 'DOSSIER · ' . count_label(count($entries['dirs']), count($entries['files'])) . ' · ' . format_datetime($mtime),
        'show_tools' => false,
        'extra_css'  => ['css/preview.css'],
        'kindLabel'  => KINDS[$kind][0],
        'kindTip'    => KINDS[$kind][2],
        'summary'    => $summary,
        'dirUrl'     => raw_url($rel) . '/',
        'entries'    => $entries,
        'base'       => raw_url($rel),
    ]);
    exit;
}

if (!is_file($path)) {
    http_response_code(404);
    exit('Fichier introuvable');
}
if (preview_blocked($rel)) {
    http_response_code(403);
    exit('Aperçu non autorisé pour ce fichier');
}

$name   = basename($rel);
$relDir = rtrim(dirname($rel), '/');
$kind   = preview_kind($name);
$size   = @filesize($path);
$mtime  = @filemtime($path) ?: 0;

// ?pdf=1 : renvoie le PDF exporté (avec &download=1 pour le télécharger plutôt que l'afficher).
$pdf = $kind === 'office' ? office_cached_pdf($path) : null;
if (isset($_GET['pdf'])) {
    if ($pdf === null) {
        http_response_code(404);
        exit;
    }
    $pdfName = pathinfo($name, PATHINFO_FILENAME) . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Length: ' . filesize($pdf));
    header('Content-Disposition: ' . (isset($_GET['download']) ? 'attachment' : 'inline')
        . "; filename*=UTF-8''" . rawurlencode($pdfName));
    header('Cache-Control: private, max-age=300');
    readfile($pdf);
    exit;
}

// ?thumb=1 : renvoie l'image de première page (document Office) ; elle est préparée ici si elle manque.
$thumb = $kind === 'office' && $pdf === null ? office_thumbnail($path) : null;
if (isset($_GET['thumb'])) {
    if ($thumb === null) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: image/png');
    header('Content-Length: ' . filesize($thumb));
    header('Cache-Control: private, max-age=300');
    readfile($thumb);
    exit;
}

$text       = null;
$prettyJson = false;
$truncated  = false;

if (in_array($kind, ['text', 'json', 'markdown'], true)) {
    $read = read_preview_text($path);
    if ($read === null) {
        $kind = null; // fichier binaire déguisé : pas d'aperçu texte
    } else {
        [$text, $truncated] = $read;
        if ($kind === 'json' && !$truncated && ($pretty = pretty_json($text)) !== null) {
            $text       = $pretty;
            $prettyJson = true;
        }
    }
}

// Fil d'Ariane : les dossiers qui mènent au fichier.
$crumbs = [];
$href   = '';
foreach (array_filter(explode('/', $relDir), 'strlen') as $segment) {
    $href    .= '/' . rawurlencode($segment);
    $crumbs[] = [$segment, "$href/"];
}

$crumbs[] = [$name, ''];

[$prev, $next] = preview_neighbors(__DIR__ . $relDir, $relDir, $name);
$ext = strtoupper(pathinfo($name, PATHINFO_EXTENSION)) ?: 'FICHIER';

render('preview', [
    'title'       => $name,
    'heading'     => $name,
    'crumbs'      => $crumbs,
    'subtitle'    => $ext . ' · ' . ($size !== false ? format_size($size) : '—') . ' · ' . format_date($mtime),
    'show_tools'  => false,
    'extra_css'   => ['css/preview.css'],
    'extra_js'    => match ($kind) {
        'markdown' => ['js/markdown.js', 'js/preview.js'],
        'office'   => ['js/export.js'],
        default    => [],
    },
    'kind'        => $kind,
    'pageHref'    => preview_href($rel),
    'pdfUrl'      => $pdf === null ? null : preview_href($rel) . '&pdf=1',
    'thumbUrl'    => $thumb === null ? null : preview_href($rel) . '&thumb=1',
    'exportCommand' => $kind === 'office' ? export_command($path) : null,
    'url'         => raw_url($rel),
    'dirUrl'      => ($relDir === '' ? '' : raw_url($relDir)) . '/',
    'text'        => $text,
    'truncated'   => $truncated,
    'prettyJson'  => $prettyJson,
    'imageSize'   => $kind === 'image' && strtolower($ext) !== 'svg' ? @getimagesize($path) : false,
    'prev'        => $prev === null ? null : [$prev, preview_href("$relDir/$prev")],
    'next'        => $next === null ? null : [$next, preview_href("$relDir/$next")],
]);
