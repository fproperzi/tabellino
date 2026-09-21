<?php
declare(strict_types=1);

/*
 * Tabellino live - API minimale (PHP 8.2+, PDO SQLite)
 *   GET  api.php?action=list
 *   GET  api.php?action=load&id=...
 *   POST api.php?action=save   (body: JSON dello stato)
 *   GET  api.php?action=ping   (mantiene viva la sessione)
 * Tutte le chiamate richiedono il login (auth.php).
 */

require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';
auth_require_api();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const MAX_BODY_BYTES = 2_000_000;

function reply(array $payload, int $status = 200)
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = db_connect();
} catch (Throwable $e) {
    reply(['ok' => false, 'error' => 'Database non disponibile'], 500);
}

$action = $_GET['action'] ?? '';
$me = (string)(auth_current_user() ?? '');

switch ($action) {
    case 'ping':
        reply(['ok' => true]);

    case 'list':
        $rows = $db->query('SELECT id, title, match_date, owner, created_at, updated_at, forked_from FROM matches ORDER BY updated_at DESC LIMIT 200')->fetchAll();
        foreach ($rows as &$r) {
            $r['mine'] = ($r['owner'] === '' || $r['owner'] === $me);
        }
        unset($r);
        reply(['ok' => true, 'items' => $rows]);

    case 'load':
        $id = (string)($_GET['id'] ?? '');
        if (!preg_match('/^[a-z0-9]{1,40}$/i', $id)) {
            reply(['ok' => false, 'error' => 'Id non valido'], 400);
        }
        $st = $db->prepare('SELECT data, owner FROM matches WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) {
            reply(['ok' => false, 'error' => 'Partita non trovata'], 404);
        }
        $mine = ($row['owner'] === '' || $row['owner'] === $me);
        reply(['ok' => true, 'data' => json_decode($row['data'], true), 'owner' => $row['owner'], 'mine' => $mine]);

    case 'save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            reply(['ok' => false, 'error' => 'Metodo non consentito'], 405);
        }
        $raw = file_get_contents('php://input', false, null, 0, MAX_BODY_BYTES + 1);
        if ($raw === false || strlen($raw) > MAX_BODY_BYTES) {
            reply(['ok' => false, 'error' => 'Dati troppo grandi'], 413);
        }
        $state = json_decode($raw, true);
        if (!is_array($state) || !isset($state['id'], $state['teams']['h']['name'], $state['teams']['a']['name'])) {
            reply(['ok' => false, 'error' => 'Formato non valido'], 400);
        }
        $id = (string)$state['id'];
        if (!preg_match('/^[a-z0-9]{1,40}$/i', $id)) {
            reply(['ok' => false, 'error' => 'Id non valido'], 400);
        }

        // Solo chi ha creato il tabellino può sovrascriverlo: se l'id è di un
        // altro utente si salva come nuova copia, l'originale resta intatto.
        $sourceId = $id;
        $st = $db->prepare('SELECT owner FROM matches WHERE id = ?');
        $st->execute([$id]);
        $existing = $st->fetch();
        $forked = false;
        if ($existing && $existing['owner'] !== '' && $existing['owner'] !== $me) {
            $id = 'm' . bin2hex(random_bytes(6));
            $forked = true;
        }

        $title = trim($state['teams']['h']['name'] . ' v ' . $state['teams']['a']['name']);
        $date = (string)($state['info']['data'] ?? '');
        $state['id'] = $id;
        // created_at e forked_from non compaiono nella DO UPDATE: si scrivono
        // solo quando la riga nasce, un aggiornamento successivo non li tocca.
        $st = $db->prepare('INSERT INTO matches (id, title, match_date, data, owner, created_at, updated_at, forked_from)
            VALUES (:id, :title, :d, :data, :owner, :c, :u, :forked_from)
            ON CONFLICT(id) DO UPDATE SET title = excluded.title, match_date = excluded.match_date,
                data = excluded.data, updated_at = excluded.updated_at');
        $st->execute([
            ':id' => $id,
            ':title' => $title . ($date !== '' ? " ($date)" : ''),
            ':d' => $date,
            ':data' => json_encode($state, JSON_UNESCAPED_UNICODE),
            ':owner' => $me,
            ':c' => date('Y-m-d H:i:s'),
            ':u' => date('Y-m-d H:i:s'),
            ':forked_from' => $forked ? $sourceId : '',
        ]);
        reply(['ok' => true, 'id' => $id, 'forked' => $forked]);

    default:
        reply(['ok' => false, 'error' => 'Azione sconosciuta'], 400);
}
