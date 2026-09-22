<?php
declare(strict_types=1);

/*
 * Tabellino live - autenticazione a sessione (PHP 8.1+)
 *
 * Gli utenti si creano dal browser: al primo avvio login.php chiede di creare
 * l'amministratore, poi si gestiscono da utenti.php. Le password sono salvate
 * solo come hash in data/users.json.
 */

const AUTH_LIFETIME     = 43200;  // 12 ore di inattività prima di dover rifare il login
const AUTH_MAX_FAILS    = 5;      // tentativi errati consentiti...
const AUTH_LOCK_SECONDS = 900;    // ...in questa finestra (15 minuti) per IP
const AUTH_MIN_PASSWORD = 8;
const AUTH_DATA_DIR     = __DIR__ . '/data';
const AUTH_USERS_FILE   = AUTH_DATA_DIR . '/users.json';

/*
 * Compatibilità con le installazioni v1.02: se qui è presente un hash reale e
 * users.json non esiste ancora, l'utente viene importato come amministratore.
 * Per le nuove installazioni lasciare l'array vuoto.
 */
const AUTH_USERS = [];

/* ---------- Cartella dati ---------- */

function auth_ensure_data_dir(): void
{
    if (!is_dir(AUTH_DATA_DIR) && !mkdir(AUTH_DATA_DIR, 0775, true) && !is_dir(AUTH_DATA_DIR)) {
        throw new RuntimeException('Impossibile creare la cartella data: controlla i permessi di scrittura.');
    }
    // Protezione automatica: nessun file di data/ deve essere scaricabile
    $ht = AUTH_DATA_DIR . '/.htaccess';
    if (!is_file($ht)) {
        @file_put_contents($ht, "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n");
    }
}

/*
 * auth_ensure_data_dir() sa creare la cartella, ma non prova mai a scriverci
 * dentro: su qualche hosting la mkdir riesce e la scrittura no (proprietario
 * diverso, quota esaurita). Usata al primo avvio, prima di far creare
 * l'amministratore, così l'errore compare subito e con un messaggio utile
 * invece che al primo salvataggio di una partita.
 */
function auth_data_dir_selftest(): ?string
{
    try {
        auth_ensure_data_dir();
    } catch (RuntimeException $e) {
        return $e->getMessage();
    }
    $f = AUTH_DATA_DIR . '/.selftest-' . bin2hex(random_bytes(4));
    if (@file_put_contents($f, 'ok') === false) {
        return 'La cartella "data" esiste ma non è scrivibile: dal pannello del provider o da FileZilla (tasto destro → Permessi file) dai alla cartella "tabellino" i permessi 755 oppure 775.';
    }
    @unlink($f);
    return null;
}

/* ---------- Archivio utenti ---------- */

function auth_users(): array
{
    auth_ensure_data_dir();
    if (!is_file(AUTH_USERS_FILE)) {
        $imported = [];
        foreach (AUTH_USERS as $name => $hash) {
            if (is_string($hash) && strpos($hash, '$2y$') === 0 && strpos($hash, 'SOSTITUISCI') === false) {
                $imported[$name] = ['hash' => $hash, 'admin' => true, 'created' => date('Y-m-d H:i:s')];
            }
        }
        if ($imported) {
            auth_save_users($imported);
        }
        return $imported;
    }
    $data = json_decode((string)file_get_contents(AUTH_USERS_FILE), true);
    return is_array($data['users'] ?? null) ? $data['users'] : [];
}

function auth_save_users(array $users): void
{
    auth_ensure_data_dir();
    $tmp = AUTH_USERS_FILE . '.' . bin2hex(random_bytes(4)) . '.tmp';
    $json = json_encode(['users' => $users], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($tmp, $json, LOCK_EX) === false || !rename($tmp, AUTH_USERS_FILE)) {
        @unlink($tmp);
        throw new RuntimeException('Impossibile salvare gli utenti: controlla i permessi della cartella data.');
    }
}

function auth_has_users(): bool
{
    return count(auth_users()) > 0;
}

/** Restituisce un messaggio d'errore oppure null se i dati sono validi */
function auth_validate(string $name, string $password, string $confirm): ?string
{
    if (!preg_match('/^[a-z0-9._-]{3,32}$/i', $name)) {
        return 'Il nome utente deve avere da 3 a 32 caratteri: lettere, numeri, punto, trattino o underscore.';
    }
    return auth_validate_password($password, $confirm);
}

function auth_validate_password(string $password, string $confirm): ?string
{
    // Conta i caratteri UTF-8 senza richiedere l'estensione mbstring
    if (preg_match_all('/./us', $password) < AUTH_MIN_PASSWORD) {
        return 'La password deve avere almeno ' . AUTH_MIN_PASSWORD . ' caratteri.';
    }
    if ($password !== $confirm) {
        return 'Le due password non coincidono.';
    }
    return null;
}

function auth_create_user(string $name, string $password, bool $admin): ?string
{
    $users = auth_users();
    $key = strtolower($name);
    foreach (array_keys($users) as $existing) {
        if (strtolower((string)$existing) === $key) {
            return 'Esiste già un utente con questo nome.';
        }
    }
    $users[$name] = [
        'hash'    => password_hash($password, PASSWORD_DEFAULT),
        'admin'   => $admin,
        'created' => date('Y-m-d H:i:s'),
    ];
    auth_save_users($users);
    return null;
}

function auth_set_password(string $name, string $password): bool
{
    $users = auth_users();
    if (!isset($users[$name])) {
        return false;
    }
    $users[$name]['hash'] = password_hash($password, PASSWORD_DEFAULT);
    auth_save_users($users);
    return true;
}

function auth_delete_user(string $name): ?string
{
    $users = auth_users();
    if (!isset($users[$name])) {
        return 'Utente non trovato.';
    }
    if ($users[$name]['admin'] && count(array_filter($users, fn($u) => !empty($u['admin']))) === 1) {
        return 'Non puoi eliminare l’ultimo amministratore.';
    }
    unset($users[$name]);
    auth_save_users($users);
    return null;
}

/* ---------- Sessione ---------- */

function auth_cookie_path(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    return rtrim($dir, '/') . '/';
}

function auth_cookie_options(int $expires): array
{
    return [
        'expires'  => $expires,
        'path'     => auth_cookie_path(),
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function auth_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    auth_ensure_data_dir();
    // Sessioni in una cartella dedicata: altre app sullo stesso PHP
    // non possono cancellarle con un gc_maxlifetime più corto.
    $session_dir = AUTH_DATA_DIR . '/sessions';
    if (!is_dir($session_dir)) {
        mkdir($session_dir, 0770, true);
    }
    ini_set('session.save_path', $session_dir);
    ini_set('session.gc_maxlifetime', (string)AUTH_LIFETIME);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('TABELLINOSID');
    $opts = auth_cookie_options(0);
    unset($opts['expires']);
    session_set_cookie_params(['lifetime' => AUTH_LIFETIME] + $opts);
    session_start();
}

function auth_current_user(): ?string
{
    auth_start();
    return isset($_SESSION['user']) ? (string)$_SESSION['user'] : null;
}

function auth_is_admin(): bool
{
    $user = auth_current_user();
    $users = auth_users();
    return $user !== null && !empty($users[$user]['admin']);
}

function auth_is_logged(): bool
{
    auth_start();
    $users = auth_users();
    if (empty($_SESSION['user']) || !isset($users[$_SESSION['user']])) {
        return false;
    }
    if (time() - (int)($_SESSION['last'] ?? 0) > AUTH_LIFETIME) {
        auth_logout();
        return false;
    }
    $_SESSION['last'] = time();
    // Rinnova la scadenza del cookie a ogni richiesta autenticata
    setcookie(session_name(), session_id(), auth_cookie_options(time() + AUTH_LIFETIME));
    return true;
}

/*
 * Senza queste intestazioni il browser può tenersi in cache una pagina
 * vecchia (successo davvero: "Importa JSON" non c'era ancora nella copia in
 * cache di qualcuno). Va chiamata da ogni pagina con un form/CSRF o dati che
 * cambiano da una richiesta all'altra: non solo quelle dietro login, anche
 * login.php stessa.
 */
function auth_no_cache_headers(): void
{
    header('Cache-Control: no-store, must-revalidate');
    header('Pragma: no-cache');
}

function auth_require_page(): void
{
    if (!auth_is_logged()) {
        header('Location: login.php');
        exit;
    }
    auth_no_cache_headers();
}

function auth_require_api(): void
{
    if (!auth_is_logged()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Accesso richiesto'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // Libera il lock della sessione: richieste parallele non si bloccano
    session_write_close();
}

function auth_csrf_token(): string
{
    auth_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function auth_csrf_check(?string $token): bool
{
    auth_start();
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/* ---------- Limite tentativi per IP ---------- */

function auth_with_fails(callable $fn)
{
    auth_ensure_data_dir();
    $fh = fopen(AUTH_DATA_DIR . '/login_fails.json', 'c+');
    flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);
    $data = json_decode($raw ?: '{}', true) ?: [];
    $now = time();
    foreach ($data as $ip => $list) {
        $data[$ip] = array_values(array_filter($list, fn($t) => $now - $t < AUTH_LOCK_SECONDS));
        if (!$data[$ip]) {
            unset($data[$ip]);
        }
    }
    [$result, $data] = $fn($data);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($data));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $result;
}

function auth_is_locked(string $ip): bool
{
    return auth_with_fails(fn($d) => [count($d[$ip] ?? []) >= AUTH_MAX_FAILS, $d]);
}

function auth_verify_password(string $user, string $password): bool
{
    $users = auth_users();
    // Verifica sempre un hash, anche per utenti inesistenti (tempi uniformi)
    $hash = $users[$user]['hash'] ?? '$2y$10$usesomesillystringforsaltuJ1Ff0y5yRbGTc5RyC7SvjG1QDWu';
    return password_verify($password, $hash) && isset($users[$user]);
}

function auth_login_session(string $user): void
{
    auth_start();
    session_regenerate_id(true);
    $_SESSION['user'] = $user;
    $_SESSION['last'] = time();
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function auth_try_login(string $user, string $password): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (auth_is_locked($ip)) {
        return false;
    }
    if (!auth_verify_password($user, $password)) {
        auth_with_fails(function ($d) use ($ip) {
            $d[$ip][] = time();
            return [null, $d];
        });
        usleep(700000);
        return false;
    }
    auth_with_fails(function ($d) use ($ip) {
        unset($d[$ip]);
        return [null, $d];
    });
    auth_login_session($user);
    return true;
}

function auth_logout(): void
{
    auth_start();
    $_SESSION = [];
    setcookie(session_name(), '', auth_cookie_options(time() - 3600));
    session_destroy();
}
