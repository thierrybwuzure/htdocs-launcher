<?php
// Page d'accueil. Variables : $projects, $files (listes de list_entries), $base.
// Ordre : les familles neutres d'abord, puis les fichiers, puis les projets tout en bas (discrétion : voir aussi le bouton « Masquer les projets »).
foreach (KIND_GROUPS as $group => $groupTitle) {
    if ($group !== 'projets') {
        require __DIR__ . '/partials/group.php';
    }
}
?>

<?php if ($files): ?>
    <section data-group>
        <h2>Fichiers</h2>
        <ul class="list">
            <?php foreach ($files as $file) require __DIR__ . '/partials/file_row.php'; ?>
        </ul>
    </section>
<?php endif; ?>

<?php
$group      = 'projets';
$groupTitle = KIND_GROUPS['projets'];
require __DIR__ . '/partials/group.php';
