<?php
// Aptaujas izveide un redigesana
require __DIR__ . '/logic/logic.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$survey = null;
$questions = [];

if ($id) {
    $survey = get_survey($id, user_id());
    if (!$survey) {
        show_message('Aptauja nav atrasta');
    }
    // Ja kads jau atbildejis, redigeti nedrikst
    if (count_responses($id) > 0) {
        flash('Aptauju, kurai jau ir atbildes, nevar rediģēt.', 'error');
        redirect('index.php');
    }
    foreach (get_questions($id) as $q) {
        $questions[] = ['text' => $q['text'], 'type' => $q['type'], 'choices' => array_column($q['choices'], 'text')];
    }
}

$errors = [];
$title = $survey['title'] ?? '';
$deadline = $survey['deadline'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $title = trim($_POST['title'] ?? '');
    $deadline = $_POST['deadline'] ?? '';
    $questions = clean_questions(json_decode($_POST['questions'] ?? '[]', true));

    $errors = validate_survey($title, $deadline, $questions);

    if (!$errors) {
        save_survey(user_id(), $title, $deadline, $questions, $survey ? $id : null);
        flash('Aptauja saglabāta!');
        redirect('index.php');
    }
}

$pageTitle = $survey ? 'Rediģēt aptauju' : 'Jauna aptauja';
require __DIR__ . '/views/header.php';
?>

<form method="post" id="survey-form">
    <?= csrf_field() ?>
    <input type="hidden" name="questions" id="questions-input"
           value="<?= e(json_encode($questions, JSON_UNESCAPED_UNICODE)) ?>">

    <div class="card card-head">
        <div class="head-row">
            <input class="input-title" name="title" placeholder="Aptaujas nosaukums"
                   value="<?= e($title) ?>" minlength="3" maxlength="100" required>
            <label class="deadline-box">
                Termiņš:
                <input type="date" name="deadline" value="<?= e($deadline) ?>"
                       min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+1 year')) ?>" required>
            </label>
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

    <!-- Seit JavaScript ieliek jautajumus -->
    <div id="questions"></div>

    <div class="bottom-row">
        <button type="button" class="btn" id="open-modal">+ Pievienot jautājumu</button>
        <button type="submit" class="btn">+ Saglabāt</button>
    </div>
</form>

<!-- Jauna jautajuma logs -->
<div class="modal-bg" id="modal" hidden>
    <div class="modal">
        <input class="input-title modal-title" id="m-text" placeholder="Jautājuma nosaukums" maxlength="255">

        <label class="label" for="m-type">Izvēlies atbilžu veidu</label>
        <select class="input select" id="m-type">
            <option value="" disabled selected>Izvēlies atbilžu veidu</option>
            <option value="text">Teksta atbilde</option>
            <option value="single">Viena atbilde</option>
            <option value="multiple">Vairākas atbildes</option>
            <option value="scale">Skala 1–5</option>
        </select>

        <div id="m-choices-box" hidden>
            <div id="m-choices"></div>
            <button type="button" class="btn btn-xs btn-gray" id="m-add-choice">+ Pievienot atbildi</button>
        </div>

        <p class="field-error" id="m-error"></p>

        <div class="modal-buttons">
            <button type="button" class="btn" id="m-save">+ Pievienot jautājumu</button>
            <button type="button" class="btn btn-gray" id="m-cancel">- Atcelt</button>
        </div>
    </div>
</div>

<?php require __DIR__ . '/views/footer.php'; ?>
