<?php
// Aptaujas aizpildisana (respondentam nav japiesakas)
require __DIR__ . '/logic/logic.php';

$survey = get_survey_by_code($_GET['code'] ?? '');

if (!$survey) {
    show_message('Aptauja nav atrasta', 'Pārbaudi, vai saite ir pareiza.');
}
if (!is_open($survey)) {
    show_message($survey['title'], 'Aptaujas termiņš ir beidzies.');
}

$questions = get_questions($survey['id']);
$email = '';
$emailError = null;
$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = mb_strtolower(trim($_POST['email'] ?? ''));
    $old = is_array($_POST['answers'] ?? null) ? $_POST['answers'] : [];

    if ($err = validate_email($email)) {
        $emailError = $err;
    } elseif (email_used($survey['id'], $email)) {
        $emailError = 'Ar šo e-pastu aptauja jau ir aizpildīta.';
    }

    [$errors, $answers] = validate_answers($questions, $old);

    if (!$emailError && !$errors) {
        save_response($survey['id'], $email, $answers);
        show_message('Paldies!', 'Tavas atbildes ir saglabātas.');
    }
}

$pageTitle = $survey['title'];
require __DIR__ . '/views/header.php';
?>

<form method="post">
    <?= csrf_field() ?>

    <div class="card card-head">
        <div class="head-row">
            <div>
                <h1 class="page-title"><?= e($survey['title']) ?></h1>
                <p class="sub">Jāizpilda līdz: <?= show_date($survey['deadline']) ?></p>
            </div>
            <div class="email-box">
                <label class="label" for="email">E-pasts *</label>
                <input class="input <?= $emailError ? 'invalid' : '' ?>" id="email" name="email" type="email"
                       placeholder="Ievadiet savu e-pastu" value="<?= e($email) ?>" maxlength="254" required>
                <?php if ($emailError): ?>
                    <p class="field-error"><?= e($emailError) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php foreach ($questions as $i => $q): ?>
        <?php $name = 'answers[' . $q['id'] . ']'; $value = $old[$q['id']] ?? null; ?>
        <div class="card">
            <h2 class="q-title"><?= $i + 1 ?>. <?= e($q['text']) ?></h2>

            <?php if ($q['type'] === 'text'): ?>
                <input class="input input-half" name="<?= $name ?>" placeholder="Ievadiet savu atbildi"
                       value="<?= e(is_string($value) ? $value : '') ?>" maxlength="1000" required>

            <?php elseif ($q['type'] === 'scale'): ?>
                <div class="scale-labels"><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span></div>
                <input type="range" name="<?= $name ?>" min="1" max="5" value="<?= is_numeric($value) ? (int) $value : 3 ?>">

            <?php else: ?>
                <div class="choices">
                    <?php foreach ($q['choices'] as $c): ?>
                        <label class="choice">
                            <?php if ($q['type'] === 'single'): ?>
                                <input type="radio" name="<?= $name ?>" value="<?= $c['id'] ?>" required
                                    <?= (string) $value === (string) $c['id'] ? 'checked' : '' ?>>
                            <?php else: ?>
                                <input type="checkbox" name="<?= $name ?>[]" value="<?= $c['id'] ?>"
                                    <?= is_array($value) && in_array((string) $c['id'], $value, true) ? 'checked' : '' ?>>
                            <?php endif; ?>
                            <?= e($c['text']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="bottom-row end">
        <button class="btn btn-wide">Iesniegt</button>
    </div>
</form>

<?php require __DIR__ . '/views/footer.php'; ?>
