<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
auth_require_page();
$csrf = auth_csrf_token();

// Messaggio mostrato una sola volta, subito dopo il primo avvio
$demo_notice = '';
if (!empty($_SESSION['demo_installed'])) {
    $demo_notice = 'Partita di prova caricata: aprila da Partita → “Apri dal server”.';
    unset($_SESSION['demo_installed']);
} elseif (!empty($_SESSION['demo_failed'])) {
    $demo_notice = 'Non è stato possibile caricare la partita di prova. Puoi riprovare da ripristina_demo.php.';
    unset($_SESSION['demo_failed']);
}
?><!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0a1628">
<meta name="robots" content="noindex, nofollow">
<title>Tabellino live v1.14</title>
<style>
:root {
    --bg: #0a1628;
    --panel: #12223b;
    --panel2: #182c4a;
    --line: #243c60;
    --text: #e9eff9;
    --muted: #8ea3c2;
    --home: #2e8bff;
    --away: #ffae1f;
    --ok: #2fbf71;
    --ko: #ff5a5f;
    --yellow: #ffd400;
    --red: #e5383b;
    --r: 12px;
}
* { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
html, body { margin: 0; background: var(--bg); color: var(--text); }
body {
    font-family: "Barlow", "Segoe UI", Roboto, system-ui, sans-serif;
    font-size: 16px;
    padding-bottom: calc(72px + env(safe-area-inset-bottom));
}
button, input, select, textarea { font: inherit; color: inherit; }
button { cursor: pointer; border: 0; background: none; transition: transform .08s ease, filter .08s ease; }
button:focus-visible, input:focus-visible, textarea:focus-visible { outline: 2px solid var(--home); outline-offset: 2px; }
/* -webkit-tap-highlight-color è disattivato sopra: senza questo, su telefono
   toccare un bottone non dà nessun segnale, sembra che il tocco non sia
   arrivato. .act ha già il suo :active più marcato (riga sotto), qui
   copriamo tutti gli altri bottoni (.btn, tab, switch, dialoghi...). */
button:active { transform: scale(.96); filter: brightness(.92); }
.num { font-family: "Barlow Condensed", "Arial Narrow", sans-serif; font-variant-numeric: tabular-nums; }

/* Scoreboard */
.board {
    position: sticky; top: 0; z-index: 20;
    display: grid; grid-template-columns: 1fr auto 1fr; align-items: stretch;
    background: #06101f; border-bottom: 1px solid var(--line);
    padding-top: env(safe-area-inset-top);
}
.side { padding: 8px 12px; display: flex; flex-direction: column; justify-content: center; min-width: 0; }
.side.h { border-left: 5px solid var(--home); order: 1; }
.side.a { border-right: 5px solid var(--away); text-align: right; order: 3; }
.clock { order: 2; }
/* Lati invertiti: solo visualizzazione, casa e ospiti restano tali nel tabellino */
body.swapped .side.h { order: 3; border-left: 0; border-right: 5px solid var(--home); text-align: right; }
body.swapped .side.a { order: 1; border-right: 0; border-left: 5px solid var(--away); text-align: left; }
.side .tn { font-size: 13px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.side .sc { font-size: 44px; line-height: 1; font-weight: 700; }
.clock { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 6px 4px; gap: 2px; }
.clock .min { font-size: 30px; font-weight: 700; line-height: 1; padding: 0 6px; border-radius: 6px; }
.clock button.min { color: var(--text); }
.clock .row { display: flex; gap: 4px; align-items: center; }
.clock .half { font-size: 12px; color: var(--muted); }
.cbtn { width: 34px; height: 30px; border-radius: 8px; background: var(--panel2); font-weight: 700; }
.cbtn.run { background: var(--ok); color: #04210f; }

/* Views */
.view { display: none; padding: 12px; max-width: 900px; margin: 0 auto; }
.view.on { display: block; }
h2 { font-size: 18px; margin: 18px 0 8px; font-weight: 600; }
h2:first-child { margin-top: 4px; }

/* Team switch */
.switch { display: grid; grid-template-columns: 1fr auto 1fr; gap: 8px; margin-bottom: 12px; }
.switch button.swap {
    padding: 0 10px; min-width: 48px; font-size: 22px; font-weight: 700; color: var(--muted);
    background: transparent; border: 2px dashed var(--line);
}
.switch button {
    padding: 14px 8px; border-radius: var(--r); background: var(--panel);
    border: 2px solid var(--line); font-weight: 600; font-size: 17px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.switch button.on.h { background: var(--home); border-color: var(--home); color: #fff; }
.switch button.on.a { background: var(--away); border-color: var(--away); color: #1d1300; }

/* Action grid */
.actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
@media (min-width: 640px) { .actions { grid-template-columns: repeat(4, 1fr); } }
.act {
    min-height: 70px; border-radius: var(--r); background: var(--panel);
    border: 1px solid var(--line); padding: 8px; text-align: left;
    display: flex; flex-direction: column; justify-content: space-between;
}
.act b { font-size: 17px; font-weight: 600; }
.act small { color: var(--muted); font-size: 12px; }
.act.big { background: var(--panel2); border-color: #35527d; }
.act .dot { display: inline-block; width: 12px; height: 16px; border-radius: 2px; vertical-align: -2px; margin-right: 6px; }
.act:active { transform: scale(.98); }

/* Chips for open situations */
.pending { display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }
.pend {
    display: flex; align-items: center; gap: 8px; padding: 8px 10px;
    border-radius: 10px; background: var(--panel); border-left: 4px solid var(--muted); font-size: 14px;
}
.pend.h { border-left-color: var(--home); }
.pend.a { border-left-color: var(--away); }
.pend span { flex: 1; }
.pend button { padding: 8px 12px; border-radius: 8px; background: var(--panel2); font-weight: 600; }

/* Forms */
.grid2 { display: grid; grid-template-columns: 1fr; gap: 8px; }
@media (min-width: 640px) { .grid2 { grid-template-columns: 1fr 1fr; } }
label.f { display: flex; flex-direction: column; gap: 4px; font-size: 13px; color: var(--muted); }
input[type=text], input[type=date], input[type=number], textarea, select {
    width: 100%; padding: 10px; border-radius: 10px; border: 1px solid var(--line);
    background: #0d1a2f; color: var(--text); font-size: 16px;
}
textarea { min-height: 90px; resize: vertical; }
.roster { display: grid; grid-template-columns: 1fr; gap: 4px; }
@media (min-width: 700px) { .roster { grid-template-columns: 1fr 1fr; column-gap: 16px; } }
.pr { display: grid; grid-template-columns: 38px 1fr 46px; gap: 6px; align-items: center; }
.pr .n { text-align: center; font-weight: 700; font-size: 20px; }
.pr.bench .n { color: var(--muted); }
.pr label { font-size: 11px; color: var(--muted); display: flex; flex-direction: column; align-items: center; }
.btnrow { display: flex; flex-wrap: wrap; gap: 8px; margin: 10px 0; }
.btn { padding: 11px 14px; border-radius: 10px; background: var(--panel2); border: 1px solid var(--line); font-weight: 600; }
.btn.pri { background: var(--home); border-color: var(--home); color: #fff; }
.btn.dng { background: transparent; border-color: var(--ko); color: var(--ko); }
.hint { color: var(--muted); font-size: 13px; margin: 4px 0 8px; }
.teamtabs { display: flex; gap: 8px; margin: 8px 0; }
.teamtabs button { flex: 1; padding: 10px; border-radius: 10px; background: var(--panel); border: 2px solid var(--line); font-weight: 600; }
.teamtabs button.on.h { border-color: var(--home); }
.teamtabs button.on.a { border-color: var(--away); }

/* Event list */
.ev {
    display: grid; grid-template-columns: 62px 1fr auto; gap: 8px; align-items: center;
    padding: 10px; border-bottom: 1px solid var(--line); border-left: 4px solid transparent;
}
.ev.h { border-left-color: var(--home); }
.ev.a { border-left-color: var(--away); }
.ev .m { font-size: 22px; font-weight: 700; text-align: right; }
.ev .d { font-size: 15px; }
.ev .d small { display: block; color: var(--muted); font-size: 12px; }
.ev .tools { display: flex; gap: 4px; }
.ev .tools button { padding: 8px 10px; border-radius: 8px; background: var(--panel2); font-size: 13px; }
.ko { color: var(--ko); }
.okc { color: var(--ok); }
.empty { color: var(--muted); padding: 24px 8px; text-align: center; }

/* Output */
.out {
    word-wrap: break-word; background: var(--panel);
    border: 1px solid var(--line); border-radius: var(--r); padding: 14px;
    font-family: Calibri, Carlito, "Segoe UI", sans-serif; font-size: 15px; line-height: 1.55;
}
.out p { margin: 0; }
.hbadge { font-size: 11px; color: var(--muted); font-weight: 600; margin-left: 2px; }
.minbox .hseg { display: flex; flex-direction: column; gap: 2px; margin-left: 4px; }
.minbox .hseg button { width: 38px; height: 19px; font-size: 11px; border-radius: 6px; }
.minbox .hseg button.on { background: var(--home); color: #fff; }

/* Bottom nav */
nav.tabs {
    position: fixed; bottom: 0; left: 0; right: 0; z-index: 30;
    display: grid; grid-template-columns: repeat(4, 1fr);
    background: #06101f; border-top: 1px solid var(--line);
    padding-bottom: env(safe-area-inset-bottom);
}
nav.tabs button { padding: 10px 4px 12px; color: var(--muted); font-size: 13px; display: flex; flex-direction: column; align-items: center; gap: 3px; }
nav.tabs button.on { color: var(--home); }
nav.tabs svg { width: 22px; height: 22px; }

/* Sheet */
.overlay { position: fixed; inset: 0; background: rgba(2, 8, 18, .72); z-index: 50; display: none; align-items: flex-end; justify-content: center; }
.overlay.on { display: flex; }
.sheet {
    width: 100%; max-width: 720px; max-height: 92vh; overflow: auto;
    background: var(--panel); border-radius: 18px 18px 0 0; border-top: 4px solid var(--home);
    padding: 14px 14px calc(14px + env(safe-area-inset-bottom));
}
.sheet.a { border-top-color: var(--away); }
.sheet .top { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.sheet .top h3 { flex: 1; margin: 0; font-size: 19px; }
.sheet .top h3 small { display: block; font-size: 13px; color: var(--muted); font-weight: 400; }
.minbox { display: flex; align-items: center; gap: 4px; }
.minbox button { width: 38px; height: 40px; border-radius: 8px; background: var(--panel2); font-size: 20px; font-weight: 700; }
.minbox input { width: 64px; text-align: center; font-size: 22px; font-weight: 700; padding: 6px; }
.okseg { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 10px; }
.okseg button { padding: 12px; border-radius: 10px; background: var(--panel2); font-weight: 700; border: 2px solid transparent; }
.okseg button.on.y { border-color: var(--ok); color: var(--ok); }
.okseg button.on.n { border-color: var(--ko); color: var(--ko); }
.pgrid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; }
@media (min-width: 520px) { .pgrid { grid-template-columns: repeat(6, 1fr); } }
.pb {
    min-height: 60px; border-radius: 10px; background: var(--panel2); border: 2px solid transparent;
    display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4px 2px;
}
.pb b { font-size: 24px; line-height: 1; }
.pb small { font-size: 11px; color: var(--muted); max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pb.dim { opacity: .38; }
.pb.hl { border-color: var(--ok); }
.sep { grid-column: 1 / -1; font-size: 12px; color: var(--muted); margin-top: 6px; }

/* Toast / dialog */
.toast {
    position: fixed; left: 50%; bottom: calc(86px + env(safe-area-inset-bottom)); transform: translateX(-50%);
    background: #eaf2ff; color: #0a1628; padding: 10px 16px; border-radius: 10px; font-weight: 600;
    z-index: 80; display: none; max-width: 90vw;
}
.toast.on { display: block; }
.dialog { max-width: 420px; border-radius: 16px; margin: auto; border-top: 0; }
.overlay.center { align-items: center; padding: 16px; }

/* FAB menu utente (Utenti e password / Esci) */
/*
 * pointer-events:none sul contenitore: è position:fixed più grande del solo
 * cerchio "+" (include lo spazio del menu sopra, anche a menu chiuso quando
 * le righe sono invisibili ma restano nel layout), e senza questo intercetta
 * il tap diretto a qualunque bottone sotto di lui nella pagina — bug reale,
 * trovato su mobile: "Importa JSON"/"Nuova partita" non rispondevano perché
 * il tocco arrivava qui invece che al bottone, scrollando la scheda Partita
 * fino in fondo. Riattivato subito dopo, in modo mirato, solo su .fabMain
 * (il cerchio "+", sempre cliccabile) e su .fabRow quando il menu è aperto.
 */
.fab {
    position: fixed; right: 16px; bottom: calc(86px + env(safe-area-inset-bottom)); z-index: 40;
    display: flex; flex-direction: column; align-items: flex-end; gap: 12px;
    pointer-events: none;
}
.fabMenu { display: flex; flex-direction: column; align-items: flex-end; gap: 12px; }
.fabRow {
    display: flex; align-items: center; gap: 10px;
    opacity: 0; transform: translateY(8px) scale(.9); pointer-events: none;
    transition: opacity .15s, transform .15s;
}
.fab.open .fabRow { opacity: 1; transform: translateY(0) scale(1); pointer-events: auto; }
.fabLabel {
    background: var(--panel); padding: 6px 12px; border-radius: 8px; font-size: 14px;
    white-space: nowrap; box-shadow: 0 2px 10px rgba(0, 0, 0, .35);
}
.fabItem, .fabMain {
    display: flex; align-items: center; justify-content: center; border-radius: 50%;
    background: var(--panel2); color: var(--text); text-decoration: none; flex-shrink: 0;
}
.fabItem { width: 46px; height: 46px; box-shadow: 0 2px 10px rgba(0, 0, 0, .35); }
.fabItem svg { width: 20px; height: 20px; }
.fabItem.dng { color: var(--ko); }
.fabMain { width: 56px; height: 56px; background: var(--home); color: #fff; box-shadow: 0 4px 14px rgba(0, 0, 0, .45); pointer-events: auto; }
.fabMain svg { width: 24px; height: 24px; transition: transform .2s; }
.fab.open .fabMain svg { transform: rotate(135deg); }

@media (prefers-reduced-motion: reduce) { button:active { transform: none; } }
</style>
</head>
<body>

<header class="board">
    <div class="side h"><div class="tn" id="bH"></div><div class="sc num" id="sH">0</div></div>
    <div class="clock">
        <div class="half" id="bHalf">1° tempo</div>
        <button class="min num" id="bMin" data-do="setClock" aria-label="Imposta minuto e tempo">1’</button>
        <div class="row">
            <button class="cbtn" data-do="minDown" aria-label="Minuto indietro">−</button>
            <button class="cbtn" id="bRun" data-do="toggleClock" aria-label="Avvia o ferma cronometro">▶</button>
            <button class="cbtn" data-do="minUp" aria-label="Minuto avanti">+</button>
        </div>
    </div>
    <div class="side a"><div class="tn" id="bA"></div><div class="sc num" id="sA">0</div></div>
</header>

<!-- LIVE -->
<main class="view on" id="v-live">
    <div class="switch" id="teamSwitch"></div>
    <div class="pending" id="pending"></div>
    <div class="actions">
        <button class="act big" data-act="try"><b>Meta</b><small>5 punti, poi trasformazione</small></button>
        <button class="act" data-act="conv" data-ok="1"><b class="okc">Trasf. centrata</b><small>2 punti</small></button>
        <button class="act" data-act="conv" data-ok="0"><b class="ko">Trasf. sbagliata</b><small>conta nei calci</small></button>
        <button class="act big" data-act="pen" data-ok="1"><b class="okc">Calcio piazzato</b><small>3 punti</small></button>
        <button class="act" data-act="pen" data-ok="0"><b class="ko">C.p. sbagliato</b><small>conta nei calci</small></button>
        <button class="act" data-act="drop" data-ok="1"><b>Drop</b><small>3 punti, non conta nei calci</small></button>
        <button class="act" data-act="ptry"><b>Meta tecnica</b><small>7 punti</small></button>
        <button class="act" data-act="yc"><b><span class="dot" style="background:var(--yellow)"></span>Giallo</b><small>10 minuti</small></button>
        <button class="act" data-act="rc"><b><span class="dot" style="background:var(--red)"></span>Rosso</b><small>espulsione</small></button>
        <button class="act big" data-act="sub"><b>Sostituzione</b><small>esce → entra</small></button>
        <button class="act" data-act="tsub"><b>Sost. temporanea</b><small>sangue, HIA, giallo pilone</small></button>
        <button class="act" data-do="setHalf2"><b>Fine 1° tempo</b><small>il 2° riparte da 1’</small></button>
    </div>
</main>

<!-- EVENTI -->
<main class="view" id="v-events">
    <h2>Eventi registrati</h2>
    <div id="evList"></div>
</main>

<!-- PARTITA -->
<main class="view" id="v-setup">
    <h2>Partita</h2>
    <div class="grid2">
        <label class="f">Luogo e stadio<input type="text" data-info="luogo" placeholder="Mogliano Veneto – Stadio “Maurizio Quaggia”"></label>
        <label class="f">Data<input type="date" data-info="data"></label>
        <label class="f">Campionato<input type="text" data-info="campionato" placeholder="SOLADRIA SERIE A ELITE"></label>
        <label class="f">Giornata<input type="text" data-info="giornata" placeholder="XVII giornata"></label>
        <label class="f">Spettatori (circa)<input type="number" inputmode="numeric" data-info="spettatori"></label>
    </div>
    <h2>Ufficiali di gara</h2>
    <div class="grid2">
        <label class="f">Arbitro<input type="text" data-info="arbitro"></label>
        <label class="f">TMO<input type="text" data-info="tmo"></label>
        <label class="f">Assistente 1 (AA1)<input type="text" data-info="aa1"></label>
        <label class="f">Assistente 2 (AA2)<input type="text" data-info="aa2"></label>
        <label class="f">Quarto uomo<input type="text" data-info="quarto"></label>
    </div>
    <h2>Premi e classifica</h2>
    <div class="grid2">
        <label class="f">Dicitura premio<input type="text" data-info="potmLabel" placeholder="Simecom Player of the Match"></label>
        <label class="f">Player of the Match<input type="text" data-info="potm" placeholder="Cognome (Squadra)"></label>
        <label class="f">Punti in classifica casa / ospiti
            <span style="display:flex;gap:6px"><input type="number" inputmode="numeric" data-info="puntiH"><input type="number" inputmode="numeric" data-info="puntiA"></span>
        </label>
    </div>
    <label class="f" style="margin-top:8px">Note (meteo, campo, minuto di silenzio…)<textarea data-info="note"></textarea></label>

    <h2>Squadre e formazioni</h2>
    <div class="teamtabs" id="rosterTabs"></div>
    <div class="grid2">
        <label class="f">Nome ufficiale squadra<input type="text" id="tName"></label>
        <label class="f">Allenatore<input type="text" id="tCoach"></label>
    </div>
    <p class="hint">Numeri 1–15 titolari, 16–23 a disposizione. Usa il cognome come deve comparire nel tabellino (es. “Bustos G.”).</p>
    <div class="roster" id="roster"></div>
    <details style="margin-top:10px">
        <summary class="hint" style="cursor:pointer">Incolla formazione da testo</summary>
        <p class="hint">Una riga per giocatore: <em>numero cognome</em>. Aggiungi “(cap)” per il capitano.</p>
        <textarea id="pasteBox" placeholder="1 Genovese&#10;2 Pelli&#10;3 Gentile&#10;…"></textarea>
        <div class="btnrow"><button class="btn" data-do="applyPaste">Applica alla squadra selezionata</button></div>
    </details>

    <h2>Archivio</h2>
    <div class="btnrow">
        <button class="btn pri" data-do="serverSave">Salva sul server</button>
        <button class="btn" data-do="serverList">Apri dal server</button>
        <button class="btn" data-do="exportJson">Esporta JSON</button>
        <button class="btn" data-do="importJson">Importa JSON</button>
        <input type="file" id="importFile" accept=".json,application/json" style="display:none">
        <button class="btn dng" data-do="newMatch">Nuova partita</button>
    </div>
    <p class="hint">Tutto viene salvato automaticamente anche sul dispositivo, quindi un ricaricamento o l’assenza di rete non fanno perdere i dati.</p>
</main>
<form method="post" action="logout.php" id="logoutForm" style="display:none">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
</form>

<!-- TABELLINO -->
<main class="view" id="v-out">
    <h2>Tabellino</h2>
    <div class="btnrow">
        <button class="btn pri" data-do="copyOut">Copia con grassetti e corsivi</button>
        <button class="btn" data-do="copyPlain">Copia solo testo</button>
        <button class="btn" data-do="downloadDoc">Scarica .doc</button>
    </div>
    <p class="hint">Incolla in Word o nella mail: la formattazione segue il modello Serie A Elite 2026. Tutti i minuti sono progressivi, come chiede la FIR (15’ st diventa 55’).</p>
    <div class="out" id="out"></div>
</main>

<nav class="tabs">
    <button class="on" data-view="live"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2 2M9 2h6"/></svg>Live</button>
    <button data-view="events"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6h11M9 12h11M9 18h11M5 6v.01M5 12v.01M5 18v.01"/></svg>Eventi</button>
    <button data-view="setup"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2M16 3.13a4 4 0 0 1 0 7.75M21 21v-2a4 4 0 0 0-3-3.85"/></svg>Partita</button>
    <button data-view="out"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2zM9 13h6M9 17h6"/></svg>Tabellino</button>
</nav>

<div class="fab" id="fab">
    <div class="fabMenu">
        <div class="fabRow"><span class="fabLabel">Esci</span>
            <button class="fabItem dng" id="fabLogout" aria-label="Esci"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></button>
        </div>
        <div class="fabRow"><span class="fabLabel">Utenti e password</span>
            <a class="fabItem" href="utenti.php" aria-label="Utenti e password"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></a>
        </div>
    </div>
    <button class="fabMain" id="fabToggle" aria-label="Menu utente"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></button>
</div>

<div class="overlay" id="overlay"><div class="sheet" id="sheet"></div></div>
<div class="overlay center" id="dlgOverlay"><div class="sheet dialog" id="dlg"></div></div>
<div class="toast" id="toast"></div>
<?php if ($demo_notice !== ''): ?>
<script>window.__notice = <?= json_encode($demo_notice, JSON_UNESCAPED_UNICODE) ?>;</script>
<?php endif; ?>

<script>
'use strict';

const API_URL = 'api.php';
const LS_KEY = 'tabellino_live_v1';
const PTS = { try: 5, ptry: 7, conv: 2, pen: 3, drop: 3 };
const SLOT_GROUPS = [[15], [14, 13, 12, 11], [10, 9], [8, 7, 6], [5, 4], [3, 2, 1]];
const LABEL = {
    try: 'Meta', ptry: 'Meta tecnica', conv: 'Trasformazione', pen: 'Calcio piazzato',
    drop: 'Drop', yc: 'Cartellino giallo', rc: 'Cartellino rosso', sub: 'Sostituzione', tsub: 'Sost. temporanea'
};
const Q = '’';

/* ---------- Stato ---------- */
function emptyPlayers() {
    const o = {};
    for (let i = 1; i <= 23; i++) o[i] = { name: '', cap: false };
    return o;
}
function newState() {
    return {
        id: 'm' + Date.now().toString(36),
        info: {
            luogo: '', data: '', campionato: '', giornata: '', note: '', spettatori: '', puntiH: '', puntiA: '',
            arbitro: '', aa1: '', aa2: '', quarto: '', tmo: '',
            potmLabel: 'Player of the Match', potm: ''
        },
        minuteMode: 'half',
        teams: {
            h: { name: 'Casa', coach: '', players: emptyPlayers() },
            a: { name: 'Ospiti', coach: '', players: emptyPlayers() }
        },
        events: [],
        clock: { half: 1, running: false, startTs: 0, accMs: 0 },
        team: 'h',
        rosterTeam: 'h',
        lastKicker: { h: null, a: null },
        seq: 1
    };
}
function load() {
    try {
        const raw = localStorage.getItem(LS_KEY);
        return raw ? JSON.parse(raw) : null;
    } catch (e) { return null; }
}
function save() {
    try { localStorage.setItem(LS_KEY, JSON.stringify(S)); } catch (e) { /* storage non disponibile */ }
}
/* Dalla v1.03 i minuti sono relativi al tempo (1'-40'+ nel 1°, di nuovo da 1' nel 2°) */
function migrate(st) {
    if (!st) return st;
    if (st.minuteMode !== 'half') {
        (st.events || []).forEach(e => {
            if (e.half === 2 && e.min > 40) e.min -= 40;
            if (e.t === 'tsub' && e.end != null) {
                if (e.end > 40 && (e.half === 2 || e.end > 45)) { e.end -= 40; e.endHalf = 2; }
                else e.endHalf = e.half;
            }
        });
        st.minuteMode = 'half';
    }
    st.info = Object.assign({ aa1: '', aa2: '', quarto: '', tmo: '', potmLabel: 'Player of the Match', potm: '' }, st.info || {});
    delete st.info.style;  // le versioni 1.03-1.06 avevano due schemi, ora ce n'è uno solo
    if (st.info.mom && !st.info.potm) st.info.potm = st.info.mom;
    delete st.info.mom;
    return st;
}
const MAX_HALF_MS = 60 * 60000;  // oltre 60' in un tempo il cronometro è sicuramente rimasto acceso

/* Evita cronometri "fantasma": un tempo non dura mai più di 60 minuti */
function sanitizeClock(st, forcePause) {
    if (!st) return st;
    const c = st.clock;
    if (!c || typeof c.accMs !== 'number') {
        st.clock = { half: (c && c.half) || 1, running: false, startTs: 0, accMs: 0 };
        return st;
    }
    const el = c.accMs + (c.running ? Date.now() - (c.startTs || 0) : 0);
    if (el < 0 || el > MAX_HALF_MS) {
        st.clock = { half: c.half || 1, running: false, startTs: 0, accMs: 40 * 60000 - 60000 };
        st._clockFixed = true;
    } else if (forcePause && c.running) {
        st.clock = { half: c.half || 1, running: false, startTs: 0, accMs: el };
    }
    return st;
}
let S = sanitizeClock(migrate(load()), false) || newState();
/* Invalida un "Apri dal server" ancora in corso se nel frattempo si importa un
   JSON o si parte con una nuova partita: senza, la risposta in arrivo in ritardo
   sovrascriverebbe in silenzio quello che l'utente ha fatto dopo. */
let opSeq = 0;

/* Preferenze del solo dispositivo (non vanno sul server): ognuno sceglie il proprio lato */
const UI_KEY = 'tabellino_ui_v1';
let UI = { swapped: false };
try { UI = Object.assign(UI, JSON.parse(localStorage.getItem(UI_KEY) || '{}')); } catch (e) { /* ignora */ }
function saveUI() {
    try { localStorage.setItem(UI_KEY, JSON.stringify(UI)); } catch (e) { /* storage non disponibile */ }
}
function sideOrder() { return UI.swapped ? ['a', 'h'] : ['h', 'a']; }
function applySwap() { document.body.classList.toggle('swapped', UI.swapped); }

/* ---------- Utility ---------- */
const $ = (sel, root = document) => root.querySelector(sel);
function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}
function other(t) { return t === 'h' ? 'a' : 'h'; }
function pname(team, n) {
    const p = S.teams[team].players[n];
    return (p && p.name.trim()) ? p.name.trim() : '#' + n;
}
function toast(msg) {
    const t = $('#toast');
    t.textContent = msg;
    t.classList.add('on');
    clearTimeout(toast._t);
    toast._t = setTimeout(() => t.classList.remove('on'), 2200);
}
function confirmBox(text, okLabel, onOk) {
    const d = $('#dlg');
    d.innerHTML = `<p style="margin:4px 0 16px;font-size:17px">${esc(text)}</p>
        <div class="btnrow" style="justify-content:flex-end">
            <button class="btn" id="dNo">Annulla</button>
            <button class="btn pri" id="dYes">${esc(okLabel)}</button>
        </div>`;
    $('#dlgOverlay').classList.add('on');
    $('#dNo').onclick = closeDlg;
    $('#dYes').onclick = () => { closeDlg(); onOk(); };
}
function closeDlg() { $('#dlgOverlay').classList.remove('on'); }

/* ---------- Cronometro ---------- */
function elapsedMs() {
    const c = S.clock;
    return Math.max(0, c.accMs + (c.running ? Date.now() - c.startTs : 0));
}
function curMin() {
    return Math.floor(elapsedMs() / 60000) + 1;
}
function curHalf() { return S.clock.half; }
function toggleClock() {
    const c = S.clock;
    if (c.running) { c.accMs = elapsedMs(); c.running = false; }
    else { c.startTs = Date.now(); c.running = true; }
    save(); renderBoard();
}
function shiftMin(delta) {
    const c = S.clock;
    c.accMs = Math.max(0, elapsedMs() + delta * 60000);
    if (c.running) c.startTs = Date.now();
    save(); renderBoard();
}

/* ---------- Calcoli ---------- */
function isScoring(e) { return e.t === 'try' || e.t === 'ptry' || (['conv', 'pen', 'drop'].includes(e.t) && e.ok); }
function points(team, onlyHalf) {
    return S.events
        .filter(e => e.team === team && isScoring(e) && (!onlyHalf || e.half === onlyHalf))
        .reduce((s, e) => s + PTS[e.t], 0);
}
/* Minuto progressivo per il tabellino FIR: 15' del 2° tempo = 55' */
function pm(half, min) { return half === 2 ? min + 40 : min; }
/* Chiave di ordinamento: prima il tempo, poi il minuto (43' pt viene prima di 2' st) */
function tkey(half, min) { return half * 1000 + min; }
function halfLabel(h) { return h === 2 ? 'st' : 'pt'; }
function sortedEvents() {
    return [...S.events].sort((x, y) => tkey(x.half, x.min) - tkey(y.half, y.min) || x.id - y.id);
}

/* Simula titolari e cambi: restituisce chi occupa ogni ruolo e le annotazioni del tabellino */
function simulate(team) {
    const occ = {}, ann = {};
    for (let s = 1; s <= 15; s++) { occ[s] = s; ann[s] = []; }
    const items = [];
    S.events.filter(e => e.team === team && (e.t === 'sub' || e.t === 'tsub')).forEach(e => {
        items.push({ k: tkey(e.half, e.min), ord: 1, e, kind: e.t });
        if (e.t === 'tsub' && e.end != null) items.push({ k: tkey(e.endHalf || e.half, e.end), ord: 0, e, kind: 'back' });
    });
    items.sort((x, y) => x.k - y.k || x.ord - y.ord || x.e.id - y.e.id);
    const findSlot = n => Object.keys(occ).find(s => occ[s] === n);
    items.forEach(it => {
        const e = it.e;
        if (it.kind === 'back') {
            const s = findSlot(e.n2);
            if (s) occ[s] = e.n;
            return;
        }
        const s = findSlot(e.n);
        if (!s) return;
        const txt = (e.t === 'tsub' && e.end != null)
            ? `${pm(e.half, e.min)}${Q}-${pm(e.endHalf || e.half, e.end)}${Q} ${pname(team, e.n2)}`
            : `${pm(e.half, e.min)}${Q} ${pname(team, e.n2)}`;
        ann[s].push(txt);
        occ[s] = e.n2;
    });
    return { occ, ann };
}
function onField(team) {
    return new Set(Object.values(simulate(team).occ));
}
function lastOpenTry(team) {
    const convLinked = new Set(S.events.filter(e => e.t === 'conv' && e.link).map(e => e.link));
    const tries = S.events.filter(e => e.team === team && e.t === 'try' && !convLinked.has(e.id));
    return tries.length ? tries[tries.length - 1] : null;
}

/* ---------- Tabellino ---------- */
function fmtDate(iso) {
    if (!iso) return '';
    const d = new Date(iso + 'T12:00:00');
    return d.toLocaleDateString('it-IT', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
}
/*
 * Formato del modello FIR "Serie A Elite 2026".
 * Ogni riga è un elenco di segmenti [testo, stile] con stile '' normale, 'i' corsivo, 'b' grassetto (combinabili, es. 'bi').
 * Grassetto+corsivo: le due righe di apertura (luogo/data, campionato/giornata).
 * Grassetto: tutte le etichette a sinistra dei due punti (Marcatori:, p.t., s.t., nome squadra, all.:,
 * arb.:, AA1:/AA2:, quarto uomo:, TMO:, Cartellini:, Calciatori:, Note:, Punti conquistati in classifica:,
 * dicitura premio). Il resto è normale.
 */
const st = {
    header: 'bi', mLabel: 'b', team: 'b', label: 'b', vs: 'vs', pt: 'p.t.', st: 's.t.',
    cap: '(Cap.)', arb: 'Arb.:', campSep: ', ', coach: 'all.: '
};
function buildLines() {
    const H = S.teams.h, A = S.teams.a, I = S.info;
    const tn = t => S.teams[t].name;
    const L = [];
    const line = (...segs) => L.push(segs.filter(x => x[0] !== ''));
    const blank = () => L.push([]);

    line([[I.luogo, fmtDate(I.data)].filter(Boolean).join(' – '), st.header]);
    line([[I.campionato, I.giornata].filter(Boolean).join(st.campSep), st.header]);
    line([`${H.name} ${st.vs} ${A.name} ${points('h')}-${points('a')} (${points('h', 1)}-${points('a', 1)})`, '']);
    blank();

    // Marcatori
    const convOf = {};
    S.events.filter(e => e.t === 'conv' && e.link).forEach(e => { convOf[e.link] = e; });
    let sh = 0, sa = 0;
    const halves = { 1: [], 2: [] };
    sortedEvents().forEach(e => {
        const m = pm(e.half, e.min);
        let txt = null, pts = 0;
        if (e.t === 'try') {
            const c = convOf[e.id];
            pts = 5 + (c && c.ok ? 2 : 0);
            txt = `${m}${Q} m. ${pname(e.team, e.n)}` + (c && c.ok ? ` tr. ${pname(e.team, c.n)}` : '');
        } else if (e.t === 'ptry') {
            pts = 7; txt = `${m}${Q} m. tecnica`;
        } else if (e.t === 'conv' && e.ok && !e.link) {
            pts = 2; txt = `${m}${Q} tr. ${pname(e.team, e.n)}`;
        } else if (e.t === 'pen' && e.ok) {
            pts = 3; txt = `${m}${Q} cp. ${pname(e.team, e.n)}`;
        } else if (e.t === 'drop' && e.ok) {
            pts = 3; txt = `${m}${Q} drop ${pname(e.team, e.n)}`;
        }
        if (!txt) return;
        if (e.team === 'h') sh += pts; else sa += pts;
        halves[e.half === 2 ? 2 : 1].push(`${txt} (${sh}-${sa})`);
    });
    line(['Marcatori: ', st.mLabel], [st.pt + ' ', st.mLabel], [halves[1].join('; '), '']);
    line([st.st + ' ', st.mLabel], [halves[2].join('; '), '']);
    blank();

    // Formazioni
    ['h', 'a'].forEach(t => {
        const team = S.teams[t];
        const { ann } = simulate(t);
        const groups = SLOT_GROUPS.map(g => g.map(slot => {
            const p = team.players[slot];
            let txt = pname(t, slot) + (p.cap ? ' ' + st.cap : '');
            if (ann[slot].length) txt += ` (${ann[slot].join(', ')})`;
            return txt;
        }).join(', '));
        line([team.name + ': ', st.team], [groups.join('; '), '']);
        const bench = [];
        for (let n = 16; n <= 23; n++) {
            const p = team.players[n];
            if (p && p.name.trim()) bench.push(p.name.trim() + (p.cap ? ' ' + st.cap : ''));
        }
        if (bench.length) line(['a disposizione: ', st.label], [bench.join(', '), '']);
        if (team.coach) line([st.coach, st.label], [team.coach, '']);
        blank();
    });

    // Ufficiali di gara
    if (I.arbitro) line([st.arb + ' ', st.label], [I.arbitro, '']);
    if (I.aa1 || I.aa2) {
        const segs = [];
        if (I.aa1) segs.push(['AA1: ', st.label], [I.aa1, '']);
        if (I.aa2) segs.push([(I.aa1 ? ' ' : '') + 'AA2: ', st.label], [I.aa2, '']);
        line(...segs);
    }
    if (I.quarto) line(['quarto uomo: ', st.label], [I.quarto, '']);
    if (I.tmo) line(['TMO: ', st.label], [I.tmo, '']);

    // Cartellini, raggruppati per tempo, minuto e colore
    const cg = [];
    sortedEvents().filter(e => e.t === 'yc' || e.t === 'rc').forEach(e => {
        const col = e.t === 'yc' ? 'giallo' : 'rosso';
        const k = tkey(e.half, e.min);
        const last = cg[cg.length - 1];
        const who = `${pname(e.team, e.n)} (${tn(e.team)})`;
        if (last && last.k === k && last.col === col) last.who.push(who);
        else cg.push({ k, m: pm(e.half, e.min), col, who: [who] });
    });
    line(['Cartellini: ', st.label], [cg.map(c => `${c.m}${Q} ${c.col} ${c.who.join(' e ')}`).join('; '), '']);

    // Calciatori: trasformazioni + piazzati, drop esclusi
    const kick = [];
    const kIdx = {};
    sortedEvents().filter(e => e.t === 'conv' || e.t === 'pen').forEach(e => {
        const k = e.team + ':' + e.n;
        if (!(k in kIdx)) { kIdx[k] = kick.length; kick.push({ team: e.team, n: e.n, ok: 0, tot: 0 }); }
        const r = kick[kIdx[k]];
        r.tot++; if (e.ok) r.ok++;
    });
    kick.sort((x, y) => (x.team === y.team ? 0 : x.team === 'h' ? -1 : 1));
    line(['Calciatori: ', st.label], [kick.map(k => `${pname(k.team, k.n)} (${tn(k.team)}) ${k.ok}/${k.tot}`).join('; '), '']);

    const note = [I.note.trim(), I.spettatori ? `spettatori circa ${I.spettatori}` : ''].filter(Boolean).join(', ');
    line(['Note: ', st.label], [note ? note.replace(/\.?$/, '.') : '', '']);
    line(['Punti conquistati in classifica: ', st.label], [`${H.name} ${I.puntiH}; ${A.name} ${I.puntiA}`, '']);
    line([(I.potmLabel || 'Player of the Match') + ': ', st.label], [I.potm, '']);
    return L;
}
function linesToText(L) {
    return L.map(l => l.map(x => x[0]).join('')).join('\n');
}
function linesToHtml(L) {
    return L.map(l => {
        if (!l.length) return '<p style="margin:0">&nbsp;</p>';
        return '<p style="margin:0">' + l.map(([t, sty]) => {
            let h = esc(t);
            if (sty.includes('i')) h = '<i>' + h + '</i>';
            if (sty.includes('b')) h = '<b>' + h + '</b>';
            return h;
        }).join('') + '</p>';
    }).join('');
}
function buildTabellino() { return linesToText(buildLines()); }

/* ---------- Registrazione eventi ---------- */
function addEvent(ev) {
    ev.id = S.seq++;
    // Il tempo segue il cronometro (il recupero del 1° tempo, es. 43', resta p.t.);
    // una trasformazione eredita il tempo della sua meta.
    if (ev.half !== 1 && ev.half !== 2) {
        const linked = ev.link ? S.events.find(x => x.id === ev.link) : null;
        ev.half = linked ? linked.half : S.clock.half;
    }
    S.events.push(ev);
    save();
    renderAll();
    return ev;
}

function startAction(type, ok) {
    const team = S.team;
    const min = curMin();
    if (type === 'ptry') {
        addEvent({ t: 'ptry', team, min, half: S.clock.half, n: null });
        toast('Meta tecnica registrata');
        return;
    }
    if (type === 'sub' || type === 'tsub') {
        const field = onField(team);
        pickPlayer({
            title: `${LABEL[type]}: chi esce`, team, min,
            dim: n => !field.has(n),
            onPick: (nOut, m, _ok, h) => {
                pickPlayer({
                    title: `${LABEL[type]}: entra al posto di ${pname(team, nOut)}`, team, min: m, half: h,
                    dim: n => field.has(n),
                    onPick: (nIn, m2, _ok2, h2) => {
                        addEvent({ t: type, team, min: m2, half: h2, n: nOut, n2: nIn, end: null, endHalf: null });
                        toast(type === 'sub' ? 'Sostituzione registrata' : 'Sostituzione temporanea aperta');
                    }
                });
            }
        });
        return;
    }
    if (type === 'conv') {
        const tr = lastOpenTry(team);
        askKick({ team, min: tr ? tr.min : min, half: tr ? tr.half : S.clock.half, ok, link: tr ? tr.id : null });
        return;
    }
    if (type === 'pen' || type === 'drop') {
        askKick({ team, min, ok, t: type });
        return;
    }
    // try, yc, rc
    pickPlayer({
        title: LABEL[type], team, min,
        onPick: (n, m, _ok, h) => {
            const ev = addEvent({ t: type, team, min: m, half: h, n });
            if (type === 'try') {
                askKick({ team, min: m, half: h, ok: true, link: ev.id, afterTry: true });
            } else {
                toast(`${LABEL[type]} a ${pname(team, n)}`);
            }
        }
    });
}

function askKick({ team, min, half = S.clock.half, ok, link = null, t = 'conv', afterTry = false }) {
    const title = t === 'conv' ? 'Trasformazione' : LABEL[t];
    pickPlayer({
        title, team, min, half,
        okToggle: t === 'drop' ? null : ok,
        highlight: S.lastKicker[team],
        skipLabel: afterTry ? 'Nessuna trasformazione da registrare' : null,
        onPick: (n, m, okVal, h) => {
            S.lastKicker[team] = n;
            const ev = { t, team, min: m, half: h, n, ok: t === 'drop' ? true : okVal };
            if (t === 'conv') ev.link = link;
            addEvent(ev);
            toast(`${title} ${ev.ok ? 'centrata' : 'sbagliata'}: ${pname(team, n)}`);
        }
    });
}

/*
 * Corregge un evento già registrato. Il picker copre già minuto, tempo e
 * giocatore/i in un unico passaggio (stessa UI della registrazione); solo la
 * meta tecnica (nessun giocatore) e il rientro di una sostituzione temporanea
 * (un secondo minuto, non un giocatore) restano fuori e usano il dialogo.
 */
function editEvent(e) {
    if (e.t === 'ptry') { editEventTime(e); return; }
    if (e.t === 'tsub') { editEventPlayer(e, () => editTsubReturn(e)); return; }
    editEventPlayer(e);
}
function editEventPlayer(e, onDone) {
    const team = e.team;
    if (e.t === 'sub' || e.t === 'tsub') {
        pickPlayer({
            title: `${LABEL[e.t]}: chi esce`, team, min: e.min, half: e.half, highlight: e.n,
            onPick: (nOut, m, _ok, h) => {
                pickPlayer({
                    title: `${LABEL[e.t]}: entra al posto di ${pname(team, nOut)}`, team, min: m, half: h, highlight: e.n2,
                    onPick: (nIn, m2, _ok2, h2) => {
                        e.n = nOut; e.n2 = nIn; e.min = m2; e.half = h2;
                        save(); renderAll(); toast('Giocatori aggiornati');
                        if (onDone) onDone();
                    }
                });
            }
        });
        return;
    }
    pickPlayer({
        title: `Correggi: ${LABEL[e.t]}`, team, min: e.min, half: e.half, highlight: e.n,
        okToggle: (e.t === 'conv' || e.t === 'pen') ? e.ok : null,
        onPick: (n, m, okVal, h) => {
            e.n = n; e.min = m; e.half = h;
            if (e.t === 'conv' || e.t === 'pen') e.ok = okVal;
            save(); renderAll(); toast('Giocatore aggiornato');
            if (onDone) onDone();
        }
    });
}
/* Solo minuto e tempo: usato per la meta tecnica, che non ha un giocatore. */
function editEventTime(e) {
    const d = $('#dlg');
    d.innerHTML = `<h3 style="margin:0 0 12px">Correggi evento</h3>
        <div class="grid2">
            <label class="f">Minuto<input type="number" inputmode="numeric" id="eMin" value="${e.min}"></label>
            <label class="f">Tempo<select id="eHalf"><option value="1" ${e.half === 1 ? 'selected' : ''}>1°</option><option value="2" ${e.half === 2 ? 'selected' : ''}>2°</option></select></label>
        </div>
        <div class="btnrow" style="justify-content:flex-end"><button class="btn" id="dNo">Annulla</button><button class="btn pri" id="dYes">Salva</button></div>`;
    $('#dlgOverlay').classList.add('on');
    $('#dNo').onclick = closeDlg;
    $('#dYes').onclick = () => {
        e.min = Math.max(1, parseInt($('#eMin').value, 10) || e.min);
        e.half = parseInt($('#eHalf').value, 10);
        closeDlg(); save(); renderAll(); toast('Evento aggiornato');
    };
}
/* Minuto di rientro di una sostituzione temporanea: un secondo minuto, non un giocatore. */
function editTsubReturn(e) {
    const d = $('#dlg');
    d.innerHTML = `<h3 style="margin:0 0 12px">Rientro</h3>
        <div class="grid2">
            <label class="f">Minuto rientro<input type="number" inputmode="numeric" id="eEnd" value="${e.end ?? ''}"></label>
            <label class="f">Tempo del rientro<select id="eEndHalf"><option value="1" ${(e.endHalf || e.half) === 1 ? 'selected' : ''}>1°</option><option value="2" ${(e.endHalf || e.half) === 2 ? 'selected' : ''}>2°</option></select></label>
        </div>
        <p class="hint">Lascia vuoto se non è ancora rientrato.</p>
        <div class="btnrow" style="justify-content:flex-end"><button class="btn" id="dNo">Salta</button><button class="btn pri" id="dYes">Salva</button></div>`;
    $('#dlgOverlay').classList.add('on');
    $('#dNo').onclick = closeDlg;
    $('#dYes').onclick = () => {
        const v = $('#eEnd').value;
        e.end = v === '' ? null : parseInt(v, 10);
        e.endHalf = e.end == null ? null : parseInt($('#eEndHalf').value, 10);
        closeDlg(); save(); renderAll(); toast('Evento aggiornato');
    };
}

/* Bottom sheet di selezione giocatore */
function pickPlayer(opt) {
    const { team, title } = opt;
    let min = opt.min;
    let okVal = opt.okToggle;
    let half = opt.half === 1 || opt.half === 2 ? opt.half : S.clock.half;
    const sh = $('#sheet');
    sh.className = 'sheet ' + team;

    const draw = () => {
        let grid = '';
        for (let n = 1; n <= 23; n++) {
            if (n === 16) grid += '<div class="sep">A disposizione</div>';
            const cls = ['pb'];
            if (opt.dim && opt.dim(n)) cls.push('dim');
            if (opt.highlight === n) cls.push('hl');
            const nm = S.teams[team].players[n].name;
            grid += `<button class="${cls.join(' ')}" data-n="${n}"><b class="num">${n}</b><small>${esc(nm || '—')}</small></button>`;
        }
        sh.innerHTML = `
            <div class="top">
                <h3>${esc(title)}<small>${esc(S.teams[team].name)}</small></h3>
                <div class="minbox">
                    <button data-m="-1" aria-label="Minuto meno">−</button>
                    <input type="number" inputmode="numeric" id="pkMin" value="${min}" class="num" aria-label="Minuto">
                    <button data-m="1" aria-label="Minuto più">+</button>
                    <div class="hseg">
                        <button data-h="1" class="${half === 1 ? 'on' : ''}">pt</button>
                        <button data-h="2" class="${half === 2 ? 'on' : ''}">st</button>
                    </div>
                </div>
            </div>
            ${okVal === null || okVal === undefined ? '' : `
            <div class="okseg">
                <button class="y ${okVal ? 'on' : ''}" data-ok="1">Centrato</button>
                <button class="n ${!okVal ? 'on' : ''}" data-ok="0">Sbagliato</button>
            </div>`}
            <div class="pgrid">${grid}</div>
            <div class="btnrow" style="margin-top:12px">
                <button class="btn" data-close style="flex:1">${esc(opt.skipLabel || 'Annulla')}</button>
            </div>`;
    };
    draw();
    $('#overlay').classList.add('on');

    sh.onclick = ev => {
        const b = ev.target.closest('button');
        if (!b) return;
        const inp = $('#pkMin');
        if (inp) min = Math.max(1, parseInt(inp.value, 10) || min);
        if (b.dataset.m) { min = Math.max(1, min + parseInt(b.dataset.m, 10)); inp.value = min; return; }
        if (b.dataset.ok !== undefined) { okVal = b.dataset.ok === '1'; draw(); return; }
        if (b.dataset.h) { half = parseInt(b.dataset.h, 10); draw(); return; }
        if (b.hasAttribute('data-close')) { closeSheet(); return; }
        if (b.dataset.n) {
            closeSheet();
            opt.onPick(parseInt(b.dataset.n, 10), min, okVal, half);
        }
    };
}
function closeSheet() {
    $('#overlay').classList.remove('on');
    $('#sheet').onclick = null;
}

/* ---------- Render ---------- */
function renderBoard() {
    $('#bH').textContent = S.teams.h.name;
    $('#bA').textContent = S.teams.a.name;
    $('#sH').textContent = points('h');
    $('#sA').textContent = points('a');
    $('#bMin').textContent = curMin() + Q;
    $('#bHalf').textContent = S.clock.half === 1 ? '1° tempo' : '2° tempo';
    const r = $('#bRun');
    r.textContent = S.clock.running ? '❚❚' : '▶';
    r.classList.toggle('run', S.clock.running);
}
function renderSwitch() {
    const [l, r] = sideOrder();
    const btn = t => `<button class="${t} ${S.team === t ? 'on' : ''}" data-team="${t}">${esc(S.teams[t].name)}</button>`;
    $('#teamSwitch').innerHTML = btn(l)
        + '<button class="swap" data-do="swapSides" aria-label="Inverti i lati delle squadre" title="Inverti lati">⇄</button>'
        + btn(r);
}
function renderPending() {
    const now = curMin();
    const ch = S.clock.half;
    const out = [];
    S.events.forEach(e => {
        if (e.t === 'tsub' && e.end == null) {
            out.push(`<div class="pend ${e.team}"><span><b class="num">${e.min}${Q}</b><span class="hbadge">${halfLabel(e.half)}</span> ${esc(pname(e.team, e.n2))} dentro per ${esc(pname(e.team, e.n))}</span>
                <button data-back="${e.id}">Rientra ora</button></div>`);
        }
        // Giallo ancora in corso: 10' di gioco, anche a cavallo dell'intervallo
        const played = e.half === ch ? now - e.min : (e.half === 1 && ch === 2 ? Math.max(0, 40 - e.min) + now : 99);
        if (e.t === 'yc' && played >= 0 && played < 10) {
            out.push(`<div class="pend ${e.team}"><span><span class="dot" style="display:inline-block;width:10px;height:14px;background:var(--yellow);border-radius:2px"></span>
                ${esc(pname(e.team, e.n))} fuori dal ${e.min}${Q} ${halfLabel(e.half)}, mancano circa ${10 - played}${Q}</span></div>`);
        }
    });
    $('#pending').innerHTML = out.join('');
}
function describe(e) {
    const p = n => esc(pname(e.team, n));
    switch (e.t) {
        case 'try': return `Meta ${p(e.n)}`;
        case 'ptry': return 'Meta tecnica';
        case 'conv': return `Trasformazione ${p(e.n)} <span class="${e.ok ? 'okc' : 'ko'}">${e.ok ? 'centrata' : 'sbagliata'}</span>`;
        case 'pen': return `Calcio piazzato ${p(e.n)} <span class="${e.ok ? 'okc' : 'ko'}">${e.ok ? 'centrato' : 'sbagliato'}</span>`;
        case 'drop': return `Drop ${p(e.n)}`;
        case 'yc': return `Giallo a ${p(e.n)}`;
        case 'rc': return `Rosso a ${p(e.n)}`;
        case 'sub': return `Esce ${p(e.n)}, entra ${p(e.n2)}`;
        case 'tsub': return `Temporanea: ${p(e.n2)} per ${p(e.n)}` + (e.end != null ? ` (rientro ${e.end}${Q} ${halfLabel(e.endHalf || e.half)})` : ' (aperta)');
    }
    return e.t;
}
function renderEvents() {
    const list = sortedEvents().reverse();
    if (!list.length) { $('#evList').innerHTML = '<div class="empty">Nessun evento: registra dalla schermata Live.</div>'; return; }
    $('#evList').innerHTML = list.map(e => `
        <div class="ev ${e.team}">
            <div class="m num">${e.min}${Q}<span class="hbadge">${halfLabel(e.half)}</span></div>
            <div class="d">${describe(e)}<small>${esc(S.teams[e.team].name)} · ${e.half}° tempo · tabellino ${pm(e.half, e.min)}${Q}</small></div>
            <div class="tools">
                <button data-edit="${e.id}">Modifica</button>
                <button data-del="${e.id}" class="ko">Elimina</button>
            </div>
        </div>`).join('');
}
function renderSetup() {
    document.querySelectorAll('[data-info]').forEach(el => {
        if (document.activeElement !== el) el.value = S.info[el.dataset.info] ?? '';
    });
    const rt = S.rosterTeam;
    $('#rosterTabs').innerHTML = sideOrder().map(t =>
        `<button class="${t} ${rt === t ? 'on' : ''}" data-rteam="${t}">${esc(S.teams[t].name)}</button>`).join('');
    const team = S.teams[rt];
    if (document.activeElement !== $('#tName')) $('#tName').value = team.name;
    if (document.activeElement !== $('#tCoach')) $('#tCoach').value = team.coach;
    let html = '';
    for (let n = 1; n <= 23; n++) {
        const p = team.players[n];
        html += `<div class="pr ${n > 15 ? 'bench' : ''}">
            <div class="n num">${n}</div>
            <input type="text" data-pn="${n}" value="${esc(p.name)}" placeholder="${n > 15 ? 'panchina' : 'titolare'}" autocomplete="off">
            <label><input type="checkbox" data-pc="${n}" ${p.cap ? 'checked' : ''}>cap</label>
        </div>`;
    }
    $('#roster').innerHTML = html;
}
function renderOut() { $('#out').innerHTML = linesToHtml(buildLines()); }
function renderAll() {
    renderBoard(); renderSwitch(); renderPending(); renderEvents(); renderOut();
}

/* ---------- Server ---------- */
function sessionExpired() {
    const d = $('#dlg');
    d.innerHTML = `<h3 style="margin:0 0 8px">Sessione scaduta</h3>
        <p class="hint" style="font-size:15px">I dati della partita restano salvati su questo dispositivo. Accedi di nuovo e poi ripeti il salvataggio.</p>
        <div class="btnrow" style="justify-content:flex-end">
            <button class="btn" id="dNo">Più tardi</button>
            <a class="btn pri" href="login.php" style="text-decoration:none">Accedi</a>
        </div>`;
    $('#dlgOverlay').classList.add('on');
    $('#dNo').onclick = closeDlg;
}
function checkAuth(r) {
    if (r.status === 401) { sessionExpired(); throw new Error('auth'); }
    return r;
}
async function serverSave() {
    try {
        const r = await fetch(API_URL + '?action=save', {
            // Sul server il cronometro va fermo al minuto attuale: chi apre la partita
            // più tardi non deve ritrovarsi un conteggio partito ore prima
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(Object.assign({}, S, {
                clock: { half: S.clock.half, running: false, startTs: 0, accMs: elapsedMs() }
            }))
        }).then(checkAuth);
        const j = await r.json();
        if (!r.ok || !j.ok) throw new Error(j.error || 'errore');
        if (j.forked && j.id !== S.id) {
            // Il tabellino caricato era di un altro utente: il server ha creato
            // una copia con nuovo id, l'originale resta invariato
            S.id = j.id; save();
            toast('Non è un tuo tabellino: salvato come copia');
        } else {
            toast('Salvato sul server');
        }
    } catch (e) {
        if (e.message === 'auth') return;
        toast('Salvataggio sul server non riuscito: i dati restano sul dispositivo');
    }
}
async function serverList() {
    try {
        const r = await fetch(API_URL + '?action=list&nc=' + Date.now(), { cache: 'no-store' }).then(checkAuth);
        const j = await r.json();
        if (!j.ok) throw new Error(j.error);
        const d = $('#dlg');
        d.innerHTML = `<h3 style="margin:0 0 10px">Partite salvate</h3>
            ${j.items.length ? j.items.map(it => {
                const who = it.owner === '' ? 'libera per tutti' : (it.mine ? 'tua' : 'di ' + esc(it.owner));
                const copia = it.forked_from ? ' · copia' : '';
                const created = it.created_at || it.updated_at;
                const dates = created !== it.updated_at
                    ? `creata ${esc(created)} · modificata ${esc(it.updated_at)}`
                    : `creata ${esc(created)}`;
                return `<button class="btn" style="width:100%;text-align:left;margin-bottom:6px" data-load="${esc(it.id)}">
                    ${esc(it.title)}<br><small class="hint">${who}${copia} · ${dates}</small></button>`;
            }).join('') : '<p class="hint">Nessuna partita sul server.</p>'}
            <div class="btnrow" style="justify-content:flex-end"><button class="btn" id="dNo">Chiudi</button></div>`;
        $('#dlgOverlay').classList.add('on');
        $('#dNo').onclick = closeDlg;
        d.querySelectorAll('[data-load]').forEach(b => b.onclick = () => {
            confirmBox('Aprire questa partita? Quella sul dispositivo verrà sostituita.', 'Apri', () => serverLoad(b.dataset.load));
        });
    } catch (e) {
        if (e.message === 'auth') return;
        toast('Server non raggiungibile');
    }
}
async function serverLoad(id) {
    const my = ++opSeq;
    try {
        const r = await fetch(API_URL + '?action=load&id=' + encodeURIComponent(id) + '&nc=' + Date.now(), { cache: 'no-store' }).then(checkAuth);
        const j = await r.json();
        if (!j.ok) throw new Error(j.error);
        if (my !== opSeq) return;  // nel frattempo è stata aperta un'altra partita, importato un JSON o iniziata una nuova
        S = sanitizeClock(migrate(j.data), true); save(); renderSetup(); renderAll();
        if (S._clockFixed) { delete S._clockFixed; save(); toast('Il cronometro era rimasto acceso: fermato al 40’'); }
        toast(j.mine ? 'Partita caricata' : 'Partita caricata: non è tua, salvando ne farai una copia');
    } catch (e) {
        if (e.message === 'auth') return;
        toast('Caricamento non riuscito');
    }
}
function importJsonFile(file) {
    const reader = new FileReader();
    reader.onload = () => {
        let data;
        try { data = JSON.parse(reader.result); } catch (e) {
            toast('File non valido: non è un JSON leggibile'); return;
        }
        if (!data || !data.teams || !data.teams.h || !data.teams.a) {
            toast('File non valido: non è un tabellino esportato da qui'); return;
        }
        confirmBox('Importare questo tabellino? Quello sul dispositivo verrà sostituito (salvalo prima se ti serve).', 'Importa', () => {
            ++opSeq;  // invalida un eventuale "Apri dal server" ancora in corso
            // Id nuovo: un file importato non deve mai sovrascrivere una partita esistente sul server
            data.id = 'm' + Date.now().toString(36);
            S = sanitizeClock(migrate(data), true); save(); renderSetup(); renderAll();
            if (S._clockFixed) { delete S._clockFixed; save(); }
            toast('Tabellino importato: salvalo sul server per condividerlo');
        });
    };
    reader.readAsText(file, 'utf-8');
}

/* ---------- Copia ---------- */
function copyPlain(txt) {
    const fallback = () => {
        const ta = document.createElement('textarea');
        ta.value = txt; document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); toast('Testo copiato'); } catch (e) { toast('Copia non riuscita'); }
        ta.remove();
    };
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(txt).then(() => toast('Testo copiato'), fallback);
    } else fallback();
}
async function copyRich() {
    const L = buildLines();
    const html = linesToHtml(L), txt = linesToText(L);
    try {
        if (navigator.clipboard && window.ClipboardItem && window.isSecureContext) {
            await navigator.clipboard.write([new ClipboardItem({
                'text/html': new Blob([html], { type: 'text/html' }),
                'text/plain': new Blob([txt], { type: 'text/plain' })
            })]);
            toast('Tabellino copiato con formattazione');
            return;
        }
    } catch (e) { /* passa al metodo alternativo */ }
    // Alternativa: seleziona l'anteprima formattata e copia la selezione
    renderOut();
    const el = $('#out');
    const range = document.createRange();
    range.selectNodeContents(el);
    const sel = window.getSelection();
    sel.removeAllRanges(); sel.addRange(range);
    try { document.execCommand('copy'); toast('Tabellino copiato con formattazione'); } catch (e) { toast('Copia non riuscita'); }
    sel.removeAllRanges();
}

/* ---------- Eventi UI ---------- */
document.addEventListener('click', ev => {
    const b = ev.target.closest('button');
    if (!b) return;

    if (b.dataset.view) {
        document.querySelectorAll('nav.tabs button').forEach(x => x.classList.toggle('on', x === b));
        document.querySelectorAll('.view').forEach(v => v.classList.remove('on'));
        $('#v-' + b.dataset.view).classList.add('on');
        if (b.dataset.view === 'setup') renderSetup();
        if (b.dataset.view === 'out') renderOut();
        window.scrollTo(0, 0);
        return;
    }
    if (b.dataset.team) { S.team = b.dataset.team; save(); renderSwitch(); return; }
    if (b.dataset.rteam) { S.rosterTeam = b.dataset.rteam; save(); renderSetup(); return; }
    if (b.dataset.act) { startAction(b.dataset.act, b.dataset.ok === '1'); return; }
    if (b.dataset.back) {
        const e = S.events.find(x => x.id === +b.dataset.back);
        if (e) { e.end = curMin(); e.endHalf = S.clock.half; save(); renderAll(); toast(`${pname(e.team, e.n)} rientra al ${e.end}${Q} ${halfLabel(e.endHalf)}`); }
        return;
    }
    if (b.dataset.del) {
        const id = +b.dataset.del;
        confirmBox('Eliminare questo evento?', 'Elimina', () => {
            S.events = S.events.filter(x => x.id !== id && x.link !== id);
            save(); renderAll(); toast('Evento eliminato');
        });
        return;
    }
    if (b.dataset.edit) {
        const e = S.events.find(x => x.id === +b.dataset.edit);
        if (e) editEvent(e);
        return;
    }

    switch (b.dataset.do) {
        case 'toggleClock': toggleClock(); break;
        case 'setClock': {
            const d = $('#dlg');
            d.innerHTML = `<h3 style="margin:0 0 12px">Cronometro</h3>
                <div class="grid2">
                    <label class="f">Minuto<input type="number" inputmode="numeric" id="cMin" value="${curMin()}" min="1" max="60"></label>
                    <label class="f">Tempo<select id="cHalf"><option value="1" ${S.clock.half === 1 ? 'selected' : ''}>1°</option><option value="2" ${S.clock.half === 2 ? 'selected' : ''}>2°</option></select></label>
                </div>
                <p class="hint">Il cronometro resta fermo: premi ▶ alla ripresa del gioco.</p>
                <div class="btnrow" style="justify-content:space-between">
                    <button class="btn dng" id="cReset">Azzera</button>
                    <span style="display:flex;gap:8px"><button class="btn" id="dNo">Annulla</button><button class="btn pri" id="dYes">Imposta</button></span>
                </div>`;
            $('#dlgOverlay').classList.add('on');
            $('#dNo').onclick = closeDlg;
            $('#cReset').onclick = () => {
                S.clock = { half: parseInt($('#cHalf').value, 10), running: false, startTs: 0, accMs: 0 };
                closeDlg(); save(); renderAll(); toast('Cronometro azzerato');
            };
            $('#dYes').onclick = () => {
                const m = Math.min(60, Math.max(1, parseInt($('#cMin').value, 10) || 1));
                S.clock = { half: parseInt($('#cHalf').value, 10), running: false, startTs: 0, accMs: (m - 1) * 60000 };
                closeDlg(); save(); renderAll(); toast(`Cronometro al ${m}’ ${halfLabel(S.clock.half)}`);
            };
            break;
        }
        case 'swapSides':
            UI.swapped = !UI.swapped;
            saveUI(); applySwap(); renderSwitch(); renderSetup();
            break;
        case 'minUp': shiftMin(1); break;
        case 'minDown': shiftMin(-1); break;
        case 'setHalf2':
            confirmBox('Chiudere il 1° tempo? Il cronometro del 2° riparte da 1’ (nel tabellino diventa 41’).', 'Vai al 2° tempo', () => {
                S.clock = { half: 2, running: false, startTs: 0, accMs: 0 };
                save(); renderAll(); toast('2° tempo: premi ▶ al fischio');
            });
            break;
        case 'applyPaste': {
            const team = S.teams[S.rosterTeam];
            let count = 0;
            $('#pasteBox').value.split(/\r?\n/).forEach(line => {
                const m = line.trim().match(/^(\d{1,2})[\s.)\-–]+(.+)$/);
                if (!m) return;
                const n = parseInt(m[1], 10);
                if (n < 1 || n > 23) return;
                let name = m[2].trim();
                const cap = /\((cap|c)\.?\)/i.test(name);
                name = name.replace(/\((cap|c)\.?\)/ig, '').trim();
                team.players[n] = { name, cap };
                count++;
            });
            save(); renderSetup(); renderAll();
            toast(`${count} giocatori inseriti`);
            break;
        }
        case 'serverSave': serverSave(); break;
        case 'serverList': serverList(); break;
        case 'exportJson': {
            const blob = new Blob([JSON.stringify(S, null, 2)], { type: 'application/json' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = `tabellino_${S.id}.json`;
            a.click();
            break;
        }
        case 'importJson': $('#importFile').click(); break;
        case 'newMatch':
            confirmBox('Iniziare una nuova partita? Salva prima sul server se ti serve questa.', 'Nuova partita', () => {
                ++opSeq;  // invalida un eventuale "Apri dal server" ancora in corso
                S = newState(); save(); renderSetup(); renderAll(); toast('Nuova partita pronta');
            });
            break;
        case 'copyOut': copyRich(); break;
        case 'copyPlain': copyPlain(buildTabellino()); break;
        case 'downloadDoc': {
            const html = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head><meta charset="utf-8"><title>Tabellino</title></head>
<body style="font-family:Calibri,sans-serif;font-size:11pt">${linesToHtml(buildLines())}</body></html>`;
            const blob = new Blob([html], { type: 'application/msword' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = `tabellino_${S.id}.doc`;
            a.click();
            break;
        }
    }
});

document.addEventListener('input', ev => {
    const el = ev.target;
    if (el.dataset.info) { S.info[el.dataset.info] = el.value; }
    else if (el.id === 'tName') { S.teams[S.rosterTeam].name = el.value; }
    else if (el.id === 'tCoach') { S.teams[S.rosterTeam].coach = el.value; }
    else if (el.dataset.pn) { S.teams[S.rosterTeam].players[el.dataset.pn].name = el.value; }
    else if (el.dataset.pc) { S.teams[S.rosterTeam].players[el.dataset.pc].cap = el.checked; }
    else return;
    save();
    renderBoard(); renderSwitch();
    if (el.id === 'tName') {
        document.querySelectorAll('#rosterTabs button').forEach(x => {
            if (x.dataset.rteam === S.rosterTeam) x.textContent = el.value;
        });
    }
});

$('#overlay').addEventListener('click', ev => { if (ev.target.id === 'overlay') closeSheet(); });
$('#dlgOverlay').addEventListener('click', ev => { if (ev.target.id === 'dlgOverlay') closeDlg(); });
$('#importFile').addEventListener('change', ev => {
    const file = ev.target.files[0];
    if (file) importJsonFile(file);
    ev.target.value = '';
});
$('#fabToggle').addEventListener('click', () => $('#fab').classList.toggle('open'));
$('#fabLogout').addEventListener('click', () => $('#logoutForm').submit());
document.addEventListener('click', ev => {
    const fab = $('#fab');
    if (fab.classList.contains('open') && !fab.contains(ev.target)) fab.classList.remove('open');
});

setInterval(() => { renderBoard(); renderPending(); }, 1000);
// Mantiene viva la sessione durante la partita; senza rete fallisce in silenzio
setInterval(() => {
    fetch(API_URL + '?action=ping&nc=' + Date.now(), { cache: 'no-store' })
        .then(r => { if (r.status === 401) sessionExpired(); })
        .catch(() => {});
}, 10 * 60 * 1000);
applySwap();
if (window.__notice) setTimeout(() => toast(window.__notice), 400);
if (S._clockFixed) { delete S._clockFixed; save(); setTimeout(() => toast('Il cronometro era rimasto acceso: fermato al 40’'), 300); }
renderSetup();
renderAll();
</script>
</body>
</html>
