<?php
// Aperçu d'un fichier. Variables : $kind, $url, $dirUrl, $text, $truncated, $prettyJson, $imageSize,
// $thumbUrl, $pdfUrl, $exportCommand, $prev, $next.
?>
<div class="actions">
    <a class="btn" href="<?= h($url) ?>" target="_blank" rel="noopener">Ouvrir</a>
    <a class="btn" href="<?= h($url) ?>" download>Télécharger</a>
    <?php if ($kind === 'office'): ?>
        <?php if ($pdfUrl): ?>
            <a class="btn" href="<?= h($pdfUrl) ?>&amp;download=1">Télécharger le PDF</a>
        <?php endif; ?>
        <button type="button" class="btn" data-export-toggle aria-expanded="false"><?= $pdfUrl ? 'Exporter à nouveau en PDF' : 'Exporter en PDF' ?></button>
    <?php endif; ?>
    <?php if ($kind === 'markdown'): ?>
        <span class="switch" role="group" aria-label="Affichage">
            <button type="button" class="btn" data-view="rendered" aria-pressed="true">Rendu</button>
            <button type="button" class="btn" data-view="source" aria-pressed="false">Source</button>
        </span>
    <?php endif; ?>
    <span class="spacer"></span>
    <?php if ($prev): ?><a class="btn" href="<?= h($prev[1]) ?>" title="<?= h($prev[0]) ?>">‹ Précédent</a><?php endif; ?>
    <?php if ($next): ?><a class="btn" href="<?= h($next[1]) ?>" title="<?= h($next[0]) ?>">Suivant ›</a><?php endif; ?>
</div>

<?php if ($truncated): ?>
    <p class="notice">Fichier long : seul le premier Mo est affiché. Utilise « Ouvrir » ou « Télécharger » pour le voir en entier.</p>
<?php endif; ?>
<?php if ($prettyJson): ?>
    <p class="notice">JSON mis en forme. « Ouvrir » affiche le fichier tel qu'il est écrit.</p>
<?php endif; ?>

<?php if ($kind === 'image'): ?>
    <div class="stage">
        <img src="<?= h($url) ?>" alt="">
    </div>
    <?php if ($imageSize): ?>
        <p class="notice"><?= (int) $imageSize[0] ?> × <?= (int) $imageSize[1] ?> pixels</p>
    <?php endif; ?>

<?php elseif ($kind === 'office'): ?>
    <div class="export-help" id="export-help" hidden>
        <p>PowerPoint, Word et Excel s'ouvrent sur ton Mac pour produire le PDF. macOS ne laisse pas Apache lire le résultat : l'export se lance donc depuis le Terminal.</p>
        <ol>
            <li>Copie la commande ci-dessous.</li>
            <li>Colle-la dans le Terminal (ou dans Claude Code avec <code>!</code> devant) et valide.</li>
            <li>Quand elle affiche « OK », recharge cette page : le PDF s'affiche en entier.</li>
        </ol>
        <pre class="command" id="export-command"><?= h($exportCommand) ?></pre>
        <button type="button" class="btn" data-export-copy>Copier la commande</button>
        <span class="copied" id="export-copied" hidden>Commande copiée</span>
    </div>
    <?php if ($pdfUrl): ?>
        <p class="notice">PDF produit par l'application du document. « Télécharger le PDF » l'enregistre sur ton Mac.</p>
        <iframe class="frame" src="<?= h($pdfUrl) ?>" title="Aperçu PDF"></iframe>
    <?php elseif ($thumbUrl): ?>
        <p class="notice">Première page ou diapositive seulement, produite par macOS : la mise en page est proche de l'original sans être exacte. « Télécharger » donne le document complet.</p>
        <div class="stage">
            <img src="<?= h($thumbUrl) ?>" alt="Première page">
        </div>
    <?php else: ?>
        <p class="notice">macOS n'a pas pu produire l'aperçu de ce document (rendu trop lourd, fichier protégé ou illisible). Utilise « Exporter en PDF » pour l'afficher en entier ici, ou « Télécharger » pour l'ouvrir dans son application.</p>
    <?php endif; ?>

<?php elseif ($kind === 'pdf'): ?>
    <iframe class="frame" src="<?= h($url) ?>" title="Aperçu PDF"></iframe>

<?php elseif ($kind === 'video'): ?>
    <video class="media" src="<?= h($url) ?>" controls preload="metadata"></video>

<?php elseif ($kind === 'audio'): ?>
    <audio class="media" src="<?= h($url) ?>" controls preload="metadata"></audio>

<?php elseif ($kind === 'markdown'): ?>
    <article id="md-rendered" class="md" data-base="<?= h($dirUrl) ?>"></article>
    <pre id="md-source" class="code" hidden><?= code_lines($text) ?></pre>
    <textarea id="md-raw" hidden><?= h($text) ?></textarea>

<?php elseif ($kind === 'text' || $kind === 'json'): ?>
    <pre class="code"><?= code_lines($text) ?></pre>

<?php else: ?>
    <p class="notice">Pas d'aperçu pour ce type de fichier dans le navigateur. Utilise « Télécharger » pour l'ouvrir dans son application.</p>
<?php endif; ?>
