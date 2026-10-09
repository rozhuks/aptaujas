<?php
// Registracijas lapa
require __DIR__ . '/logic/logic.php';

if (user_id()) {
    redirect('index.php');
}

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $email = mb_strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($err = validate_username($username)) {
        $errors['username'] = $err;
    } elseif (username_taken($username)) {
        $errors['username'] = 'Šāds lietotājvārds jau pastāv';
    }

    if ($err = validate_email($email)) {
        $errors['email'] = $err;
    } elseif (email_taken($email)) {
        $errors['email'] = 'Šāds e-pasts jau pastāv';
    }

    if ($err = validate_password($password)) {
        $errors['password'] = $err;
    }

    if (!$errors) {
        $id = create_user($username, $email, $password);
        login_user($id, $username);
        flash('Konts izveidots!');
        redirect('index.php');
    }
}

$pageTitle = 'Reģistrācija';
$authPage = true;
require __DIR__ . '/views/header.php';
?>

<div class="auth-card">
    <div class="auth-head">
        <img src="assets/logo.svg" alt="">
        <div>
            <h1>Reģistrācija</h1>
            <p>Aptaujas</p>
        </div>
    </div>

    <form method="post">
        <?= csrf_field() ?>

        <label class="label" for="username">Lietotājvārds</label>
        <input class="input <?= isset($errors['username']) ? 'invalid' : '' ?>" id="username" name="username"
               placeholder="Ievadiet savu lietotājvārdu" value="<?= e($username) ?>"
               minlength="3" maxlength="30" pattern="[A-Za-z0-9_]+" required>
        <?php if (isset($errors['username'])): ?>
            <p class="field-error"><?= e($errors['username']) ?></p>
        <?php endif; ?>

        <label class="label" for="email">E-pasts</label>
        <input class="input <?= isset($errors['email']) ? 'invalid' : '' ?>" id="email" name="email" type="email"
               placeholder="Ievadiet savu e-pastu" value="<?= e($email) ?>" maxlength="254" required>
        <?php if (isset($errors['email'])): ?>
            <p class="field-error"><?= e($errors['email']) ?></p>
        <?php endif; ?>

        <label class="label" for="password">Parole</label>
        <input class="input <?= isset($errors['password']) ? 'invalid' : '' ?>" id="password" name="password"
               type="password" placeholder="Ievadiet savu paroli" minlength="8" maxlength="64" required>
        <?php if (isset($errors['password'])): ?>
            <p class="field-error"><?= e($errors['password']) ?></p>
        <?php endif; ?>

        <button class="btn btn-block btn-big">Reģistrēties</button>
        <a href="login.php" class="btn btn-gray btn-block btn-big">Ir konts? Pieteikties</a>
    </form>
</div>

<?php require __DIR__ . '/views/footer.php'; ?>
