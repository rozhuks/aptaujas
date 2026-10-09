<?php
$flashMsg = get_flash();
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Aptaujas') ?></title>
    <link rel="icon" href="assets/logo.svg">
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php if (empty($authPage)): ?>
<header class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="brand"><img src="assets/logo.svg" alt=""> Aptaujas</a>
        <div class="topbar-right">
            <a href="edit.php" class="btn btn-sm"><?= user_id() ? '+ Izveidot jaunu aptauju' : '+ Izveidot savu aptauju' ?></a>
            <?php if (user_id()): ?>
                <span class="avatar" title="<?= e($_SESSION['username']) ?>">
                    <svg viewBox="0 0 24 24"><circle cx="11.7" cy="7" r="4"/><path d="M3.5 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
                </span>
            <?php endif; ?>
        </div>
    </div>
    <?php if (user_id()): ?>
        <a href="login.php?logout=1" class="btn btn-sm btn-dark logout">Izrakstīties</a>
    <?php endif; ?>
</header>
<?php endif; ?>

<main class="<?= empty($authPage) ? 'container' : 'auth-page' ?>">

<?php if ($flashMsg): ?>
    <div class="alert <?= $flashMsg['type'] === 'error' ? 'alert-error' : '' ?>"><?= e($flashMsg['text']) ?></div>
<?php endif; ?>
