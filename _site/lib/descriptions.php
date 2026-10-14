<?php
/**
 * Courte description de chaque dossier de l'accueil, écrite d'après son contenu réel (README, CLAUDE.md, fichiers).
 * Clé : nom du dossier à la racine de htdocs. Un dossier absent de la liste n'a pas de description.
 * Garder chaque texte court (une centaine de caractères) : la carte a une hauteur fixée par la proportion d'or.
 */
const DESCRIPTIONS = [
    // 'mon-dossier' => "Courte description affichée sur la carte.",
];

/** Lien externe d'une carte (site du cours ou de la ressource). Clé : nom du dossier à la racine : [libellé, adresse]. */
const EXTERNAL_LINKS = [
    // 'mon-dossier' => ['Libellé', 'https://exemple.com/'],
];

/** Description d'un dossier de la racine, ou chaîne vide. */
function folder_description(string $name): string
{
    return DESCRIPTIONS[$name] ?? '';
}

/** Libellé court du type d'un fichier, en français, d'après son extension. */
const FILE_TYPE_NAMES = [
    'pdf'  => 'Document PDF',        'docx' => 'Document Word',          'doc'  => 'Document Word',
    'odt'  => 'Document texte',      'pptx' => 'Présentation PowerPoint', 'ppt'  => 'Présentation PowerPoint',
    'xlsx' => 'Classeur Excel',      'xls'  => 'Classeur Excel',         'epub' => 'Livre numérique ePub',
    'md'   => 'Document Markdown',   'markdown' => 'Document Markdown',  'txt'  => 'Fichier texte',
    'html' => 'Page web HTML',       'htm'  => 'Page web HTML',          'css'  => 'Feuille de style CSS',
    'scss' => 'Feuille de style Sass', 'js' => 'Script JavaScript',      'ts'   => 'Script TypeScript',
    'json' => 'Données JSON',        'xml'  => 'Données XML',            'yml'  => 'Configuration YAML',
    'yaml' => 'Configuration YAML',  'php'  => 'Script PHP',             'py'   => 'Script Python',
    'sh'   => 'Script shell',        'zip'  => 'Archive compressée',     'rar'  => 'Archive compressée',
    'gz'   => 'Archive compressée',  'tar'  => 'Archive',                '7z'   => 'Archive compressée',
    'dmg'  => 'Image disque macOS',  'png'  => 'Image PNG',              'jpg'  => 'Image JPEG',
    'jpeg' => 'Image JPEG',          'gif'  => 'Image GIF',              'webp' => 'Image WebP',
    'svg'  => 'Image vectorielle SVG', 'ico' => 'Icône',                 'avif' => 'Image AVIF',
    'mp4'  => 'Vidéo MP4',           'mov'  => 'Vidéo QuickTime',        'webm' => 'Vidéo WebM',
    'mp3'  => 'Audio MP3',           'wav'  => 'Audio WAV',              'm4a'  => 'Audio M4A',
];

/**
 * Courte description d'un fichier : son type en toutes lettres, avec un détail tiré du contenu quand c'est peu coûteux
 * et sans risque (titre d'un Markdown ou d'une page HTML, première ligne d'un texte, dimensions d'une image, nombre de
 * pages d'un PDF). On ne lit jamais le contenu des fichiers qui peuvent porter des mots de passe (PHP, config...) :
 * ils n'ont que leur type.
 *
 * @param int|false $size
 */
function file_description(string $path, string $name, $size, bool $blocked): string
{
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $type = FILE_TYPE_NAMES[$ext] ?? ($ext !== '' ? 'Fichier ' . strtoupper($ext) : 'Fichier');
    if ($blocked || $size === false) {
        return $type;
    }

    $cut = fn(string $s, int $n = 70) => mb_strlen($s) > $n ? rtrim(mb_substr($s, 0, $n - 1)) . '…' : $s;
    $head = function () use ($path, $size) {
        if ($size > 1048576) {
            return '';
        }
        $raw = (string) @file_get_contents($path, false, null, 0, 4096);
        return mb_check_encoding($raw, 'UTF-8') ? $raw : mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-1');
    };

    switch ($ext) {
        case 'md':
        case 'markdown':
            if (preg_match('/^\s*#{1,6}\s+(.+)$/m', $head(), $m)) {
                return $type . ' : ' . $cut(trim($m[1]));
            }
            break;
        case 'html':
        case 'htm':
            if (preg_match('/<title[^>]*>\s*(.*?)\s*<\/title>/is', $head(), $m) && trim($m[1]) !== '') {
                return $type . ' : ' . $cut(trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8')));
            }
            break;
        case 'txt':
            foreach (preg_split('/\R/u', $head()) as $line) {
                if (trim($line) !== '') {
                    return $type . ' : ' . $cut(trim($line));
                }
            }
            break;
        case 'png':
        case 'jpg':
        case 'jpeg':
        case 'gif':
        case 'webp':
            if ($size <= 20971520 && ($info = @getimagesize($path)) !== false) {
                return $type . ', ' . (int) $info[0] . ' × ' . (int) $info[1] . ' pixels';
            }
            break;
        case 'pdf':
            if ($size <= 3145728) {
                $count = preg_match_all('/\/Type\s*\/Page[^s]/', (string) @file_get_contents($path));
                if ($count > 0) {
                    return $type . ' de ' . $count . ' page' . ($count > 1 ? 's' : '');
                }
            }
            break;
    }
    return $type;
}

/** Vrai si l'aperçu de ce dossier est interdit (dossier de configuration ou de secrets, dossier caché). */
function folder_preview_blocked(string $name): bool
{
    return $name === '' || $name[0] === '.' || in_array(strtolower($name), PREVIEW_BLOCKED_DIRS, true);
}

/** Première ligne utile (titre, sinon première phrase) du README d'un dossier, ou chaîne vide. */
function folder_readme_line(string $path): string
{
    $cut = fn(string $s, int $n = 80) => mb_strlen($s) > $n ? rtrim(mb_substr($s, 0, $n - 1)) . '…' : $s;

    foreach (['README.md', 'readme.md', 'README.txt'] as $readme) {
        $file = "$path/$readme";
        if (is_file($file) && (@filesize($file) ?: 0) <= 1048576) {
            $raw = (string) @file_get_contents($file, false, null, 0, 4096);
            $raw = mb_check_encoding($raw, 'UTF-8') ? $raw : mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-1');
            if (preg_match('/^\s*#{1,6}\s+(.+)$/m', $raw, $m)) {
                return $cut(trim(strip_tags($m[1])));
            }
            foreach (preg_split('/\R/u', $raw) as $line) {
                $line = trim($line);
                if ($line !== '' && !str_starts_with($line, '!') && !str_starts_with($line, '<') && !str_starts_with($line, '---')) {
                    return $cut($line);
                }
            }
        }
    }
    return '';
}

/**
 * Description d'un dossier qui n'a pas de texte écrit à la main : la première ligne utile de son README s'il en a un,
 * sinon sa nature en quelques mots et le nombre de dossiers et de fichiers qu'il contient.
 */
function folder_summary(string $path, string $tip): string
{
    if (($line = folder_readme_line($path)) !== '') {
        return $line;
    }

    $dirs = $files = 0;
    foreach (@scandir($path) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..' || $entry[0] === '.') {
            continue;
        }
        is_dir("$path/$entry") ? $dirs++ : $files++;
    }
    $nature = trim(explode(' : ', $tip)[0]);
    $parts  = [];
    if ($dirs) {
        $parts[] = $dirs . ' dossier' . ($dirs > 1 ? 's' : '');
    }
    if ($files) {
        $parts[] = $files . ' fichier' . ($files > 1 ? 's' : '');
    }
    return $nature . ($parts ? ' · ' . implode(' et ', $parts) : ' · vide');
}
