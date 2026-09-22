<?php
declare(strict_types=1);

/*
 * Tabellino live - connessione al database e migrazioni (PHP 8.1+, PDO SQLite)
 *
 * Schema unico condiviso da api.php e demo.php: per aggiungere una colonna o
 * una tabella si aggiunge una nuova voce in MIGRATIONS con il numero
 * successivo, senza mai modificare quelle già rilasciate. db_connect() le
 * applica una sola volta, in ordine, tracciando il punto raggiunto con
 * PRAGMA user_version (intero salvato nell'header del file .sqlite stesso).
 */

const DB_PATH = __DIR__ . '/data/tabellini.sqlite';

const MIGRATIONS = [
    1 => "CREATE TABLE IF NOT EXISTS matches (
        id          TEXT PRIMARY KEY,
        title       TEXT NOT NULL,
        match_date  TEXT,
        data        TEXT NOT NULL,
        updated_at  TEXT NOT NULL
    )",
    2 => "ALTER TABLE matches ADD COLUMN owner TEXT NOT NULL DEFAULT ''",
    3 => "ALTER TABLE matches ADD COLUMN created_at TEXT NOT NULL DEFAULT ''",
    4 => "ALTER TABLE matches ADD COLUMN forked_from TEXT NOT NULL DEFAULT ''",
    5 => "UPDATE matches SET created_at = updated_at WHERE created_at = ''",
];

function db_connect(): PDO
{
    $dir = dirname(DB_PATH);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Impossibile creare la cartella data.');
    }
    $db = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $db->exec('PRAGMA journal_mode = WAL');
    db_migrate($db);
    return $db;
}

function db_migrate(PDO $db): void
{
    $version = (int)$db->query('PRAGMA user_version')->fetchColumn();
    // user_version = 0 vuol dire "mai versionato": può essere un db nuovo di
    // zecca oppure uno creato prima che esistesse questo sistema (in quel
    // caso lo schema reale è già più avanti). Si controlla una volta sola.
    if ($version === 0) {
        $version = db_detect_baseline_version($db);
        if ($version > 0) {
            $db->exec('PRAGMA user_version = ' . $version);
        }
    }
    $migrations = MIGRATIONS;
    ksort($migrations);
    foreach ($migrations as $v => $sql) {
        if ($v <= $version) {
            continue;
        }
        $db->exec($sql);
        $db->exec('PRAGMA user_version = ' . $v);
        $version = $v;
    }
}

/** Deduce da dove ripartire guardando lo schema reale (solo per db mai versionati). */
function db_detect_baseline_version(PDO $db): int
{
    $exists = $db->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'matches'")->fetchColumn();
    if (!$exists) {
        return 0;
    }
    $cols = $db->query('PRAGMA table_info(matches)')->fetchAll(PDO::FETCH_COLUMN, 1);
    return in_array('owner', $cols, true) ? 2 : 1;
}

/*
 * Prova un giro di scrittura completo sulla tabella matches (lo stesso che fa
 * api.php?action=save salvando un tabellino) e lo cancella subito dopo.
 * Usata al primo avvio per scoprire subito un hosting senza pdo_sqlite o con
 * data/ non scrivibile, invece di lasciare che l'utente lo scopra da un
 * "Salvataggio sul server non riuscito" a partita già in corso. Torna null se
 * il giro riesce, altrimenti un messaggio pensato per chi non programma.
 */
function db_selftest(): ?string
{
    if (!extension_loaded('pdo_sqlite')) {
        return 'Manca l’estensione PHP "pdo_sqlite": chiedi al tuo hosting di abilitarla (di solito basta togliere il ; davanti a extension=pdo_sqlite in php.ini).';
    }
    try {
        $db = db_connect();
        $id = '__selftest__' . bin2hex(random_bytes(4));
        $db->prepare('INSERT INTO matches (id, title, match_date, data, owner, created_at, updated_at, forked_from)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$id, 'selftest', '', '{}', '', date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), '']);
        $db->prepare('DELETE FROM matches WHERE id = ?')->execute([$id]);
    } catch (Throwable $e) {
        return 'Il database non è scrivibile (' . $e->getMessage() . '): controlla i permessi della cartella "data" oppure lo spazio disco disponibile.';
    }
    return null;
}
