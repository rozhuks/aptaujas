<?php
// Manas aptaujas (galvena lapa) un aptaujas dzesana
require __DIR__ . '/logic/logic.php';
require_login();

// Dzesana (tikai ja nav atbilzu)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $survey = get_survey((int) ($_POST['delete'] ?? 0), user_id());
    if ($survey && count_responses($survey['id']) === 0) {
        delete_survey($survey['id']);
        flash('Aptauja izdzēsta.');
    } else {
        flash('Šo aptauju nevar dzēst.', 'error');
    }
    redirect('index.php');
}

$surveys = get_user_surveys(user_id());

$pageTitle = 'Manas aptaujas';
require __DIR__ . '/views/header.php';
?>

<div class="card card-head">
    <h1 class="page-title">Manas aptaujas</h1>
    <p class="sub">Sveiks, <?= e($_SESSION['username']) ?>!</p>
</div>

<div class="card">
    <?php if (!$surveys): ?>
        <p class="muted">Tev vēl nav nevienas aptaujas. Spied "+ Izveidot jaunu aptauju".</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <tr>
                    <th>Nosaukums</th>
                    <th>Termiņš</th>
                    <th>Atbildes</th>
                    <th>Darbības</th>
                </tr>
                <?php foreach ($surveys as $s): ?>
                    <tr>
                        <td>
                            <?= e($s['title']) ?>
                            <?php if (is_open($s)): ?>
                                <span class="badge badge-open">Aktīva</span>
                            <?php else: ?>
                                <span class="badge badge-closed">Beigusies</span>
                            <?php endif; ?>
                        </td>
                        <td><?= show_date($s['deadline']) ?></td>
                        <td><?= (int) $s['answers'] ?></td>
                        <td>
                            <div class="actions">
                                <a href="results.php?id=<?= $s['id'] ?>" class="btn btn-sm">Rezultāti</a>
                                <button type="button" class="btn btn-sm btn-gray copy-link"
                                        data-link="<?= e(survey_link($s['code'])) ?>">Kopēt saiti</button>
                                <?php if ((int) $s['answers'] === 0): ?>
                                    <a href="edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-gray">Rediģēt</a>
                                    <form method="post" onsubmit="return confirm('Dzēst šo aptauju?')">
                                        <?= csrf_field() ?>
                                        <button name="delete" value="<?= $s['id'] ?>" class="btn btn-sm btn-red">Dzēst</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/views/footer.php'; ?>
