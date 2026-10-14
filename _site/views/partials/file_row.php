<?php
// Une ligne de fichier. Variables : $file (élément de list_entries), $base (préfixe d'adresse, '' à la racine).
$rel        = rawurldecode($base) . '/' . $file['name'];
$canPreview = !$file['blocked'];   // chaque fichier a un bouton Aperçu, sauf ceux dont l'aperçu est interdit (grisé)
?>
<li class="has-desc" data-name="<?= h(mb_strtolower($file['name'])) ?>" data-date="<?= $file['mtime'] ?>">
    <a href="<?= $base . '/' . rawurlencode($file['name']) ?>">
        <span class="badge k-<?= $file['class'] ?>"><?= h(mb_substr($file['label'], 0, 5)) ?></span>
        <span class="name">
            <span class="name-main"><?= h($file['name']) ?></span>
            <span class="file-desc"><?= h($file['description']) ?></span>
        </span>
        <span class="meta">
            <span><?= $file['size'] !== false ? format_size($file['size']) : '—' ?></span>
            <span><?= format_datetime($file['mtime']) ?></span>
        </span>
    </a>
    <?php if ($canPreview): ?>
        <a class="btn" href="<?= h(preview_href($rel)) ?>">Aperçu</a>
    <?php else: ?>
        <span class="btn is-off" aria-disabled="true" title="Aperçu non autorisé : ce fichier peut contenir un mot de passe">Aperçu</span>
    <?php endif; ?>
</li>
