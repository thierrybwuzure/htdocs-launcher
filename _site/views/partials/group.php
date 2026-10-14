<?php
// Un groupe de cartes. Variables : $group (clé de KIND_GROUPS), $groupTitle, $projects, $base.
$members = array_filter($projects, fn(array $p) => $p['group'] === $group);
if (!$members) {
    return;
}
?>
<section data-group data-kind="<?= h($group) ?>">
    <h2><?= h($groupTitle) ?></h2>
    <div class="grid">
        <?php foreach ($members as $project) require __DIR__ . '/project_card.php'; ?>
    </div>
</section>
