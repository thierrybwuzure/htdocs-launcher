<?php
// Contenu d'un dossier. Variables : $dirs, $files (listes de list_entries), $base.
// Pas de ligne « Dossier parent » : le fil d'Ariane à côté du titre permet de remonter.
?>
<?php if ($dirs): ?>
    <section data-group>
        <h2>Dossiers</h2>
        <ul class="list">
            <?php foreach ($dirs as $dir) require __DIR__ . '/partials/dir_row.php'; ?>
        </ul>
    </section>
<?php endif; ?>

<?php if ($files): ?>
    <section data-group>
        <h2>Fichiers</h2>
        <ul class="list">
            <?php foreach ($files as $file) require __DIR__ . '/partials/file_row.php'; ?>
        </ul>
    </section>
<?php endif; ?>

<?php if (!$dirs && !$files): ?>
    <ul class="list">
        <li class="empty-row">Ce dossier est vide.</li>
    </ul>
<?php endif; ?>
