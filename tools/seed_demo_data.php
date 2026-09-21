<?php
declare(strict_types=1);

/*
 * Genera da zero i dati della demo pubblica: qualche utente non
 * amministrativo con password fissa (nota, per chi visita la demo) e la
 * partita di prova già installata. Gira SOLO dentro la action che pubblica
 * su InfinityFree (vedi .github/workflows/deploy-demo.yml), su un checkout
 * appena clonato che non ha ancora una cartella data/: non tocca mai
 * un'installazione vera, e non fa niente se lanciato altrove per sbaglio
 * (richiede scrivere in ./data, che qui non esiste ancora).
 */

require __DIR__ . '/../auth.php';
require __DIR__ . '/../demo.php';

const DEMO_PASSWORD = 'TabellinoDemo1!';
const DEMO_USERS = ['demo1', 'demo2', 'demo3', 'demo4', 'demo5'];

foreach (DEMO_USERS as $name) {
    $err = auth_create_user($name, DEMO_PASSWORD, false);
    if ($err !== null) {
        fwrite(STDERR, "Errore creando l'utente demo \"$name\": $err\n");
        exit(1);
    }
}

demo_install();

echo 'Demo pronta: ' . count(DEMO_USERS) . ' utenti (' . implode(', ', DEMO_USERS)
    . '), password unica "' . DEMO_PASSWORD . "\", partita di prova installata.\n";
