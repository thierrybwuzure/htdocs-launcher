<?php
// Aperçu d'un dossier. Variables : $kindLabel, $kindTip, $summary, $dirUrl, $entries (dirs, files de list_entries), $base.
$shown = 0;
$max   = 40;   // au plus 40 éléments : l'aperçu donne une idée du contenu, le listing complet est à un clic
?>
<div class="actions">
    <a class="btn" href="<?= h($dirUrl) ?>">Ouvrir le dossier</a>
</div>

<p class="notice"><strong><?= h($kindLabel) ?></strong> · <?= h($kindTip) ?></p>
<?php if ($summary !== ''): ?>
    <p class="notice"><?= h($summary) ?></p>
<?php endif; ?>

<?php if ($entries['dirs'] || $entries['files']): ?>
    <ul class="list">
        <?php foreach ($entries['dirs'] as $dir): if ($shown++ >= $max) break; require __DIR__ . '/partials/dir_row.php'; endforeach; ?>
        <?php foreach ($entries['files'] as $file): if ($shown++ >= $max) break; require __DIR__ . '/partials/file_row.php'; endforeach; ?>
    </ul>
    <?php $total = count($entries['dirs']) + count($entries['files']); if ($total > $max): ?>
        <p class="notice"><?= $max ?> premiers éléments sur <?= $total ?> : « Ouvrir le dossier » montre la liste complète.</p>
    <?php endif; ?>
<?php else: ?>
    <ul class="list">
        <li class="empty-row">Ce dossier est vide.</li>
    </ul>
<?php endif; ?>
