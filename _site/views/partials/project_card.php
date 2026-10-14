<?php
// Une carte de dossier. Variables : $project (élément de list_entries), $base.
// Toute la carte est cliquable (lien principal) ; l'adresse .local est un second lien posé par-dessus.
// Le lien principal ouvre l'adresse .local quand elle sert exactement ce dossier (certaines applications ne marchent pas sous localhost/dossier/),
// sinon le dossier sur localhost.
$host = local_host_for($project['path']);
$target = $host && local_host_serves_folder($project['path'], $host) ? 'http://' . $host . '/' : $base . '/' . rawurlencode($project['name']) . '/';
?>
<article class="card" data-name="<?= h(mb_strtolower($project['name'])) ?>" data-date="<?= $project['mtime'] ?>">
    <div class="card-title">
        <a class="card-link" href="<?= h($target) ?>">
            <strong><?= h($project['name']) ?></strong>
        </a>
        <span class="tag tag-<?= $project['group'] ?> tag-k-<?= $project['kind'] ?>" title="<?= h($project['tip']) ?>"><?= h($project['label']) ?></span>
    </div>
    <small title="Date du fichier le plus récent du dossier"><?= format_datetime($project['mtime']) ?></small>
    <?php if ($project['description'] !== ''): ?>
        <p class="card-desc"><?= h($project['description']) ?></p>
    <?php endif; ?>
    <div class="card-actions">
        <?php if ($host): ?>
            <a class="host" href="http://<?= h($host) ?>/" target="_blank" rel="noopener" title="Ouvrir <?= h($host) ?> dans un nouvel onglet"><?= h($host) ?></a>
        <?php endif; ?>
        <?php if (isset(EXTERNAL_LINKS[$project['name']])): ?>
            <a class="host" href="<?= h(EXTERNAL_LINKS[$project['name']][1]) ?>" target="_blank" rel="noopener" title="Ouvrir <?= h(EXTERNAL_LINKS[$project['name']][1]) ?> dans un nouvel onglet"><?= h(EXTERNAL_LINKS[$project['name']][0]) ?></a>
        <?php endif; ?>
        <a class="code-link" href="<?= h(vscode_url($project['path'])) ?>" title="Ouvrir le dossier dans VS Code">VS Code</a>
    </div>
</article>
