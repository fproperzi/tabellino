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
auth_require_api();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Consigliato: cartella dati fuori dalla document root
const DB_PATH = __DIR__ . '/data/tabellini.sqlite';
const MAX_BODY_BYTES = 2_000_000;

function reply(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $dir = dirname(DB_PATH);
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        reply(['ok' => false, 'error' => 'Impossibile creare la cartella dati'], 500);
    }
    $db = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $db->exec('PRAGMA journal_mode = WAL');
    $db->exec('CREATE TABLE IF NOT EXISTS matches (
        id          TEXT PRIMARY KEY,
        title       TEXT NOT NULL,
        match_date  TEXT,
        data        TEXT NOT NULL,
        updated_at  TEXT NOT NULL
    )');
} catch (Throwable $e) {
    reply(['ok' => false, 'error' => 'Database non disponibile'], 500);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'ping':
        reply(['ok' => true]);

    case 'list':
        $rows = $db->query('SELECT id, title, match_date, updated_at FROM matches ORDER BY updated_at DESC LIMIT 200')->fetchAll();
        reply(['ok' => true, 'items' => $rows]);

    case 'load':
        $id = (string)($_GET['id'] ?? '');
        if (!preg_match('/^[a-z0-9]{1,40}$/i', $id)) {
            reply(['ok' => false, 'error' => 'Id non valido'], 400);
        }
        $st = $db->prepare('SELECT data FROM matches WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) {
            reply(['ok' => false, 'error' => 'Partita non trovata'], 404);
        }
        reply(['ok' => true, 'data' => json_decode($row['data'], true)]);

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
        $title = trim($state['teams']['h']['name'] . ' v ' . $state['teams']['a']['name']);
        $date = (string)($state['info']['data'] ?? '');
        $st = $db->prepare('INSERT INTO matches (id, title, match_date, data, updated_at)
            VALUES (:id, :title, :d, :data, :u)
            ON CONFLICT(id) DO UPDATE SET title = excluded.title, match_date = excluded.match_date,
                data = excluded.data, updated_at = excluded.updated_at');
        $st->execute([
            ':id' => $id,
            ':title' => $title . ($date !== '' ? " ($date)" : ''),
            ':d' => $date,
            ':data' => json_encode($state, JSON_UNESCAPED_UNICODE),
            ':u' => date('Y-m-d H:i:s'),
        ]);
        reply(['ok' => true, 'id' => $id]);

    default:
        reply(['ok' => false, 'error' => 'Azione sconosciuta'], 400);
}
