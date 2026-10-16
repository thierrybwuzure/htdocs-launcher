<?php
/**
 * Nature d'un dossier : ce qu'il est vraiment (application, site, exercice, cours, images…).
 * Le classement lit le contenu du dossier ; un fichier .tag permet de corriger un cas à la main.
 */

// clé => [étiquette, groupe, explication affichée au survol]
const KINDS = [
    'app'      => ['APP',      'projets',    "Application : du PHP avec une base de données ou une structure MVC"],
    'live'     => ['LIVE',     'projets',    "Site web statique (pages HTML, images) qui s'ouvre directement"],
    'mockup'   => ['MOCKUP',   'projets',    'Maquette : des pages de design à montrer'],
    'template' => ['TEMPLATE', 'projets',    'Modèle ou exemple prêt à adapter'],
    'lab'      => ['LAB',      'cours',      'Laboratoire : pour tester, découvrir, faire une preuve de concept (proof)'],
    'exercise' => ['KATA',     'cours',      'Exercice à faire pour pratiquer, sans rendu'],
    'course'   => ['COURSE',   'cours',      'Cours : regroupe des exercices et des supports'],
    'homework' => ['ASSIGN',   'cours',      'Devoir à rendre'],
    'docs'     => ['DOCS',     'cours',      'Documents : PDF, présentations, Word'],
    'images'   => ['IMAGES',   'ressources', "Dossier d'images"],
    'backup'   => ['BACKUP',   'ressources', 'Sauvegarde ou archive'],
    'folder'   => ['FOLDER',   'dossiers',   'Dossier : affiche son contenu'],
];

// groupe => titre affiché sur l'accueil (dans cet ordre)
const KIND_GROUPS = [
    'projets'    => 'Projets',
    'cours'      => 'Cours et exercices',
    'ressources' => 'Ressources',
    'dossiers'   => 'Dossiers',
];

// Exceptions : dossier (chemin depuis htdocs) => nature. Pour les dossiers qui sont des dépôts git :
// ne pas y ajouter de fichier .tag, qui apparaîtrait dans leur « git status ».
const KIND_OVERRIDES = [
    // 'dossier/sous-dossier' => 'template',
];

const PROFILE_FILE_CAP = 300;   // au-delà, le dossier est « gros » et on arrête de compter
const SMALL_FOLDER     = 12;    // un dossier de ce nombre de fichiers ou moins est « petit »

const EXT_CATEGORIES = [
    'img'  => ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'avif'],
    'doc'  => ['pdf', 'docx', 'doc', 'pptx', 'ppt', 'xlsx', 'xls', 'epub', 'odt'],
    'text' => ['md', 'txt'],
    'php'  => ['php'],
    'html' => ['html', 'htm'],
    'code' => ['js', 'ts', 'jsx', 'tsx', 'css', 'scss', 'py', 'sh', 'json'],
];

/** Compte les fichiers d'un dossier par catégorie (sans les cachés, node_modules, .git…), en s'arrêtant à PROFILE_FILE_CAP. */
function folder_profile(string $path): array
{
    $p = ['files' => 0, 'big' => false, 'img' => 0, 'doc' => 0, 'text' => 0, 'php' => 0, 'html' => 0, 'code' => 0,
          'subdirs' => 0, 'git' => false, 'db' => false, 'mvc' => false];

    foreach (scandir($path) as $name) {
        if ($name === '.git') {
            $p['git'] = true;
        } elseif ($name[0] !== '.' && !in_array($name, ['node_modules', 'vendor', 'BACKUP'], true) && is_dir("$path/$name")) {
            $p['subdirs']++;
        }
    }
    $p['mvc'] = is_dir("$path/app") && is_dir("$path/core");

    try {
        $filter = new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            fn(SplFileInfo $f) => $f->getFilename()[0] !== '.' && !in_array($f->getFilename(), ['node_modules', 'vendor', 'BACKUP'], true)
        );
        $phpRead = 0;
        foreach (new RecursiveIteratorIterator($filter) as $file) {
            if ($file->getFilename() === 'CLAUDE.md') {
                continue;
            }
            if (++$p['files'] > PROFILE_FILE_CAP) {
                $p['big'] = true;
                break;
            }
            $ext = strtolower($file->getExtension());
            foreach (EXT_CATEGORIES as $category => $extensions) {
                if (in_array($ext, $extensions, true)) {
                    $p[$category]++;
                    if ($category === 'php' && !$p['db'] && $phpRead++ < 40) {
                        $head = (string) @file_get_contents($file->getPathname(), false, null, 0, 8000);
                        $p['db'] = (bool) preg_match('/new PDO|mysqli_connect|new mysqli|mysqli_init/', $head);
                    }
                    break;
                }
            }
        }
    } catch (Throwable $e) {
        // dossier illisible : on garde ce qu'on a compté
    }
    return $p;
}

/**
 * Nature d'un dossier, par ordre de priorité :
 *  1. un fichier .tag (ou .lab, .site pour LIVE) dans le dossier, ou la liste KIND_OVERRIDES, qui la fixe à la main ;
 *  2. son nom (sauvegarde, devoir, maquette) ;
 *  3. son contenu : une page d'accueil donne une application, un site ou un exercice ; sans page d'accueil,
 *     images seules, documents seuls, cours (plusieurs sous-dossiers), exercice (petit dossier de code).
 * Renvoie une clé de KINDS.
 */
function folder_kind(string $path): string
{
    // 1. Réglage à la main
    foreach (['.tag' => null, '.lab' => 'lab', '.site' => 'live'] as $marker => $fixed) {
        if (is_file("$path/$marker")) {
            if ($fixed !== null) {
                return $fixed;
            }
            $word = strtolower(trim((string) strtok((string) @file_get_contents("$path/$marker"), "\n")));
            if (isset(KINDS[$word])) {
                return $word;
            }
        }
    }

    // 1 bis. Liste d'exceptions
    $relative = ltrim(substr($path, strlen(dirname(__DIR__, 2))), '/');
    if (isset(KIND_OVERRIDES[$relative])) {
        return KIND_OVERRIDES[$relative];
    }

    // 2. Le nom
    $name = strtolower(basename($path));
    if (preg_match('/backup|sauvegarde/', $name)) {
        return 'backup';
    }
    if (preg_match('/devoir|homework|brief|assignment/', $name)) {
        return 'homework';
    }
    if (preg_match('/exercice|exercise|(^|[^a-z])exos?([^a-z]|$)|practice|kata|basics/', $name)) {
        return 'exercise';
    }
    if (preg_match('/mockup|maquette/', $name)) {
        return 'mockup';
    }

    // 3. Le contenu
    $p = folder_profile($path);
    $n = max($p['files'], 1);
    if ($p['files'] === 0) {
        return 'folder';
    }

    $index = null;
    foreach (['index.php', 'index.html', 'index.htm'] as $candidate) {
        if (is_file("$path/$candidate")) {
            $index = $candidate;
            break;
        }
    }

    // Une structure app/ + core/ est une application, même si sa page d'accueil est dans public/
    if ($p['mvc'] || ($index !== null && $p['db'])) {
        return 'app';
    }

    if ($index !== null) {
        if ($index !== 'index.php' && ($p['git'] || $p['html'] >= 3 || $p['img'] >= 5)) {
            return 'live';
        }
        if ($p['files'] <= SMALL_FOLDER + 1) {
            return 'lab';
        }
        return $p['code'] > $p['html'] + $p['img'] && $index !== 'index.php' ? 'lab' : 'live';
    }

    // Pas de page d'accueil : on regarde de quoi le dossier est fait
    if ($p['img'] / $n >= 0.8) {
        return 'images';
    }
    if (($p['doc'] + $p['text']) / $n >= 0.8 && $p['doc'] > 0) {
        return 'docs';
    }
    if ($p['subdirs'] >= 2 && !$p['big']) {
        return 'course';
    }
    if ($p['files'] <= SMALL_FOLDER && (($p['php'] + $p['html'] + $p['code']) > 0 || $p['git'])) {
        return 'lab';
    }
    return 'folder';
}
