<?php
declare(strict_types=1);

/*
 * Tabellino live - ripristino della partita di prova Rovigo v Viadana (23/05/2015)
 * Richiede il login. Dopo l'uso cancella questo file dal server.
 */

require __DIR__ . '/auth.php';
require __DIR__ . '/demo.php';

if (!auth_is_logged()) {
    header('Location: login.php');
    exit;
}

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$db = demo_db();

$message = '';
$is_error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target = (string)($_POST['target'] ?? '');
    if (!auth_csrf_check($_POST['csrf'] ?? null)) {
        $message = 'Modulo scaduto: ricarica la pagina e riprova.';
        $is_error = true;
    } elseif ($target !== 'new' && !preg_match('/^[a-z0-9]{1,40}$/i', $target)) {
        $message = 'Scelta non valida.';
        $is_error = true;
    } else {
        $id = demo_install($target === 'new' ? null : $target);
        $message = $target === 'new'
            ? "Partita di prova creata (id $id)."
            : "Partita $id ripristinata.";
    }
}

$rows = $db->query('SELECT id, title, updated_at, length(data) AS size FROM matches ORDER BY updated_at DESC')->fetchAll();
$csrf = auth_csrf_token();
?><!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Ripristino partita di prova</title>
<style>
:root { --bg: #0a1628; --panel: #12223b; --line: #243c60; --text: #e9eff9; --muted: #8ea3c2; --home: #2e8bff; --ok: #2fbf71; --ko: #ff5a5f; }
* { box-sizing: border-box; }
body { margin: 0; background: var(--bg); color: var(--text); font-family: "Segoe UI", Roboto, system-ui, sans-serif; padding: 16px; }
main { max-width: 640px; margin: 0 auto; }
h1 { font-size: 22px; font-weight: 600; margin: 4px 0 6px; }
p { color: var(--muted); line-height: 1.5; }
.msg { padding: 10px 12px; border-radius: 8px; border-left: 4px solid var(--ok); background: rgba(47, 191, 113, .12); color: var(--text); }
.msg.err { border-color: var(--ko); background: rgba(255, 90, 95, .12); }
label.row { display: flex; gap: 10px; align-items: flex-start; padding: 12px; border: 1px solid var(--line); border-radius: 10px; background: var(--panel); margin-bottom: 8px; cursor: pointer; }
label.row input { margin-top: 4px; width: 18px; height: 18px; }
label.row small { display: block; color: var(--muted); }
button { padding: 12px 16px; border: 0; border-radius: 10px; background: var(--home); color: #fff; font-size: 16px; font-weight: 600; cursor: pointer; }
a { color: var(--home); }
</style>
</head>
<body>
<main>
    <h1>Ripristino partita di prova</h1>
    <p>Riscrive la partita Femi-CZ Rovigo v MPS Viadana (23/05/2015) con i dati del fac-simile FIR: 22-18 (9-9), 30 eventi, cronometro fermo. Il tabellino esce nel formato Serie A Elite 2026.</p>
<?php if ($message !== ''): ?>
    <p class="msg <?= $is_error ? 'err' : '' ?>"><?= h($message) ?>
    <?php if (!$is_error): ?><br>Sul telefono apri <a href="index.php">l’app</a> → Partita → “Apri dal server” per ricaricarla.<?php endif; ?></p>
<?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
<?php foreach ($rows as $i => $r): ?>
        <label class="row">
            <input type="radio" name="target" value="<?= h($r['id']) ?>" <?= $i === 0 && str_contains($r['title'], 'Rovigo') ? 'checked' : '' ?>>
            <span>Sovrascrivi: <?= h($r['title']) ?>
                <small>id <?= h($r['id']) ?> · aggiornata <?= h($r['updated_at']) ?> · <?= (int)$r['size'] ?> byte</small></span>
        </label>
<?php endforeach; ?>
        <label class="row">
            <input type="radio" name="target" value="new" <?= !$rows ? 'checked' : '' ?>>
            <span>Crea una nuova copia<small>non tocca le partite esistenti</small></span>
        </label>
        <button type="submit">Ripristina</button>
    </form>
    <p>Dopo l’uso cancella <code>ripristina_demo.php</code> dal server.</p>
</main>
</body>
</html>
