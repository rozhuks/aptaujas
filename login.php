<?php
// Pieteiksanas lapa (ari izrakstisanas)
require __DIR__ . '/logic/logic.php';

// Izrakstisanas
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    redirect('login.php');
}

if (user_id()) {
    redirect('index.php');
}

$error = null;
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $login = trim($_POST['login'] ?? '');
    $user = find_user($login);

    if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
        login_user($user['id'], $user['username']);
        redirect('index.php');
    }
    $error = 'Nepareizs lietotājvārds vai parole.';
}

$pageTitle = 'Pieteikšanās';
$authPage = true;
require __DIR__ . '/views/header.php';
?>

<div class="auth-card">
    <div class="auth-head">
        <img src="assets/logo.svg" alt="">
        <div>
            <h1>Pieteikšanās</h1>
            <p>Aptaujas</p>
        </div>
    </div>

    <form method="post">
        <?= csrf_field() ?>

        <label class="label" for="login">Lietotājvārds vai e-pasts</label>
        <input class="input <?= $error ? 'invalid' : '' ?>" id="login" name="login"
               placeholder="Ievadiet savu lietotājvārdu" value="<?= e($login) ?>" required>

        <label class="label" for="password">Parole</label>
        <input class="input <?= $error ? 'invalid' : '' ?>" id="password" name="password" type="password"
               placeholder="Ievadiet savu paroli" required>

        <?php if ($error): ?>
            <p class="field-error"><?= e($error) ?></p>
        <?php endif; ?>

        <button class="btn btn-block btn-big">Pieteikties</button>
        <a href="register.php" class="btn btn-gray btn-block btn-big">Nav konta? Reģistrēties</a>
    </form>
</div>

<?php require __DIR__ . '/views/footer.php'; ?>
