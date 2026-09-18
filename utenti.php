<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
auth_require_page();

$me = (string)auth_current_user();
$is_admin = auth_is_admin();
$msg = '';
$is_error = false;

function fail(string $text): void
{
    global $msg, $is_error;
    $msg = $text;
    $is_error = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    if (!auth_csrf_check($_POST['csrf'] ?? null)) {
        fail('Modulo scaduto: ricarica la pagina e riprova.');
    } elseif ($action === 'own_password') {
        $err = !auth_verify_password($me, (string)($_POST['current'] ?? ''))
            ? 'La password attuale non è corretta.'
            : auth_validate_password((string)($_POST['password'] ?? ''), (string)($_POST['password2'] ?? ''));
        if ($err) {
            fail($err);
        } else {
            auth_set_password($me, (string)$_POST['password']);
            $msg = 'La tua password è stata cambiata.';
        }
    } elseif (!$is_admin) {
        fail('Solo un amministratore può gestire gli altri utenti.');
    } elseif ($action === 'add') {
        $name = trim((string)($_POST['user'] ?? ''));
        $err = auth_validate($name, (string)($_POST['password'] ?? ''), (string)($_POST['password2'] ?? ''))
            ?? auth_create_user($name, (string)$_POST['password'], !empty($_POST['admin']));
        if ($err) {
            fail($err);
        } else {
            $msg = "Utente $name creato.";
        }
    } elseif ($action === 'reset') {
        $name = (string)($_POST['user'] ?? '');
        $err = auth_validate_password((string)($_POST['password'] ?? ''), (string)($_POST['password2'] ?? ''));
        if ($err) {
            fail($err);
        } elseif (!auth_set_password($name, (string)$_POST['password'])) {
            fail('Utente non trovato.');
        } else {
            $msg = "Password di $name reimpostata.";
        }
    } elseif ($action === 'delete') {
        $name = (string)($_POST['user'] ?? '');
        if ($name === $me) {
            fail('Non puoi eliminare il tuo stesso utente.');
        } elseif ($err = auth_delete_user($name)) {
            fail($err);
        } else {
            $msg = "Utente $name eliminato.";
        }
    }
}

$users = auth_users();
ksort($users, SORT_NATURAL | SORT_FLAG_CASE);
$csrf = auth_csrf_token();
$h = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?><!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0a1628">
<meta name="robots" content="noindex, nofollow">
<title>Utenti e password - Tabellino live</title>
<style>
:root { --bg: #0a1628; --panel: #12223b; --panel2: #182c4a; --line: #243c60; --text: #e9eff9; --muted: #8ea3c2; --home: #2e8bff; --ok: #2fbf71; --ko: #ff5a5f; }
* { box-sizing: border-box; }
body { margin: 0; background: var(--bg); color: var(--text); font-family: "Barlow", "Segoe UI", Roboto, system-ui, sans-serif; padding: 16px 16px 40px; }
main { max-width: 640px; margin: 0 auto; }
header { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px; }
h1 { font-size: 22px; font-weight: 600; margin: 0; }
h2 { font-size: 17px; font-weight: 600; margin: 0 0 12px; }
a.back { color: var(--home); text-decoration: none; font-weight: 600; }
section { background: var(--panel); border: 1px solid var(--line); border-radius: 14px; padding: 16px; margin-top: 14px; }
p.hint { color: var(--muted); font-size: 13px; margin: -6px 0 12px; line-height: 1.45; }
.grid { display: grid; grid-template-columns: 1fr; gap: 10px; }
@media (min-width: 560px) { .grid { grid-template-columns: 1fr 1fr; } }
label.f { display: flex; flex-direction: column; gap: 4px; font-size: 13px; color: var(--muted); }
label.chk { display: flex; align-items: center; gap: 8px; font-size: 15px; margin-top: 10px; }
input[type=text], input[type=password], select { width: 100%; padding: 11px; border-radius: 10px; border: 1px solid var(--line); background: #0d1a2f; color: var(--text); font-size: 16px; }
input[type=checkbox] { width: 20px; height: 20px; }
input:focus-visible, select:focus-visible, button:focus-visible { outline: 2px solid var(--home); outline-offset: 2px; }
button { padding: 11px 16px; border: 0; border-radius: 10px; background: var(--home); color: #fff; font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 12px; }
button.dng { background: transparent; border: 1px solid var(--ko); color: var(--ko); margin: 0; padding: 8px 12px; font-size: 14px; }
.msg { padding: 10px 12px; border-radius: 8px; border-left: 4px solid var(--ok); background: rgba(47, 191, 113, .12); margin-top: 12px; }
.msg.err { border-color: var(--ko); background: rgba(255, 90, 95, .12); }
.user { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--line); }
.user:last-child { border-bottom: 0; }
.user span { flex: 1; }
.user small { display: block; color: var(--muted); font-size: 12px; }
.badge { font-size: 11px; font-weight: 700; color: var(--home); border: 1px solid var(--home); border-radius: 6px; padding: 1px 6px; margin-left: 6px; }
</style>
</head>
<body>
<main>
    <header>
        <h1>Utenti e password</h1>
        <a class="back" href="index.php">← Torna all’app</a>
    </header>

<?php if ($msg !== ''): ?>
    <div class="msg <?= $is_error ? 'err' : '' ?>"><?= $h($msg) ?></div>
<?php endif; ?>

    <section>
        <h2>Cambia la tua password</h2>
        <p class="hint">Sei collegato come <strong><?= $h($me) ?></strong>.</p>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
            <input type="hidden" name="action" value="own_password">
            <label class="f">Password attuale<input type="password" name="current" autocomplete="current-password" required></label>
            <div class="grid" style="margin-top:10px">
                <label class="f">Nuova password<input type="password" name="password" autocomplete="new-password" minlength="<?= AUTH_MIN_PASSWORD ?>" required></label>
                <label class="f">Ripeti la nuova password<input type="password" name="password2" autocomplete="new-password" minlength="<?= AUTH_MIN_PASSWORD ?>" required></label>
            </div>
            <button type="submit">Cambia password</button>
        </form>
    </section>

<?php if ($is_admin): ?>
    <section>
        <h2>Aggiungi un utente</h2>
        <p class="hint">Scegli nome e password, poi comunicali alla persona. Gli amministratori possono gestire gli altri utenti.</p>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
            <input type="hidden" name="action" value="add">
            <label class="f">Nome utente<input type="text" name="user" autocomplete="off" autocapitalize="none" required></label>
            <div class="grid" style="margin-top:10px">
                <label class="f">Password<input type="password" name="password" autocomplete="new-password" minlength="<?= AUTH_MIN_PASSWORD ?>" required></label>
                <label class="f">Ripeti la password<input type="password" name="password2" autocomplete="new-password" minlength="<?= AUTH_MIN_PASSWORD ?>" required></label>
            </div>
            <label class="chk"><input type="checkbox" name="admin" value="1">Amministratore</label>
            <button type="submit">Crea utente</button>
        </form>
    </section>

    <section>
        <h2>Reimposta la password di un utente</h2>
        <p class="hint">Utile quando qualcuno l’ha dimenticata.</p>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
            <input type="hidden" name="action" value="reset">
            <label class="f">Utente
                <select name="user">
<?php foreach ($users as $name => $u): ?>
                    <option value="<?= $h((string)$name) ?>"><?= $h((string)$name) ?></option>
<?php endforeach; ?>
                </select>
            </label>
            <div class="grid" style="margin-top:10px">
                <label class="f">Nuova password<input type="password" name="password" autocomplete="new-password" minlength="<?= AUTH_MIN_PASSWORD ?>" required></label>
                <label class="f">Ripeti la password<input type="password" name="password2" autocomplete="new-password" minlength="<?= AUTH_MIN_PASSWORD ?>" required></label>
            </div>
            <button type="submit">Reimposta</button>
        </form>
    </section>

    <section>
        <h2>Utenti</h2>
<?php foreach ($users as $name => $u): ?>
        <div class="user">
            <span><?= $h((string)$name) ?><?= !empty($u['admin']) ? '<span class="badge">ADMIN</span>' : '' ?>
                <small>creato il <?= $h((string)($u['created'] ?? '')) ?></small></span>
<?php if ((string)$name !== $me): ?>
            <form method="post" class="del">
                <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user" value="<?= $h((string)$name) ?>">
                <button type="submit" class="dng">Elimina</button>
            </form>
<?php endif; ?>
        </div>
<?php endforeach; ?>
    </section>
<?php endif; ?>
</main>
<script>
// Eliminazione in due tocchi, senza finestre di dialogo del browser
document.querySelectorAll('form.del').forEach(f => f.addEventListener('submit', ev => {
    const b = f.querySelector('button');
    if (!b.dataset.armed) {
        ev.preventDefault();
        b.dataset.armed = '1';
        b.textContent = 'Conferma eliminazione';
        setTimeout(() => { delete b.dataset.armed; b.textContent = 'Elimina'; }, 4000);
    }
}));
</script>
</body>
</html>
