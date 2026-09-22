<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';

try {
    if (auth_is_logged()) {
        header('Location: index.php');
        exit;
    }
    $setup = !auth_has_users();
} catch (RuntimeException $e) {
    http_response_code(500);
    exit(htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
auth_no_cache_headers();

$error = '';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim((string)($_POST['user'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if (!auth_csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Sessione del modulo scaduta: ricarica la pagina e riprova.';
    } elseif ($setup) {
        // Primo avvio: creazione dell'amministratore, possibile solo finché non esistono utenti
        $error = auth_validate($user, $password, (string)($_POST['password2'] ?? ''))
            ?? auth_create_user($user, $password, true)
            ?? '';
        if ($error === '') {
            auth_login_session($user);
            if (!empty($_POST['demo'])) {
                // La partita di prova non deve impedire l'accesso se qualcosa va storto
                try {
                    require __DIR__ . '/demo.php';
                    demo_install();
                    $_SESSION['demo_installed'] = true;
                } catch (Throwable $e) {
                    $_SESSION['demo_failed'] = true;
                }
            }
            header('Location: index.php');
            exit;
        }
    } elseif (auth_is_locked($ip)) {
        $error = 'Troppi tentativi errati. Riprova tra 15 minuti.';
    } elseif (auth_try_login($user, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Utente o password non corretti.';
    }
}

$csrf = auth_csrf_token();
$h = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?><!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0a1628">
<meta name="robots" content="noindex, nofollow">
<title><?= $setup ? 'Primo avvio' : 'Accesso' ?> - Tabellino live</title>
<style>
:root { --bg: #0a1628; --panel: #12223b; --line: #243c60; --text: #e9eff9; --muted: #8ea3c2; --home: #2e8bff; --ko: #ff5a5f; --ok: #2fbf71; }
* { box-sizing: border-box; }
html, body { margin: 0; min-height: 100%; background: var(--bg); color: var(--text); }
body { font-family: "Barlow", "Segoe UI", Roboto, system-ui, sans-serif; display: grid; place-items: center; min-height: 100vh; padding: 16px; }
form { width: 100%; max-width: 380px; background: var(--panel); border: 1px solid var(--line); border-top: 4px solid var(--home); border-radius: 16px; padding: 22px; }
form.setup { border-top-color: var(--ok); }
h1 { margin: 0 0 4px; font-size: 22px; font-weight: 600; }
p.sub { margin: 0 0 18px; color: var(--muted); font-size: 14px; line-height: 1.45; }
label { display: flex; flex-direction: column; gap: 4px; font-size: 13px; color: var(--muted); margin-bottom: 12px; }
input { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--line); background: #0d1a2f; color: var(--text); font-size: 16px; }
input:focus-visible, button:focus-visible { outline: 2px solid var(--home); outline-offset: 2px; }
button { width: 100%; padding: 13px; border: 0; border-radius: 10px; background: var(--home); color: #fff; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 6px; }
form.setup button { background: var(--ok); color: #04210f; }
.chk { flex-direction: row; align-items: flex-start; gap: 10px; color: var(--text); font-size: 15px; background: #0d1a2f; border: 1px solid var(--line); border-radius: 10px; padding: 12px; cursor: pointer; }
.chk input { width: 20px; height: 20px; flex: 0 0 auto; margin-top: 2px; }
.chk small { display: block; color: var(--muted); font-size: 12px; margin-top: 3px; line-height: 1.4; }
.err { background: rgba(255, 90, 95, .12); border-left: 4px solid var(--ko); padding: 10px 12px; border-radius: 8px; margin-bottom: 14px; font-size: 14px; }
</style>
</head>
<body>
<form method="post" action="login.php" autocomplete="on" class="<?= $setup ? 'setup' : '' ?>">
<?php if ($setup): ?>
    <h1>Benvenuto</h1>
    <p class="sub">È il primo avvio: crea l’utente amministratore. Potrai aggiungere altre persone dopo, dalla pagina “Utenti e password”.</p>
<?php else: ?>
    <h1>Tabellino live</h1>
    <p class="sub">Accedi per registrare le partite</p>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="err"><?= $h($error) ?></div>
<?php endif; ?>
    <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
    <label>Nome utente<input type="text" name="user" value="<?= $h((string)($_POST['user'] ?? '')) ?>" autocomplete="username" autocapitalize="none" required autofocus></label>
    <label>Password<input type="password" name="password" autocomplete="<?= $setup ? 'new-password' : 'current-password' ?>" required<?= $setup ? ' minlength="' . AUTH_MIN_PASSWORD . '"' : '' ?>></label>
<?php if ($setup): ?>
    <label>Ripeti la password<input type="password" name="password2" autocomplete="new-password" required minlength="<?= AUTH_MIN_PASSWORD ?>"></label>
    <label class="chk"><input type="checkbox" name="demo" value="1" checked>
        <span>Carica una partita di prova<small>Rovigo v Viadana 22-18, già compilata: serve per vedere come funziona. Puoi eliminarla quando vuoi.</small></span></label>
    <button type="submit">Crea amministratore ed entra</button>
<?php else: ?>
    <button type="submit">Accedi</button>
<?php endif; ?>
</form>
</body>
</html>
