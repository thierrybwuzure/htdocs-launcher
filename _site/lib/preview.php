<?php
/**
 * Aperçu de fichiers : quels types, quels fichiers refuser, lecture du texte.
 * Ne dépend que de fs.php (HIDDEN_EXTENSIONS, list_entries) et view.php (h).
 */

// Type d'aperçu selon l'extension. Les .php n'y figurent pas volontairement : leur code contient parfois des mots de passe.
const PREVIEW_KINDS = [
    'image'    => ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'avif'],
    'pdf'      => ['pdf'],
    'markdown' => ['md', 'markdown'],
    'json'     => ['json'],
    'text'     => ['txt', 'yml', 'yaml', 'xml', 'xsl', 'html', 'htm', 'css', 'scss', 'js', 'mjs', 'ts', 'jsx', 'tsx', 'sh', 'py', 'toml'],
    'video'    => ['mp4', 'webm', 'mov', 'm4v'],
    'audio'    => ['mp3', 'wav', 'ogg', 'm4a'],
    'office'   => ['docx', 'doc', 'pptx', 'ppt', 'xlsx', 'xls', 'odt'], // première page seulement, via macOS
];

// Fichiers et dossiers qui contiennent des identifiants : jamais d'aperçu.
const PREVIEW_BLOCKED_NAMES = ['database.php', 'db.php', 'config.php', 'wp-config.php', 'settings.local.json', 'id_rsa',
    'credentials.json', 'secrets.json', 'service-account.json', 'appsettings.json'];
const PREVIEW_BLOCKED_DIRS  = ['config', 'secrets', 'private'];

const PREVIEW_TEXT_LIMIT   = 1048576;                      // 1 Mo
const THUMB_MAX_BYTES      = 50 * 1048576;                 // pas d'aperçu au-delà de 50 Mo
const THUMB_TIMEOUT        = 20;                           // secondes avant d'abandonner qlmanage
const THUMB_SIZE           = 1400;                         // côté le plus long de l'image, en pixels
const THUMB_RETRY_AFTER    = 3600;                         // secondes avant de retenter un document qui a échoué

function preview_kind(string $name): ?string
{
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    foreach (PREVIEW_KINDS as $kind => $extensions) {
        if (in_array($ext, $extensions, true)) {
            return $kind;
        }
    }
    return null;
}

/** Chemin relatif à htdocs (commence par /). Vrai si l'aperçu est interdit. */
function preview_blocked(string $relPath): bool
{
    $parts = array_values(array_filter(explode('/', $relPath), 'strlen'));
    $name  = end($parts);

    if ($name === false || $name[0] === '.' || str_starts_with($name, '~$') || preg_match(HIDDEN_EXTENSIONS, $name)
        || in_array(strtolower($name), PREVIEW_BLOCKED_NAMES, true)) {
        return true;
    }

    foreach (array_slice($parts, 0, -1) as $dir) {
        if ($dir[0] === '.' || in_array(strtolower($dir), PREVIEW_BLOCKED_DIRS, true)) {
            return true;
        }
    }
    return false;
}

/** Adresse de la page d'aperçu d'un fichier. */
function preview_href(string $relPath): string
{
    return '/_preview.php?f=' . rawurlencode($relPath);
}

/** Adresse directe du fichier (chaque segment encodé). */
function raw_url(string $relPath): string
{
    return implode('/', array_map('rawurlencode', explode('/', $relPath)));
}

/**
 * Lit au plus 1 Mo de texte, en UTF-8. Renvoie [texte, tronqué] ou null si le fichier est binaire.
 */
function read_preview_text(string $path): ?array
{
    $raw = @file_get_contents($path, false, null, 0, PREVIEW_TEXT_LIMIT + 1);
    if ($raw === false || strpos(substr($raw, 0, 8000), "\0") !== false) {
        return null;
    }

    $truncated = strlen($raw) > PREVIEW_TEXT_LIMIT;
    if ($truncated) {
        $raw = mb_strcut($raw, 0, PREVIEW_TEXT_LIMIT, 'UTF-8');
    }
    if (!mb_check_encoding($raw, 'UTF-8')) {
        $raw = mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-1');
    }
    return [$raw, $truncated];
}

/** JSON mis en forme, ou null si le texte n'est pas du JSON valide. */
function pretty_json(string $text): ?string
{
    $data = json_decode($text);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/** Une ligne de code par <span class="line"> ; les numéros viennent du CSS. */
function code_lines(string $text): string
{
    $text = preg_replace('/\R\z/u', '', $text);
    $html = '';
    foreach (preg_split('/\R/u', $text) as $line) {
        $html .= '<span class="line">' . h($line) . '</span>';
    }
    return $html;
}

/** Fichiers précédent et suivant du même dossier qui ont un aperçu : [prev|null, next|null] (noms). */
function preview_neighbors(string $dirPath, string $relDir, string $name): array
{
    $names = [];
    foreach (list_entries($dirPath)['files'] as $file) {
        if (preview_kind($file['name']) !== null && !preview_blocked("$relDir/{$file['name']}")) {
            $names[] = $file['name'];
        }
    }
    $i = array_search($name, $names, true);
    if ($i === false) {
        return [null, null];
    }
    return [$names[$i - 1] ?? null, $names[$i + 1] ?? null];
}


/**
 * Identifiant d'un document pour le cache : l'empreinte de son contenu (SHA-1, comme `shasum`).
 * Le chemin n'entre pas en jeu : un accent écrit de deux façons par macOS, ou une copie du fichier
 * ailleurs, donnent la même clé, et les copies identiques partagent le même aperçu.
 */
function office_cache_key(string $path): string
{
    return sha1_file($path) ?: sha1($path);
}

/** Dossier où sont gardées les images de première page et les PDF exportés (hors de htdocs). */
function thumb_cache_dir(): string
{
    $home = getenv('HOME') ?: '/Users/' . get_current_user();
    return "$home/Library/Caches/htdocs-preview";
}

/**
 * Image PNG de la première page d'un document Office, produite par l'outil Aperçu de macOS (qlmanage).
 * Mise en cache : l'image est refaite seulement quand le fichier change (nom, taille ou date).
 * Renvoie le chemin de l'image, ou null si macOS n'a pas pu la produire.
 */
function office_thumbnail(string $path): ?string
{
    $size = @filesize($path);
    if ($size === false || $size > THUMB_MAX_BYTES) {
        return null;
    }

    $cache = thumb_cache_dir();
    if (!is_dir($cache) && !@mkdir($cache, 0700, true)) {
        return null;
    }

    $target = "$cache/" . office_cache_key($path) . '.png';
    if (is_file($target)) {
        return $target;
    }

    // Échec récent : on ne refait pas attendre 20 secondes à chaque visite.
    $failed = "$target.echec";
    if (is_file($failed) && time() - filemtime($failed) < THUMB_RETRY_AFTER) {
        return null;
    }

    $work = "$cache/work-" . bin2hex(random_bytes(6));
    if (!@mkdir($work, 0700)) {
        return null;
    }

    // Commande sous forme de liste : aucun passage par un shell, donc pas d'injection par le nom du fichier.
    $process = @proc_open(
        ['/usr/bin/qlmanage', '-t', '-s', (string) THUMB_SIZE, '-o', $work, $path],
        [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
        $pipes
    );

    $result = null;
    if (is_resource($process)) {
        $deadline = time() + THUMB_TIMEOUT;
        while (proc_get_status($process)['running'] && time() < $deadline) {
            usleep(100000);
        }
        if (proc_get_status($process)['running']) {
            proc_terminate($process, 9);
        }
        proc_close($process);

        // qlmanage lance parfois un sous-processus qui survit : on le retrouve grâce au nom (unique) du dossier de travail.
        $cleanup = @proc_open(['/usr/bin/pkill', '-9', '-f', $work], [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $ignored);
        if (is_resource($cleanup)) {
            proc_close($cleanup);
        }

        $made = glob("$work/*.png");
        if ($made && @rename($made[0], $target)) {
            $result = $target;
        }
    }

    array_map('unlink', glob("$work/*") ?: []);
    @rmdir($work);

    if ($result === null) {
        @touch($failed);
    }
    return $result;
}


/** PDF déjà exporté pour ce document (et toujours à jour), ou null. */
function office_cached_pdf(string $path): ?string
{
    $size = @filesize($path);
    if ($size === false) {
        return null;
    }
    $pdf = thumb_cache_dir() . '/' . office_cache_key($path) . '.pdf';
    return is_file($pdf) ? $pdf : null;
}

/**
 * Commande à lancer dans le Terminal pour exporter ce document en PDF.
 * L'export doit se faire depuis le Terminal : macOS interdit à Apache de lire le PDF que PowerPoint,
 * Word ou Excel écrivent dans leur dossier protégé. Le script copie ensuite le PDF dans le cache,
 * et la page d'aperçu l'affiche.
 */
function export_command(string $path): string
{
    $quote = fn(string $text) => "'" . str_replace("'", "'\\''", $text) . "'";
    return 'bash ' . $quote(dirname(__DIR__) . '/export/export-pdf.sh') . ' ' . $quote($path);
}
