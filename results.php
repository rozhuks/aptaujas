<?php
// Aptaujas rezultati un CSV lejupielade (tikai autoram)
require __DIR__ . '/logic/logic.php';
require_login();

$survey = get_survey((int) ($_GET['id'] ?? 0), user_id());
if (!$survey) {
    show_message('Aptauja nav atrasta');
}

$questions = get_questions($survey['id']);
$responses = get_responses($survey['id']);

// CSV lejupielade
if (isset($_GET['csv'])) {
    export_csv($survey, $questions, $responses);
}

$pageTitle = $survey['title'] . ' - Atbildes';
$useChart = true;
require __DIR__ . '/views/header.php';
?>

<div class="card card-head">
    <h1 class="page-title"><?= e($survey['title']) ?> - Atbildes</h1>
    <p class="sub">Jāizpilda līdz: <?= show_date($survey['deadline']) ?> · Atbilžu skaits: <?= count($responses) ?></p>
</div>

<?php if (!$responses): ?>
    <div class="card"><p class="muted">Vēl nav nevienas atbildes.</p></div>
<?php endif; ?>

<?php foreach ($questions as $i => $q): ?>
    <?php $chart = chart_data($q, $responses); ?>
    <div class="card">
        <h2 class="q-title"><?= $i + 1 ?>. <?= e($q['text']) ?></h2>

        <?php if ($chart && $responses): ?>
            <div class="chart-box">
                <canvas class="chart"
                        data-labels="<?= e(json_encode($chart['labels'], JSON_UNESCAPED_UNICODE)) ?>"
                        data-counts="<?= e(json_encode($chart['counts'])) ?>"></canvas>
            </div>
            <?php if ($chart['avg'] !== null): ?>
                <p class="avg">Vidēji: <?= str_replace('.', ',', $chart['avg']) ?></p>
            <?php endif; ?>
        <?php endif; ?>

        <?php foreach ($responses as $r): ?>
            <?php $values = $r['answers'][$q['id']] ?? []; ?>
            <div class="answer-row">
                <span class="answer-email"><?= e($r['email']) ?></span>
                <?php if ($q['type'] === 'scale'): ?>
                    <div class="answer-scale">
                        <input type="range" min="1" max="5" value="<?= (int) ($values[0] ?? 3) ?>" disabled>
                        <span class="scale-value"><?= e($values[0] ?? '-') ?></span>
                    </div>
                <?php else: ?>
                    <div class="answer-box"><?= e(implode(', ', $values)) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<a href="results.php?id=<?= $survey['id'] ?>&csv=1" class="btn">+ Saglabāt CSV formātā</a>

<?php require __DIR__ . '/views/footer.php'; ?>
