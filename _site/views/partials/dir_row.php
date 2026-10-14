<?php
// Une ligne de dossier. Variables : $dir (élément de list_entries), $base (préfixe d'adresse, '' à la racine).
$dirRel = rawurldecode($base) . '/' . $dir['name'];
?>
<li class="has-desc" data-name="<?= h(mb_strtolower($dir['name'])) ?>" data-date="<?= $dir['mtime'] ?>">
    <a href="<?= $base . '/' . rawurlencode($dir['name']) ?>/">
        <span class="badge dir <?= $dir['group'] ?> d-<?= $dir['kind'] ?>" title="<?= h($dir['tip']) ?>"><?= h($dir['label']) ?></span>
        <span class="name">
            <span class="name-main"><?= h($dir['name']) ?></span>
            <span class="file-desc"><?= h($dir['description']) ?></span>
        </span>
        <span class="meta"><span><?= format_datetime($dir['mtime']) ?></span></span>
    </a>
    <?php if (!$dir['blocked']): ?>
        <a class="btn" href="<?= h(preview_href($dirRel)) ?>">Aperçu</a>
    <?php else: ?>
        <span class="btn is-off" aria-disabled="true" title="Aperçu non autorisé : ce dossier peut contenir des mots de passe">Aperçu</span>
    <?php endif; ?>
</li>
