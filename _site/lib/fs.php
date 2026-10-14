<?php
require_once __DIR__ . '/kind.php';
require_once __DIR__ . '/descriptions.php';

/**
 * Lecture des dossiers et mise en forme des fichiers.
 * Rien ici n'affiche de HTML : ces fonctions renvoient des données.
 */

// Extensions refusées par htdocs/.htaccess : on ne les affiche pas non plus.
const HIDDEN_EXTENSIONS = '/\.(sql|csv|env|bak|log|sqlite|db|ini|conf|pem|key)$/i';

// Fichiers du site lui-même et notes de travail : jamais listés, dans aucun dossier.
const HIDDEN_NAMES = ['_listing.php', '_preview.php', '_site', 'CLAUDE.md'];

function format_size(float $bytes): string
{
    foreach (['o', 'Ko', 'Mo', 'Go'] as $unit) {
        if ($bytes < 1024) {
            return ($unit === 'o' ? (int) $bytes : round($bytes, 1)) . " $unit";
        }
        $bytes /= 1024;
    }
    return round($bytes, 1) . ' To';
}

function format_date(int $timestamp): string
{
    return $timestamp ? date('d/m/Y', $timestamp) : '—';
}

/** Date et heure : 07/10/2026 · 01:58. */
function format_datetime(int $timestamp): string
{
    return $timestamp ? date('d/m/Y · H:i', $timestamp) : '—';
}

/** Catégorie d'un fichier d'après son extension : [libellé court, classe de couleur]. */
function file_kind(string $name): array
{
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $map = [
        'code' => ['php', 'js', 'ts', 'jsx', 'tsx', 'css', 'scss', 'html', 'htm', 'json', 'xml', 'py', 'sh', 'yml', 'yaml'],
        'img'  => ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'avif'],
        'doc'  => ['md', 'txt', 'pdf', 'docx', 'doc', 'pptx', 'ppt', 'xlsx', 'xls', 'epub', 'odt'],
        'zip'  => ['zip', 'rar', 'gz', 'tar', '7z', 'dmg'],
    ];
    foreach ($map as $class => $extensions) {
        if (in_array($ext, $extensions, true)) {
            return [strtoupper($ext), $class];
        }
    }
    return [$ext !== '' ? strtoupper($ext) : 'FICHIER', 'other'];
}

/** Date du dernier commit de code d'un dépôt git (à la racine du dossier ; les commits qui ne touchent que CLAUDE.md, .gitignore ou .htaccess sont ignorés), ou 0 si le dossier n'est pas un dépôt ou si git n'est pas joignable. */
function last_commit(string $path): int
{
    if (!file_exists("$path/.git")) {
        return 0;
    }
    foreach (['/opt/homebrew/bin/git', '/usr/local/bin/git', '/usr/bin/git'] as $git) {
        if (is_executable($git)) {
            // safe.directory : Apache n'est pas le propriétaire des dossiers, git refuserait sinon.
            $out = @shell_exec(escapeshellarg($git) . ' -c safe.directory=' . escapeshellarg($path)
                . ' -C ' . escapeshellarg($path) . ' log -1 --format=%ct -- . ":(exclude)CLAUDE.md" ":(exclude).gitignore" ":(exclude).htaccess" 2>/dev/null');
            return ctype_digit(trim((string) $out)) ? (int) trim($out) : 0;
        }
    }
    return 0;
}

/**
 * Date de la dernière modification d'un dossier. Dépôt git : l'heure de son dernier commit (un « git pull » ou un
 * changement de branche réécrit les fichiers et ne dirait rien du travail). Sinon : celle du fichier le plus récent
 * qu'il contient, à n'importe quelle profondeur (la date du dossier lui-même est trompeuse : elle ne change que
 * quand on y ajoute, retire ou renomme un élément).
 * Ignore les fichiers cachés, CLAUDE.md et les dossiers node_modules, vendor, BACKUP.
 * Renvoie 0 si le dossier ne contient aucun fichier.
 */
function last_change(string $path): int
{
    if ($commit = last_commit($path)) {
        return $commit;
    }
    $newest = 0;
    try {
        $filter = new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            fn(SplFileInfo $f) => $f->getFilename()[0] !== '.' && $f->getFilename() !== 'CLAUDE.md'
                && !in_array($f->getFilename(), ['node_modules', 'vendor', 'BACKUP'], true)
        );
        foreach (new RecursiveIteratorIterator($filter) as $file) {
            $newest = max($newest, (int) $file->getMTime());
        }
    } catch (Throwable $e) {
        // dossier illisible : on garde la date trouvée jusque-là
    }
    return $newest;
}

/**
 * Liste un dossier. Ignore les fichiers cachés (point), les extensions sensibles
 * et les noms de $exclude. Chaque élément : name, mtime (pour un dossier : son fichier le plus récent, voir last_change) ; un dossier a path, kind, label, group, tip (voir kind.php) ; un fichier a size, label, class.
 *
 * @return array{dirs: array, files: array} trié par nom (ordre naturel)
 */
function list_entries(string $dir, array $exclude = []): array
{
    $dirs = $files = [];

    foreach (scandir($dir) as $name) {
        if ($name[0] === '.' || str_starts_with($name, '~$') || in_array($name, HIDDEN_NAMES, true) || in_array($name, $exclude, true)
            || preg_match(HIDDEN_EXTENSIONS, $name)) {
            continue;
        }

        $path = "$dir/$name";
        $item = ['name' => $name, 'mtime' => @filemtime($path) ?: 0];

        if (is_dir($path)) {
            $item['mtime'] = last_change($path) ?: $item['mtime'];
            $kind   = folder_kind($path);
            $dirs[] = $item + ['path' => $path, 'kind' => $kind, 'label' => KINDS[$kind][0], 'group' => KINDS[$kind][1], 'tip' => KINDS[$kind][2],
                                'blocked' => folder_preview_blocked($name),
                                'description' => ($dir === dirname(__DIR__, 2) ? folder_description($name) : '') ?: folder_summary($path, KINDS[$kind][2])];
        } elseif (is_file($path)) {
            [$label, $class] = file_kind($name);
            $size    = @filesize($path);
            // Fichier dont l'aperçu est interdit (mots de passe possibles) : sa description n'est jamais tirée du contenu.
            $rel     = substr($path, strlen(dirname(__DIR__, 2)));
            $blocked = function_exists('preview_blocked') ? preview_blocked($rel) : true;
            $files[] = $item + ['size' => $size, 'label' => $label, 'class' => $class, 'blocked' => $blocked,
                                'description' => file_description($path, $name, $size, $blocked)];
        }
    }

    $byName = fn(array $a, array $b) => strnatcasecmp($a['name'], $b['name']);
    usort($dirs, $byName);
    usort($files, $byName);

    return ['dirs' => $dirs, 'files' => $files];
}

/** « 3 dossiers · 1 fichier » */
function count_label(int $dirs, int $files): string
{
    return "$dirs dossier" . ($dirs > 1 ? 's' : '') . " · $files fichier" . ($files > 1 ? 's' : '');
}

function sort_by_date_desc(array $items): array
{
    usort($items, fn(array $a, array $b) => $b['mtime'] <=> $a['mtime']);
    return $items;
}
